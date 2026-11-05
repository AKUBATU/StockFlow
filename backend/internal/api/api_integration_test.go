package api

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"net/http/httptest"
	"os"
	"path/filepath"
	"stockflow/migrations"
	"sync"
	"sync/atomic"
	"testing"
	"time"

	"github.com/jackc/pgx/v5"
	"github.com/jackc/pgx/v5/pgxpool"
)

// Each test gets an isolated schema. A running PostgreSQL is deliberately required:
// mocks cannot verify row locks, foreign keys or transaction rollback behavior.
func testAPI(t *testing.T) (*API, *pgxpool.Pool) {
	t.Helper()
	url := os.Getenv("TEST_DATABASE_URL")
	if url == "" {
		t.Skip("Set TEST_DATABASE_URL to run PostgreSQL integration tests")
	}
	ctx := context.Background()
	admin, err := pgxpool.New(ctx, url)
	if err != nil {
		t.Fatal(err)
	}
	schema := fmt.Sprintf("stockflow_test_%d", time.Now().UnixNano())
	quoted := pgx.Identifier{schema}.Sanitize()
	if _, err = admin.Exec(ctx, "CREATE SCHEMA "+quoted); err != nil {
		admin.Close()
		t.Fatal(err)
	}
	config, err := pgxpool.ParseConfig(url)
	if err != nil {
		t.Fatal(err)
	}
	config.ConnConfig.RuntimeParams["search_path"] = schema
	config.MaxConns = 24
	pool, err := pgxpool.NewWithConfig(ctx, config)
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { pool.Close(); _, _ = admin.Exec(ctx, "DROP SCHEMA "+quoted+" CASCADE"); admin.Close() })
	sql, err := os.ReadFile(filepath.Join("..", "..", "migrations", "001_schema.sql"))
	if err != nil {
		t.Fatal(err)
	}
	if _, err = pool.Exec(ctx, string(sql)); err != nil {
		t.Fatal(err)
	}
	return &API{db: pool}, pool
}
func call(a *API, method, path, body string) *httptest.ResponseRecorder {
	r := httptest.NewRequest(method, path, bytes.NewBufferString(body))
	r.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	a.ServeHTTP(w, r)
	return w
}
func insertProduct(t *testing.T, db *pgxpool.Pool, sku string, stock int) int {
	t.Helper()
	var id int
	if err := db.QueryRow(context.Background(), `INSERT INTO products(sku,name,price,stock) VALUES($1,$1,5000,$2) RETURNING id`, sku, stock).Scan(&id); err != nil {
		t.Fatal(err)
	}
	return id
}
func count(t *testing.T, db *pgxpool.Pool, sql string) int {
	t.Helper()
	var n int
	if err := db.QueryRow(context.Background(), sql).Scan(&n); err != nil {
		t.Fatal(err)
	}
	return n
}
func requireStatus(t *testing.T, w *httptest.ResponseRecorder, status int) {
	t.Helper()
	if w.Code != status {
		t.Fatalf("status = %d, want %d; body = %s", w.Code, status, w.Body.String())
	}
}
func TestStockInsufficientWritesNothing(t *testing.T) {
	a, db := testAPI(t)
	id := insertProduct(t, db, "A", 2)
	w := call(a, "POST", fmt.Sprintf("/api/products/%d/stock", id), `{"type":"out","qty":3,"note":"test"}`)
	requireStatus(t, w, 409)
	var envelope struct {
		Error apiError `json:"error"`
	}
	if err := json.Unmarshal(w.Body.Bytes(), &envelope); err != nil {
		t.Fatal(err)
	}
	if envelope.Error.Code != "INSUFFICIENT_STOCK" || envelope.Error.Available == nil || *envelope.Error.Available != 2 {
		t.Fatal(w.Body.String())
	}
	if count(t, db, `SELECT stock FROM products WHERE sku='A'`) != 2 || count(t, db, `SELECT count(*) FROM stock_movements`) != 0 {
		t.Fatal("rejected stock adjustment changed database")
	}
}
func TestStockHappyPathWritesMovementAndStock(t *testing.T) {
	a, db := testAPI(t)
	id := insertProduct(t, db, "A", 2)
	requireStatus(t, call(a, "POST", fmt.Sprintf("/api/products/%d/stock", id), `{"type":"in","qty":5,"note":"restock"}`), 200)
	if count(t, db, `SELECT stock FROM products WHERE sku='A'`) != 7 || count(t, db, `SELECT count(*) FROM stock_movements WHERE type='in' AND qty=5 AND note='restock'`) != 1 {
		t.Fatal("movement and stock were not both committed")
	}
	var p Product
	w := call(a, "GET", "/api/products?search=a&page=1&limit=10", "")
	requireStatus(t, w, 200)
	var page struct {
		Data []Product `json:"data"`
	}
	if err := json.Unmarshal(w.Body.Bytes(), &page); err != nil {
		t.Fatal(err)
	}
	if len(page.Data) != 1 {
		t.Fatal(w.Body.String())
	}
	p = page.Data[0]
	if p.LastMovementAt == nil {
		t.Fatal("missing last_movement_at")
	}
	requireStatus(t, call(a, "DELETE", fmt.Sprintf("/api/products/%d", id), ""), 409)
}
func TestOrderInsufficientRollsBackAllItems(t *testing.T) {
	a, db := testAPI(t)
	insertProduct(t, db, "A", 10)
	insertProduct(t, db, "B", 1)
	w := call(a, "POST", "/api/orders", `{"customer":"Acme","items":[{"sku":"A","qty":3},{"sku":"B","qty":2}]}`)
	requireStatus(t, w, 409)
	var result struct {
		Error apiError `json:"error"`
	}
	_ = json.Unmarshal(w.Body.Bytes(), &result)
	if result.Error.SKU != "B" || result.Error.Available == nil || *result.Error.Available != 1 {
		t.Fatal(w.Body.String())
	}
	if count(t, db, `SELECT stock FROM products WHERE sku='A'`) != 10 || count(t, db, `SELECT stock FROM products WHERE sku='B'`) != 1 {
		t.Fatal("partial stock decrement")
	}
	for _, table := range []string{"sales_orders", "sales_order_items", "stock_movements"} {
		if count(t, db, "SELECT count(*) FROM "+table) != 0 {
			t.Fatalf("unexpected write to %s", table)
		}
	}
}
func TestOrderValidationWritesNothing(t *testing.T) {
	a, db := testAPI(t)
	insertProduct(t, db, "A", 10)
	for _, body := range []string{
		`{"customer":"","items":[{"sku":"A","qty":1}]}`,
		`{"customer":"Acme","items":[]}`,
		`{"customer":"Acme","items":[{"sku":"A","qty":0}]}`,
		`{"customer":"Acme","items":[{"sku":"A","qty":1.5}]}`,
		`{"customer":"Acme","items":[{"sku":"A","qty":1},{"sku":"A","qty":2}]}`,
		`{"customer":"Acme","items":[{"sku":"A","qty":1},{"sku":"UNKNOWN","qty":1}]}`,
		`{"customer":"Acme","items":[{"sku":"A","qty":1,"unit_price":1}]}`,
	} {
		requireStatus(t, call(a, "POST", "/api/orders", body), 400)
	}
	if count(t, db, `SELECT stock FROM products WHERE sku='A'`) != 10 || count(t, db, `SELECT count(*) FROM sales_orders`) != 0 || count(t, db, `SELECT count(*) FROM stock_movements`) != 0 {
		t.Fatal("validation failure wrote data")
	}
}
func TestOrderHappyPathAndCancelExactlyOnce(t *testing.T) {
	a, db := testAPI(t)
	insertProduct(t, db, "A", 10)
	insertProduct(t, db, "B", 5)
	w := call(a, "POST", "/api/orders", `{"customer":"Acme","items":[{"sku":"B","qty":1},{"sku":"A","qty":2}]}`)
	requireStatus(t, w, 201)
	var o Order
	if err := json.Unmarshal(w.Body.Bytes(), &o); err != nil {
		t.Fatal(err)
	}
	if o.Total != 15000 || len(o.Items) != 2 || o.Items[0].UnitPrice != 5000 {
		t.Fatal(w.Body.String())
	}
	if count(t, db, `SELECT stock FROM products WHERE sku='A'`) != 8 || count(t, db, `SELECT count(*) FROM stock_movements`) != 2 {
		t.Fatal("order not applied atomically")
	}
	var successful int32
	var bad int32
	var wg sync.WaitGroup
	for i := 0; i < 8; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			r := call(a, "POST", fmt.Sprintf("/api/orders/%d/cancel", o.ID), "")
			if r.Code == 200 {
				atomic.AddInt32(&successful, 1)
			} else if r.Code != 409 {
				atomic.AddInt32(&bad, 1)
			}
		}()
	}
	wg.Wait()
	if successful != 1 || bad != 0 {
		t.Fatalf("cancel success=%d unexpected=%d", successful, bad)
	}
	if count(t, db, `SELECT stock FROM products WHERE sku='A'`) != 10 || count(t, db, `SELECT stock FROM products WHERE sku='B'`) != 5 || count(t, db, `SELECT count(*) FROM stock_movements`) != 4 {
		t.Fatal("cancellation restored stock incorrectly")
	}
	w = call(a, "GET", fmt.Sprintf("/api/orders/%d", o.ID), "")
	requireStatus(t, w, 200)
	if err := json.Unmarshal(w.Body.Bytes(), &o); err != nil {
		t.Fatal(err)
	}
	if o.Status != "CANCELLED" || o.CancelledAt == nil {
		t.Fatal(w.Body.String())
	}
}
func TestConcurrentOrdersNeverOverdraw(t *testing.T) {
	a, db := testAPI(t)
	insertProduct(t, db, "A", 7)
	var successes, conflicts, unexpected int32
	var wg sync.WaitGroup
	start := make(chan struct{})
	for i := 0; i < 20; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			<-start
			w := call(a, "POST", "/api/orders", `{"customer":"Concurrent","items":[{"sku":"A","qty":1}]}`)
			switch w.Code {
			case 201:
				atomic.AddInt32(&successes, 1)
			case 409:
				atomic.AddInt32(&conflicts, 1)
			default:
				atomic.AddInt32(&unexpected, 1)
			}
		}()
	}
	close(start)
	wg.Wait()
	if successes != 7 || conflicts != 13 || unexpected != 0 {
		t.Fatalf("created=%d conflicts=%d unexpected=%d", successes, conflicts, unexpected)
	}
	if count(t, db, `SELECT stock FROM products WHERE sku='A'`) != 0 || count(t, db, `SELECT count(*) FROM sales_orders`) != 7 || count(t, db, `SELECT count(*) FROM stock_movements`) != 7 {
		t.Fatal("concurrent order consistency failed")
	}
}
func TestProductCRUDAndPagination(t *testing.T) {
	a, db := testAPI(t)
	requireStatus(t, call(a, "POST", "/api/products", `{"sku":"ABC","name":"Pulpen","price":5000,"is_active":true}`), 201)
	requireStatus(t, call(a, "POST", "/api/products", `{"sku":"ABC","name":"Pulpen","price":5000}`), 409)
	requireStatus(t, call(a, "POST", "/api/products", `{"sku":"B","name":"Pulpen","price":1.2}`), 400)
	requireStatus(t, call(a, "GET", "/api/products?page=0", ""), 400)
	id := count(t, db, `SELECT id FROM products WHERE sku='ABC'`)
	requireStatus(t, call(a, "PUT", fmt.Sprintf("/api/products/%d", id), `{"sku":"ABC","name":"Pulpen baru","price":6000,"is_active":false}`), 200)
	w := call(a, "GET", "/api/products?search=pULp&filter=inactive", "")
	requireStatus(t, w, 200)
	var page struct {
		Data  []Product `json:"data"`
		Total int       `json:"total"`
	}
	if err := json.Unmarshal(w.Body.Bytes(), &page); err != nil {
		t.Fatal(err)
	}
	if page.Total != 1 || page.Data[0].IsActive {
		t.Fatal(w.Body.String())
	}
	requireStatus(t, call(a, "DELETE", fmt.Sprintf("/api/products/%d", id), ""), 204)
	requireStatus(t, call(a, "DELETE", fmt.Sprintf("/api/products/%d", id), ""), 404)
}

