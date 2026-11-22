<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    public const LAYANAN_BAWAAN = [
        'cuci' => ['nama' => 'Cuci', 'harga' => 20000, 'estimasi_hari' => 3],
        'reparasi' => ['nama' => 'Reparasi', 'harga' => 35000, 'estimasi_hari' => 5],
        'cuci_reparasi' => ['nama' => 'Cuci dan Reparasi', 'harga' => 50000, 'estimasi_hari' => 7],
    ];

    public static function tarifDasar(string $nama): int
    {
        return array_column(self::LAYANAN_BAWAAN, 'harga', 'nama')[$nama] ?? 0;
    }

    public function hargaMinimum(): float
    {
        return max((float) $this->harga, self::tarifDasar($this->nama));
    }
    protected $table = 'services';

    protected $fillable = [
        'nama',
        'deskripsi',
        'harga',
        'estimasi_hari',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'estimasi_hari' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
