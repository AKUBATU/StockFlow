@extends('layouts.app')

@section('content')

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="table-card text-center">
            <div class="avatar">
                {{ strtoupper(substr($profile->nama ?? 'A', 0, 1)) }}
            </div>

            <h4>{{ $profile->nama ?? 'Administrator Gudang' }}</h4>
            <p>{{ '@' . ($profile->username ?? 'admin') }}</p>

            <span class="badge bg-primary">
                {{ $profile->role ?? 'admin' }}
            </span>
        </div>
    </div>

    <div class="col-md-8">
        <div class="table-card">
            <h5>Profil Saya</h5>
            <p>
                Menu ini digunakan untuk melihat profil admin dan mengelola pengguna aplikasi.
            </p>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="table-card">
            <h5>Tambah Pengguna</h5>

            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <label>Nama</label>
                <input 
                    name="nama" 
                    class="form-control mb-2" 
                    required
                >

                <label>Username</label>
                <input 
                    name="username" 
                    class="form-control mb-2" 
                    required
                >

                <label>Password</label>
                <input 
                    type="password" 
                    name="password" 
                    class="form-control mb-2" 
                    required
                >

                <label>Role</label>
                <select name="role" class="form-select mb-3" required>
                    <option value="admin">Admin</option>
                    <option value="gudang">Gudang</option>
                    <option value="pimpinan">Pimpinan</option>
                </select>

                <button class="btn btn-main">
                    Simpan
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="table-card">
            <h5>Data Pengguna</h5>

            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $u->nama }}</td>
                            <td>{{ $u->username }}</td>
                            <td>{{ $u->role }}</td>
                            <td>
                                <button 
                                    type="button" 
                                    class="btn btn-sm btn-info text-white"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailUser{{ $u->id }}"
                                >
                                    Detail
                                </button>

                                <button 
                                    type="button" 
                                    class="btn btn-sm btn-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editUser{{ $u->id }}"
                                >
                                    Edit
                                </button>

                                <form 
                                    id="deleteUserForm{{ $u->id }}" 
                                    action="{{ route('users.destroy', $u->id) }}" 
                                    method="POST" 
                                    class="d-inline"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button 
                                        type="button"
                                        onclick="confirmDeleteUser({{ $u->id }})" 
                                        class="btn btn-sm btn-danger"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal Detail Pengguna -->
                        <div 
                            class="modal fade" 
                            id="detailUser{{ $u->id }}" 
                            tabindex="-1" 
                            aria-hidden="true"
                        >
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">

                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            Detail Pengguna
                                        </h5>

                                        <button 
                                            type="button" 
                                            class="btn-close" 
                                            data-bs-dismiss="modal"
                                        ></button>
                                    </div>

                                    <div class="modal-body">
                                        <p><b>ID:</b> {{ $u->id }}</p>
                                        <p><b>Nama:</b> {{ $u->nama }}</p>
                                        <p><b>Username:</b> {{ $u->username }}</p>
                                        <p><b>Role:</b> {{ $u->role }}</p>
                                        <p><b>Dibuat:</b> {{ $u->created_at }}</p>
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

                        <!-- Modal Edit Pengguna -->
                        <div 
                            class="modal fade" 
                            id="editUser{{ $u->id }}" 
                            tabindex="-1" 
                            aria-hidden="true"
                        >
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">

                                    <form method="POST" action="{{ route('users.update', $u->id) }}">
                                        @csrf
                                        @method('PUT')

                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                Edit Pengguna
                                            </h5>

                                            <button 
                                                type="button" 
                                                class="btn-close" 
                                                data-bs-dismiss="modal"
                                            ></button>
                                        </div>

                                        <div class="modal-body">
                                            <label>Nama</label>
                                            <input 
                                                name="nama" 
                                                class="form-control mb-2" 
                                                value="{{ $u->nama }}" 
                                                required
                                            >

                                            <label>Username</label>
                                            <input 
                                                name="username" 
                                                class="form-control mb-2" 
                                                value="{{ $u->username }}" 
                                                required
                                            >

                                            <label>Password Baru</label>
                                            <input 
                                                type="password" 
                                                name="password" 
                                                class="form-control mb-2"
                                                placeholder="Kosongkan jika tidak ingin mengganti password"
                                            >

                                            <label>Role</label>
                                            <select name="role" class="form-select mb-3" required>
                                                <option value="admin" {{ $u->role == 'admin' ? 'selected' : '' }}>
                                                    Admin
                                                </option>

                                                <option value="gudang" {{ $u->role == 'gudang' ? 'selected' : '' }}>
                                                    Gudang
                                                </option>

                                                <option value="pimpinan" {{ $u->role == 'pimpinan' ? 'selected' : '' }}>
                                                    Pimpinan
                                                </option>
                                            </select>
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
                            <td colspan="5" class="text-center text-muted">
                                Belum ada data pengguna.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function confirmDeleteUser(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: 'Data pengguna yang dihapus tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteUserForm' + id).submit();
            }
        });
    }
</script>

@endsection