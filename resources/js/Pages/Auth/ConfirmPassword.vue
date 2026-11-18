<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthLayout
        aside-title="Satu langkah keamanan lagi"
        aside-text="Konfirmasi password untuk melanjutkan ke bagian yang dilindungi."
    >
        <Head title="Konfirmasi Password" />

        <form novalidate class="space-y-5" @submit.prevent="submit">
            <header class="mb-8">
                <h1
                    class="font-poppins text-transparent bg-clip-text bg-gradient-to-r from-red-700 to-red-900 text-4xl font-extrabold mb-3"
                >
                    Konfirmasi password
                </h1>
                <p class="text-slate-500 text-lg">
                    Ini area yang aman. Masukkan passwordmu sebelum melanjutkan.
                </p>
            </header>

            <BaseInput
                v-model="form.password"
                label="Password"
                type="password"
                name="password"
                autocomplete="current-password"
                placeholder="Masukkan password"
                :error="form.errors.password"
            />

            <BaseButton type="submit" block :loading="form.processing" class="mt-2">
                {{ form.processing ? 'Memproses…' : 'Konfirmasi' }}
            </BaseButton>
        </form>
    </AuthLayout>
</template>