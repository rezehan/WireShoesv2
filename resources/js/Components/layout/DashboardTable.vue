<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    title: { type: String, default: '' },
    kolom: { type: Array, required: true },
    baris: { type: Array, required: true },
    prev: { type: String, default: null },
    next: { type: String, default: null },
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

const hasActions = computed(() => props.baris.some((row) => row.url));
const statusColumns = computed(() =>
    props.kolom.map((label) => ['Status', 'Ketersediaan'].includes(label)),
);
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <header v-if="title || $slots.actions"
            class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
            <h2 v-if="title" class="font-semibold">{{ title }}</h2>
            <slot name="actions" />
        </header>

        <div v-if="baris.length" class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th v-for="label in kolom" :key="label" scope="col"
                            class="whitespace-nowrap px-5 py-3 text-xs font-semibold">
                            {{ label }}
                        </th>
                        <th v-if="hasActions" scope="col" class="px-5 py-3 text-right text-xs font-semibold">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <tr v-for="(row, index) in baris" :key="row.url ?? index" class="hover:bg-slate-50/80">
                        <td v-for="(value, i) in row.nilai" :key="i"
                            class="px-5 py-3.5 text-slate-700" :class="{ 'font-semibold': i === 0 }">
                            <span v-if="value && statusColumns[i]"
                                class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="statusColors[value] || 'bg-slate-100 text-slate-700'">
                                {{ value }}
                            </span>
                            <template v-else>{{ value ?? '—' }}</template>
                        </td>
                        <td v-if="hasActions" class="px-5 py-3.5 text-right">
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

        <nav v-if="prev || next" aria-label="Halaman data"
            class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3 text-sm font-medium text-slate-700">
            <Link v-if="prev" :href="prev" class="rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-50">
                Sebelumnya
            </Link>
            <Link v-if="next" :href="next" class="rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-50">
                Berikutnya
            </Link>
        </nav>
    </section>
</template>
