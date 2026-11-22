<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Kreait\Firebase\Exception\Auth\EmailExists;
use Throwable;

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
    public function store(Request $request, FirebaseIdentity $firebase): RedirectResponse
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

        try {
            $record = $firebase->admin()->createUser([
                'email' => $data['email'],
                'password' => $data['password'],
                'displayName' => $data['nama_lengkap'],
            ]);
        } catch (EmailExists) {
            throw ValidationException::withMessages(['email' => 'Email sudah dipakai. Coba masuk atau atur ulang password.']);
        } catch (Throwable $error) {
            throw $firebase->unavailable($error);
        }

        try {
            DB::transaction(function () use ($data, $record): void {
                $user = new User([
                    'name' => $data['nama_lengkap'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'alamat' => $data['alamat'],
                    'role' => User::ROLE_PELANGGAN,
                ]);
                $user->firebase_uid = $record->uid;
                $user->save();
                $user->ensureCustomer();
            });
        } catch (Throwable $error) {
            try {
                $firebase->admin()->deleteUser($record->uid);
            } catch (Throwable $cleanupError) {
                $firebase->unavailable($cleanupError);
            }

            throw $error;
        }

        $status = 'Akun berhasil dibuat. Cek email verifikasi, lalu masuk ke WireShoes.';

        try {
            $firebase->admin()->sendEmailVerificationLink($data['email'], null, 'id');
        } catch (Throwable $error) {
            $firebase->unavailable($error);
            $status = 'Akun berhasil dibuat. Setelah masuk, buka halaman verifikasi untuk meminta email verifikasi.';
        }

        return redirect()->route('login')->with('status', $status);
    }
}
