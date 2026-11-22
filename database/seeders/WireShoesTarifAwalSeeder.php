<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WireShoesTarifAwalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (Service::LAYANAN_BAWAAN as $pilihan) {
                $layanan = Service::firstOrCreate(['nama' => $pilihan['nama']], [
                    'estimasi_hari' => $pilihan['estimasi_hari'],
                    'harga' => $pilihan['harga'],
                    'aktif' => true,
                ]);

                $layanan->update(['harga' => $pilihan['harga']]);
            }
        });
    }
}