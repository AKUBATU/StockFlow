<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gudang ATK Laravel</title>

    <link 
        rel="stylesheet" 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    >

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">Gudang<span>ATK</span></div>

        <nav>
            @if(session('role') === 'admin')
                <a href="{{ route('dashboard') }}">
                    Dashboard
                </a>

                <a href="{{ route('persediaan.index') }}">
                    Persediaan Barang
                </a>

                <button 
                    class="sidebar-dropdown-btn" 
                    type="button" 
                    onclick="toggleDropdown()"
                >
                    Master Data 
                    <span id="dropdownIcon">▼</span>
                </button>

                <div class="sidebar-dropdown-menu" id="masterDropdown">
                    <a href="{{ route('kategori.index') }}">
                        Kategori Barang
                    </a>

                    <a href="{{ route('barang.index') }}">
                        Daftar Barang
                    </a>

                    <a href="{{ route('users.index') }}">
                        Manajemen Pengguna
                    </a>
                </div>

                <a href="{{ route('laporan.index') }}">
                    Laporan
                </a>
            @endif

            @if(session('role') === 'gudang')
                <a href="{{ route('gudang.dashboard') }}">
                    Dashboard Gudang
                </a>

                <a href="{{ route('persediaan.index') }}">
                    Persediaan Barang
                </a>

                <a href="{{ route('barang.index') }}">
                    Daftar Barang
                </a>
            @endif

            @if(session('role') === 'pimpinan')
                <a href="{{ route('pimpinan.dashboard') }}">
                    Dashboard Pimpinan
                </a>

                <a href="{{ route('laporan.index') }}">
                    Laporan
                </a>
            @endif

            <a href="{{ route('logout') }}" class="logout">
                Logout
            </a>
        </nav>
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <h5 class="mb-0">
                    Aplikasi Persediaan Barang Online
                </h5>
                <small>
                    Laravel + MySQL - Studi Kasus Gudang ATK
                </small>
            </div>

            <div class="user-box">
                Halo, {{ session('nama') ?? 'Admin' }} 
                <span class="badge bg-primary ms-2">
                    {{ strtoupper(session('role') ?? '-') }}
                </span>
            </div>
        </div>

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleDropdown() {
        const dropdown = document.getElementById('masterDropdown');
        const icon = document.getElementById('dropdownIcon');

        if (!dropdown || !icon) {
            return;
        }

        if (dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
            icon.textContent = '▼';
        } else {
            dropdown.style.display = 'block';
            icon.textContent = '▲';
        }
    }

    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: @json(session('success')),
            timer: 2200,
            showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: @json(session('error'))
        });
    @endif

    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Data gagal diproses',
            html: `{!! implode('<br>', $errors->all()) !!}`
        });
    @endif
</script>

</body>
</html>