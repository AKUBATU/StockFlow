CREATE DATABASE IF NOT EXISTS db_gudang_atk_laravel;
USE db_gudang_atk_laravel;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','gudang','pimpinan') DEFAULT 'admin',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE kategori (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE supplier (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_supplier VARCHAR(120) NOT NULL,
  telepon VARCHAR(30),
  alamat TEXT,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

CREATE TABLE barang (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_barang VARCHAR(30) NOT NULL UNIQUE,
  nama_barang VARCHAR(150) NOT NULL,
  kategori_id BIGINT UNSIGNED NULL,
  satuan VARCHAR(30) NOT NULL,
  stok INT DEFAULT 0,
  stok_minimum INT DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
);

CREATE TABLE barang_masuk (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  barang_id BIGINT UNSIGNED NOT NULL,
  supplier_id BIGINT UNSIGNED NULL,
  tanggal DATE NOT NULL,
  jumlah INT NOT NULL,
  keterangan TEXT,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
  FOREIGN KEY (supplier_id) REFERENCES supplier(id) ON DELETE SET NULL
);

CREATE TABLE barang_keluar (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  barang_id BIGINT UNSIGNED NOT NULL,
  tanggal DATE NOT NULL,
  jumlah INT NOT NULL,
  tujuan VARCHAR(120) NOT NULL,
  keterangan TEXT,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE
);

INSERT INTO users (nama, username, password, role, created_at, updated_at)
VALUES (
    'Administrator Gudang',
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    NOW(),
    NOW()
);

INSERT INTO users (nama, username, password, role, created_at, updated_at)
VALUES 
(
    'Petugas Gudang',
    'gudang',
    '$2y$12$2nyge.4QVPIOnzH4yaTNgu3z8/bLtjoEoEs0da7.Kel7Rs33KqMma',
    'gudang',
    NOW(),
    NOW()
),
(
    'Pimpinan',
    'pimpinan',
    '$2y$12$2nyge.4QVPIOnzH4yaTNgu3z8/bLtjoEoEs0da7.Kel7Rs33KqMma',
    'pimpinan',
    NOW(),
    NOW()
);