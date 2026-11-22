<?php

namespace Database\Seeders;

use App\Models\WorkStatus;
use Illuminate\Database\Seeder;

class WireShoesOrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        if (WorkStatus::query()->doesntExist()) {
            WorkStatus::firstOrCreate(['nama' => 'Dipesan'], ['urutan' => 1]);
        }
    }
}
