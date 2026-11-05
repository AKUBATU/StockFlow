<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;

class GudangController extends Controller
{
    private function cekGudang()
    {
        if (!session()->has('user_id')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (session('role') !== 'gudang') {
            return redirect()->route('login')->with('error', 'Anda tidak memiliki akses ke halaman gudang.');
        }

        return null;
    }

    public function dashboard()
    {
        if ($r = $this->cekGudang()) return $r;

        return view('gudang.dashboard', [
            'total_barang' => Barang::count(),
            'total_stok_masuk' => BarangMasuk::sum('jumlah'),
            'total_stok_keluar' => BarangKeluar::sum('jumlah'),
            'stok_rendah' => Barang::where('stok', '<', 10)->get(),
            'barang' => Barang::with('kategori')->latest()->get(),
        ]);
    }
}