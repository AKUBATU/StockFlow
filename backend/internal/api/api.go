package api

import (
	"encoding/json"
	"errors"
	"fmt"
	"github.com/jackc/pgx/v5"
	"github.com/jackc/pgx/v5/pgconn"
	"github.com/jackc/pgx/v5/pgxpool"
	"io"
	"log"
	"math"
	"net/http"
	"sort"
	"strconv"
	"strings"
	"time"
)

type API struct{ db *pgxpool.Pool }

func New(db *pgxpool.Pool) http.Handler { return &API{db: db} }

type Product struct {
	ID             int        `json:"id"`
	SKU            string     `json:"sku"`
	Name           string     `json:"name"`
	Price          int64      `json:"price"`
	Stock          int64      `json:"stock"`
	IsActive       bool       `json:"is_active"`
	CreatedAt      time.Time  `json:"created_at"`
	LastMovementAt *time.Time `json:"last_movement_at"`
}
type Item struct {
	SKU       string `json:"sku"`
	Qty       int64  `json:"qty"`
	UnitPrice int64  `json:"unit_price"`
}
type Order struct {
	ID          int        `json:"id"`
	Customer    string     `json:"customer"`
	Status      string     `json:"status"`
	Total       int64      `json:"total"`
	CreatedAt   time.Time  `json:"created_at"`
	CancelledAt *time.Time `json:"cancelled_at"`
	Items       []Item     `json:"items"`
}
type apiError struct {
	Code      string `json:"code"`
	Message   string `json:"message"`
	SKU       string `json:"sku,omitempty"`
	Available *int64 `json:"available,omitempty"`
}

func respond(w http.ResponseWriter, status int, data interface{}) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(status)
	if data != nil {
		_ = json.NewEncoder(w).Encode(data)
	}
}
func fail(w http.ResponseWriter, status int, code, message string) {
	respond(w, status, map[string]interface{}{"error": apiError{Code: code, Message: message}})
}
func insufficient(w http.ResponseWriter, sku string, stock int64) {
	respond(w, 409, map[string]interface{}{"error": apiError{Code: "INSUFFICIENT_STOCK", Message: "Stok tidak cukup", SKU: sku, Available: &stock}})
}
func dbError(w http.ResponseWriter, err error) {
	if errors.Is(err, pgx.ErrNoRows) {
		fail(w, 404, "NOT_FOUND", "Data tidak ditemukan")
		return
	}
	var pg *pgconn.PgError
	if errors.As(err, &pg) {
		if pg.Code == "23505" {
			fail(w, 409, "SKU_EXISTS", "SKU sudah digunakan")
			return
		}
		if pg.Code == "23503" || pg.Code == "23001" {
			fail(w, 409, "PRODUCT_HAS_HISTORY", "Produk memiliki riwayat dan tidak dapat dihapus")
			return
		}
		if pg.Code == "22003" {
			fail(w, 400, "VALUE_TOO_LARGE", "Nilai melebihi batas integer PostgreSQL")
			return
		}
	}
	log.Printf("database error: %v", err)
	fail(w, 500, "INTERNAL_ERROR", "Permintaan gagal. Silakan coba lagi.")
}
func decode(w http.ResponseWriter, r *http.Request, v interface{}) bool {
	r.Body = http.MaxBytesReader(w, r.Body, 1<<20)
	d := json.NewDecoder(r.Body)
	d.DisallowUnknownFields()
	if err := d.Decode(v); err != nil {
		fail(w, 400, "VALIDATION_ERROR", "JSON tidak valid atau terdapat field yang tidak didukung")
		return false
	}
	if err := d.Decode(new(interface{})); err != io.EOF {
		fail(w, 400, "VALIDATION_ERROR", "Body harus berisi satu objek JSON")
		return false
	}
	return true
}
func (a *API) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("X-Content-Type-Options", "nosniff")
	path := strings.Trim(r.URL.Path, "/")
	parts := strings.Split(path, "/")
	if path == "api/health" && r.Method == "GET" {
		if err := a.db.Ping(r.Context()); err != nil {
			fail(w, 503, "DATABASE_UNAVAILABLE", "Database belum siap")
			return
		}
		respond(w, 200, map[string]string{"status": "ok"})
		return
	}
	if path == "api/products" {
		switch r.Method {
		case "GET":
			a.products(w, r)
		case "POST":
			a.saveProduct(w, r, 0)
		default:
			fail(w, 405, "METHOD_NOT_ALLOWED", "Metode tidak didukung")
		}
		return
	}
	if path == "api/summary" && r.Method == "GET" {
		a.summary(w, r)
		return
	}
	if path == "api/orders" {
		switch r.Method {
		case "POST":
			a.createOrder(w, r)
		case "GET":
			a.orders(w, r)
		default:
			fail(w, 405, "METHOD_NOT_ALLOWED", "Metode tidak didukung")
		}
		return
	}
	if len(parts) >= 3 && parts[0] == "api" && (parts[1] == "products" || parts[1] == "orders") {
		id, err := strconv.Atoi(parts[2])
		if err != nil || id <= 0 || int64(id) > math.MaxInt32 {
			fail(w, 400, "VALIDATION_ERROR", "ID harus berupa integer positif")
			return
		}
		if parts[1] == "products" {
			if len(parts) == 3 {
				switch r.Method {
				case "PUT":
					a.saveProduct(w, r, id)
				case "DELETE":
					a.deleteProduct(w, r, id)
				default:
					fail(w, 405, "METHOD_NOT_ALLOWED", "Metode tidak didukung")
				}
				return
			}
			if len(parts) == 4 && parts[3] == "stock" && r.Method == "POST" {
				a.stock(w, r, id)
				return
			}
			if len(parts) == 4 && parts[3] == "movements" && r.Method == "GET" {
				a.movements(w, r, id)
				return
			}
		} else {
			if len(parts) == 3 && r.Method == "GET" {
				a.order(w, r, id)
				return
			}
			if len(parts) == 4 && parts[3] == "cancel" && r.Method == "POST" {
				a.cancelOrder(w, r, id)
				return
			}
		}
	}
	fail(w, 404, "NOT_FOUND", "Endpoint tidak ditemukan")
}

