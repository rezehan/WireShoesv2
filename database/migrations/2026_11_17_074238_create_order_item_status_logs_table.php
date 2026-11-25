<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_status_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->cascadeOnDelete();

            $table->foreignId('status_id')
                ->constrained('work_statuses')
                ->restrictOnDelete();

            $table->foreignId('dicatat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('catatan', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['order_item_id', 'created_at'],
                'idx_status_logs_item'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_status_logs');
    }
};
