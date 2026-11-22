<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseAlert from '@/Components/ui/BaseAlert.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';

defineProps({ status: String });

const form = useForm({});
</script>

<template>
    <AuthLayout
        aside-title="Cek emailmu"
        aside-text="Buka tautan verifikasi yang dikirim ke alamat email akunmu."
    >
        <Head title="Verifikasi Email" />

        <section class="space-y-5">
            <h1 class="font-poppins text-4xl font-extrabold text-red-800">Verifikasi email</h1>
            <p class="text-slate-500">
                Klik tautan verifikasi dari Firebase. Kembali ke sini dan pilih Cek status verifikasi untuk melanjutkan.
            </p>

            <BaseAlert v-if="status === 'verification-link-sent'" variant="success">
                Tautan verifikasi baru sudah dikirim.
            </BaseAlert>
            <BaseAlert v-if="status === 'verification-pending'">
                Email masih belum terverifikasi. Periksa kotak masuk dan folder spam.
            </BaseAlert>
            <BaseAlert v-if="form.errors.firebase || form.errors.email">
                {{ form.errors.firebase || form.errors.email }}
            </BaseAlert>

            <div class="flex flex-wrap items-center gap-3">
                <BaseButton type="button" :loading="form.processing" @click="form.post(route('verification.refresh'))">
                    Cek status verifikasi
                </BaseButton>
                <button type="button" class="text-sm font-bold text-red-700 hover:underline"
                    :disabled="form.processing" @click="form.post(route('verification.send'))">
                    Kirim ulang email
                </button>
            </div>
            <Link href="/" class="inline-block text-sm font-bold text-red-700 hover:underline">Kembali ke beranda</Link>
        </section>
    </AuthLayout>
</template>
