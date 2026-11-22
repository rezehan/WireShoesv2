<script setup>
import { computed, defineAsyncComponent } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Components/layout/AdminLayout.vue';
import DashboardForm from '@/Components/layout/DashboardForm.vue';

const StatisticsChart = defineAsyncComponent(() =>
    import('@/Components/main/dashboard/ecommerce/statistics-chart/StatisticsChart.vue'),
);

const props = defineProps({
    menu: { type: Object, required: true },
    bagian: { type: String, required: true },
    judul: { type: String, required: true },
    pesan: { type: String, default: null },
    tabs: { type: Object, default: null },
    tab: { type: String, default: null },
    detail: { type: Object, default: null },
    urlKembali: { type: String, default: null },
    cards: { type: Object, default: null },
    kolom: { type: Array, default: () => [] },
    baris: { type: Array, default: () => [] },
    prev: { type: String, default: null },
    next: { type: String, default: null },
    tambahan: { type: Object, default: null },
    forms: { type: Array, default: () => [] },
    filters: { type: Array, default: () => [] },
    statistikPesanan: { type: Array, default: () => [] },
});

const statusColors = {
    Dipesan: 'bg-indigo-50 text-indigo-700',
    Diproses: 'bg-amber-50 text-amber-800',
    Selesai: 'bg-emerald-50 text-emerald-800',
    Dibatalkan: 'bg-red-50 text-red-700',
    Terbit: 'bg-emerald-50 text-emerald-800',
    Terpakai: 'bg-indigo-50 text-indigo-700',
    Kedaluwarsa: 'bg-slate-100 text-slate-700',
    Aktif: 'bg-emerald-50 text-emerald-800',
    Nonaktif: 'bg-slate-100 text-slate-700',
    Hadir: 'bg-emerald-50 text-emerald-800',
    Izin: 'bg-indigo-50 text-indigo-700',
    Sakit: 'bg-amber-50 text-amber-800',
    Alpa: 'bg-red-50 text-red-700',
};

const tables = computed(() => {
    const primaryTitle = {
        ringkasan: 'Pesanan yang perlu ditangani',
        pelanggan: props.detail ? 'Riwayat pesanan' : 'Daftar pelanggan',
        tim: props.tab === 'absensi' ? 'Riwayat absensi' : 'Daftar pekerja',
    }[props.bagian] ?? (props.tabs?.[props.tab] || `Daftar ${props.judul.toLowerCase()}`);

    const secondaryTitle = {
        voucher: 'Pesanan yang menunggu voucher',
        pelanggan: 'Riwayat voucher',
        tim: 'Akun staf',
    }[props.bagian];

    return [
        ...(props.kolom.length ? [{
            title: primaryTitle,
            kolom: props.kolom,
            baris: props.baris,
            prev: props.prev,
            next: props.next,
        }] : []),
        ...(props.tambahan ? [{ title: secondaryTitle, ...props.tambahan }] : []),
    ].map((table) => ({ ...table, hasActions: table.baris.some((row) => row.url) }));
});

const filterFields = computed(() => [
    { name: 'bagian', type: 'hidden', value: props.bagian },
    ...(props.tab ? [{ name: 'tab', type: 'hidden', value: props.tab }] : []),
    ...props.filters,
]);

const summary = computed(() => {
    if (props.bagian !== 'ringkasan' || !props.cards) return null;

    const total = Number(props.cards['Perlu ditangani hari ini'] ?? 0);
    const late = Number(props.cards['Pesanan aktif lewat estimasi'] ?? 0);

    return { total, late, percent: total ? late / total : 0 };
});

const percent = new Intl.NumberFormat('id-ID', {
    style: 'percent',
    maximumFractionDigits: 1,
});

function tabUrl(tab) {
    const fields = props.bagian === 'pesanan' ? ['q', 'sumber'] : ['mulai', 'akhir'];

    if (props.bagian === 'pesanan' && tab === 'riwayat' && props.tab === 'riwayat') {
        fields.push('mulai', 'akhir');
    }

    const filters = Object.fromEntries(props.filters
        .filter(({ name }) => fields.includes(name))
        .map(({ name, value }) => [name, value]));

    return route('dashboard', { bagian: props.bagian, tab, ...filters });
}

function statusClass(label, value) {
    return ['Status', 'Ketersediaan'].includes(label)
        ? statusColors[value] || 'bg-slate-100 text-slate-700'
        : null;
}
</script>

