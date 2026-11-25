<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\WorkStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WireShoesDashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now(config('app.timezone'))->startOfMinute();

        DB::transaction(function () use ($now): void {
            $pelanggan = Customer::firstOrCreate(['kode_unik' => 'WSDASHDEMO01'], [
                'nama' => 'Pelanggan Demo WireShoes',
                'alamat' => 'Alamat contoh WireShoes',
            ]);

            $layanan = collect([
                ['Cuci', 25000, 3],
                ['Reparasi', 40000, 5],
                ['Cuci dan Reparasi', 65000, 7],
            ])->map(fn (array $data) => Service::firstOrCreate(['nama' => $data[0]], [
                'harga' => $data[1],
                'estimasi_hari' => $data[2],
                'aktif' => true,
            ]));

            $status = collect(['dipesan' => 1, 'diproses' => 2, 'selesai' => 3])
                ->map(fn (int $urutan, string $nama) => WorkStatus::firstOrCreate(
                    ['nama' => ucfirst($nama)],
                    ['urutan' => $urutan],
                ));

            $teknisi = $this->pekerja('Teknisi Demo WireShoes', 'teknisi');
            $kurir = $this->pekerja('Kurir Demo WireShoes', 'kurir');

            // Format setiap baris: status, sumber, indeks layanan, estimasi selesai, waktu aktual siap.
            $contoh = [
                '98000001' => ['dipesan', 'online', 0, null, null],
                '98000002' => ['diproses', 'toko', 1, $now->addHour(), null],
                '98000003' => ['diproses', 'online', 2, $now->subHours(2), null],
                '98000004' => ['selesai', 'online', 0, $now->subHours(4), $now->subHours(2)],
                '98000005' => ['selesai', 'online', 1, $now->subHours(10), $now->subHours(6)],
                '98000006' => ['selesai', 'online', 2, $now->subHours(20), $now->subHours(14)],
                '98000007' => ['selesai', 'online', 2, $now->subHours(4), $now->subHours(5)],
                '98000008' => ['selesai', 'online', 1, $now->subDays(4), $now->subDays(3)],
            ];

            $pengiriman = [
                '98000005' => [$now->subHours(3), $now->subHour()],
                '98000007' => [$now->subHour(), $now->subHours(2)],
            ];

            $pesanan = [];

            foreach ($contoh as $nomor => [$tahap, $sumber, $pilihan, $estimasi, $siap]) {
                $order = Order::where('nomor_pesanan', (string) $nomor)->first();

                if ($order) {
                    if ((int) $order->customer_id !== (int) $pelanggan->id
                        || $order->catatan !== 'Data demo dashboard WireShoes.') {
                        throw new RuntimeException("Nomor pesanan demo {$nomor} sudah dipakai.");
                    }

                    $pesanan[$nomor] = $order;

                    continue;
                }

                $masuk = $tahap === 'dipesan' ? $now->subMinutes(40) : $now->subDays(6);
                [$estimasiAntar, $aktualAntar] = $pengiriman[$nomor] ?? [null, null];

                $order = new Order;
                $order->forceFill([
                    'nomor_pesanan' => (string) $nomor,
                    'customer_id' => $pelanggan->id,
                    'nama_pemesan' => $pelanggan->nama,
                    'sumber' => $sumber,
                    'status' => $tahap,
                    'catatan' => 'Data demo dashboard WireShoes.',
                    'tanggal_masuk' => $masuk,
                    'diterima_pada' => $tahap === 'dipesan' ? null : $masuk->addHour(),
                    'teknisi_id' => $tahap === 'dipesan' ? null : $teknisi,
                    'kurir_id' => $aktualAntar ? $kurir : null,
                    'estimasi_antar' => $estimasiAntar,
                    'diantar_pada' => $aktualAntar,
                ])->save();

                $item = $order->items()->create([
                    'service_id' => $layanan[$pilihan]->id,
                    'harga' => $layanan[$pilihan]->harga,
                    'status_id' => $status[$tahap]->id,
                    'estimasi_selesai' => $estimasi,
                    'tanggal_selesai' => $siap,
                ]);

                $item->statusLogs()->create(['status_id' => $status[$tahap]->id]);
                $pesanan[$nomor] = $order;
            }

            $this->voucher($pesanan['98000005'], 5000, $now->subHours(5), $now->addDays(7));
            $this->voucher($pesanan['98000008'], 5000, $now->subDays(3), $now->subDay());

            $tujuan = $pesanan['98000007'];
            $nilai = min(5000, (int) $tujuan->total_harga);

            if ($nilai > 0) {
                $this->voucher(
                    $pesanan['98000006'],
                    $nilai,
                    $now->subHours(12),
                    $now->addDays(7),
                    tujuan: $tujuan,
                    dipakai: $now->subHours(4),
                );
            }

            $hariIni = $now->setTimezone('Asia/Makassar');

            foreach ([$teknisi, $kurir] as $pekerja) {
                foreach ([$hariIni, $hariIni->subDay()] as $hari) {
                    DB::table('staff_attendances')->insertOrIgnore([
                        'staff_member_id' => $pekerja,
                        'tanggal' => $hari->toDateString(),
                        'status' => 'hadir',
                        'jam_masuk' => '08:00:00',
                        'jam_pulang' => '16:00:00',
                        'catatan' => 'Data demo dashboard WireShoes.',
                    ]);
                }
            }
        });
    }

    private function pekerja(string $nama, string $jabatan): int
    {
        $tabel = DB::table('staff_members');
        $id = (clone $tabel)->whereNull('user_id')->where(compact('nama', 'jabatan'))->value('id');

        return $id ? (int) $id : (int) $tabel->insertGetId(compact('nama', 'jabatan'));
    }

    private function voucher(
        Order $asal,
        int $nilai,
        CarbonImmutable $terbit,
        CarbonImmutable $berlaku,
        ?Order $tujuan = null,
        ?CarbonImmutable $dipakai = null,
    ): void {
        DB::table('vouchers')->insertOrIgnore([
            'order_id' => $asal->id,
            'nilai' => $nilai,
            'terbit_pada' => $terbit,
            'berlaku_sampai' => $berlaku,
            'dipakai_di_order_id' => $tujuan?->id,
            'dipakai_pada' => $dipakai,
        ]);
    }
}
