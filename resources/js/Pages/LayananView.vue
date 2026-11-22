<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import BaseAlert from '@/Components/ui/BaseAlert.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';

const props = defineProps({
    hargaLayanan: { type: Object, required: true },
    customer: { type: Object, required: true },
    orderNumber: { type: String, default: null },
});

const layananOptions = [
    { label: 'Cuci', value: 'cuci' },
    { label: 'Reparasi', value: 'reparasi' },
    { label: 'Cuci dan Reparasi', value: 'cuci_reparasi' },
];

const form = useForm({
    nama: props.customer.name ?? '',
    alamat: props.customer.address ?? '',
    no_telepon: props.customer.phone ?? '',
    layanan: '',
    catatan: '',
});

const canSubmit = computed(() =>
    !form.processing && layananOptions.some((option) => option.value === form.layanan)
);

const errorLayanan = computed(() => form.errors.layanan || form.errors.service_id);

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

function submit() {
    if (!canSubmit.value) return;

    form.post(route('orders.store'), {
        onSuccess: () => form.reset('layanan', 'catatan'),
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Pesan Layanan" />
    <Navbar />

    <main class="mx-auto max-w-xl px-6 pb-16 pt-36">
        <h1 class="mb-6 text-3xl font-bold text-red-800">Pesan layanan</h1>

        <BaseAlert v-if="orderNumber" variant="success" class="mb-6">
            <p>Pesanan berhasil dibuat.</p>
            <p class="mt-2">Kode pesanan</p>
            <p class="font-mono text-2xl font-bold tracking-widest">{{ orderNumber }}</p>
        </BaseAlert>

        <form class="space-y-5" @submit.prevent="submit">
            <BaseInput
                v-model="form.nama"
                name="nama"
                label="Nama pemesan"
                autocomplete="name"
                maxlength="100"
                required
                :error="form.errors.nama"
            />

            <BaseInput
                v-model="form.alamat"
                name="alamat"
                label="Alamat"
                type="textarea"
                autocomplete="street-address"
                maxlength="255"
                required
                :error="form.errors.alamat"
            />

            <BaseInput
                v-model="form.no_telepon"
                name="no_telepon"
                label="Nomor HP"
                type="tel"
                autocomplete="tel"
                maxlength="20"
                required
                :error="form.errors.no_telepon"
            />

            <div>
                <label for="service" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Layanan
                </label>

                <select
                    id="service"
                    v-model="form.layanan"
                    required
                    :disabled="form.processing"
                    :aria-invalid="!!errorLayanan"
                    class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-800"
                >
                    <option disabled value="">Pilih layanan</option>
                    <option v-for="option in layananOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>

                <p v-if="form.layanan && hargaLayanan[form.layanan] != null" class="mt-1.5 text-sm text-slate-600">
                    Mulai {{ currency.format(hargaLayanan[form.layanan]) }}.
                    Harga final ditentukan setelah sepatu diperiksa.
                </p>

                <p v-if="errorLayanan" class="mt-1.5 text-sm text-red-700" role="alert">
                    {{ errorLayanan }}
                </p>
            </div>

            <BaseInput
                v-model="form.catatan"
                name="catatan"
                label="Catatan (opsional)"
                type="textarea"
                maxlength="1000"
                :error="form.errors.catatan"
            />

            <BaseButton type="submit" block :disabled="!canSubmit" :loading="form.processing">
                Kirim pesanan
            </BaseButton>
        </form>
    </main>
</template>