@extends('layouts.app')

@section('content')

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-stat p-3 bg-white">
            <p>Total Barang</p>
            <div class="stat-number">{{ $total_barang }}</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card-stat p-3 bg-white">
            <p>Total Stok Masuk</p>
            <div class="stat-number text-success">{{ $total_stok_masuk }}</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card-stat p-3 bg-white">
            <p>Total Stok Keluar</p>
            <div class="stat-number text-danger">{{ $total_stok_keluar }}</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card-stat p-3 bg-white">
            <p>Stok Rendah &lt; 10</p>
            <div class="stat-number text-warning">{{ $stok_rendah->count() }}</div>
        </div>
    </div>
</div>

<div class="table-card mb-3">
    <h5>Dashboard Petugas Gudang</h5>

    <p>
        Halaman ini digunakan oleh petugas gudang untuk memantau stok barang,
        mengelola barang masuk, dan mengelola barang keluar.
    </p>

    <a href="{{ route('persediaan.index') }}" class="btn btn-main">
        Kelola Persediaan Barang
    </a>

    <a href="{{ route('barang.index') }}" class="btn btn-secondary">
        Lihat Daftar Barang
    </a>
</div>

<div class="table-card">
    <h5>Data Stok Barang</h5>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
            @forelse($barang as $b)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $b->kode_barang }}</td>
                    <td>{{ $b->nama_barang }}</td>
                    <td>{{ $b->kategori->nama_kategori ?? '-' }}</td>
                    <td>{{ $b->stok }} {{ $b->satuan }}</td>
                    <td>
                        @if($b->stok > 0)
                            <span class="badge bg-success">Tersedia</span>
                        @else
                            <span class="badge bg-danger">Tidak Tersedia</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        Belum ada data barang.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection