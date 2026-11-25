<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->string('kode_unik', 12)
                ->unique('uq_customers_kode_unik');

            $table->string('nama', 100);
            $table->string('no_telepon', 20)->nullable();
            $table->text('alamat')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->unique('uq_customers_user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->index('nama', 'idx_customers_nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
