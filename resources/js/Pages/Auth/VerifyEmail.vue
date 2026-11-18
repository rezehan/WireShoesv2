<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseAlert from '@/Components/ui/BaseAlert.vue';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <AuthLayout
        aside-title="Cek emailmu"
        aside-text="Satu klik pada tautan verifikasi, lalu akunmu siap dipakai."
    >
        <Head title="Verifikasi Email" />

        <form novalidate class="space-y-5" @submit.prevent="submit">
            <header class="mb-8">
                <h1
                    class="font-poppins text-transparent bg-clip-text bg-gradient-to-r from-red-700 to-red-900 text-4xl font-extrabold mb-3"
                >
                    Verifikasi email
                </h1>
                <p class="text-slate-500 text-lg">
                    Terima kasih sudah mendaftar! Klik tautan verifikasi yang baru kami kirim ke
                    emailmu. Kalau belum menerimanya, kami kirimkan lagi.
                </p>
            </header>

            <BaseAlert v-if="verificationLinkSent" variant="success">
                Tautan verifikasi baru sudah dikirim ke email yang kamu pakai saat mendaftar.
            </BaseAlert>

            <div class="flex items-center justify-between gap-4 pt-2">
                <BaseButton type="submit" :loading="form.processing">
                    {{ form.processing ? 'Memproses…' : 'Kirim ulang email verifikasi' }}
                </BaseButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-bold text-red-700 hover:underline focus-visible:underline focus-visible:outline-none"
                >
                    Keluar
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>