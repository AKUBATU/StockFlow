@extends('layouts.app')
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="table-card"><h5>Input Barang Masuk</h5><form method="POST" action="{{ route('persediaan.masuk') }}">@csrf
        <div class="mb-2"><label>Barang</label><select name="barang_id" class="form-select" required><option value="">Pilih</option>@foreach($barang as $b)<option value="{{ $b->id }}">{{ $b->kode_barang }} - {{ $b->nama_barang }}</option>@endforeach</select></div>
        <div class="mb-2"><label>Supplier</label><select name="supplier_id" class="form-select"><option value="">-</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->nama_supplier }}</option>@endforeach</select></div>
        <div class="mb-2"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required></div>
        <div class="mb-2"><label>Jumlah</label><input type="number" name="jumlah" class="form-control" min="1" required></div>
        <div class="mb-2"><label>Keterangan</label><textarea name="keterangan" class="form-control"></textarea></div>
        <button class="btn btn-success">Simpan Barang Masuk</button></form></div></div>
    <div class="col-md-6"><div class="table-card"><h5>Input Barang Keluar</h5><form method="POST" action="{{ route('persediaan.keluar') }}">@csrf
        <div class="mb-2"><label>Barang</label><select name="barang_id" class="form-select" required><option value="">Pilih</option>@foreach($barang as $b)<option value="{{ $b->id }}">{{ $b->kode_barang }} - {{ $b->nama_barang }} | Stok: {{ $b->stok }}</option>@endforeach</select></div>
        <div class="mb-2"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required></div>
        <div class="mb-2"><label>Jumlah</label><input type="number" name="jumlah" class="form-control" min="1" required></div>
        <div class="mb-2"><label>Tujuan</label><input type="text" name="tujuan" class="form-control" required></div>
        <div class="mb-2"><label>Keterangan</label><textarea name="keterangan" class="form-control"></textarea></div>
        <button class="btn btn-danger">Simpan Barang Keluar</button></form></div></div>
</div>
<div class="table-card"><h5>Data Persediaan Barang Saat Ini</h5><table class="table"><thead><tr><th>No</th><th>Kode</th><th>Nama</th><th>Kategori</th><th>Stok Saat Ini</th><th>Status</th></tr></thead><tbody>@foreach($barang as $b)<tr><td>{{ $loop->iteration }}</td><td>{{ $b->kode_barang }}</td><td>{{ $b->nama_barang }}</td><td>{{ $b->kategori->nama_kategori ?? '-' }}</td><td><strong>{{ $b->stok }}</strong> {{ $b->satuan }}</td><td>{!! $b->stok > 0 ? '<span class="badge bg-success">Tersedia</span>' : '<span class="badge bg-danger">Tidak Tersedia</span>' !!}</td></tr>@endforeach</tbody></table></div>
@endsection
