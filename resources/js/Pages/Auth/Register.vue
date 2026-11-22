<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseAlert from '@/Components/ui/BaseAlert.vue';

const form = useForm({
    nama_lengkap: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    alamat: '',
});

const submit = () => {
    form.transform(data => ({ ...data, email: data.email.trim().toLowerCase() })).post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout
        aside-title="Satu akun untuk semua pesananmu"
        aside-text="Pesan layanan cuci dan perbaikan sepatu, lalu pantau setiap tahapnya dalam satu tempat."
    >
        <Head title="Daftar" />

        <form novalidate class="space-y-5" @submit.prevent="submit">
            <header class="mb-8">
                <h1
                    class="font-poppins text-transparent bg-clip-text bg-gradient-to-r from-red-700 to-red-900 text-4xl font-extrabold mb-3"
                >
                    Buat akun baru
                </h1>
                <p class="text-slate-500 text-lg">
                    Daftar untuk memesan dan memantau layanan sepatumu.
                </p>
            </header>

            <BaseAlert v-if="form.errors.firebase">{{ form.errors.firebase }}</BaseAlert>

            <BaseInput
                v-model="form.nama_lengkap"
                label="Nama lengkap"
                name="nama_lengkap"
                autocomplete="name"
                placeholder="Nama sesuai identitas"
                maxlength="100"
                :error="form.errors.nama_lengkap"
            />

            <BaseInput
                v-model="form.username"
                label="Username"
                name="username"
                autocomplete="username"
                placeholder="renlveed"
                maxlength="20"
                hint="3–20 karakter: huruf, angka, atau underscore."
                :error="form.errors.username"
            />

            <BaseInput
                v-model="form.email"
                label="Email"
                type="email"
                name="email"
                autocomplete="email"
                placeholder="nama@email.com"
                maxlength="255"
                :error="form.errors.email"
            />

            <BaseInput
                v-model="form.password"
                label="Password"
                type="password"
                name="password"
                autocomplete="new-password"
                placeholder="Minimal 8 karakter"
                maxlength="72"
                :error="form.errors.password"
            />

            <BaseInput
                v-model="form.password_confirmation"
                label="Konfirmasi password"
                type="password"
                name="password_confirmation"
                autocomplete="new-password"
                placeholder="Ulangi password"
                maxlength="72"
                :error="form.errors.password_confirmation"
            />

            <BaseInput
                v-model="form.alamat"
                label="Alamat"
                type="textarea"
                name="alamat"
                autocomplete="street-address"
                placeholder="Jalan, nomor, kelurahan, kota"
                maxlength="255"
                :error="form.errors.alamat"
            />

            <BaseButton type="submit" block :loading="form.processing" class="mt-2">
                {{ form.processing ? 'Memproses…' : 'Daftar' }}
            </BaseButton>

            <p class="text-center text-sm text-slate-500">
                Sudah punya akun?
                <Link
                    :href="route('login')"
                    class="font-bold text-red-700 hover:underline focus-visible:underline focus-visible:outline-none"
                >
                    Masuk
                </Link>
            </p>
        </form>
    </AuthLayout>
</template>