<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Barang;
use App\Models\Supplier;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;

class PersediaanController extends Controller
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

        return view('persediaan.index', [
            'barang' => Barang::with('kategori')->orderBy('nama_barang')->get(),
            'suppliers' => Supplier::orderBy('nama_supplier')->get(),
            'riwayat_masuk' => BarangMasuk::with(['barang','supplier'])->latest()->limit(5)->get(),
            'riwayat_keluar' => BarangKeluar::with('barang')->latest()->limit(5)->get()
        ]);
    }

    public function barangMasuk(Request $request)
    {
        if ($redirect = $this->cekLogin()) return $redirect;

        $request->validate([
            'barang_id' => 'required|exists:barang,id',
            'supplier_id' => 'nullable|exists:supplier,id',
            'tanggal' => 'required|date',
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'nullable|string'
        ]);

        try {
            DB::transaction(function () use ($request) {
                BarangMasuk::create($request->only('barang_id','supplier_id','tanggal','jumlah','keterangan'));
                Barang::where('id', $request->barang_id)->increment('stok', $request->jumlah);
            });
            return back()->with('success', 'Barang masuk berhasil dimasukkan dan stok bertambah.');
        } catch (\Exception $e) {
            return back()->with('error', 'Barang masuk gagal dimasukkan.');
        }
    }

    public function barangKeluar(Request $request)
    {
        if ($redirect = $this->cekLogin()) return $redirect;

        $request->validate([
            'barang_id' => 'required|exists:barang,id',
            'tanggal' => 'required|date',
            'jumlah' => 'required|integer|min:1',
            'tujuan' => 'required|string|max:120',
            'keterangan' => 'nullable|string'
        ]);

        $barang = Barang::find($request->barang_id);
        if (!$barang || $request->jumlah > $barang->stok) {
            return back()->with('error', 'Barang gagal dikeluarkan karena stok tidak cukup.');
        }

        try {
            DB::transaction(function () use ($request) {
                BarangKeluar::create($request->only('barang_id','tanggal','jumlah','tujuan','keterangan'));
                Barang::where('id', $request->barang_id)->decrement('stok', $request->jumlah);
            });
            return back()->with('success', 'Barang keluar berhasil dimasukkan dan stok berkurang.');
        } catch (\Exception $e) {
            return back()->with('error', 'Barang keluar gagal dimasukkan.');
        }
    }
}
