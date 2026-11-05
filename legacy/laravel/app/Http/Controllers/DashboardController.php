<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;

class DashboardController extends Controller
{
    private function cekLogin()
    {
        if (!session()->has('user_id')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }
        return null;
    }

    public function index()
    {
        if ($redirect = $this->cekLogin()) return $redirect;

        $data = [
            'total_barang' => Barang::count(),
            'total_stok_masuk' => BarangMasuk::sum('jumlah'),
            'total_stok_keluar' => BarangKeluar::sum('jumlah'),
            'stok_terendah_count' => Barang::where('stok', '<', 10)->count(),
            'stok_tertinggi' => Barang::orderByDesc('stok')->first(),
            'barang_terendah' => Barang::with('kategori')->where('stok', '<', 10)->orderBy('stok')->get()
        ];

        return view('dashboard.index', $data);
    }
}
