<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Kategori;
use App\Models\Supplier;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['nama'=>'Administrator Gudang','username'=>'admin','password'=>Hash::make('admin123'),'role'=>'admin']);
        $kategori = ['Alat Tulis','Kertas','Arsip','Printer','Perlengkapan Kantor'];
        foreach($kategori as $k) Kategori::create(['nama_kategori'=>$k]);
        Supplier::create(['nama_supplier'=>'CV Sumber ATK','telepon'=>'081234567890','alamat'=>'Bogor']);
        Supplier::create(['nama_supplier'=>'Toko Maju Jaya','telepon'=>'082111223344','alamat'=>'Jakarta']);
        Barang::create(['kode_barang'=>'ATK001','nama_barang'=>'Pulpen Standard','kategori_id'=>1,'satuan'=>'pcs','stok'=>120,'stok_minimum'=>10]);
        Barang::create(['kode_barang'=>'ATK002','nama_barang'=>'Kertas HVS A4','kategori_id'=>2,'satuan'=>'rim','stok'=>35,'stok_minimum'=>10]);
        Barang::create(['kode_barang'=>'ATK003','nama_barang'=>'Map Plastik','kategori_id'=>3,'satuan'=>'pcs','stok'=>8,'stok_minimum'=>10]);
        Barang::create(['kode_barang'=>'ATK004','nama_barang'=>'Tinta Printer Hitam','kategori_id'=>4,'satuan'=>'botol','stok'=>12,'stok_minimum'=>10]);
        Barang::create(['kode_barang'=>'ATK005','nama_barang'=>'Staples','kategori_id'=>5,'satuan'=>'pcs','stok'=>5,'stok_minimum'=>10]);
        BarangMasuk::create(['barang_id'=>1,'supplier_id'=>1,'tanggal'=>date('Y-m-d'),'jumlah'=>50,'keterangan'=>'Stok awal tambahan pulpen']);
        BarangKeluar::create(['barang_id'=>3,'tanggal'=>date('Y-m-d'),'jumlah'=>2,'tujuan'=>'Divisi Arsip','keterangan'=>'Penggunaan map plastik']);
    }
}
