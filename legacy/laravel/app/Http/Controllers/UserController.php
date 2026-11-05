<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    private function cekAdmin()
    {
        if (!session()->has('user_id')) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (session('role') !== 'admin') {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke halaman pengguna.');
        }

        return null;
    }

    public function index()
    {
        if ($r = $this->cekAdmin()) return $r;

        return view('users.index', [
            'users' => User::latest()->get(),
            'profile' => User::find(session('user_id')),
        ]);
    }

    public function store(Request $request)
    {
        if ($r = $this->cekAdmin()) return $r;

        $request->validate([
            'nama' => 'required|max:100',
            'username' => 'required|max:50|unique:users,username',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

        User::create([
            'nama' => $request->nama,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role
        ]);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        if ($r = $this->cekAdmin()) return $r;

        $request->validate([
            'nama' => 'required|max:100',
            'username' => 'required|max:50|unique:users,username,' . $user->id,
            'password' => 'nullable|min:6',
            'role' => 'required'
        ]);

        $data = [
            'nama' => $request->nama,
            'username' => $request->username,
            'role' => $request->role
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($user->id == session('user_id')) {
            session([
                'nama' => $user->nama,
                'username' => $user->username,
                'role' => $user->role
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($r = $this->cekAdmin()) return $r;

        if ($user->id == session('user_id')) {
            return redirect()->back()->with('error', 'Pengguna yang sedang login tidak boleh dihapus.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}