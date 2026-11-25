<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_KASIR = 'kasir';

    public const ROLE_PELANGGAN = 'pelanggan';

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'alamat',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'firebase_uid',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function ensureCustomer(): Customer
    {
        return $this->customer()->firstOrCreate([], [
            'kode_unik' => 'CST'.Str::upper(Str::random(9)),
            'nama' => Str::limit($this->name, 100, ''),
            'alamat' => $this->alamat,
        ]);
    }

    public function ordersDibuat(): HasMany
    {
        return $this->hasMany(Order::class, 'dibuat_oleh');
    }

    public function ordersDiterima(): HasMany
    {
        return $this->hasMany(Order::class, 'diterima_oleh');
    }

    public function ordersDiambil(): HasMany
    {
        return $this->hasMany(Order::class, 'diambil_oleh');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderItemStatusLog::class, 'dicatat_oleh');
    }

    // Helper role

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKasir(): bool
    {
        return $this->role === self::ROLE_KASIR;
    }

    public function isPelanggan(): bool
    {
        return $this->role === self::ROLE_PELANGGAN;
    }

    public function isStaf(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_KASIR], true);
    }
}
