<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\PimpinanController;
use App\Http\Controllers\PersediaanController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LaporanController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/gudang/dashboard', [GudangController::class, 'dashboard'])->name('gudang.dashboard');

Route::get('/pimpinan/dashboard', [PimpinanController::class, 'dashboard'])->name('pimpinan.dashboard');

Route::get('/persediaan', [PersediaanController::class, 'index'])->name('persediaan.index');
Route::post('/persediaan/masuk', [PersediaanController::class, 'barangMasuk'])->name('persediaan.masuk');
Route::post('/persediaan/keluar', [PersediaanController::class, 'barangKeluar'])->name('persediaan.keluar');

Route::resource('/kategori', KategoriController::class);
Route::resource('/barang', BarangController::class);
Route::resource('/users', UserController::class);

Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');