<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\TroliController; // <-- PEMBARUAN: Import TroliController

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login & register.
     */
    public function showWelcomeForm()
    {
        return view('welcome');
    }

    /**
     * Menangani proses registrasi.
     */
    public function register(Request $request)
    {
        // Validasi input dari form register
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users',
            'no_tlpn' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Membuat user baru
        User::create([
            'username' => $request->username,
            'no_tlpn' => $request->no_tlpn,
            'password' => Hash::make($request->password),
        ]);

        // Redirect kembali ke halaman utama dengan pesan sukses
        return redirect('/')->with('success', 'Registrasi berhasil! Silakan login menggunakan akun Anda.');
    }

    /**
     * Menangani proses login.
     */
    public function login(Request $request)
    {
        // Validasi input dari form login
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Coba untuk otentikasi user
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // ==================================================================
            // PEMBARUAN: Sinkronkan keranjang session ke database setelah login
            // ==================================================================
            (new TroliController())->sync();
            // ==================================================================

            // Jika berhasil, redirect ke halaman dashboard
            return redirect()->intended('dashboard');
        }

        // Jika gagal, kembali ke halaman login dengan pesan error
        return back()->withErrors([
            'username' => 'Username atau password yang diberikan salah.',
        ])->onlyInput('username');
    }

    /**
     * Menangani proses logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}