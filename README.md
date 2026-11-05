# StockFlow — Inventory & Sales

Penyesuaian proyek Gudang ATK menjadi mini ERP sesuai `software-engineer-fullstack.pdf`: **React + TypeScript strict (Vite), Go, PostgreSQL**, dengan frontend yang memakai API asli. Produk tetap memakai contoh kebutuhan ATK.

> PDF menyatakan AI code generation tidak diperbolehkan dalam tes tersebut. Implementasi ini dibuat dengan bantuan AI dan tidak boleh dinyatakan sebagai pekerjaan tanpa bantuan AI. Periksa ketentuan penyelenggara sebelum menggunakannya untuk submission.

## Waktu pengerjaan

- Mulai pengerjaan: **4 November 2026 (GMT+7)**, berdasarkan keterangan pemilik proyek.
- Total waktu kerja aktif yang dilaporkan: **3 jam**, dicatat pada 5 November 2026. Ini merupakan waktu aktif, bukan selisih waktu kalender sejak tanggal mulai.
- Riwayat Git mencatat saat source diunggah dan perubahan berikutnya. Tanggal mulai di atas adalah keterangan pengerjaan, bukan klaim bahwa tersedia commit sejak tanggal tersebut.

## Menjalankan dengan Docker

Prasyarat: Docker Desktop dengan Linux containers sudah aktif. Port 3000, 55432, dan 18080 tersedia. Jalankan dari folder ini:

```sh
docker compose up --build
```

Buka **http://localhost:3000**. API di http://localhost:18080/api dan PostgreSQL di localhost:55432. Tidak ada login karena kontrak PDF tidak meminta autentikasi.

PostgreSQL menjalankan `backend/migrations/001_schema.sql` dan `002_seed.sql` otomatis pada volume baru; backend juga memeriksa ledger migrasi saat startup: 14 produk realistis, 2 pesanan, item pesanan, dan riwayat stok yang konsisten. Data seed adalah data database asli, bukan mock di frontend. Volume `stockflow_data` menyimpan perubahan saat container dimatikan.

```sh
docker compose down
docker compose logs backend
```

SQL init Docker dijalankan saat volume pertama dibuat. Backend memeriksa `stockflow_schema_migrations` dan menerapkan file migration baru yang belum tercatat saat startup, dalam transaksi dengan advisory lock. Seed yang sudah tercatat tidak dijalankan ulang. Jangan menghapus volume jika ingin mempertahankan data. Untuk upgrade schema, tambahkan file migration baru; jangan mengedit migration yang sudah diterapkan.

## Menjalankan aplikasi secara lokal

Prasyarat: **Go 1.21+**, **Node.js 22.12+**, npm, dan PostgreSQL 17 (dapat memakai container db). Tidak memerlukan PHP, Composer, atau MySQL.

```sh
docker compose up -d db
```

Terminal pertama:

```sh
cd backend
go run ./cmd/server
```

Backend memakai `postgres://stockflow:stockflow@localhost:55432/stockflow?sslmode=disable` dan port `18080` secara default. Atur environment `DATABASE_URL` dan `PORT` jika berbeda. Salin `backend/.env.example` menjadi `backend/.env` untuk konfigurasi lokal. Backend memuat `.env` dari working directory; environment variable memiliki prioritas lebih tinggi. `AUTO_MIGRATE=true` mengaktifkan migrasi/seed saat startup; set `false` hanya jika database sudah disiapkan sendiri.

Terminal kedua:

```sh
cd frontend
npm ci
npm run dev
```

