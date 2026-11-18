<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'service_id',
        'warna',
        'catatan',
        'harga',
        'status_id',
        'estimasi_selesai',
        'tanggal_selesai',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'estimasi_selesai' => 'datetime',
            'tanggal_selesai' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(WorkStatus::class, 'status_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderItemStatusLog::class)->orderBy('created_at');
    }
}