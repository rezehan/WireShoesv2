<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('nama', 100);
            $table->string('jabatan', 15);
        });

        $this->ubahOrders(function (Blueprint $table) {
            $table->foreignId('teknisi_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->foreignId('kurir_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->dateTime('estimasi_antar')->nullable();
            $table->dateTime('diantar_pada')->nullable();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('dipakai_di_order_id')->nullable()->unique()->constrained('orders')->restrictOnDelete();
            $table->unsignedInteger('nilai');
            $table->dateTime('terbit_pada')->index();
            $table->dateTime('berlaku_sampai')->index();
            $table->dateTime('dipakai_pada')->nullable()->index();
        });

        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained()->restrictOnDelete();
            $table->date('tanggal')->index();
            $table->string('status', 12);
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->unique(['staff_member_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('vouchers');

        $this->ubahOrders(function (Blueprint $table) {
            $table->dropConstrainedForeignId('teknisi_id');
            $table->dropConstrainedForeignId('kurir_id');
            $table->dropColumn(['estimasi_antar', 'diantar_pada']);
        });

        Schema::dropIfExists('staff_members');
    }

    private function ubahOrders(Closure $changes): void
    {
        $triggers = DB::getDriverName() === 'sqlite'
            ? DB::table('sqlite_master')->where('type', 'trigger')->where('tbl_name', 'orders')->pluck('sql', 'name')
            : [];

        Schema::table('orders', $changes);

        foreach ($triggers as $name => $sql) {
            if (! DB::table('sqlite_master')->where('type', 'trigger')->where('name', $name)->exists()) {
                DB::unprepared($sql);
            }
        }
    }
};