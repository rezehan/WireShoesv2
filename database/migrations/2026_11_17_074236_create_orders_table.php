<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::create('orders', function (Blueprint $table) use ($sqlite) {
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
            $trackingCode = $table->char('kode_tracking', 8)->nullable()->unique('uq_orders_kode_tracking');

            if (! $sqlite) {
                $trackingCode->charset('ascii')->collation('ascii_bin');
            }

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

        if ($sqlite) {
            $this->addSqliteTrackingChecks();

            return;
        }

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

    private function addSqliteTrackingChecks(): void
    {
        foreach (['INSERT', 'UPDATE'] as $operation) {
            DB::statement(<<<SQL
                CREATE TRIGGER orders_check_tracking_{$operation}
                BEFORE {$operation} ON orders
                WHEN NEW.kode_tracking IS NOT NULL AND (
                    NEW.sumber <> 'online'
                    OR NEW.diterima_pada IS NULL
                    OR length(NEW.kode_tracking) <> 8
                    OR NEW.kode_tracking GLOB '*[^0-9]*'
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Kode tracking pesanan tidak valid');
                END
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
