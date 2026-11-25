<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkStatus extends Model
{
    protected $table = 'work_statuses';

    protected $fillable = [
        'nama',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function scopeUrut(Builder $query): Builder
    {
        return $query->orderBy('urutan');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'status_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderItemStatusLog::class, 'status_id');
    }
}
