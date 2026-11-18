<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 20)
                ->nullable()
                ->unique('uq_users_username');

            $table->text('alamat')->nullable();

            $table->enum('role', [
                'admin',
                'kasir',
                'pelanggan',
            ])->default('pelanggan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('uq_users_username');
            $table->dropColumn(['username', 'alamat', 'role']);
        });
    }
};