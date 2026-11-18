<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->string('nomor_pesanan', 30)
                ->unique('uq_orders_nomor_pesanan');

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->enum('sumber', [
                'toko',
                'online',
            ])->default('online');

            $table->enum('status', [
                'dipesan',
                'diproses',
                'selesai',
                'dibatalkan',
            ])->default('dipesan');

            // Salinan kontak saat formulir pesanan dikirim.
            $table->string('nama_pemesan', 100)->nullable();
            $table->text('alamat_pemesan')->nullable();
            $table->string('no_telepon_pemesan', 20)->nullable();

            $table->text('catatan')->nullable();
            $table->date('jadwal_antar')->nullable();
            $table->dateTime('tanggal_masuk')->useCurrent();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Diisi backend ketika admin atau kasir menerima pesanan.
            $table->char('kode_tracking', 8)
                ->charset('ascii')
                ->collation('ascii_bin')
                ->nullable()
                ->unique('uq_orders_kode_tracking');

            $table->dateTime('diterima_pada')->nullable();

            $table->foreignId('diterima_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('diambil_pada')->nullable();

            $table->foreignId('diambil_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->index(['status', 'sumber'], 'idx_orders_status');
        });

        // MySQL 8.0.16+. Kode terisi harus terdiri dari delapan angka.
        DB::statement(<<<'SQL'
            ALTER TABLE orders
            ADD CONSTRAINT chk_orders_kode_tracking CHECK (
                kode_tracking IS NULL OR (
                    sumber = 'online'
                    AND diterima_pada IS NOT NULL
                    AND CHAR_LENGTH(kode_tracking) = 8
                    AND NOT REGEXP_LIKE(
                        kode_tracking,
                        _ascii'[^0-9]',
                        'c'
                    )
                )
            )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};