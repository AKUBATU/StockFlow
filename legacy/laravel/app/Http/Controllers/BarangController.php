<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Barang;
use App\Models\Kategori;

class BarangController extends Controller
{
    private function cekAksesBarang()
    {
        if (!session()->has('user_id')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (!in_array(session('role'), ['admin', 'gudang'])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke halaman barang.');
        }

        return null;
    }

    public function index()
    {
        if ($r = $this->cekAksesBarang()) return $r;

        return view('barang.index', [
            'barang' => Barang::with('kategori')->latest()->get(),
            'kategori' => Kategori::orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(Request $request)
    {
        if ($r = $this->cekAksesBarang()) return $r;

        if (!in_array(session('role'), ['admin', 'gudang'])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses menambah barang.');
        }

        $request->validate([
            'kode_barang' => 'required|max:30|unique:barang,kode_barang',
            'nama_barang' => 'required|max:150',
            'kategori_id' => 'required|exists:kategori,id',
            'satuan' => 'required|max:30',
            'stok' => 'required|integer|min:0',
            'stok_minimum' => 'required|integer|min:0',
        ]);

        try {
            Barang::create([
                'kode_barang' => $request->kode_barang,
                'nama_barang' => $request->nama_barang,
                'kategori_id' => $request->kategori_id,
                'satuan' => $request->satuan,
                'stok' => $request->stok,
                'stok_minimum' => $request->stok_minimum,
            ]);

            return redirect()->route('barang.index')->with('success', 'Data barang berhasil dimasukkan.');
        } catch (\Exception $e) {
            return redirect()->route('barang.index')->with('error', 'Data barang gagal dimasukkan.');
        }
    }

    public function update(Request $request, Barang $barang)
    {
        if ($r = $this->cekAksesBarang()) return $r;

        if (!in_array(session('role'), ['admin', 'gudang'])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses mengubah barang.');
        }

        $request->validate([
            'kode_barang' => 'required|max:30|unique:barang,kode_barang,' . $barang->id,
            'nama_barang' => 'required|max:150',
            'kategori_id' => 'required|exists:kategori,id',
            'satuan' => 'required|max:30',
            'stok' => 'required|integer|min:0',
            'stok_minimum' => 'required|integer|min:0',
        ]);

        try {
            $barang->update([
                'kode_barang' => $request->kode_barang,
                'nama_barang' => $request->nama_barang,
                'kategori_id' => $request->kategori_id,
                'satuan' => $request->satuan,
                'stok' => $request->stok,
                'stok_minimum' => $request->stok_minimum,
            ]);

            return redirect()->route('barang.index')->with('success', 'Data barang berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->route('barang.index')->with('error', 'Data barang gagal diperbarui.');
        }
    }

    public function destroy(Barang $barang)
    {
        if ($r = $this->cekAksesBarang()) return $r;

        if (session('role') !== 'admin') {
            return redirect()->back()->with('error', 'Hanya admin yang boleh menghapus barang.');
        }

        try {
            $barang->delete();

            return redirect()->route('barang.index')->with('success', 'Data barang berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('barang.index')->with('error', 'Data barang gagal dihapus.');
        }
    }
}