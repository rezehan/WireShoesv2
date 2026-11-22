<script setup>
defineProps({
    selectedTab: { type: String, required: true },
    total: { type: Number, required: true },
});

const emit = defineEmits(['tabChange']);

const tabs = {
    overview: 'Semua',
    online: 'Online',
    toko: 'Toko',
};

const numberFormat = new Intl.NumberFormat('id-ID');
</script>

<template>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-semibold text-slate-950">Statistik pesanan</h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ numberFormat.format(total) }} pesanan · 7 hari terakhir (WITA)
            </p>
        </div>

        <div role="group" aria-label="Sumber pesanan"
            class="inline-flex w-fit gap-1 rounded-lg bg-slate-100 p-1">
            <button v-for="(label, key) in tabs" :key="key" type="button"
                :aria-pressed="selectedTab === key"
                class="rounded-md px-3 py-1.5 text-sm font-medium"
                :class="selectedTab === key ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                @click="emit('tabChange', key)">
                {{ label }}
            </button>
        </div>
    </div>
</template>