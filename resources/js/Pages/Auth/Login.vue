<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseAlert from '@/Components/ui/BaseAlert.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout
        aside-title="Sepatumu siap kembali seperti baru"
        aside-text="Masuk untuk melihat status pengerjaan dan estimasi waktu selesai pesananmu."
    >
        <Head title="Masuk" />

        <form novalidate class="space-y-5" @submit.prevent="submit">
            <header class="mb-8">
                <h1
                    class="font-poppins text-transparent bg-clip-text bg-gradient-to-r from-red-700 to-red-900 text-4xl font-extrabold mb-3"
                >
                    Masuk ke akunmu
                </h1>
                <p class="text-slate-500 text-lg">Pantau status pengerjaan sepatumu kapan saja.</p>
            </header>

            <BaseAlert v-if="status" variant="success">{{ status }}</BaseAlert>

            <BaseAlert v-if="form.errors.firebase">{{ form.errors.firebase }}</BaseAlert>

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
                label="Password"
                type="password"
                name="password"
                autocomplete="current-password"
                placeholder="Masukkan password"
                :error="form.errors.password"
            />

            <div v-if="canResetPassword" class="text-right">
                <Link
                    :href="route('password.request')"
                    class="text-sm font-semibold text-red-700 hover:underline focus-visible:underline focus-visible:outline-none"
                >
                    Lupa password?
                </Link>
            </div>

            <BaseButton type="submit" block :loading="form.processing" class="mt-2">
                {{ form.processing ? 'Memproses…' : 'Masuk' }}
            </BaseButton>

            <p class="text-center text-sm text-slate-500">
                Belum punya akun?
                <Link
                    :href="route('register')"
                    class="font-bold text-red-700 hover:underline focus-visible:underline focus-visible:outline-none"
                >
                    Daftar sekarang
                </Link>
            </p>
        </form>
    </AuthLayout>
</template>