const productColumns = `p.id,p.sku,p.name,p.price,p.stock,p.is_active,p.created_at,(SELECT max(m.created_at) FROM stock_movements m WHERE m.product_id=p.id)`

func scanProduct(row pgx.Row) (Product, error) {
	var p Product
	err := row.Scan(&p.ID, &p.SKU, &p.Name, &p.Price, &p.Stock, &p.IsActive, &p.CreatedAt, &p.LastMovementAt)
	return p, err
}
func pagination(r *http.Request) (int, int, error) {
	page, limit := 1, 10
	var err error
	if v := r.URL.Query().Get("page"); v != "" {
		page, err = strconv.Atoi(v)
		if err != nil {
			return 0, 0, err
		}
	}
	if v := r.URL.Query().Get("limit"); v != "" {
		limit, err = strconv.Atoi(v)
		if err != nil {
			return 0, 0, err
		}
	}
	if page < 1 || page > 1000000 || limit < 1 || limit > 100 {
		return 0, 0, fmt.Errorf("page/limit invalid")
	}
	return page, limit, nil
}
func (a *API) products(w http.ResponseWriter, r *http.Request) {
	page, limit, err := pagination(r)
	if err != nil {
		fail(w, 400, "VALIDATION_ERROR", "page harus 1–1000000 dan limit 1–100")
		return
	}
	filter := r.URL.Query().Get("filter")
	if filter != "" && filter != "all" && filter != "active" && filter != "inactive" && filter != "low" {
		fail(w, 400, "VALIDATION_ERROR", "Filter tidak valid")
		return
	}
	// A read-only repeatable-read snapshot keeps count and page consistent under writes.
	tx, err := a.db.BeginTx(r.Context(), pgx.TxOptions{IsoLevel: pgx.RepeatableRead, AccessMode: pgx.ReadOnly})
	if err != nil {
		dbError(w, err)
		return
	}
	defer tx.Rollback(r.Context())
	where := ` WHERE (strpos(lower(p.sku),lower($1))>0 OR strpos(lower(p.name),lower($1))>0) AND ($2 IN ('','all') OR ($2='active' AND p.is_active) OR ($2='inactive' AND NOT p.is_active) OR ($2='low' AND p.stock<=10))`
	var total int
	search := strings.TrimSpace(r.URL.Query().Get("search"))
	if err = tx.QueryRow(r.Context(), `SELECT count(*) FROM products p`+where, search, filter).Scan(&total); err != nil {
		dbError(w, err)
		return
	}
	rows, err := tx.Query(r.Context(), `SELECT `+productColumns+` FROM products p`+where+` ORDER BY p.id DESC LIMIT $3 OFFSET $4`, search, filter, limit, (page-1)*limit)
	if err != nil {
		dbError(w, err)
		return
	}
	data := []Product{}
	for rows.Next() {
		p, e := scanProduct(rows)
		if e != nil {
			rows.Close()
			dbError(w, e)
			return
		}
		data = append(data, p)
	}
	rows.Close()
	if err = rows.Err(); err != nil {
		dbError(w, err)
		return
	}
	if err = tx.Commit(r.Context()); err != nil {
		dbError(w, err)
		return
	}
	respond(w, 200, map[string]interface{}{"data": data, "page": page, "limit": limit, "total": total})
}
func (a *API) saveProduct(w http.ResponseWriter, r *http.Request, id int) {
	var in struct {
		SKU      string `json:"sku"`
		Name     string `json:"name"`
		Price    *int64 `json:"price"`
		IsActive *bool  `json:"is_active"`
	}
	if !decode(w, r, &in) {
		return
	}
	in.SKU = strings.TrimSpace(in.SKU)
	in.Name = strings.TrimSpace(in.Name)
	if in.SKU == "" || in.Name == "" || len(in.SKU) > 100 || len(in.Name) > 200 || in.Price == nil || *in.Price < 0 || *in.Price > math.MaxInt32 {
		fail(w, 400, "VALIDATION_ERROR", "SKU dan nama wajib diisi; harga harus integer 0–2147483647; SKU maksimal 100 dan nama 200 karakter")
		return
	}
	active := true
	if in.IsActive != nil {
		active = *in.IsActive
	}
	var p Product
	var err error
	if id == 0 {
		p, err = scanProduct(a.db.QueryRow(r.Context(), `INSERT INTO products AS p(sku,name,price,is_active) VALUES($1,$2,$3,$4) RETURNING p.id,p.sku,p.name,p.price,p.stock,p.is_active,p.created_at,NULL::timestamptz`, in.SKU, in.Name, *in.Price, active))
	} else {
		p, err = scanProduct(a.db.QueryRow(r.Context(), `UPDATE products AS p SET sku=$1,name=$2,price=$3,is_active=COALESCE($4,is_active) WHERE id=$5 RETURNING `+productColumns, in.SKU, in.Name, *in.Price, in.IsActive, id))
	}
	if err != nil {
		dbError(w, err)
		return
	}
	status := 200
	if id == 0 {
		status = 201
	}
	respond(w, status, p)
}
func (a *API) deleteProduct(w http.ResponseWriter, r *http.Request, id int) {
	// FK RESTRICT is the final guard against concurrent movement/order insertion.
	tag, err := a.db.Exec(r.Context(), `DELETE FROM products WHERE id=$1`, id)
	if err != nil {
		dbError(w, err)
		return
	}
	if tag.RowsAffected() == 0 {
		fail(w, 404, "NOT_FOUND", "Produk tidak ditemukan")
		return
	}
	respond(w, 204, nil)
}
func (a *API) stock(w http.ResponseWriter, r *http.Request, id int) {
	var in struct {
		Type string `json:"type"`
		Qty  int64  `json:"qty"`
		Note string `json:"note"`
	}
	if !decode(w, r, &in) {
		return
	}
	if (in.Type != "in" && in.Type != "out") || in.Qty <= 0 || in.Qty > math.MaxInt32 || len(in.Note) > 1000 {
		fail(w, 400, "VALIDATION_ERROR", "Arah harus in/out; qty integer 1–2147483647; catatan maksimal 1000 karakter")
		return
	}
	tx, err := a.db.Begin(r.Context())
	if err != nil {
		dbError(w, err)
		return
	}
	defer tx.Rollback(r.Context())
	var sku string
	var available int64
	if err = tx.QueryRow(r.Context(), `SELECT sku,stock FROM products WHERE id=$1 FOR UPDATE`, id).Scan(&sku, &available); err != nil {
		dbError(w, err)
		return
	}
	if in.Type == "out" && in.Qty > available {
		insufficient(w, sku, available)
		return
	}
	delta := in.Qty
	if in.Type == "out" {
		delta = -delta
	}
	if available+delta > math.MaxInt32 {
		fail(w, 400, "VALUE_TOO_LARGE", "Stok melebihi batas integer")
		return
	}
	if _, err = tx.Exec(r.Context(), `UPDATE products SET stock=stock+$1 WHERE id=$2 AND stock+$1>=0`, delta, id); err != nil {
		dbError(w, err)
		return
	}
	if _, err = tx.Exec(r.Context(), `INSERT INTO stock_movements(product_id,type,qty,note) VALUES($1,$2,$3,$4)`, id, in.Type, in.Qty, in.Note); err != nil {
		dbError(w, err)
		return
	}
	p, err := scanProduct(tx.QueryRow(r.Context(), `SELECT `+productColumns+` FROM products p WHERE p.id=$1`, id))
	if err != nil {
		dbError(w, err)
		return
	}
	if err = tx.Commit(r.Context()); err != nil {
		dbError(w, err)
		return
	}
	respond(w, 200, p)
}
func (a *API) movements(w http.ResponseWriter, r *http.Request, id int) {
	var exists bool
	if err := a.db.QueryRow(r.Context(), `SELECT EXISTS(SELECT 1 FROM products WHERE id=$1)`, id).Scan(&exists); err != nil {
		dbError(w, err)
		return
	}
	if !exists {
		fail(w, 404, "NOT_FOUND", "Produk tidak ditemukan")
		return
	}
	rows, err := a.db.Query(r.Context(), `SELECT id,type,qty,note,created_at FROM stock_movements WHERE product_id=$1 ORDER BY created_at DESC,id DESC`, id)
	if err != nil {
		dbError(w, err)
		return
	}
	defer rows.Close()
	type movement struct {
		ID        int       `json:"id"`
		Type      string    `json:"type"`
		Qty       int64     `json:"qty"`
		Note      string    `json:"note"`
		CreatedAt time.Time `json:"created_at"`
	}
	data := []movement{}
	for rows.Next() {
		var m movement
		if err = rows.Scan(&m.ID, &m.Type, &m.Qty, &m.Note, &m.CreatedAt); err != nil {
			dbError(w, err)
			return
		}
		data = append(data, m)
	}
	if err = rows.Err(); err != nil {
		dbError(w, err)
		return
	}
	respond(w, 200, map[string]interface{}{"data": data})
}

