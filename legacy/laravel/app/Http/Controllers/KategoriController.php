<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;

class KategoriController extends Controller
{
    private function cekAdmin()
    {
        if (!session()->has('user_id')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (session('role') !== 'admin') {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke halaman kategori.');
        }

        return null;
    }

    public function index()
    {
        if ($r = $this->cekAdmin()) return $r;

        return view('kategori.index', [
            'kategori' => Kategori::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        if ($r = $this->cekAdmin()) return $r;

        $request->validate([
            'nama_kategori' => 'required|max:100'
        ]);

        try {
            Kategori::create([
                'nama_kategori' => $request->nama_kategori
            ]);

            return redirect()->route('kategori.index')->with('success', 'Kategori berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->route('kategori.index')->with('error', 'Kategori gagal ditambahkan.');
        }
    }

    public function update(Request $request, Kategori $kategori)
    {
        if ($r = $this->cekAdmin()) return $r;

        $request->validate([
            'nama_kategori' => 'required|max:100'
        ]);

        try {
            $kategori->update([
                'nama_kategori' => $request->nama_kategori
            ]);

            return redirect()->route('kategori.index')->with('success', 'Kategori berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->route('kategori.index')->with('error', 'Kategori gagal diperbarui.');
        }
    }

    public function destroy(Kategori $kategori)
    {
        if ($r = $this->cekAdmin()) return $r;

        try {
            $kategori->delete();

            return redirect()->route('kategori.index')->with('success', 'Kategori berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('kategori.index')->with('error', 'Kategori gagal dihapus. Pastikan kategori tidak sedang digunakan oleh data barang.');
        }
    }
}