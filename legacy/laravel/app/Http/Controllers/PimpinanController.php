<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;

class PimpinanController extends Controller
{
    private function cekPimpinan()
    {
        if (!session()->has('user_id')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (session('role') !== 'pimpinan') {
            return redirect()->route('login')->with('error', 'Anda tidak memiliki akses ke halaman pimpinan.');
        }

        return null;
    }

    public function dashboard()
    {
        if ($r = $this->cekPimpinan()) return $r;

        return view('pimpinan.dashboard', [
            'total_barang' => Barang::count(),
            'total_stok_masuk' => BarangMasuk::sum('jumlah'),
            'total_stok_keluar' => BarangKeluar::sum('jumlah'),
            'stok_rendah' => Barang::where('stok', '<', 10)->get(),
            'barang' => Barang::with('kategori')->latest()->get(),
            'barang_masuk' => BarangMasuk::with('barang')->latest()->get(),
            'barang_keluar' => BarangKeluar::with('barang')->latest()->get(),
        ]);
    }
}