<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('service_id')
                ->constrained('services')
                ->restrictOnDelete();

            $table->string('warna', 50)->nullable();
            $table->text('catatan')->nullable();

            // Harga layanan pada saat pesanan dibuat.
            $table->decimal('harga', 12, 2);

            $table->foreignId('status_id')
                ->constrained('work_statuses')
                ->restrictOnDelete();

            $table->dateTime('estimasi_selesai')->nullable();
            $table->dateTime('tanggal_selesai')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};