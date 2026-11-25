<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StaffDashboardController extends Controller
{
    private const TZ = 'Asia/Makassar';

    private const BATAS_VOUCHER_MENIT = 60;

    private const MENU = [
        'ringkasan' => 'Ringkasan',
        'pesanan' => 'Pesanan',
        'voucher' => 'Voucher',
        'pelanggan' => 'Pelanggan',
        'layanan' => 'Layanan',
        'tim' => 'Tim',
        'laporan' => 'Laporan',
    ];

    private const TRANSISI = [
        'dipesan' => ['dipesan', 'diproses', 'dibatalkan'],
        'diproses' => ['diproses', 'selesai', 'dibatalkan'],
        'selesai' => ['selesai'],
        'dibatalkan' => ['dibatalkan'],
    ];

    private const LAYANAN_BAWAAN = ['Cuci', 'Reparasi', 'Cuci dan Reparasi'];

    private const ABSENSI = ['hadir', 'izin', 'sakit', 'alpa'];

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isStaf()) {
            return redirect('/');
        }

        if (! $user->hasVerifiedEmail()) {
            abort_if($request->expectsJson(), 403, 'Email belum terverifikasi.');

            return redirect()->guest(route('verification.notice'));
        }

        $menu = $user->isAdmin() ? self::MENU : array_slice(self::MENU, 0, 2, true);

        $data = $request->validate([
            'bagian' => ['sometimes', Rule::in(array_keys(self::MENU))],
            'tab' => ['sometimes', 'string', 'max:30'],
            'id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:30'],
            'sumber' => ['nullable', Rule::in(['online', 'toko'])],
            'mulai' => ['nullable', 'date_format:Y-m-d'],
            'akhir' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:mulai'],
        ]);

        $bagian = $request->route('bagian') ?? ($data['bagian'] ?? 'ringkasan');
        abort_unless(isset($menu[$bagian]), 403);

        if ($request->query->has('bagian')) {
            return redirect()->route($this->ruteBagian($bagian), $request->except('bagian'));
        }

        $isi = match ($bagian) {
            'ringkasan' => $this->ringkasan($user),
            'pesanan' => $this->pesanan($data, $user),
            'voucher' => $this->voucher(),
            'pelanggan' => $this->pelanggan($data),
            'layanan' => $this->layanan($data),
            'tim' => $this->tim($data),
            'laporan' => $this->laporan($data),
        };

        $page = $bagian === 'pesanan' ? 'PesananStaf' : 'Dashboard';

        return Inertia::render($page, [
            'menu' => $menu,
            'bagian' => $bagian,
            'judul' => $menu[$bagian],
            'pesan' => $request->session()->get('dashboard_pesan'),
            ...$isi,
        ]);
    }

    public function simpan(Request $request, string $aksi): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isStaf() && $user->hasVerifiedEmail(), 403);
        abort_unless(
            in_array($aksi, ['pesanan', 'pesanan-toko'], true) || $user->isAdmin(),
            403,
        );

        $tujuan = match ($aksi) {
            'pesanan' => $this->simpanPesanan($request),
            'pesanan-toko' => $this->simpanPesananToko($request),
            'terbit' => $this->terbitkanVoucher($request),
            'pakai' => $this->pakaiVoucher($request),
            'layanan' => $this->simpanLayanan($request),
            'pekerja' => $this->simpanPekerja($request),
            'akun' => $this->simpanAkun($request),
            'absensi' => $this->simpanAbsensi($request),
            default => abort(404),
        };

        $rute = $this->ruteBagian($tujuan['bagian']);
        unset($tujuan['bagian']);

        return redirect()->route($rute, $tujuan)
            ->with('dashboard_pesan', 'Data berhasil disimpan.');
    }

    private function ruteBagian(string $bagian): string
    {
        return $bagian === 'ringkasan' ? 'dashboard' : 'staff.'.$bagian;
    }

    private function orders(): Builder
    {
        $items = DB::table('order_items')->groupBy('order_id')
            ->selectRaw('order_id,
                CASE WHEN COUNT(estimasi_selesai) = COUNT(*) THEN MAX(estimasi_selesai) END AS estimasi,
                CASE WHEN COUNT(tanggal_selesai) = COUNT(*) THEN MAX(tanggal_selesai) END AS siap');

        return DB::table('orders as o')
            ->join('customers as c', 'c.id', '=', 'o.customer_id')
            ->leftJoinSub($items, 'i', 'i.order_id', '=', 'o.id')
            ->leftJoin('vouchers as v', 'v.order_id', '=', 'o.id')
            ->leftJoin('vouchers as potongan', 'potongan.dipakai_di_order_id', '=', 'o.id')
            ->select(
                'o.*',
                'c.nama as nama_pelanggan',
                'i.estimasi',
                'i.siap',
                'v.id as voucher_id',
                'v.nilai as nilai_voucher',
                'v.dipakai_pada as voucher_dipakai',
                'v.berlaku_sampai as voucher_berlaku',
                'potongan.nilai as potongan_voucher',
            );
    }

    private function pesananLayakVoucher(): Builder
    {
        $selisihDetik = DB::getDriverName() === 'sqlite'
            ? "strftime('%s', i.siap) - strftime('%s', i.estimasi)"
            : 'TIMESTAMPDIFF(SECOND, i.estimasi, i.siap)';

        return $this->orders()
            ->where('o.status', Order::STATUS_SELESAI)
            ->whereRaw("($selisihDetik) >= ?", [self::BATAS_VOUCHER_MENIT * 60]);
    }

    private function ringkasan(User $user): array
    {
        $hariIni = CarbonImmutable::today(self::TZ);
        $aktif = $this->orders()->whereIn('o.status', ['dipesan', 'diproses']);

        $perluDitangani = (clone $aktif)->where(fn (Builder $q) => $q
            ->where('o.status', 'dipesan')
            ->orWhere('i.estimasi', '<', $this->waktuSimpan($hariIni->addDay())));

        $terlambat = (clone $aktif)->where('i.estimasi', '<', now());

        $cards = [
            'Perlu ditangani hari ini' => $perluDitangani->count(),
            'Pesanan aktif lewat estimasi' => $terlambat->count(),
        ];

        if ($user->isAdmin()) {
            $cards['Voucher perlu diterbitkan'] = $this->pesananLayakVoucher()
                ->whereNull('v.id')
                ->count();
        }

        return [
            'cards' => $cards,
            ...$this->daftarPesanan($perluDitangani->orderBy('o.tanggal_masuk')->orderBy('o.id')->paginate(15)),
            'statistikPesanan' => $this->statistikPesanan($hariIni),
        ];
    }

    private function statistikPesanan(CarbonImmutable $hariIni): array
    {
        $awal = $hariIni->subDays(6);

        $query = DB::table('orders')
            ->select('sumber')
            ->where('tanggal_masuk', '>=', $this->waktuSimpan($awal))
            ->where('tanggal_masuk', '<', $this->waktuSimpan($hariIni->addDay()))
            ->groupBy('sumber');

        $tanggal = [];

        for ($i = 0; $i < 7; $i++) {
            $hari = $awal->addDays($i);
            $alias = 'hari_'.$i;
            $tanggal[$alias] = $hari->toDateString();

            $query->selectRaw(
                "SUM(CASE WHEN tanggal_masuk >= ? AND tanggal_masuk < ? THEN 1 ELSE 0 END) AS {$alias}",
                [$this->waktuSimpan($hari), $this->waktuSimpan($hari->addDay())],
            );
        }

        $jumlah = $query->get()->keyBy('sumber');
        $online = $jumlah->get(Order::SUMBER_ONLINE);
        $toko = $jumlah->get(Order::SUMBER_TOKO);
        $hasil = [];

        foreach ($tanggal as $alias => $tgl) {
            $hasil[] = [
                'tanggal' => $tgl,
                'online' => (int) ($online?->{$alias} ?? 0),
                'toko' => (int) ($toko?->{$alias} ?? 0),
            ];
        }

        return $hasil;
    }

    private function formPesananToko(): array
    {
        $layanan = Service::aktif()->orderBy('nama')->get()->mapWithKeys(
            fn (Service $service) => [
                $service->id => $service->nama.' · mulai '.$this->rupiah($service->hargaMinimum()),
            ],
        )->all();

        return $this->form('Buat pesanan toko', 'pesanan-toko', [
            $this->field('nama', 'Nama pelanggan', ''),
            $this->field('no_telepon', 'Nomor HP', '', 'tel'),
            $this->field('alamat', 'Alamat (opsional untuk ambil di toko)', ''),
            $this->field('service_id', 'Layanan', '', 'select', ['' => 'Pilih layanan'] + $layanan),
            $this->field('catatan', 'Catatan kondisi sepatu', ''),
        ]);
    }

    private function simpanPesananToko(Request $request): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'no_telepon' => ['required', 'string', 'max:20', 'regex:/^[+0-9][0-9 ()-]*$/'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'service_id' => ['required', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = DB::transaction(function () use ($data, $request): Order {
            $service = Service::aktif()->lockForUpdate()->find($data['service_id']);
            $this->pastikan($service !== null, 'service_id', 'Pilih layanan yang masih aktif.');

            $customer = Customer::query()->lockForUpdate()->firstOrCreate([
                'user_id' => null,
                'nama' => $data['nama'],
                'no_telepon' => $data['no_telepon'],
            ], [
                'kode_unik' => 'CST'.Str::upper(Str::random(9)),
                'alamat' => $data['alamat'] ?? null,
            ]);

            $order = Order::buatDenganNomorUnik($customer, [
                'sumber' => Order::SUMBER_TOKO,
                'status' => Order::STATUS_DIPESAN,
                'tanggal_masuk' => now(),
                'nama_pemesan' => $data['nama'],
                'no_telepon_pemesan' => $data['no_telepon'],
                'alamat_pemesan' => $data['alamat'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'dibuat_oleh' => $request->user()->id,
            ]);

            $status = WorkStatus::firstOrCreate(['nama' => 'Dipesan'], ['urutan' => 1]);

            $item = $order->items()->create([
                'service_id' => $service->id,
                'harga' => $service->hargaMinimum(),
                'status_id' => $status->id,
            ]);

            $item->statusLogs()->create([
                'status_id' => $status->id,
                'dicatat_oleh' => $request->user()->id,
            ]);

            return $order;
        }, attempts: 3);

        return ['bagian' => 'pesanan', 'id' => $order->id, 'tab' => 'hari_ini'];
    }

    private function pesanan(array $data, User $user): array
    {
        if (isset($data['id'])) {
            return $this->detailPesanan((int) $data['id'], $user, $data);
        }

        $tabs = ['hari_ini' => 'Hari Ini', 'riwayat' => 'Riwayat Pesanan'];
        $tab = ($data['tab'] ?? '') === 'riwayat' ? 'riwayat' : 'hari_ini';
        $hariIni = CarbonImmutable::today(self::TZ);
        $q = $data['q'] ?? '';
        $sumber = $data['sumber'] ?? '';

        $orders = $this->orders()
            ->when($q !== '', fn (Builder $rows) => $rows->where('o.nomor_pesanan', $q))
            ->when($sumber !== '', fn (Builder $rows) => $rows->where('o.sumber', $sumber))
            ->orderByDesc('o.tanggal_masuk')
            ->orderByDesc('o.id');

        $context = ['tab' => $tab, 'q' => $q, 'sumber' => $sumber];
        $filters = [
            $this->field('q', 'Nomor pesanan', $q),
            $this->field('sumber', 'Sumber', $sumber, 'select', [
                '' => 'Semua',
                'online' => 'Online',
                'toko' => 'Toko',
            ]),
        ];

        if ($tab === 'riwayat') {
            $kemarin = $hariIni->subDay()->toDateString();
            $akhir = $data['akhir'] ?? $kemarin;
            $mulai = $data['mulai'] ?? CarbonImmutable::parse($akhir, self::TZ)->subDays(29)->toDateString();

            $this->pastikan(
                $mulai <= $akhir && $akhir <= $kemarin,
                'akhir',
                'Untuk riwayat, pilih tanggal sampai yang sama atau sesudah tanggal mulai dan paling lambat kemarin.',
            );

            $orders->where(
                'o.tanggal_masuk',
                '>=',
                $this->waktuSimpan(CarbonImmutable::parse($mulai, self::TZ)),
            )->where(
                'o.tanggal_masuk',
                '<',
                $this->waktuSimpan(CarbonImmutable::parse($akhir, self::TZ)->addDay()),
            );

            $filters[] = $this->field('mulai', 'Mulai (WITA)', $mulai, 'date');
            $filters[] = $this->field('akhir', 'Sampai (WITA)', $akhir, 'date');
            $context += compact('mulai', 'akhir');
        } else {
            $orders->where('o.tanggal_masuk', '>=', $this->waktuSimpan($hariIni))
                ->where('o.tanggal_masuk', '<', $this->waktuSimpan($hariIni->addDay()));
        }

        return [
            'tabs' => $tabs,
            'tab' => $tab,
            'filters' => $filters,
            'forms' => $tab === 'hari_ini' ? [$this->formPesananToko()] : [],
            ...$this->daftarPesanan(
                $orders->paginate(15),
                array_filter($context, fn ($value) => $value !== ''),
            ),
        ];
    }

    private function daftarPesanan(LengthAwarePaginator $orders, array $context = []): array
    {
        return $this->tabel(
            ['Nomor pesanan', 'Pelanggan', 'Sumber', 'Status', 'Estimasi (WITA)', 'Terlambat'],
            $orders,
            fn ($o) => [
                'nilai' => [
                    $o->nomor_pesanan,
                    $o->nama_pemesan ?: $o->nama_pelanggan,
                    ucfirst($o->sumber),
                    ucfirst($o->status),
                    $this->waktu($o->estimasi),
                    $this->durasi($o),
                ],
                'url' => route('staff.pesanan', ['id' => $o->id, ...$context]),
            ],
        );
    }

    private function detailPesanan(int $id, User $user, array $data): array
    {
        $o = $this->orders()->where('o.id', $id)->first();
        abort_unless($o, 404);

        $pesanan = Order::with('items.service')->findOrFail($id);
        $metode = $pesanan->metodeSerahTerima();
        $baru = $pesanan->status === Order::STATUS_DIPESAN;
        $forms = [];

        if ($o->status !== 'dibatalkan' && ! $o->diantar_pada && ! $o->diambil_pada) {
            $states = self::TRANSISI[$o->status];

            $saranEstimasi = CarbonImmutable::now(self::TZ)
                ->addDays((int) ($pesanan->items->max('service.estimasi_hari') ?? 1))
                ->format('Y-m-d\TH:i');

            $forms[] = $this->form($baru ? 'Terima pesanan' : 'Perbarui pesanan', 'pesanan', [
                $this->field('id', '', $id, 'hidden'),
                $this->field(
                    'status',
                    'Status',
                    $baru ? 'diproses' : $o->status,
                    'select',
                    array_combine($states, array_map('ucfirst', $states)),
                ),
                ...($baru ? [
                    $this->field('estimasi_selesai', 'Estimasi selesai (WITA)', $saranEstimasi, 'datetime-local'),
                ] : []),
                ...($baru ? $pesanan->items->map(fn ($item) => $this->field(
                    'harga_item_'.$item->id,
                    'Harga final '.$item->service->nama.' (min. '.$this->rupiah($item->service->hargaMinimum()).')',
                    max((float) $item->harga, $item->service->hargaMinimum()),
                    'number',
                ))->all() : []),
                $this->field(
                    'metode_serah_terima',
                    'Cara serah terima',
                    $metode ?? ($baru && $o->sumber === Order::SUMBER_TOKO ? 'ambil' : null),
                    'select',
                    [
                        '' => 'Pilih cara serah terima',
                        'antar' => 'Diantar kurir',
                        'ambil' => 'Ambil di toko',
                    ],
                ),
                ...($o->status !== 'selesai' ? [
                    $this->field('teknisi_id', 'Teknisi', $o->teknisi_id, 'select', $this->pilihanPekerja('teknisi')),
                ] : []),
                $this->field('kurir_id', 'Kurir', $o->kurir_id, 'select', $this->pilihanPekerja('kurir')),
                $this->field(
                    'estimasi_antar',
                    'Estimasi antar (WITA)',
                    $o->estimasi_antar ? $this->lokal($o->estimasi_antar)->format('Y-m-d\TH:i') : null,
                    'datetime-local',
                ),
                $this->field('serah', 'Serah terima aktual', '', 'select', [
                    '' => 'Belum diserahterimakan',
                    'antar' => 'Sudah diantar',
                    'ambil' => 'Sudah diambil',
                ]),
            ]);
        }

        $voucher = $o->voucher_id
            ? $this->rupiah($o->nilai_voucher).' · '.$this->statusVoucher($o->voucher_dipakai, $o->voucher_berlaku)
            : '—';

        $tanggal = $this->lokal($o->tanggal_masuk)->toDateString();
        $hariIni = CarbonImmutable::today(self::TZ)->toDateString();
        $kembali = array_intersect_key($data, array_flip(['q', 'sumber']));

        if ($tanggal < $hariIni) {
            $kembali += [
                'tab' => 'riwayat',
                'mulai' => $data['mulai'] ?? $tanggal,
                'akhir' => $data['akhir'] ?? $tanggal,
            ];
        }

        return [
            'urlKembali' => route('staff.pesanan', $kembali),
            'detail' => [
                'Nomor pesanan' => $o->nomor_pesanan,
                'Pelanggan' => $o->nama_pemesan ?: $o->nama_pelanggan,
                'Layanan' => $pesanan->items->pluck('service.nama')->filter()->implode(', '),
                'Catatan' => $o->catatan,
                'Status' => ucfirst($o->status),
                'Diterima (WITA)' => $this->waktu($o->diterima_pada),
                'Estimasi selesai (WITA)' => $this->waktu($o->estimasi),
                'Aktual siap (WITA)' => $this->waktu($o->siap),
                'Durasi terlambat' => $this->durasi($o),
                'Cara serah terima' => match ($metode) {
                    'antar' => 'Diantar kurir',
                    'ambil' => 'Ambil di toko',
                    default => 'Belum ditetapkan',
                },
                'Estimasi antar (WITA)' => $this->waktu($o->estimasi_antar),
                'Aktual diantar (WITA)' => $this->waktu($o->diantar_pada),
                'Aktual diambil (WITA)' => $this->waktu($o->diambil_pada),
                'Voucher kompensasi' => $user->isAdmin() ? $voucher : ($o->voucher_id ? 'Sudah diterbitkan' : '—'),
                ($baru ? 'Estimasi harga' : 'Total setelah potongan voucher') => $this->rupiah(max(0, $pesanan->total_harga - ($o->potongan_voucher ?? 0))),
            ],
            'forms' => $forms,
        ];
    }

    private function simpanPesanan(Request $request): array
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'status' => ['required', Rule::in(array_keys(self::TRANSISI))],
            'teknisi_id' => ['nullable', 'integer', Rule::exists('staff_members', 'id')->where('jabatan', 'teknisi')],
            'kurir_id' => ['nullable', 'integer', Rule::exists('staff_members', 'id')->where('jabatan', 'kurir')],
            'estimasi_selesai' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'estimasi_antar' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'metode_serah_terima' => ['nullable', 'in:antar,ambil'],
            'serah' => ['nullable', 'in:antar,ambil'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $o = Order::query()->lockForUpdate()->findOrFail($data['id']);
            $status = $data['status'];
            $serah = $data['serah'] ?? null;
            $metode = $data['metode_serah_terima'] ?? $o->metodeSerahTerima();
            $sekarang = now();

            $this->pastikan(
                ! $o->diantar_pada && ! $o->diambil_pada && $o->status !== 'dibatalkan'
                    && in_array($status, self::TRANSISI[$o->status], true),
                'status',
                'Perubahan status pesanan ini tidak tersedia.',
            );

            $this->pastikan(
                ! $serah || $status === 'selesai',
                'serah',
                'Selesaikan pesanan sebelum serah terima.',
            );

            $estimasiSelesai = null;

            if ($o->status === 'dipesan' && $status === 'diproses') {
                $estimasiSelesai = isset($data['estimasi_selesai'])
                    ? CarbonImmutable::parse($data['estimasi_selesai'], self::TZ)
                    : null;

                $this->pastikan(
                    $estimasiSelesai && $estimasiSelesai->greaterThan($sekarang),
                    'estimasi_selesai',
                    'Isi estimasi selesai setelah waktu sekarang.',
                );

                $this->pastikan($metode !== null, 'metode_serah_terima', 'Pilih cara serah terima.');
            }

            $this->pastikan(
                ! $serah || ! $metode || $serah === $metode,
                'serah',
                'Serah terima aktual harus sesuai dengan cara serah terima yang dipilih.',
            );

            $metode ??= $serah;

            if ($o->status !== 'selesai') {
                $o->teknisi_id = $data['teknisi_id'] ?? null;
            }

            $o->metode_serah_terima = $metode;
            $o->kurir_id = $metode === 'ambil' ? null : ($data['kurir_id'] ?? null);
            $o->estimasi_antar = $metode !== 'ambil' && isset($data['estimasi_antar'])
                ? $this->waktuSimpan(CarbonImmutable::parse($data['estimasi_antar'], self::TZ))
                : null;

            $this->pastikan(
                $serah !== 'antar' || ($o->kurir_id && $o->estimasi_antar),
                'serah',
                'Pilih kurir dan isi estimasi antar.',
            );

            if ($status !== $o->status) {
                $items = $o->items()->with('service')->lockForUpdate()->get();

                $this->pastikan(
                    $status === 'dibatalkan' || $items->isNotEmpty(),
                    'status',
                    'Pesanan ini belum memiliki item.',
                );

                $statusKerja = WorkStatus::firstOrCreate(['nama' => ucfirst($status)], [
                    'urutan' => ['diproses' => 2, 'selesai' => 3, 'dibatalkan' => 4][$status],
                ]);

                if ($estimasiSelesai && ! $o->diterima_pada) {
                    $o->diterima_pada = $sekarang;
                    $o->diterima_oleh = $request->user()->id;
                }

                $hargaFinal = [];

                if ($estimasiSelesai) {
                    $aturan = [];
                    $pesan = [];

                    foreach ($items as $item) {
                        $field = 'harga_item_'.$item->id;
                        $minimum = $item->service->hargaMinimum();

                        $aturan[$field] = [
                            'required',
                            'numeric',
                            'decimal:0,2',
                            'min:'.number_format($minimum, 2, '.', ''),
                            'max:9999999999.99',
                        ];

                        $pesan[$field.'.required'] = 'Isi harga final.';
                        $pesan[$field.'.min'] = 'Harga final minimal '.$this->rupiah($minimum).'.';
                    }

                    $hargaFinal = $request->validate($aturan, $pesan);
                }

                foreach ($items as $item) {
                    if ($estimasiSelesai) {
                        $item->estimasi_selesai = $this->waktuSimpan($estimasiSelesai);
                        $item->harga = $hargaFinal['harga_item_'.$item->id];
                    }

                    if ($status === 'selesai' && ! $item->tanggal_selesai) {
                        $item->tanggal_selesai = $sekarang;
                    }

                    $item->status_id = $statusKerja->id;
                    $item->save();

                    $item->statusLogs()->create([
                        'status_id' => $statusKerja->id,
                        'dicatat_oleh' => $request->user()->id,
                    ]);
                }
            }

            if ($serah === 'antar') {
                $o->diantar_pada = $sekarang;
            }

            if ($serah === 'ambil') {
                $o->diambil_pada = $sekarang;
                $o->diambil_oleh = $request->user()->id;
            }

            $o->status = $status;
            $o->save();
        }, attempts: 3);

        return ['bagian' => 'pesanan', 'id' => $data['id']];
    }

    private function voucher(): array
    {
        $belum = $this->pesananLayakVoucher()
            ->whereNull('v.id')
            ->orderBy('o.id')
            ->paginate(10, ['*'], 'kompensasi');

        $terbit = DB::table('vouchers as v')
            ->join('orders as o', 'o.id', '=', 'v.order_id')
            ->select('v.*', 'o.nomor_pesanan')
            ->orderByDesc('v.id')
            ->paginate(15);

        return [
            ...$this->tabel(
                ['Nomor pesanan', 'Nilai', 'Berlaku sampai (WITA)', 'Status'],
                $terbit,
                fn ($v) => $this->row([
                    $v->nomor_pesanan,
                    $this->rupiah($v->nilai),
                    $this->waktu($v->berlaku_sampai),
                    $this->statusVoucher($v->dipakai_pada, $v->berlaku_sampai),
                ]),
            ),
            'tambahan' => $this->tabel(
                ['Perlu diterbitkan', 'Keterlambatan'],
                $belum,
                fn ($o) => $this->row([$o->nomor_pesanan, $this->durasi($o)], 'pesanan', $o->id),
            ),
            'forms' => [
                $this->form('Terbitkan voucher', 'terbit', [
                    $this->field('nomor', 'Nomor pesanan terlambat'),
                    $this->field('nilai', 'Nilai voucher (Rp)', null, 'number'),
                    $this->field('hari', 'Masa berlaku (hari)', null, 'number'),
                ]),
                $this->form('Gunakan voucher', 'pakai', [
                    $this->field('asal', 'Nomor pesanan asal voucher'),
                    $this->field('tujuan', 'Nomor pesanan penerima potongan'),
                ]),
            ],
        ];
    }

    private function terbitkanVoucher(Request $request): array
    {
        $data = $request->validate([
            'nomor' => 'required|string|max:30',
            'nilai' => 'required|integer|min:1|max:999999999',
            'hari' => 'required|integer|min:1|max:3650',
        ]);

        DB::transaction(function () use ($data): void {
            $order = Order::where('nomor_pesanan', $data['nomor'])->lockForUpdate()->first();
            $this->pastikan((bool) $order, 'nomor', 'Pesanan tidak ditemukan.');

            $o = $this->pesananLayakVoucher()->where('o.id', $order->id)->first();

            $this->pastikan(
                $o !== null,
                'nomor',
                'Pesanan harus selesai dengan keterlambatan minimal '.self::BATAS_VOUCHER_MENIT.' menit.',
            );

            $this->pastikan(! $o->voucher_id, 'nomor', 'Voucher pesanan ini sudah diterbitkan.');

            DB::table('vouchers')->insert([
                'order_id' => $order->id,
                'nilai' => $data['nilai'],
                'terbit_pada' => now(),
                'berlaku_sampai' => now()->addDays((int) $data['hari']),
            ]);
        }, attempts: 3);

        return ['bagian' => 'voucher'];
    }

    private function pakaiVoucher(Request $request): array
    {
        $data = $request->validate([
            'asal' => 'required|string|max:30',
            'tujuan' => 'required|string|max:30|different:asal',
        ]);

        DB::transaction(function () use ($data): void {
            $asal = Order::where('nomor_pesanan', $data['asal'])->first();

            $voucher = $asal
                ? DB::table('vouchers')->where('order_id', $asal->id)->lockForUpdate()->first()
                : null;

            $this->pastikan(
                (bool) $voucher && $this->statusVoucher($voucher->dipakai_pada, $voucher->berlaku_sampai) === 'Terbit',
                'asal',
                'Voucher tidak tersedia, sudah terpakai, atau kedaluwarsa.',
            );

            $tujuan = Order::where('nomor_pesanan', $data['tujuan'])
                ->where('customer_id', $asal->customer_id)
                ->whereKeyNot($asal->id)
                ->lockForUpdate()
                ->first();

            $this->pastikan(
                (bool) $tujuan && $tujuan->status === 'selesai' && ! $tujuan->diambil_pada && ! $tujuan->diantar_pada,
                'tujuan',
                'Pilih pesanan lain dari pelanggan yang sama, sudah selesai, dan belum diserahterimakan.',
            );

            $this->pastikan($tujuan->total_harga >= $voucher->nilai, 'tujuan', 'Total pesanan lebih kecil dari nilai voucher.');

            $this->pastikan(
                ! DB::table('vouchers')->where('dipakai_di_order_id', $tujuan->id)->lockForUpdate()->first(),
                'tujuan',
                'Pesanan tujuan sudah memakai voucher.',
            );

            DB::table('vouchers')->where('id', $voucher->id)->update([
                'dipakai_di_order_id' => $tujuan->id,
                'dipakai_pada' => now(),
            ]);
        }, attempts: 3);

        return ['bagian' => 'voucher'];
    }

    private function pelanggan(array $data): array
    {
        if (! isset($data['id'])) {
            $rows = Customer::withCount('orders')->orderBy('nama')->orderBy('id')->paginate(15);

            return $this->tabel(
                ['Nama', 'Nomor HP', 'Jumlah pesanan'],
                $rows,
                fn ($c) => $this->row([$c->nama, $c->no_telepon, $c->orders_count], 'pelanggan', $c->id),
            );
        }

        $c = Customer::with('user:id,email')->findOrFail($data['id']);

        $orders = $this->orders()->where('o.customer_id', $c->id)->orderByDesc('o.id')->paginate(15);

        $vouchers = DB::table('vouchers as v')
            ->join('orders as o', 'o.id', '=', 'v.order_id')
            ->where('o.customer_id', $c->id)
            ->orderByDesc('v.id')
            ->paginate(10, ['v.*', 'o.nomor_pesanan'], 'voucher_page');

        return [
            'detail' => [
                'Nama' => $c->nama,
                'Email' => $c->user?->email,
                'Nomor HP' => $c->no_telepon,
                'Alamat' => $c->alamat,
            ],
            ...$this->daftarPesanan($orders),
            'tambahan' => $this->tabel(
                ['Voucher pesanan', 'Nilai', 'Status'],
                $vouchers,
                fn ($v) => $this->row([
                    $v->nomor_pesanan,
                    $this->rupiah($v->nilai),
                    $this->statusVoucher($v->dipakai_pada, $v->berlaku_sampai),
                ]),
            ),
        ];
    }

    private function layanan(array $data): array
    {
        $s = isset($data['id']) ? Service::findOrFail($data['id']) : null;

        return [
            ...$this->tabel(
                ['Layanan', 'Tarif', 'Estimasi', 'Ketersediaan'],
                Service::orderBy('nama')->paginate(15),
                fn ($row) => $this->row([
                    $row->nama,
                    $this->rupiah($row->harga),
                    $row->estimasi_hari.' hari',
                    $row->aktif ? 'Aktif' : 'Nonaktif',
                ], 'layanan', $row->id),
            ),
            'forms' => [
                $this->form($s ? 'Edit layanan' : 'Tambah layanan', 'layanan', [
                    $this->field('id', '', $s?->id, 'hidden'),
                    $this->field('nama', 'Nama layanan', $s?->nama),
                    $this->field('harga', 'Tarif (Rp)', $s ? (float) $s->harga : null, 'number'),
                    $this->field('estimasi_hari', 'Estimasi (hari)', $s?->estimasi_hari, 'number'),
                    $this->field('aktif', 'Ketersediaan', $s && ! $s->aktif ? '0' : '1', 'select', [
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ]),
                ]),
            ],
        ];
    }

    private function simpanLayanan(Request $request): array
    {
        $id = $request->validate(['id' => 'nullable|integer'])['id'] ?? null;
        $s = $id ? Service::findOrFail($id) : new Service;

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('services')->ignore($s->id)],
            'harga' => 'required|numeric|min:0|max:9999999999.99|decimal:0,2',
            'estimasi_hari' => 'required|integer|min:1|max:255',
            'aktif' => 'required|boolean',
        ]);

        $this->pastikan(
            ! in_array($s->nama, self::LAYANAN_BAWAAN, true) || $data['nama'] === $s->nama,
            'nama',
            'Nama layanan ini digunakan di pilihan layanan pelanggan.',
        );

        $minimum = Service::tarifDasar($data['nama']);

        $this->pastikan(
            (float) $data['harga'] >= $minimum,
            'harga',
            'Tarif awal minimal '.$this->rupiah($minimum).'.',
        );

        $s->fill($data)->save();

        return ['bagian' => 'layanan', 'id' => $s->id];
    }

    private function tim(array $data): array
    {
        $tabs = ['akun' => 'Akun Staf', 'absensi' => 'Absensi'];
        $tab = ($data['tab'] ?? '') === 'absensi' ? 'absensi' : 'akun';
        $team = DB::table('staff_members');

        if ($tab === 'absensi') {
            $a = isset($data['id']) ? DB::table('staff_attendances')->find($data['id']) : null;

            $rows = DB::table('staff_attendances as a')
                ->join('staff_members as s', 's.id', '=', 'a.staff_member_id')
                ->orderByDesc('a.tanggal')
                ->orderBy('s.nama')
                ->orderByDesc('a.id')
                ->paginate(15, ['a.*', 's.nama']);

            return [
                'tabs' => $tabs,
                'tab' => $tab,
                ...$this->tabel(
                    ['Tanggal', 'Pekerja', 'Status', 'Masuk', 'Pulang', 'Catatan'],
                    $rows,
                    fn ($r) => $this->row([
                        $r->tanggal,
                        $r->nama,
                        ucfirst($r->status),
                        $r->jam_masuk,
                        $r->jam_pulang,
                        $r->catatan,
                    ], 'tim', $r->id, 'absensi'),
                ),
                'forms' => [
                    $this->form($a ? 'Koreksi absensi' : 'Catat absensi', 'absensi', [
                        $this->field('id', '', $a?->id, 'hidden'),
                        [
                            ...$this->field(
                                'staff_member_id',
                                'Pekerja',
                                $a?->staff_member_id,
                                'select',
                                ['' => 'Pilih pekerja'] + $team->orderBy('nama')->pluck('nama', 'id')->all(),
                            ),
                            'locked' => (bool) $a,
                        ],
                        [
                            ...$this->field(
                                'tanggal',
                                'Tanggal (WITA)',
                                $a?->tanggal ?? CarbonImmutable::today(self::TZ)->toDateString(),
                                'date',
                            ),
                            'locked' => (bool) $a,
                        ],
                        $this->field(
                            'status',
                            'Status',
                            $a?->status ?? 'hadir',
                            'select',
                            array_combine(self::ABSENSI, array_map('ucfirst', self::ABSENSI)),
                        ),
                        $this->field('jam_masuk', 'Jam masuk', substr($a?->jam_masuk ?? '', 0, 5), 'time'),
                        $this->field('jam_pulang', 'Jam pulang', substr($a?->jam_pulang ?? '', 0, 5), 'time'),
                        $this->field('catatan', 'Catatan', $a?->catatan),
                    ]),
                ],
            ];
        }

        $pekerja = isset($data['id'])
            ? (clone $team)->whereIn('jabatan', ['teknisi', 'kurir'])->find($data['id'])
            : null;

        $akun = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_KASIR])
            ->whereNotNull('firebase_uid')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return [
            'tabs' => $tabs,
            'tab' => $tab,
            ...$this->tabel(
                ['Pekerja', 'Jabatan'],
                $team->orderBy('nama')->orderBy('id')->paginate(15),
                fn ($s) => $this->row([$s->nama, ucfirst($s->jabatan)], $s->user_id ? null : 'tim', $s->id, 'akun'),
            ),
            'tambahan' => [
                'kolom' => ['Akun Firebase', 'Email', 'Hak akses'],
                'baris' => $akun->map(fn ($u) => $this->row([$u->name, $u->email, ucfirst($u->role)])),
            ],
            'forms' => [
                $this->form($pekerja ? 'Edit nama pekerja' : 'Tambah teknisi atau kurir', 'pekerja', [
                    $this->field('id', '', $pekerja?->id, 'hidden'),
                    $this->field('nama', 'Nama pekerja', $pekerja?->nama),
                    [
                        ...$this->field('jabatan', 'Jabatan', $pekerja?->jabatan ?? 'teknisi', 'select', [
                            'teknisi' => 'Teknisi',
                            'kurir' => 'Kurir',
                        ]),
                        'locked' => (bool) $pekerja,
                    ],
                ]),
                $this->form('Atur akses akun staf dan daftarkan absensi', 'akun', [
                    $this->field(
                        'user_id',
                        'Akun staf',
                        '',
                        'select',
                        ['' => 'Pilih akun'] + $akun->pluck('email', 'id')->all(),
                    ),
                    $this->field('role', 'Hak akses', '', 'select', [
                        '' => 'Pilih hak akses',
                        'admin' => 'Admin',
                        'kasir' => 'Kasir',
                    ]),
                ]),
            ],
        ];
    }

    private function simpanPekerja(Request $request): array
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'nama' => 'required|string|max:100',
            'jabatan' => 'required|in:teknisi,kurir',
        ]);

        $team = DB::table('staff_members');

        if (isset($data['id'])) {
            $pekerja = $team->whereIn('jabatan', ['teknisi', 'kurir'])->find($data['id']);
            abort_unless($pekerja, 404);

            DB::table('staff_members')->where('id', $pekerja->id)->update(['nama' => $data['nama']]);
        } else {
            $team->insert(['nama' => $data['nama'], 'jabatan' => $data['jabatan']]);
        }

        return ['bagian' => 'tim', 'tab' => 'akun'];
    }

    private function simpanAkun(Request $request): array
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'role' => 'required|in:admin,kasir',
        ]);

        DB::transaction(function () use ($data, $request): void {
            $akun = User::query()
                ->whereIn('id', [$request->user()->id, $data['user_id']])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            abort_unless($akun->get($request->user()->id)?->isAdmin(), 403);
            $u = $akun->get($data['user_id']);
            abort_unless($u, 404);
            abort_unless($u->isStaf() && $u->firebase_uid, 403);

            $this->pastikan(
                ! $u->is($request->user()) || $data['role'] === $u->role,
                'role',
                'Hak akses akun yang sedang dipakai tidak bisa diubah.',
            );

            $u->update(['role' => $data['role']]);

            DB::table('staff_members')->updateOrInsert(['user_id' => $u->id], [
                'nama' => Str::limit($u->name, 100, ''),
                'jabatan' => $data['role'],
            ]);
        }, attempts: 3);

        return ['bagian' => 'tim', 'tab' => 'akun'];
    }

    private function simpanAbsensi(Request $request): array
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'staff_member_id' => 'required|integer|exists:staff_members,id',
            'tanggal' => 'required|date_format:Y-m-d',
            'status' => ['required', Rule::in(self::ABSENSI)],
            'jam_masuk' => ['required_if:status,hadir', 'nullable', 'date_format:H:i'],
            'jam_pulang' => ['nullable', 'date_format:H:i'],
            'catatan' => 'nullable|string|max:255',
        ]);

        $masuk = $data['jam_masuk'] ?? null;
        $pulang = $data['jam_pulang'] ?? null;

        $this->pastikan(
            $data['status'] !== 'hadir' || ! $pulang || $pulang >= $masuk,
            'jam_pulang',
            'Jam pulang harus sama atau sesudah jam masuk pada tanggal yang sama.',
        );

        if (isset($data['id'])) {
            $a = DB::table('staff_attendances')->find($data['id']);
            abort_unless($a, 404);

            $this->pastikan(
                (int) $a->staff_member_id === (int) $data['staff_member_id'] && $a->tanggal === $data['tanggal'],
                'tanggal',
                'Pekerja dan tanggal pada catatan ini bersifat tetap.',
            );
        }

        DB::table('staff_attendances')->upsert([[
            'staff_member_id' => $data['staff_member_id'],
            'tanggal' => $data['tanggal'],
            'status' => $data['status'],
            'jam_masuk' => $data['status'] === 'hadir' ? $masuk : null,
            'jam_pulang' => $data['status'] === 'hadir' ? $pulang : null,
            'catatan' => $data['catatan'] ?? null,
        ]], ['staff_member_id', 'tanggal'], ['status', 'jam_masuk', 'jam_pulang', 'catatan']);

        return ['bagian' => 'tim', 'tab' => 'absensi'];
    }

    private function laporan(array $data): array
    {
        $tabs = [
            'pesanan' => 'Pesanan',
            'voucher' => 'Voucher',
            'teknisi' => 'Kinerja Teknisi',
            'kurir' => 'Kinerja Kurir',
            'absensi' => 'Absensi',
        ];
        $tab = isset($tabs[$data['tab'] ?? '']) ? $data['tab'] : 'pesanan';

        $hari = CarbonImmutable::today(self::TZ);
        $mulai = $data['mulai'] ?? $hari->startOfMonth()->toDateString();
        $akhir = $data['akhir'] ?? $hari->toDateString();

        $this->pastikan($mulai <= $akhir, 'akhir', 'Tanggal akhir harus sama atau sesudah tanggal mulai.');

        $from = $this->waktuSimpan(CarbonImmutable::parse($mulai, self::TZ));
        $until = $this->waktuSimpan(CarbonImmutable::parse($akhir, self::TZ)->addDay());
        $periode = fn (Builder $q, string $kolom) => $q->where($kolom, '>=', $from)->where($kolom, '<', $until);

        $cards = [];
        $tabel = [];

        if ($tab === 'pesanan') {
            $q = $periode($this->orders(), 'o.tanggal_masuk');

            $cards = [
                'Pesanan masuk' => (clone $q)->count(),
                'Selesai tepat waktu' => (clone $q)->where('o.status', 'selesai')->whereColumn('i.siap', '<=', 'i.estimasi')->count(),
                'Selesai terlambat' => (clone $q)->where('o.status', 'selesai')->whereColumn('i.siap', '>', 'i.estimasi')->count(),
            ];

            $tabel = $this->daftarPesanan($q->orderByDesc('o.tanggal_masuk')->orderByDesc('o.id')->paginate(15));
        }

        if ($tab === 'voucher') {
            $vouchers = DB::table('vouchers');
            $terbit = $periode(clone $vouchers, 'terbit_pada');
            $pakai = $periode(clone $vouchers, 'dipakai_pada');

            $kedaluwarsa = $periode(clone $vouchers, 'berlaku_sampai')
                ->whereNull('dipakai_pada')->where('berlaku_sampai', '<=', now());

            $cards = [
                'Voucher diterbitkan' => $terbit->count(),
                'Nilai diterbitkan' => $this->rupiah($terbit->sum('nilai')),
                'Voucher dipakai' => $pakai->count(),
                'Nilai dipakai' => $this->rupiah($pakai->sum('nilai')),
                'Voucher kedaluwarsa tanpa dipakai' => $kedaluwarsa->count(),
            ];
        }

        if (in_array($tab, ['teknisi', 'kurir'], true)) {
            $teknisi = $tab === 'teknisi';
            $aktual = $teknisi ? 'i.siap' : 'o.diantar_pada';
            $estimasi = $teknisi ? 'i.estimasi' : 'o.estimasi_antar';

            $q = $periode($this->orders(), $aktual)
                ->where('o.status', 'selesai')
                ->join('staff_members as s', 's.id', '=', $teknisi ? 'o.teknisi_id' : 'o.kurir_id')
                ->select('s.nama')
                ->selectRaw("COUNT(*) as total,
                    SUM(CASE WHEN {$aktual} <= {$estimasi} THEN 1 ELSE 0 END) as tepat,
                    SUM(CASE WHEN {$aktual} > {$estimasi} THEN 1 ELSE 0 END) as telat")
                ->groupBy('s.id', 's.nama')
                ->orderBy('s.nama')
                ->orderBy('s.id');

            $tabel = $this->tabel(
                [ucfirst($tab), 'Selesai', 'Tepat waktu', 'Terlambat', 'Tanpa estimasi'],
                $q->paginate(15),
                fn ($s) => $this->row([$s->nama, $s->total, $s->tepat, $s->telat, $s->total - $s->tepat - $s->telat]),
            );
        }

        if ($tab === 'absensi') {
            $q = DB::table('staff_attendances as a')
                ->join('staff_members as s', 's.id', '=', 'a.staff_member_id')
                ->whereBetween('a.tanggal', [$mulai, $akhir])
                ->select('s.nama', 's.jabatan')
                ->selectRaw("SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN a.status = 'alpa' THEN 1 ELSE 0 END) as alpa")
                ->groupBy('s.id', 's.nama', 's.jabatan')
                ->orderBy('s.nama')
                ->orderBy('s.id');

            $tabel = $this->tabel(
                ['Pekerja', 'Jabatan', 'Hadir', 'Izin', 'Sakit', 'Alpa'],
                $q->paginate(15),
                fn ($s) => $this->row([$s->nama, ucfirst($s->jabatan), $s->hadir, $s->izin, $s->sakit, $s->alpa]),
            );
        }

        return [
            'tabs' => $tabs,
            'tab' => $tab,
            'cards' => $cards,
            ...$tabel,
            'filters' => [
                $this->field('mulai', 'Mulai (WITA)', $mulai, 'date'),
                $this->field('akhir', 'Sampai (WITA)', $akhir, 'date'),
            ],
        ];
    }

    private function tabel(array $kolom, LengthAwarePaginator $p, callable $map): array
    {
        $p->withQueryString()->through($map);

        return [
            'kolom' => $kolom,
            'baris' => $p->items(),
            'prev' => $p->previousPageUrl(),
            'next' => $p->nextPageUrl(),
        ];
    }

    private function row(array $nilai, ?string $bagian = null, ?int $id = null, ?string $tab = null): array
    {
        return [
            'nilai' => $nilai,
            'url' => $bagian ? route($this->ruteBagian($bagian), array_filter(compact('id', 'tab'))) : null,
        ];
    }

    private function field(string $name, string $label, mixed $value = null, string $type = 'text', array $options = []): array
    {
        return compact('name', 'label', 'value', 'type', 'options');
    }

    private function form(string $judul, string $aksi, array $fields): array
    {
        return ['judul' => $judul, 'url' => route('dashboard.simpan', ['aksi' => $aksi]), 'fields' => $fields];
    }

    private function pilihanPekerja(string $jabatan): array
    {
        return ['' => 'Belum ditugaskan'] + DB::table('staff_members')
            ->where('jabatan', $jabatan)->orderBy('nama')->pluck('nama', 'id')->all();
    }

    private function statusVoucher($dipakai, string $berlaku): string
    {
        return $dipakai ? 'Terpakai' : ($this->lokal($berlaku)->lessThanOrEqualTo(now()) ? 'Kedaluwarsa' : 'Terbit');
    }

    private function durasi(object $o): string
    {
        if (! $o->estimasi || $o->status === 'dibatalkan' || ($o->status === 'selesai' && ! $o->siap)) {
            return '—';
        }

        $batas = $this->lokal($o->estimasi);
        $akhir = $o->siap ? $this->lokal($o->siap) : now();
        $detik = max(0, $akhir->getTimestamp() - $batas->getTimestamp());

        return $detik === 0 ? '0 menit' : ceil($detik / 60).' menit';
    }

    private function lokal(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, config('app.timezone'))->setTimezone(self::TZ);
    }

    private function waktu(?string $value): string
    {
        return $value ? $this->lokal($value)->format('d/m/Y H:i') : '—';
    }

    private function waktuSimpan(CarbonImmutable $value): string
    {
        return $value->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    private function rupiah($nilai): string
    {
        return 'Rp '.number_format((float) $nilai, 2, ',', '.');
    }

    private function pastikan(bool $ok, string $field, string $pesan): void
    {
        if (! $ok) {
            throw ValidationException::withMessages([$field => $pesan]);
        }
    }
}
