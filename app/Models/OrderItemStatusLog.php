<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemStatusLog extends Model
{
    protected $table = 'order_item_status_logs';

    // Tabel ini hanya punya created_at (tanpa updated_at).
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_item_id',
        'status_id',
        'dicatat_oleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(WorkStatus::class, 'status_id');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
