<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isPelanggan(), 403);

        $orders = Order::query()
            ->leftJoin('vouchers as potongan', 'potongan.dipakai_di_order_id', '=', 'orders.id')
            ->select('orders.*', 'potongan.nilai as potongan_voucher')
            ->whereHas('customer', fn ($query) => $query->where('user_id', $user->id))
            ->with('items.service:id,nama')
            ->orderByDesc('orders.tanggal_masuk')
            ->orderByDesc('orders.id')
            ->simplePaginate(10);

        $orders->through(function (Order $order): array {
            $items = $order->items;
            $estimasi = $items->every(fn ($item) => $item->estimasi_selesai !== null)
                ? $items->max('estimasi_selesai')
                : null;

            $metode = $order->metodeSerahTerima();
            $subtotal = (float) $order->total_harga;
            $potongan = (int) ($order->potongan_voucher ?? 0);

            return [
                'nomor' => $order->nomor_pesanan,
                'layanan' => $items->pluck('service.nama')->filter()->join(', '),
                'status' => $order->status,
                'subtotal' => $subtotal,
                'potongan_voucher' => $potongan,
                'total_harga' => max(0, $subtotal - $potongan),
                'dipesan' => $this->waktu($order->tanggal_masuk),
                'diterima' => $this->waktu($order->diterima_pada),
                'estimasi_selesai' => $this->waktu($estimasi),
                'metode' => $metode,
                'estimasi_antar' => $metode === 'antar' ? $this->waktu($order->estimasi_antar) : null,
                'diantar' => $this->waktu($order->diantar_pada),
                'diambil' => $this->waktu($order->diambil_pada),
            ];
        });

        return Inertia::render('PesananSaya', [
            'pesanan' => $orders->items(),
            'prev' => $orders->previousPageUrl(),
            'next' => $orders->nextPageUrl(),
        ]);
    }

    private function waktu(DateTimeInterface|string|null $value): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value, config('app.timezone'))
            ->setTimezone('Asia/Makassar')
            ->format('d/m/Y H:i');
    }
}