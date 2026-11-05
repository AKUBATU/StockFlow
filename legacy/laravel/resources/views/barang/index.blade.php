@extends('layouts.app')

@section('content')

<div class="table-card mb-3">
    <h5>Tambah Daftar Barang</h5>

    <form method="POST" action="{{ route('barang.store') }}" class="row g-2">
        @csrf

        <div class="col-md-2">
            <label>Kode</label>
            <input 
                name="kode_barang" 
                class="form-control" 
                required
            >
        </div>

        <div class="col-md-3">
            <label>Nama</label>
            <input 
                name="nama_barang" 
                class="form-control" 
                required
            >
        </div>

        <div class="col-md-2">
            <label>Kategori</label>
            <select name="kategori_id" class="form-select" required>
                <option value="">Pilih</option>
                @foreach($kategori as $k)
                    <option value="{{ $k->id }}">
                        {{ $k->nama_kategori }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-1">
            <label>Satuan</label>
            <input 
                name="satuan" 
                class="form-control" 
                value="pcs" 
                required
            >
        </div>

        <div class="col-md-1">
            <label>Stok</label>
            <input 
                type="number" 
                name="stok" 
                class="form-control" 
                value="0" 
                min="0" 
                required
            >
        </div>

        <div class="col-md-1">
            <label>Min</label>
            <input 
                type="number" 
                name="stok_minimum" 
                class="form-control" 
                value="0" 
                min="0" 
                required
            >
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-main">
                Simpan
            </button>
        </div>
    </form>
</div>

<div class="table-card">
    <h5>Daftar Barang</h5>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Status</th>
                <th>Aksi</th>
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
                    <td>
                        <button 
                            type="button" 
                            class="btn btn-sm btn-info text-white"
                            data-bs-toggle="modal"
                            data-bs-target="#detailBarang{{ $b->id }}"
                        >
                            Detail
                        </button>

                        <button 
                            type="button" 
                            class="btn btn-sm btn-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#editBarang{{ $b->id }}"
                        >
                            Edit
                        </button>

                        <form 
                            id="deleteForm{{ $b->id }}" 
                            action="{{ route('barang.destroy', $b->id) }}" 
                            method="POST" 
                            class="d-inline"
                        >
                            @csrf
                            @method('DELETE')

                            <button 
                                type="button" 
                                class="btn btn-sm btn-danger"
                                onclick="confirmDelete({{ $b->id }})"
                            >
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>

                <!-- Modal Detail Barang -->
                <div 
                    class="modal fade" 
                    id="detailBarang{{ $b->id }}" 
                    tabindex="-1" 
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Detail Barang
                                </h5>

                                <button 
                                    type="button" 
                                    class="btn-close" 
                                    data-bs-dismiss="modal"
                                ></button>
                            </div>

                            <div class="modal-body">
                                <p><b>Kode:</b> {{ $b->kode_barang }}</p>
                                <p><b>Nama:</b> {{ $b->nama_barang }}</p>
                                <p><b>Kategori:</b> {{ $b->kategori->nama_kategori ?? '-' }}</p>
                                <p><b>Satuan:</b> {{ $b->satuan }}</p>
                                <p><b>Stok:</b> {{ $b->stok }}</p>
                                <p><b>Stok Minimum:</b> {{ $b->stok_minimum }}</p>

                                <p>
                                    <b>Status:</b>
                                    @if($b->stok > 0)
                                        <span class="badge bg-success">Tersedia</span>
                                    @else
                                        <span class="badge bg-danger">Tidak Tersedia</span>
                                    @endif
                                </p>
                            </div>

                            <div class="modal-footer">
                                <button 
                                    type="button" 
                                    class="btn btn-secondary" 
                                    data-bs-dismiss="modal"
                                >
                                    Tutup
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Modal Edit Barang -->
                <div 
                    class="modal fade" 
                    id="editBarang{{ $b->id }}" 
                    tabindex="-1" 
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">

                            <form method="POST" action="{{ route('barang.update', $b->id) }}">
                                @csrf
                                @method('PUT')

                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        Edit Barang
                                    </h5>

                                    <button 
                                        type="button" 
                                        class="btn-close" 
                                        data-bs-dismiss="modal"
                                    ></button>
                                </div>

                                <div class="modal-body">
                                    <div class="row g-3">

                                        <div class="col-md-4">
                                            <label>Kode Barang</label>
                                            <input 
                                                name="kode_barang" 
                                                class="form-control" 
                                                value="{{ $b->kode_barang }}" 
                                                required
                                            >
                                        </div>

                                        <div class="col-md-8">
                                            <label>Nama Barang</label>
                                            <input 
                                                name="nama_barang" 
                                                class="form-control" 
                                                value="{{ $b->nama_barang }}" 
                                                required
                                            >
                                        </div>

                                        <div class="col-md-4">
                                            <label>Kategori</label>
                                            <select name="kategori_id" class="form-select" required>
                                                @foreach($kategori as $k)
                                                    <option 
                                                        value="{{ $k->id }}" 
                                                        {{ $b->kategori_id == $k->id ? 'selected' : '' }}
                                                    >
                                                        {{ $k->nama_kategori }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label>Satuan</label>
                                            <input 
                                                name="satuan" 
                                                class="form-control" 
                                                value="{{ $b->satuan }}" 
                                                required
                                            >
                                        </div>

                                        <div class="col-md-2">
                                            <label>Stok</label>
                                            <input 
                                                type="number" 
                                                name="stok" 
                                                class="form-control" 
                                                value="{{ $b->stok }}" 
                                                min="0" 
                                                required
                                            >
                                        </div>

                                        <div class="col-md-3">
                                            <label>Stok Minimum</label>
                                            <input 
                                                type="number" 
                                                name="stok_minimum" 
                                                class="form-control" 
                                                value="{{ $b->stok_minimum }}" 
                                                min="0" 
                                                required
                                            >
                                        </div>

                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button 
                                        type="button" 
                                        class="btn btn-secondary" 
                                        data-bs-dismiss="modal"
                                    >
                                        Batal
                                    </button>

                                    <button 
                                        type="submit" 
                                        class="btn btn-main"
                                    >
                                        Simpan Perubahan
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>

            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        Belum ada data barang.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: 'Data barang yang dihapus tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm' + id).submit();
            }
        });
    }
</script>

@endsection