@extends('layouts.app')
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-stat p-3"><small>Total Barang</small><div class="stat-number">{{ $total_barang }}</div></div></div>
    <div class="col-md-3"><div class="card card-stat p-3"><small>Total Stok Masuk</small><div class="stat-number text-success">{{ $total_stok_masuk }}</div></div></div>
    <div class="col-md-3"><div class="card card-stat p-3"><small>Total Stok Keluar</small><div class="stat-number text-danger">{{ $total_stok_keluar }}</div></div></div>
    <div class="col-md-3"><div class="card card-stat p-3"><small>Stok Terendah &lt; 10</small><div class="stat-number text-warning">{{ $stok_terendah_count }}</div></div></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="table-card"><h5>Stok Tertinggi</h5>@if($stok_tertinggi)<h3>{{ $stok_tertinggi->nama_barang }}</h3><p>Stok saat ini: <strong>{{ $stok_tertinggi->stok }} {{ $stok_tertinggi->satuan }}</strong></p>@else<p>Belum ada data barang.</p>@endif</div></div>
    <div class="col-md-6"><div class="table-card"><h5>Informasi Sistem</h5><p>Dashboard menampilkan ringkasan total barang, stok masuk, stok keluar, stok rendah, dan stok tertinggi.</p></div></div>
</div>
<div class="table-card"><h5>Daftar Barang dengan Stok Terendah &lt; 10</h5><table class="table"><thead><tr><th>No</th><th>Kode</th><th>Nama</th><th>Kategori</th><th>Stok</th><th>Status</th></tr></thead><tbody>@forelse($barang_terendah as $b)<tr><td>{{ $loop->iteration }}</td><td>{{ $b->kode_barang }}</td><td>{{ $b->nama_barang }}</td><td>{{ $b->kategori->nama_kategori ?? '-' }}</td><td>{{ $b->stok }} {{ $b->satuan }}</td><td>{!! $b->stok > 0 ? '<span class="badge bg-success">Tersedia</span>' : '<span class="badge bg-danger">Tidak Tersedia</span>' !!}</td></tr>@empty<tr><td colspan="6" class="text-center">Tidak ada stok rendah.</td></tr>@endforelse</tbody></table></div>
@endsection