Buka URL yang dicetak Vite (default http://localhost:5173). Vite meneruskan `/api` ke `http://localhost:18080`. Pada Docker, Nginx meneruskan `/api` ke service backend. Dengan demikian frontend dan API memakai origin yang sama dan tidak perlu CORS permisif.

## Backend lokal Windows tanpa Docker

Jika PostgreSQL sudah terpasang, jalankan dari root proyek di terminal PowerShell biasa:

```powershell
.\scripts\dev-backend.ps1
```

Skrip menyiapkan cluster khusus proyek di `.local/postgres-data` dan database `stockflow` pada **127.0.0.1:55432**, menyalin `.env.example` jika `.env` belum ada, membangun backend dengan dependency di `backend/vendor`, lalu menjalankan API pada **http://localhost:18080**. Cluster PostgreSQL lain yang sudah terpasang tidak diubah. `.local` dan `.env` tidak masuk Git. Kredensial development cluster ini: `stockflow` / `stockflow`; listener database hanya localhost.

Go cache ditempatkan di `.local` agar tidak perlu menulis ke profil Windows. Dependency Go disertakan dalam `backend/vendor`, sehingga build, tes, dan startup tidak memerlukan unduhan modul. Asal source dan lisensinya dicatat di `backend/THIRD_PARTY.md`. Skrip tidak mengubah proxy sistem.

```powershell
# Menyiapkan database saja:
.\scripts\dev-backend.ps1 -PrepareOnly
# Menghentikan hanya cluster database proyek:
.\scripts\stop-local-db.ps1
```

Setelah API berjalan, frontend yang aktif pada http://localhost:5173 memakai proxy ke port 18080. Untuk mengecek:

```powershell
curl.exe http://localhost:18080/api/health
curl.exe http://localhost:18080/api/products
```

## Fitur

- Katalog dengan CRUD, harga IDR integer, badge aktif, stok, dan pergerakan terakhir relatif.
- Pencarian SKU/nama case-insensitive, debounce 350 ms, pagination, dan filter semua/aktif/nonaktif/stok ≤ 10 pada server.
- Loading skeleton, empty state, error dengan tombol coba lagi.
- Modal penyesuaian stok dan riwayat; error `409 INSUFFICIENT_STOCK` menampilkan stok tersedia termasuk nilai nol.
- Form pesanan multi-item dengan pencarian produk aktif; server menentukan harga dan total.
- Detail pesanan, konfirmasi pembatalan, pengembalian stok tepat sekali, dan refresh katalog/ringkasan setelah mutasi.
- Empat kartu ringkasan dari seluruh data server, tidak hanya halaman atau hasil filter yang terbuka.
- Penghapusan produk yang memiliki riwayat ditolak. Nonaktifkan produk tersebut melalui Edit.

## API

Semua error memakai envelope `{"error":{"code":"...","message":"..."}}`. `INSUFFICIENT_STOCK` menyertakan `sku` dan `available`. Request JSON tidak boleh menyertakan field yang tidak didukung; angka pecahan ditolak untuk harga/jumlah. Batas integer PostgreSQL adalah 2147483647.

| Method | Endpoint | Hasil |
| --- | --- | --- |
| GET | `/api/health` | Kesiapan koneksi database |
| GET | `/api/products?search=&page=1&limit=10&filter=all` | `{data,page,limit,total}`, limit 1–100 |
| POST | `/api/products` | 201, produk; stok awal 0 |
| PUT | `/api/products/{id}` | 200, produk; 404 jika tidak ada |
| DELETE | `/api/products/{id}` | 204; 409 `PRODUCT_HAS_HISTORY` |
| POST | `/api/products/{id}/stock` | 200, produk; 409 jika stok tidak cukup |
| GET | `/api/products/{id}/movements` | `{data:[...]}`, terbaru dahulu |
| POST | `/api/orders` | 201, order lengkap dengan item/harga/total |
| GET | `/api/orders/{id}` | Order lengkap |
| POST | `/api/orders/{id}/cancel` | Order yang dibatalkan; 409 jika sudah dibatalkan |
| GET | `/api/orders?page=1&limit=10` | Daftar order terbaru; tambahan untuk navigasi UI |
| GET | `/api/summary` | `total_products`, `active_products`, `total_stock_value`, `low_stock_count` |

Filter produk: `all`, `active`, `inactive`, `low`. Produk nonaktif tidak dapat dimasukkan ke order baru, tetapi stok dan riwayatnya tetap dapat dikelola. `PUT` membutuhkan SKU, nama, dan harga; jika `is_active` tidak dikirim, nilainya dipertahankan. SKU case-sensitive untuk keunikan; pencarian case-insensitive.

### Contoh curl

Di Windows PowerShell gunakan `curl.exe` atau jalankan contoh di Git Bash agar quoting JSON konsisten.

```sh
curl 'http://localhost:18080/api/products?search=kertas&page=1&limit=10'

curl -X POST http://localhost:18080/api/products \
  -H 'Content-Type: application/json' \
  -d '{"sku":"ATK015","name":"Gunting kantor","price":19000,"is_active":true}'

# Ganti 15 dengan ID yang dikembalikan server.
curl -X POST http://localhost:18080/api/products/15/stock \
  -H 'Content-Type: application/json' \
  -d '{"type":"in","qty":20,"note":"Restock pemasok"}'

curl -X POST http://localhost:18080/api/orders \
  -H 'Content-Type: application/json' \
  -d '{"customer":"Divisi SDM","items":[{"sku":"ATK001","qty":2},{"sku":"ATK002","qty":1}]}'

curl http://localhost:18080/api/orders/3
curl -X POST http://localhost:18080/api/orders/3/cancel

# Memicu 409 tanpa mengubah stok atau menulis riwayat.
curl -X POST http://localhost:18080/api/products/1/stock \
  -H 'Content-Type: application/json' \
  -d '{"type":"out","qty":1000000,"note":"Uji stok tidak cukup"}'
```

## Keputusan teknis dan keamanan data

1. **Row locks PostgreSQL.** Penyesuaian stok mengambil `SELECT ... FOR UPDATE` di dalam transaksi yang sama dengan UPDATE dan INSERT movement. Untuk order, semua baris produk dikunci menurut SKU, seluruh item divalidasi, baru ada penulisan. UPDATE pengurangan juga memakai kondisi `stock >= qty`. CHECK database mencegah stok negatif.
2. **Atomisitas.** Order, semua item, stok, dan movement berada dalam satu transaksi. Error pada tahap apa pun menyebabkan rollback. Harga diambil setelah row lock; input harga/total dari klien ditolak. Perhitungan memakai `int64`, lalu diperiksa terhadap batas integer schema agar overflow tidak tersimpan.
3. **Pembatalan tepat sekali.** Baris order dikunci sebelum memeriksa status. Produk dikunci menurut SKU, stok dipulihkan dan movement `in` ditulis dalam transaksi yang sama dengan status `CANCELLED`. Pembatalan bersamaan hanya dapat berhasil sekali.
4. **Riwayat permanen.** Foreign key `ON DELETE RESTRICT` melindungi movement dan order items bahkan saat ada operasi bersamaan. SKU yang diedit memakai `ON UPDATE CASCADE` untuk menjaga referensi item. Harga satuan order tetap menjadi snapshot saat penjualan.
5. **Last movement.** `last_movement_at` diturunkan dari `max(stock_movements.created_at)` dengan indeks `(product_id,created_at DESC,id DESC)`. Tidak ada kolom cache yang dapat tertinggal saat mutasi.
6. **Daftar konsisten.** Count dan halaman produk dibaca dalam transaksi read-only repeatable-read. Detail order memakai satu statement untuk status dan item. Ringkasan merupakan agregasi semua produk di server.
7. **Frontend.** Seluruh fetch dan parsing envelope berada di `src/api.ts`, tipe domain di `src/types.ts`. AbortController membatalkan request daftar yang sudah tidak relevan. Form tidak dapat dikirim ulang saat sedang menyimpan. Tidak ada domain `any` atau fallback mock.

## Pengujian

Workflow `.github/workflows/ci.yml` menjalankan tes backend dengan PostgreSQL 17 dan race detector, TypeScript/Vitest/build frontend, serta startup Docker Compose dan smoke test melalui Nginx. Workflow dipicu oleh push ke `main`, pull request, atau manual. Keberadaan workflow bukan bukti pemeriksaannya lolos; lihat hasil aktual di [GitHub Actions](https://github.com/AKUBATU/StockFlow/actions).

Backend integration test memerlukan database PostgreSQL sungguhan. Setiap tes membuat schema unik dan menghapus hanya schema tes itu. Akun tes harus diizinkan `CREATE SCHEMA`; gunakan database pengembangan/tes.

PowerShell:

```powershell
docker compose up -d db
cd backend
$env:TEST_DATABASE_URL = 'postgres://stockflow:stockflow@localhost:55432/stockflow?sslmode=disable'
go test -v ./...
# Jika tersedia compiler C 64-bit yang kompatibel:
go test -race -v ./...
```

Bash:

```sh
cd backend
TEST_DATABASE_URL='postgres://stockflow:stockflow@localhost:55432/stockflow?sslmode=disable' go test -race -v ./...
```

`-race` membutuhkan CGO dan C compiler (di Windows, MinGW). Tanpa `TEST_DATABASE_URL`, integration tests menampilkan **SKIP**, bukan bukti database lolos. Tes mencakup adjustment gagal tanpa penulisan, adjustment berhasil, rollback order multi-item, input invalid, harga server, CRUD, 20 order bersamaan terhadap stok 7, serta 8 pembatalan bersamaan.

Frontend:

```sh
cd frontend
npm ci
npm run typecheck
npm test
npm run build
```

Vitest mencakup validasi jumlah/harga/order, SKU ganda, format rupiah/waktu, parsing error 409 termasuk stok nol, kegagalan jaringan, dan respons DELETE 204. Mock jaringan hanya dipakai di file tes.

### Status verifikasi pada sesi pengerjaan

- TypeScript strict: **lolos**.
- Unit test validasi order Go: **9 skenario lolos** (dijalankan terpisah tanpa dependency PostgreSQL).
- Frontend: **20 tes Vitest lolos** dan bundle produksi berhasil dibuat melalui runner verifikasi lokal yang memakai TypeScript transpiler. Runner ini diperlukan karena sandbox Windows menolak subprocess esbuild dengan `EPERM`; jalur standar `npm run build`/`npm test` tetap perlu dicek di lingkungan normal.
- `docker compose config --quiet`: **lolos**.
- PostgreSQL lokal proyek: **berjalan di port 55432**. Schema dan seed berhasil diterapkan melalui psql: 14 produk dan 2 order.
- Konfigurasi backend: unit test pembacaan .env, prioritas environment, batas port dan flag migration **lolos**.
- Build backend Go dengan dependency vendor: **lolos**, tanpa unduhan jaringan. API aktif di **http://localhost:18080**.
- Seluruh tes Go: **lolos**, termasuk 9 integration tests PostgreSQL, validasi order, dan konfigurasi. Tes concurrency 20 order terhadap stok 7 dan 8 pembatalan bersamaan lolos pada PostgreSQL 18 lokal.
- Koneksi frontend ke backend: **HTTP 200** pada `http://localhost:5173/api/health`; endpoint produk dan ringkasan mengembalikan data PostgreSQL asli.
- PostgreSQL 18 mengembalikan SQLSTATE `23001` untuk pelanggaran FK RESTRICT; API sudah memetakannya ke `409 PRODUCT_HAS_HISTORY`, bersama SQLSTATE `23503`.
- Mode `go test -race` belum berhasil dibangun karena GCC yang terpasang hanya mendukung 32-bit, sedangkan Go memakai amd64. Tes concurrency biasa tetap dijalankan dan lolos.
- Docker Compose belum diuji end-to-end karena Docker engine tidak aktif; konfigurasi Compose lolos validasi.

## Struktur dan proyek lama

```text
backend/
  cmd/server/            HTTP server, config environment, graceful shutdown
  internal/api/          API, transaksi, validasi, dan tests
  migrations/            Schema, seed, dan runner migration PostgreSQL
  vendor/                Dependency Go untuk build offline
frontend/
  src/                   React UI, API client, types, validasi, tests
  Dockerfile
  nginx.conf
legacy/laravel/           Source Laravel sebelumnya, termasuk konfigurasi lokal
docker-compose.yml
README.md
```

Source Laravel disimpan sebagai referensi dan tidak dijalankan oleh Compose. Tidak ada migrasi otomatis MySQL → PostgreSQL: database StockFlow baru terpisah. User/role Laravel, kategori, supplier, dan laporan lama tidak termasuk kontrak minimum PDF, sehingga aplikasi baru fokus pada produk, stok, dan sales order.

## Batasan dan pekerjaan berikutnya

- Belum ada bonus Idempotency-Key, laporan penjualan harian, autentikasi, atau riwayat item berupa nama/SKU lama yang immutable saat katalog diedit.
- Untuk produksi: pin digest image, tambah checksum migration, timeout query database, logging terstruktur, autentikasi/otorisasi, dan browser tests di CI.
- Belum ada pengujian browser otomatis atau verifikasi visual di sesi ini.
- Source proyek dipublikasikan ke https://github.com/AKUBATU/StockFlow. Direktori awal bukan repository Git; commit upload mencerminkan hasil yang sudah ada, bukan riwayat pengerjaan sepanjang hari.
- Catatan waktu pengerjaan menggunakan keterangan pemilik proyek: mulai 4 November 2026, total kerja aktif 3 jam. Implementasi ini tetap didokumentasikan sebagai hasil pengembangan dengan bantuan AI.
