PROJECT: Gudang ATK Laravel + MySQL

CATATAN PENTING:
Folder ini berisi source code Laravel untuk aplikasi persediaan barang ATK.
Folder vendor tidak disertakan. Jalankan composer install setelah project dibuka.

CARA INSTALL CEPAT:
1. Pastikan XAMPP Apache dan MySQL aktif.
2. Copy folder gudang-atk-laravel ke htdocs.
3. Buka terminal di folder project.
4. Jalankan: composer install
5. Copy .env.example menjadi .env
6. Sesuaikan database di .env:
   DB_DATABASE=db_gudang_atk_laravel
   DB_USERNAME=root
   DB_PASSWORD=
7. Jalankan: php artisan key:generate
8. Buat database db_gudang_atk_laravel di phpMyAdmin.
9. Jalankan: php artisan migrate --seed
10. Jalankan: php artisan serve
11. Buka: http://127.0.0.1:8000

LOGIN DEFAULT:
Username: admin
Password: admin123

Admin:
username: admin
password: admin123

Gudang:
username: gudang
password: admin123

Pimpinan:
username: pimpinan
password: admin123

POP UP:
Aplikasi sudah dilengkapi popup SweetAlert2 untuk:
- Data barang berhasil dimasukkan
- Data barang gagal dimasukkan
- Login gagal
- Transaksi barang masuk/keluar berhasil atau gagal
