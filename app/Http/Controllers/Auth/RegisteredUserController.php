<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan halaman pendaftaran.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Daftarkan pelanggan baru: buat akun (role pelanggan) sekaligus data customer-nya.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[A-Za-z0-9_]+$/',
                'unique:users,username',
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'max:72', 'confirmed', Rules\Password::min(8)],
            'alamat' => ['required', 'string', 'max:255'],
        ], [
            'required' => ':attribute wajib diisi.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'unique' => ':attribute sudah dipakai.',
            'email' => 'Format email tidak valid.',
            'regex' => 'Username hanya boleh berisi huruf, angka, dan underscore.',
            'confirmed' => 'Konfirmasi password tidak cocok.',
        ], [
            'nama_lengkap' => 'Nama lengkap',
            'username' => 'Username',
            'email' => 'Email',
            'password' => 'Password',
            'alamat' => 'Alamat',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['nama_lengkap'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'], // di-hash otomatis oleh cast 'hashed'
                'alamat' => $data['alamat'],
                'role' => User::ROLE_PELANGGAN,
            ]);

            Customer::create([
                'kode_unik' => $this->generateKodeUnik(),
                'nama' => $data['nama_lengkap'],
                'alamat' => $data['alamat'],
                'user_id' => $user->id,
            ]);

            return $user;
        });

        event(new Registered($user));

        // Sama seperti alur SPA: setelah daftar, arahkan ke halaman masuk dengan pesan sukses.
        return redirect()
            ->route('verification.notice')
            ->route('login')
            ->with('status', 'Akun berhasil dibuat. Masuk dengan email dan password barumu.');
    }

    /**
     * Kode unik pelanggan 12 karakter (kolom customers.kode_unik, unik).
     */
    private function generateKodeUnik(): string
    {
        do {
            $kode = 'CST' . strtoupper(Str::random(9));
        } while (Customer::where('kode_unik', $kode)->exists());

        return $kode;
    }
}