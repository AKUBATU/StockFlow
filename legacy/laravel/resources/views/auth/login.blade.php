<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gudang ATK</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="auth-body">
<div class="auth-card">
    <h3 class="fw-bold text-center">GudangATK</h3>
    <p class="text-center text-muted">Login Admin Gudang</p>
    <form method="POST" action="{{ route('login.process') }}">
        @csrf
        <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required autofocus></div>
        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <button class="btn btn-main w-100">Login</button>
        <p class="text-center mt-3 mb-0 small text-muted">Default: admin / admin123</p>
    </form>
</div>
<script>
@if(session('success')) Swal.fire({icon:'success', title:'Berhasil', text:@json(session('success')), timer:1800, showConfirmButton:false}); @endif
@if(session('error')) Swal.fire({icon:'error', title:'Login Gagal', text:@json(session('error'))}); @endif
@if($errors->any()) Swal.fire({icon:'error', title:'Login Gagal', text:'Username dan password wajib diisi.'}); @endif
</script>
</body>
</html>
