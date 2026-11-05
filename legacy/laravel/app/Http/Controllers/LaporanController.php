<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;

class LaporanController extends Controller
{
    private function cekLogin(){ if(!session()->has('user_id')) return redirect()->route('login')->with('error','Silakan login terlebih dahulu.'); return null; }

    public function index(Request $request)
    {
        if($r=$this->cekLogin()) return $r;
        $awal = $request->awal ?? date('Y-m-01');
        $akhir = $request->akhir ?? date('Y-m-d');

        $masuk = BarangMasuk::with(['barang','supplier'])->whereBetween('tanggal', [$awal,$akhir])->get();
        $keluar = BarangKeluar::with('barang')->whereBetween('tanggal', [$awal,$akhir])->get();

        return view('laporan.index', [
            'awal'=>$awal,
            'akhir'=>$akhir,
            'masuk'=>$masuk,
            'keluar'=>$keluar,
            'total_masuk'=>$masuk->sum('jumlah'),
            'total_keluar'=>$keluar->sum('jumlah')
        ]);
    }
}
