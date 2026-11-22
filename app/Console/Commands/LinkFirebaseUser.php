<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebaseIdentity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class LinkFirebaseUser extends Command
{
    protected $signature = 'firebase:link-user {email} {--role=} {--name=}';

    protected $description = 'Hubungkan identitas Firebase dengan akun WireShoes';

    public function handle(FirebaseIdentity $firebase): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $role = $this->option('role');
        $name = $this->option('name');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $this->error('Email tidak valid.');

            return self::FAILURE;
        }

        if ($role !== null && ! in_array($role, [User::ROLE_ADMIN, User::ROLE_KASIR, User::ROLE_PELANGGAN], true)) {
            $this->error('Role harus admin, kasir, atau pelanggan.');

            return self::FAILURE;
        }

        if ($name !== null && (trim($name) === '' || Str::length($name) > 255)) {
            $this->error('Nama harus berisi 1 sampai 255 karakter.');

            return self::FAILURE;
        }

        try {
            $record = $firebase->admin()->getUserByEmail($email);
        } catch (Throwable $error) {
            $firebase->unavailable($error);
            $this->error('Akun Firebase tidak ditemukan atau layanan sedang tidak tersedia.');

            return self::FAILURE;
        }

        if ($record->disabled || ! $record->email) {
            $this->error('Akun Firebase harus aktif dan memiliki email.');

            return self::FAILURE;
        }

        return DB::transaction(function () use ($firebase, $email, $role, $name, $record): int {
            $byEmail = User::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
            $byUid = User::where('firebase_uid', $record->uid)->lockForUpdate()->first();

            if (($byEmail && $byEmail->firebase_uid && $byEmail->firebase_uid !== $record->uid)
                || ($byEmail && $byUid && ! $byEmail->is($byUid))) {
                $this->error('Email dan UID sudah terhubung ke akun yang berbeda.');

                return self::FAILURE;
            }

            $user = $byUid ?? $byEmail;

            if (! $user && $role === null) {
                $this->error('Isi --role saat menambahkan akun WireShoes baru.');

                return self::FAILURE;
            }

            $user ??= new User(['name' => $name ?? $record->displayName ?? $email, 'email' => $email]);
            $user->firebase_uid = $record->uid;
            $user->role = $role ?? $user->role;
            $user->password = null;
            $user->remember_token = null;

            $firebase->updateLocalIdentity($user, $record->email, $record->emailVerified);

            if ($user->isPelanggan()) {
                $user->ensureCustomer();
            }

            $this->info('Akun WireShoes terhubung sebagai '.$user->role.'.');

            return self::SUCCESS;
        });
    }
}