func TestSeedBalancesAndOrderTotals(t *testing.T) {
	a, db := testAPI(t)
	seed, err := os.ReadFile(filepath.Join("..", "..", "migrations", "002_seed.sql"))
	if err != nil {
		t.Fatal(err)
	}
	if _, err = db.Exec(context.Background(), string(seed)); err != nil {
		t.Fatal(err)
	}
	if count(t, db, `SELECT count(*) FROM products`) < 12 {
		t.Fatal("expected at least 12 seeded products")
	}
	if count(t, db, `SELECT count(*) FROM sales_orders`) != 2 {
		t.Fatal("expected 2 seeded orders")
	}
	if count(t, db, `SELECT count(*) FROM products p WHERE p.stock <> COALESCE((SELECT sum(CASE WHEN type='in' THEN qty ELSE -qty END) FROM stock_movements m WHERE m.product_id=p.id),0)`) != 0 {
		t.Fatal("seeded stock does not match movement ledger")
	}
	if count(t, db, `SELECT count(*) FROM sales_orders o WHERE o.total <> (SELECT sum(qty::bigint*unit_price) FROM sales_order_items WHERE order_id=o.id)`) != 0 {
		t.Fatal("seeded totals do not match order items")
	}
	requireStatus(t, call(a, "GET", "/api/summary", ""), 200)
}

func TestStartupMigrationCanRunAgainWithoutResettingStock(t *testing.T) {
	_, db := testAPI(t)
	ctx := context.Background()
	if err := migrations.Apply(ctx, db); err != nil {
		t.Fatal(err)
	}
	if _, err := db.Exec(ctx, `UPDATE products SET stock=stock+1 WHERE sku='ATK001'`); err != nil {
		t.Fatal(err)
	}
	before := count(t, db, `SELECT stock FROM products WHERE sku='ATK001'`)
	if err := migrations.Apply(ctx, db); err != nil {
		t.Fatal(err)
	}
	if count(t, db, `SELECT count(*) FROM products`) != 14 || count(t, db, `SELECT count(*) FROM sales_orders`) != 2 {
		t.Fatal("startup migration duplicated seed data")
	}
	if count(t, db, `SELECT stock FROM products WHERE sku='ATK001'`) != before {
		t.Fatal("startup migration reset existing stock")
	}
	if count(t, db, `SELECT count(*) FROM stockflow_schema_migrations`) != 2 {
		t.Fatal("migration versions were not recorded")
	}
}
