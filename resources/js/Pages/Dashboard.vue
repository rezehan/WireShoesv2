<script setup>
import { computed, defineAsyncComponent } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Components/layout/AdminLayout.vue';
import DashboardForm from '@/Components/layout/DashboardForm.vue';
import DashboardTable from '@/Components/layout/DashboardTable.vue';

defineOptions({ inheritAttrs: false });

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
    ];
});

const namaRute = computed(() => props.bagian === 'ringkasan' ? 'dashboard' : `staff.${props.bagian}`);

const filterFields = computed(() => [
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
    const filters = Object.fromEntries(props.filters
        .filter(({ name }) => ['mulai', 'akhir'].includes(name))
        .map(({ name, value }) => [name, value]));

    return route(namaRute.value, { tab, ...filters });
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
                    :url="route(namaRute)"
                    method="get"
                    label="Terapkan filter"
                />
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
                <Link :href="route(namaRute)"
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

            <DashboardTable
                v-for="table in tables"
                :key="table.kolom.join('|')"
                :title="table.title"
                :kolom="table.kolom"
                :baris="table.baris"
                :prev="table.prev"
                :next="table.next"
            >
                <template v-if="bagian === 'ringkasan'" #actions>
                    <Link :href="route('staff.pesanan')"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Semua pesanan
                    </Link>
                </template>
            </DashboardTable>
        </div>
    </AdminLayout>
</template>