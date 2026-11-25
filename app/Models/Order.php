<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Order extends Model
{
    public static function buatDenganNomorUnik(Customer $customer, array $attributes): self
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return DB::transaction(fn () => $customer->orders()->create([
                    ...$attributes,
                    'nomor_pesanan' => (string) random_int(10_000_000, 99_999_999),
                ]));
            } catch (UniqueConstraintViolationException $error) {
                $detail = (string) ($error->errorInfo[2] ?? '');

                if (
                    ! str_contains($detail, 'uq_orders_nomor_pesanan')
                    && ! str_contains($detail, 'orders.nomor_pesanan')
                ) {
                    throw $error;
                }
            }
        }

        throw ValidationException::withMessages([
            'service_id' => 'Nomor pesanan belum berhasil dibuat. Coba lagi.',
        ]);
    }

    protected $table = 'orders';

    public const SUMBER_TOKO = 'toko';

    public const SUMBER_ONLINE = 'online';

    public const STATUS_DIPESAN = 'dipesan';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'nomor_pesanan',
        'customer_id',
        'sumber',
        'status',
        'nama_pemesan',
        'alamat_pemesan',
        'no_telepon_pemesan',
        'catatan',
        'jadwal_antar',
        'tanggal_masuk',
        'dibuat_oleh',
        'kode_tracking',
        'diterima_pada',
        'diterima_oleh',
        'diambil_pada',
        'diambil_oleh',
    ];

    protected function casts(): array
    {
        return [
            'jadwal_antar' => 'date',
            'tanggal_masuk' => 'datetime',
            'diterima_pada' => 'datetime',
            'diambil_pada' => 'datetime',
        ];
    }

    // Relasi

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    public function pengambil(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diambil_oleh');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scope

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('sumber', self::SUMBER_ONLINE);
    }

    public function scopeToko(Builder $query): Builder
    {
        return $query->where('sumber', self::SUMBER_TOKO);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function metodeSerahTerima(): ?string
    {
        return $this->metode_serah_terima ?? match (true) {
            $this->diantar_pada !== null => 'antar',
            $this->diambil_pada !== null => 'ambil',
            default => null,
        };
    }
    // Helper

    public function sudahDiterima(): bool
    {
        return $this->diterima_pada !== null;
    }

    public function sudahDiambil(): bool
    {
        return $this->diambil_pada !== null;
    }

    /**
     * Total harga seluruh item pesanan.
     * Jika relasi items sudah di-load, hitung dari koleksi (tanpa query tambahan).
     */
    public function getTotalHargaAttribute(): string
    {
        $total = $this->relationLoaded('items')
            ? $this->items->sum('harga')
            : $this->items()->sum('harga');

        return number_format((float) $total, 2, '.', '');
    }
}
