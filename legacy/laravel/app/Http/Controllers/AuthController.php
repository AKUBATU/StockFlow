<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function index()
    {
        if (session()->has('user_id')) {
            if (session('role') === 'admin') {
                return redirect()->route('dashboard');
            }

            if (session('role') === 'gudang') {
                return redirect()->route('gudang.dashboard');
            }

            if (session('role') === 'pimpinan') {
                return redirect()->route('pimpinan.dashboard');
            }
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return back()->with('error', 'Username atau password salah.');
        }

        if (!Hash::check($request->password, $user->password) && $request->password !== $user->password) {
            return back()->with('error', 'Username atau password salah.');
        }

        session([
            'user_id' => $user->id,
            'nama' => $user->nama,
            'username' => $user->username,
            'role' => $user->role,
        ]);

        if ($user->role === 'admin') {
            return redirect()->route('dashboard')->with('success', 'Login berhasil sebagai Admin.');
        }

        if ($user->role === 'gudang') {
            return redirect()->route('gudang.dashboard')->with('success', 'Login berhasil sebagai Petugas Gudang.');
        }

        if ($user->role === 'pimpinan') {
            return redirect()->route('pimpinan.dashboard')->with('success', 'Login berhasil sebagai Pimpinan.');
        }

        session()->flush();

        return redirect()->route('login')->with('error', 'Role pengguna tidak dikenali.');
    }

    public function logout()
    {
        session()->flush();

        return redirect()->route('login')->with('success', 'Logout berhasil.');
    }
}