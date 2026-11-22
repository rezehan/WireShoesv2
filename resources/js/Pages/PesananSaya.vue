<script setup>
import { Head, Link, usePoll } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';

defineProps({
    pesanan: { type: Array, required: true },
    prev: { type: String, default: null },
    next: { type: String, default: null },
});

usePoll(10000, {
    only: ['pesanan', 'prev', 'next'],
});

const statuses = {
    dipesan: ['Menunggu diterima kasir', 'bg-amber-50 text-amber-800'],
    diproses: ['Sedang diproses', 'bg-indigo-50 text-indigo-700'],
    selesai: ['Selesai', 'bg-emerald-50 text-emerald-800'],
    dibatalkan: ['Dibatalkan', 'bg-red-50 text-red-700'],
};

const methods = {
    antar: 'Diantar kurir',
    ambil: 'Ambil di toko',
};

const voucherStatuses = {
    terbit: ['Terbit', 'bg-emerald-100 text-emerald-800'],
    terpakai: ['Terpakai', 'bg-indigo-100 text-indigo-800'],
    kedaluwarsa: ['Kedaluwarsa', 'bg-slate-200 text-slate-700'],
};

const rupiah = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

function petunjuk(order) {
    if (order.status === 'dibatalkan') return null;
    if (order.diantar) return `Pesanan sudah diantar pada ${order.diantar} WITA.`;
    if (order.diambil) return `Pesanan sudah diambil pada ${order.diambil} WITA.`;

    if (order.status === 'dipesan') {
        return 'Estimasi selesai dan cara serah terima akan muncul setelah kasir menerima pesanan.';
    }

    if (order.status === 'selesai' && order.metode === 'ambil') {
        return 'Pesanan siap diambil di toko. Tunjukkan nomor pesanan saat mengambil.';
    }

    if (order.status === 'selesai' && order.metode === 'antar') {
        return 'Pesanan sudah selesai dan menunggu pengantaran oleh kurir.';
    }

    return null;
}
</script>

<template>
    <Head title="Pesanan Saya" />
    <Navbar />

    <main class="mx-auto max-w-4xl px-4 pb-16 pt-36 sm:px-6">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl font-bold text-red-800">Pesanan Saya</h1>
            <Link :href="route('services.index')"
                class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
                Pesan layanan
            </Link>
        </header>

        <p v-if="!pesanan.length"
            class="rounded-2xl border border-slate-200 bg-white p-5 text-slate-600">
            Belum ada pesanan di halaman ini.
        </p>

        <ul v-else class="space-y-4">
            <li v-for="order in pesanan" :key="order.nomor"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-slate-900">#{{ order.nomor }}</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ order.layanan || 'Layanan WireShoes' }}
                        </p>
                    </div>

                    <span class="rounded-full px-3 py-1 text-xs font-semibold"
                        :class="statuses[order.status][1]">
                        {{ statuses[order.status][0] }}
                    </span>
                </div>

                <dl class="mt-4 grid gap-4 border-t border-slate-100 pt-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-600">Tanggal pesan (WITA)</dt>
                        <dd class="font-semibold text-slate-900">{{ order.dipesan }}</dd>
                    </div>

                    <div v-if="order.diterima">
                        <dt class="text-slate-600">Diterima kasir (WITA)</dt>
                        <dd class="font-semibold text-slate-900">{{ order.diterima }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-600">Estimasi selesai (WITA)</dt>
                        <dd class="font-semibold text-slate-900">
                            {{ order.estimasi_selesai ?? 'Belum ditetapkan' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-600">Cara serah terima</dt>
                        <dd class="font-semibold text-slate-900">
                            {{ methods[order.metode] ?? 'Belum ditetapkan' }}
                        </dd>
                    </div>

                    <div v-if="order.metode === 'antar' && !order.diantar && order.status !== 'dibatalkan'">
                        <dt class="text-slate-600">Estimasi antar (WITA)</dt>
                        <dd class="font-semibold text-slate-900">
                            {{ order.estimasi_antar ?? 'Belum dijadwalkan' }}
                        </dd>
                    </div>
                </dl>

                <dl class="mt-4 space-y-2 rounded-xl bg-slate-50 px-4 py-3 text-sm">
                    <div v-if="order.potongan_voucher > 0" class="flex justify-between gap-3 text-slate-600">
                        <dt>Subtotal layanan</dt>
                        <dd>{{ rupiah.format(order.subtotal) }}</dd>
                    </div>

                    <div v-if="order.potongan_voucher > 0" class="flex justify-between gap-3 text-emerald-700">
                        <dt>Potongan voucher</dt>
                        <dd>{{ rupiah.format(-order.potongan_voucher) }}</dd>
                    </div>

                    <div class="flex justify-between gap-3 font-semibold text-slate-900">
                        <dt>{{ order.status === 'dipesan' ? 'Estimasi harga' : 'Total pesanan' }}</dt>
                        <dd>{{ rupiah.format(order.total_harga) }}</dd>
                    </div>
                </dl>

                <section v-if="order.voucher"
                    class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Voucher kompensasi</h3>
                            <p class="mt-1 text-lg font-bold text-slate-900">
                                {{ rupiah.format(order.voucher.nilai) }}
                            </p>
                        </div>

                        <span class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="voucherStatuses[order.voucher.status][1]">
                            {{ voucherStatuses[order.voucher.status][0] }}
                        </span>
                    </div>

                    <p class="mt-2 text-sm text-slate-600">
                        Berlaku sampai {{ order.voucher.berlaku_sampai }} WITA.
                    </p>

                    <p v-if="order.voucher.status === 'terbit'" class="mt-1 text-sm text-slate-600">
                        Sebutkan nomor pesanan ini kepada staf untuk memakai voucher pada pesanan lain.
                    </p>
                </section>

                <p v-if="petunjuk(order)" class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
                    {{ petunjuk(order) }}
                </p>
            </li>
        </ul>

        <nav v-if="prev || next" aria-label="Halaman pesanan"
            class="mt-5 flex justify-end gap-4 text-sm font-semibold text-red-700">
            <Link v-if="prev" :href="prev">Sebelumnya</Link>
            <Link v-if="next" :href="next">Berikutnya</Link>
        </nav>
    </main>
</template>