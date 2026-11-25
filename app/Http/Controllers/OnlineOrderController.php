<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\WorkStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OnlineOrderController extends Controller
{
    private const LAYANAN = Service::LAYANAN_BAWAAN;

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isPelanggan(), 403);

        $hargaTersimpan = Service::query()
            ->whereIn('nama', array_column(self::LAYANAN, 'nama'))
            ->pluck('harga', 'nama');

        return Inertia::render('LayananView', [
            'hargaLayanan' => collect(self::LAYANAN)->map(
                fn (array $pilihan) => max(
                    (float) $hargaTersimpan->get($pilihan['nama'], 0),
                    $pilihan['harga'],
                ),
            ),
            'customer' => [
                'name' => Str::limit($user->name, 100, ''),
                'address' => $user->alamat,
                'phone' => $user->customer?->no_telepon,
            ],
            'orderNumber' => $request->session()->get('order_number'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isPelanggan(), 403);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string', 'max:255'],
            'no_telepon' => ['required', 'string', 'max:20', 'regex:/^[+0-9][0-9 ()-]*$/'],
            'layanan' => ['required_without:service_id', 'nullable', 'string', Rule::in(array_keys(self::LAYANAN))],
            'service_id' => ['required_without:layanan', 'nullable', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = DB::transaction(function () use ($data, $user): Order {
            if (isset($data['layanan'])) {
                $pilihan = self::LAYANAN[$data['layanan']];

                $service = Service::query()->lockForUpdate()->firstOrCreate(
                    ['nama' => $pilihan['nama']],
                    [...$pilihan, 'aktif' => true],
                );
            } else {
                $service = Service::aktif()->lockForUpdate()->find($data['service_id']);
            }

            if (! $service?->aktif) {
                throw ValidationException::withMessages([
                    'service_id' => 'Layanan sudah tidak tersedia. Pilih layanan lain.',
                ]);
            }

            $statusId = WorkStatus::urut()->orderBy('id')->value('id');

            if ($statusId === null) {
                throw ValidationException::withMessages([
                    'service_id' => 'Pemesanan belum dibuka. Hubungi WireShoes.',
                ]);
            }

            $customer = $user->ensureCustomer();

            Customer::query()
                ->whereKey($customer->id)
                ->whereNull('no_telepon')
                ->update(['no_telepon' => $data['no_telepon']]);
            $order = Order::buatDenganNomorUnik($customer, [
                'sumber' => Order::SUMBER_ONLINE,
                'tanggal_masuk' => now(),
                'status' => Order::STATUS_DIPESAN,
                'nama_pemesan' => $data['nama'],
                'alamat_pemesan' => $data['alamat'],
                'no_telepon_pemesan' => $data['no_telepon'],
                'catatan' => $data['catatan'] ?? null,
                'dibuat_oleh' => $user->id,
            ]);

            $item = $order->items()->create([
                'service_id' => $service->id,
                'harga' => $service->hargaMinimum(),
                'status_id' => $statusId,
            ]);

            $item->statusLogs()->create([
                'status_id' => $statusId,
                'dicatat_oleh' => $user->id,
            ]);

            return $order;
        }, attempts: 3);

        return redirect()->route('services.index')->with('order_number', $order->nomor_pesanan);
    }
}
