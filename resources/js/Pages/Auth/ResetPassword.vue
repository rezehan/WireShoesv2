<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout
        aside-title="Buat password baru"
        aside-text="Pilih password yang kuat agar akunmu dan pesananmu tetap aman."
    >
        <Head title="Password Baru" />

        <form novalidate class="space-y-5" @submit.prevent="submit">
            <header class="mb-8">
                <h1
                    class="font-poppins text-transparent bg-clip-text bg-gradient-to-r from-red-700 to-red-900 text-4xl font-extrabold mb-3"
                >
                    Password baru
                </h1>
                <p class="text-slate-500 text-lg">Masukkan password baru untuk akunmu.</p>
            </header>

            <BaseInput
                v-model="form.email"
                label="Email"
                type="email"
                name="email"
                autocomplete="email"
                placeholder="nama@email.com"
                :error="form.errors.email"
            />

            <BaseInput
                v-model="form.password"
                label="Password baru"
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

            <BaseButton type="submit" block :loading="form.processing" class="mt-2">
                {{ form.processing ? 'Memproses…' : 'Simpan password baru' }}
            </BaseButton>
        </form>
    </AuthLayout>
</template>