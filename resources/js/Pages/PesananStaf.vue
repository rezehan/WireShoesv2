<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Components/layout/AdminLayout.vue';
import DashboardForm from '@/Components/layout/DashboardForm.vue';
import DashboardTable from '@/Components/layout/DashboardTable.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    menu: { type: Object, required: true },
    judul: { type: String, default: 'Pesanan' },
    pesan: { type: String, default: null },
    tabs: { type: Object, default: null },
    tab: { type: String, default: null },
    detail: { type: Object, default: null },
    urlKembali: { type: String, default: null },
    kolom: { type: Array, default: () => [] },
    baris: { type: Array, default: () => [] },
    prev: { type: String, default: null },
    next: { type: String, default: null },
    forms: { type: Array, default: () => [] },
    filters: { type: Array, default: () => [] },
});

const filterFields = computed(() => [
    { name: 'tab', type: 'hidden', value: props.tab ?? 'hari_ini' },
    ...props.filters,
]);

function tabUrl(tab) {
    const fields = tab === 'riwayat'
        ? ['q', 'sumber', 'mulai', 'akhir']
        : ['q', 'sumber'];

    const filters = Object.fromEntries(props.filters
        .filter(({ name }) => fields.includes(name))
        .map(({ name, value }) => [name, value]));

    return route('staff.pesanan', { tab, ...filters });
}
</script>

<template>
    <Head :title="judul" />

    <AdminLayout :menu="menu" bagian="pesanan">
        <div class="space-y-5">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-[28px]">
                {{ judul }}
            </h1>

            <p v-if="pesan" role="status"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                {{ pesan }}
            </p>

            <nav v-if="tabs" aria-label="Bagian pesanan"
                class="flex flex-wrap gap-1 rounded-xl border border-slate-200 bg-white p-1.5">
                <Link v-for="(label, key) in tabs" :key="key" :href="tabUrl(key)"
                    :aria-current="tab === key ? 'page' : undefined"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors"
                    :class="tab === key ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100'">
                    {{ label }}
                </Link>
            </nav>

            <section v-if="filters.length" class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="mb-4 font-semibold">Filter pesanan</h2>

                <DashboardForm
                    :key="JSON.stringify(filterFields)"
                    :fields="filterFields"
                    :url="route('staff.pesanan')"
                    method="get"
                    label="Terapkan filter"
                />

                <Link :href="route('staff.pesanan', { tab })"
                    class="mt-3 inline-block text-sm font-semibold text-indigo-700 hover:underline">
                    Reset filter
                </Link>
            </section>

            <section v-if="detail" class="rounded-2xl border border-slate-200 bg-white p-5">
                <Link :href="urlKembali || route('staff.pesanan')"
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

            <DashboardTable v-if="kolom.length"
                :title="tabs?.[tab] ?? 'Daftar pesanan'"
                :kolom="kolom"
                :baris="baris"
                :prev="prev"
                :next="next"
            />
        </div>
    </AdminLayout>
</template>
