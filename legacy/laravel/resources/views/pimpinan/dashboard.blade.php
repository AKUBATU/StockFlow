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
    <h5>Dashboard Pimpinan</h5>

    <p>
        Halaman ini digunakan oleh pimpinan untuk memantau ringkasan data persediaan
        dan laporan keluar masuk barang. Pimpinan hanya melihat data, tidak mengubah data.
    </p>

    <a href="{{ route('laporan.index') }}" class="btn btn-main">
        Lihat Laporan
    </a>
</div>

<div class="table-card mb-3">
    <h5>Ringkasan Data Barang</h5>

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

<div class="row g-3">
    <div class="col-md-6">
        <div class="table-card">
            <h5>Transaksi Barang Masuk Terbaru</h5>

            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Barang</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($barang_masuk as $m)
                        <tr>
                            <td>{{ $m->tanggal }}</td>
                            <td>{{ $m->barang->nama_barang ?? '-' }}</td>
                            <td>{{ $m->jumlah }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                Belum ada data barang masuk.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-6">
        <div class="table-card">
            <h5>Transaksi Barang Keluar Terbaru</h5>

            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Barang</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($barang_keluar as $k)
                        <tr>
                            <td>{{ $k->tanggal }}</td>
                            <td>{{ $k->barang->nama_barang ?? '-' }}</td>
                            <td>{{ $k->jumlah }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                Belum ada data barang keluar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection