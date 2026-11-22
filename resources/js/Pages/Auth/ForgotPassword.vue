<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseInput from '@/Components/ui/BaseInput.vue';
import BaseButton from '@/Components/ui/BaseButton.vue';
import BaseAlert from '@/Components/ui/BaseAlert.vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout
        aside-title="Lupa password? Tenang saja"
        aside-text="Kami kirimkan tautan ke emailmu agar kamu bisa membuat password baru."
    >
        <Head title="Lupa Password" />

        <form novalidate class="space-y-5" @submit.prevent="submit">
            <header class="mb-8">
                <h1
                    class="font-poppins text-transparent bg-clip-text bg-gradient-to-r from-red-700 to-red-900 text-4xl font-extrabold mb-3"
                >
                    Atur ulang password
                </h1>
                <p class="text-slate-500 text-lg">
                    Masukkan email akunmu, lalu kami kirimkan tautan untuk membuat password baru.
                </p>
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

            <BaseButton type="submit" block :loading="form.processing" class="mt-2">
                {{ form.processing ? 'Memproses…' : 'Kirim tautan atur ulang' }}
            </BaseButton>

            <p class="text-center text-sm text-slate-500">
                Ingat password kamu?
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