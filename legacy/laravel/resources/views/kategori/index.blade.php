@extends('layouts.app')

@section('content')

<div class="table-card mb-3">
    <h5>Tambah Kategori Barang</h5>

    <form method="POST" action="{{ route('kategori.store') }}">
        @csrf

        <label>Nama Kategori</label>
        <input 
            type="text" 
            name="nama_kategori" 
            class="form-control mb-3" 
            required
        >

        <button class="btn btn-main">
            Simpan
        </button>
    </form>
</div>

<div class="table-card">
    <h5>Data Kategori Barang</h5>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Kategori</th>
                <th>Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($kategori as $k)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $k->nama_kategori }}</td>
                    <td>{{ $k->created_at }}</td>
                    <td>
                        <button 
                            type="button" 
                            class="btn btn-sm btn-info text-white"
                            data-bs-toggle="modal"
                            data-bs-target="#detailKategori{{ $k->id }}"
                        >
                            Detail
                        </button>

                        <button 
                            type="button" 
                            class="btn btn-sm btn-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#editKategori{{ $k->id }}"
                        >
                            Edit
                        </button>

                        <form 
                            id="deleteKategoriForm{{ $k->id }}" 
                            action="{{ route('kategori.destroy', $k->id) }}" 
                            method="POST" 
                            class="d-inline"
                        >
                            @csrf
                            @method('DELETE')

                            <button 
                                type="button" 
                                class="btn btn-sm btn-danger"
                                onclick="confirmDeleteKategori({{ $k->id }})"
                            >
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>

                <!-- Modal Detail Kategori -->
                <div 
                    class="modal fade" 
                    id="detailKategori{{ $k->id }}" 
                    tabindex="-1" 
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Detail Kategori Barang
                                </h5>

                                <button 
                                    type="button" 
                                    class="btn-close" 
                                    data-bs-dismiss="modal"
                                ></button>
                            </div>

                            <div class="modal-body">
                                <p><b>ID:</b> {{ $k->id }}</p>
                                <p><b>Nama Kategori:</b> {{ $k->nama_kategori }}</p>
                                <p><b>Dibuat:</b> {{ $k->created_at }}</p>
                                <p><b>Diperbarui:</b> {{ $k->updated_at }}</p>
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

                <!-- Modal Edit Kategori -->
                <div 
                    class="modal fade" 
                    id="editKategori{{ $k->id }}" 
                    tabindex="-1" 
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">

                            <form method="POST" action="{{ route('kategori.update', $k->id) }}">
                                @csrf
                                @method('PUT')

                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        Edit Kategori Barang
                                    </h5>

                                    <button 
                                        type="button" 
                                        class="btn-close" 
                                        data-bs-dismiss="modal"
                                    ></button>
                                </div>

                                <div class="modal-body">
                                    <label>Nama Kategori</label>
                                    <input 
                                        type="text" 
                                        name="nama_kategori" 
                                        class="form-control" 
                                        value="{{ $k->nama_kategori }}" 
                                        required
                                    >
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
                    <td colspan="4" class="text-center text-muted">
                        Belum ada data kategori.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    function confirmDeleteKategori(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: 'Data kategori yang dihapus tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteKategoriForm' + id).submit();
            }
        });
    }
</script>

@endsection