<template>
    <Head :title="judul" />

    <AdminLayout :menu="menu" :bagian="bagian">
        <div class="space-y-5">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-[28px]">
                {{ judul }}
            </h1>

            <p v-if="pesan" role="status"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                {{ pesan }}
            </p>

            <nav v-if="tabs" aria-label="Bagian halaman"
                class="flex flex-wrap gap-1 rounded-xl border border-slate-200 bg-white p-1.5">
                <Link v-for="(label, key) in tabs" :key="key" :href="tabUrl(key)"
                    :aria-current="tab === key ? 'page' : undefined"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors"
                    :class="tab === key ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100'">
                    {{ label }}
                </Link>
            </nav>

            <section v-if="filters.length" class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 font-semibold">Filter {{ judul.toLowerCase() }}</h2>

                <DashboardForm
                    :key="JSON.stringify(filterFields)"
                    :fields="filterFields"
                    :url="route('dashboard')"
                    method="get"
                    label="Terapkan filter"
                />

                <Link v-if="bagian === 'pesanan'" :href="route('dashboard', { bagian, tab })"
                    class="mt-3 inline-block text-sm font-semibold text-indigo-700 hover:underline">
                    Reset filter
                </Link>
            </section>

            <div v-if="cards && Object.keys(cards).length" class="grid gap-4"
                :class="summary ? 'xl:grid-cols-[minmax(0,1fr)_17rem]' : ''">
                <dl class="grid auto-rows-fr gap-4 sm:grid-cols-2"
                    :class="Object.keys(cards).length > 2 ? 'xl:grid-cols-3' : ''">
                    <div v-for="(value, label) in cards" :key="label"
                        class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-5">
                        <dt class="text-sm text-slate-600">
                            <span class="mb-4 grid size-10 place-items-center rounded-xl bg-slate-100 text-slate-700"
                                aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                                    stroke-linecap="round" stroke-linejoin="round" class="size-5">
                                    <path v-if="label.includes('estimasi')"
                                        d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                    <path v-else-if="label.toLowerCase().includes('voucher')"
                                        d="M3 6h18v4a2 2 0 0 0 0 4v4H3v-4a2 2 0 0 0 0-4zM14 6v2m0 3v2m0 3v2" />
                                    <path v-else d="M5 4h14v16H5zM9 9h6m-6 3h6m-6 3h4" />
                                </svg>
                            </span>
                            {{ label }}
                        </dt>

                        <dd class="mt-auto pt-1 text-[26px] font-bold leading-tight tracking-tight tabular-nums">
                            {{ value }}
                        </dd>
                    </div>
                </dl>

                <section v-if="summary"
                    class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                    <div>
                        <h2 class="text-sm font-semibold">Lewat estimasi</h2>
                        <p class="mt-1 max-w-48 text-xs leading-5 text-slate-600">
                            {{ summary.late }} dari {{ summary.total }} pesanan yang perlu ditangani hari ini
                        </p>
                    </div>

                    <div class="grid size-24 shrink-0 place-items-center rounded-full"
                        :style="{ background: `conic-gradient(#465fff ${summary.percent * 100}%, #eef2ff 0)` }">
                        <span class="grid size-20 place-items-center rounded-full bg-white text-sm font-bold tabular-nums">
                            {{ summary.total ? percent.format(summary.percent) : '—' }}
                        </span>
                    </div>
                </section>
            </div>

            <StatisticsChart
                v-if="bagian === 'ringkasan' && statistikPesanan.length"
                :days="statistikPesanan"
            />

            <section v-if="detail" class="rounded-2xl border border-slate-200 bg-white p-5">
                <Link :href="urlKembali || route('dashboard', { bagian })"
                    class="mb-4 inline-flex text-sm font-semibold text-indigo-700 hover:underline">
                    ← Kembali ke daftar
                </Link>

                <dl class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <div v-for="(value, label) in detail" :key="label" class="min-w-0">
                        <dt class="text-sm text-slate-600">{{ label }}</dt>
                        <dd class="mt-1 wrap-break-word text-sm font-semibold">
                            {{ value === '' || value == null ? '—' : value }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section v-for="form in forms" :key="JSON.stringify(form)"
                class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 font-semibold">{{ form.judul }}</h2>
                <DashboardForm :fields="form.fields" :url="form.url" />
            </section>

            <section v-for="table in tables" :key="table.kolom.join('|')"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold">{{ table.title }}</h2>

                    <Link v-if="bagian === 'ringkasan'" :href="route('dashboard', { bagian: 'pesanan' })"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Semua pesanan
                    </Link>
                </div>

                <div v-if="table.baris.length" class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th v-for="label in table.kolom" :key="label" scope="col"
                                    class="whitespace-nowrap px-5 py-3 text-xs font-semibold">
                                    {{ label }}
                                </th>
                                <th v-if="table.hasActions" scope="col"
                                    class="px-5 py-3 text-right text-xs font-semibold">
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, index) in table.baris" :key="row.url ?? index"
                                class="hover:bg-slate-50/80">
                                <td v-for="(value, i) in row.nilai" :key="i"
                                    class="px-5 py-3.5 text-slate-700" :class="{ 'font-semibold': i === 0 }">
                                    <span v-if="value && statusClass(table.kolom[i], value)"
                                        class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="statusClass(table.kolom[i], value)">
                                        {{ value }}
                                    </span>
                                    <template v-else>{{ value ?? '—' }}</template>
                                </td>

                                <td v-if="table.hasActions" class="px-5 py-3.5 text-right">
                                    <Link v-if="row.url" :href="row.url"
                                        class="whitespace-nowrap font-semibold text-indigo-700 hover:underline">
                                        Buka →
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-else class="px-5 py-8 text-sm text-slate-600">Belum ada data untuk ditampilkan.</p>

                <nav v-if="table.prev || table.next" aria-label="Halaman data"
                    class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3 text-sm font-medium text-slate-700">
                    <Link v-if="table.prev" :href="table.prev"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-50">
                        Sebelumnya
                    </Link>
                    <Link v-if="table.next" :href="table.next"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-50">
                        Berikutnya
                    </Link>
                </nav>
            </section>
        </div>
    </AdminLayout>
</template>