func (a *API) createOrder(w http.ResponseWriter, r *http.Request) {
	var in orderInput
	if !decode(w, r, &in) {
		return
	}
	if err := validateOrder(in); err != nil {
		fail(w, 400, "VALIDATION_ERROR", err.Error())
		return
	}
	// Lock SKUs in a deterministic order across all orders/cancellations to avoid deadlocks.
	sort.Slice(in.Items, func(i, j int) bool { return in.Items[i].SKU < in.Items[j].SKU })
	tx, err := a.db.Begin(r.Context())
	if err != nil {
		dbError(w, err)
		return
	}
	defer tx.Rollback(r.Context())
	type locked struct {
		id                int
		sku               string
		stock, price, qty int64
	}
	products := []locked{}
	total := int64(0)
	for _, item := range in.Items {
		p := locked{sku: item.SKU, qty: item.Qty}
		var active bool
		err = tx.QueryRow(r.Context(), `SELECT id,stock,price,is_active FROM products WHERE sku=$1 FOR UPDATE`, item.SKU).Scan(&p.id, &p.stock, &p.price, &active)
		if errors.Is(err, pgx.ErrNoRows) {
			fail(w, 400, "UNKNOWN_SKU", "SKU tidak ditemukan: "+item.SKU)
			return
		}
		if err != nil {
			dbError(w, err)
			return
		}
		if !active {
			fail(w, 400, "INACTIVE_PRODUCT", "Produk tidak aktif: "+item.SKU)
			return
		}
		products = append(products, p)
	}
	// Every SKU is validated and locked before any writes.
	for _, p := range products {
		if p.stock < p.qty {
			insufficient(w, p.sku, p.stock)
			return
		}
		subtotal := p.qty * p.price
		if subtotal > math.MaxInt32-total {
			fail(w, 400, "VALUE_TOO_LARGE", "Total pesanan melebihi 2147483647 rupiah")
			return
		}
		total += subtotal
	}
	o := Order{Items: []Item{}}
	err = tx.QueryRow(r.Context(), `INSERT INTO sales_orders(customer,total) VALUES($1,$2) RETURNING id,customer,status,total,created_at,cancelled_at`, strings.TrimSpace(in.Customer), total).Scan(&o.ID, &o.Customer, &o.Status, &o.Total, &o.CreatedAt, &o.CancelledAt)
	if err != nil {
		dbError(w, err)
		return
	}
	for _, p := range products {
		if _, err = tx.Exec(r.Context(), `UPDATE products SET stock=stock-$1 WHERE id=$2 AND stock>=$1`, p.qty, p.id); err != nil {
			dbError(w, err)
			return
		}
		if _, err = tx.Exec(r.Context(), `INSERT INTO sales_order_items(order_id,sku,qty,unit_price) VALUES($1,$2,$3,$4)`, o.ID, p.sku, p.qty, p.price); err != nil {
			dbError(w, err)
			return
		}
		if _, err = tx.Exec(r.Context(), `INSERT INTO stock_movements(product_id,type,qty,note,order_id) VALUES($1,'out',$2,$3,$4)`, p.id, p.qty, fmt.Sprintf("Pesanan #%d", o.ID), o.ID); err != nil {
			dbError(w, err)
			return
		}
		o.Items = append(o.Items, Item{SKU: p.sku, Qty: p.qty, UnitPrice: p.price})
	}
	if err = tx.Commit(r.Context()); err != nil {
		dbError(w, err)
		return
	}
	respond(w, 201, o)
}
func (a *API) order(w http.ResponseWriter, r *http.Request, id int) {
	// Single SQL statement returns a coherent order snapshot during cancellation.
	var o Order
	var items []byte
	err := a.db.QueryRow(r.Context(), `SELECT o.id,o.customer,o.status,o.total,o.created_at,o.cancelled_at,COALESCE((SELECT json_agg(json_build_object('sku',i.sku,'qty',i.qty,'unit_price',i.unit_price) ORDER BY i.sku) FROM sales_order_items i WHERE i.order_id=o.id),'[]'::json) FROM sales_orders o WHERE o.id=$1`, id).Scan(&o.ID, &o.Customer, &o.Status, &o.Total, &o.CreatedAt, &o.CancelledAt, &items)
	if err != nil {
		dbError(w, err)
		return
	}
	if err = json.Unmarshal(items, &o.Items); err != nil {
		dbError(w, err)
		return
	}
	respond(w, 200, o)
}
func (a *API) cancelOrder(w http.ResponseWriter, r *http.Request, id int) {
	tx, err := a.db.Begin(r.Context())
	if err != nil {
		dbError(w, err)
		return
	}
	defer tx.Rollback(r.Context())
	var status string
	if err = tx.QueryRow(r.Context(), `SELECT status FROM sales_orders WHERE id=$1 FOR UPDATE`, id).Scan(&status); err != nil {
		dbError(w, err)
		return
	}
	if status == "CANCELLED" {
		fail(w, 409, "ORDER_ALREADY_CANCELLED", "Pesanan sudah dibatalkan")
		return
	}
	rows, err := tx.Query(r.Context(), `SELECT p.id,p.sku,p.stock,i.qty FROM sales_order_items i JOIN products p ON p.sku=i.sku WHERE i.order_id=$1 ORDER BY p.sku COLLATE "C" FOR UPDATE OF p`, id)
	if err != nil {
		dbError(w, err)
		return
	}
	type restore struct {
		id         int
		sku        string
		stock, qty int64
	}
	items := []restore{}
	for rows.Next() {
		var i restore
		if err = rows.Scan(&i.id, &i.sku, &i.stock, &i.qty); err != nil {
			rows.Close()
			dbError(w, err)
			return
		}
		items = append(items, i)
	}
	rows.Close()
	if err = rows.Err(); err != nil {
		dbError(w, err)
		return
	}
	for _, i := range items {
		if i.stock+i.qty > math.MaxInt32 {
			fail(w, 409, "STOCK_LIMIT_EXCEEDED", "Kurangi stok terlebih dahulu sebelum membatalkan: "+i.sku)
			return
		}
	}
	for _, i := range items {
		if _, err = tx.Exec(r.Context(), `UPDATE products SET stock=stock+$1 WHERE id=$2`, i.qty, i.id); err != nil {
			dbError(w, err)
			return
		}
		if _, err = tx.Exec(r.Context(), `INSERT INTO stock_movements(product_id,type,qty,note,order_id) VALUES($1,'in',$2,$3,$4)`, i.id, i.qty, fmt.Sprintf("Pembatalan pesanan #%d", id), id); err != nil {
			dbError(w, err)
			return
		}
	}
	if _, err = tx.Exec(r.Context(), `UPDATE sales_orders SET status='CANCELLED',cancelled_at=now() WHERE id=$1`, id); err != nil {
		dbError(w, err)
		return
	}
	if err = tx.Commit(r.Context()); err != nil {
		dbError(w, err)
		return
	}
	a.order(w, r, id)
}
func (a *API) orders(w http.ResponseWriter, r *http.Request) {
	page, limit, err := pagination(r)
	if err != nil {
		fail(w, 400, "VALIDATION_ERROR", "Pagination tidak valid")
		return
	}
	rows, err := a.db.Query(r.Context(), `SELECT id,customer,status,total,created_at,cancelled_at FROM sales_orders ORDER BY id DESC LIMIT $1 OFFSET $2`, limit, (page-1)*limit)
	if err != nil {
		dbError(w, err)
		return
	}
	defer rows.Close()
	data := []Order{}
	for rows.Next() {
		var o Order
		o.Items = []Item{}
		if err = rows.Scan(&o.ID, &o.Customer, &o.Status, &o.Total, &o.CreatedAt, &o.CancelledAt); err != nil {
			dbError(w, err)
			return
		}
		data = append(data, o)
	}
	if err = rows.Err(); err != nil {
		dbError(w, err)
		return
	}
	respond(w, 200, map[string]interface{}{"data": data, "page": page, "limit": limit})
}
func (a *API) summary(w http.ResponseWriter, r *http.Request) {
	var total, active, low int
	var value int64
	err := a.db.QueryRow(r.Context(), `SELECT count(*),count(*) FILTER(WHERE is_active),COALESCE(sum(price::bigint*stock),0)::bigint,count(*) FILTER(WHERE stock<=10) FROM products`).Scan(&total, &active, &value, &low)
	if err != nil {
		dbError(w, err)
		return
	}
	respond(w, 200, map[string]interface{}{"total_products": total, "active_products": active, "total_stock_value": value, "low_stock_count": low})
}
