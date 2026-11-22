<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const actionRoute = computed(() => {
    if (!user.value) return 'register';
    return user.value.role === 'pelanggan' ? 'services.index' : 'dashboard';
});
</script>

<template>
    <div
        class="hero-section container min-h-screen flex justify-center lg:justify-evenly items-center mx-auto px-6 py-12 lg:py-0">
        
        <!-- Bagian Teks & Tombol -->
        <div class="hero-content w-full sm:w-4/5 lg:w-[35%] text-center lg:text-left">
            <h3
                class="font-poppins text-transparent bg-clip-text bg-gradient-to-r from-red-700 to-red-900 mb-5 text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight">
                Make Your
                Shoes <br>
                Look Brand New
            </h3>
            <p class="text-slate-500 text-base sm:text-lg mb-8 leading-relaxed">
                Melalui <span class="font-anton text-slate-700">WIRESHOES</span>, mari bersama mengeksplorasi cerita,
                emosi, dan
                refleksi yang muncul
                dalam interaksi
                manusia dengan teknologi.
            </p>
            <div class="flex flex-col sm:flex-row justify-center lg:justify-start gap-4">
                <Link :href="route(actionRoute)"
                    class="w-full sm:w-auto px-9 py-3.5 text-white font-semibold text-center rounded-full bg-red-700 hover:bg-red-800 transition">
                    {{ user ? (user.role === 'pelanggan' ? 'Pesan layanan' : 'Buka dashboard') : 'Daftar & Pesan' }}
                </Link>
                <Link v-if="!user" :href="route('login')"
                    class="w-full sm:w-auto px-9 py-3.5 text-red-700 font-bold text-center rounded-full border-3 border-red-700 hover:bg-red-50 transition">
                    Sudah Punya Akun?
                </Link>
            </div>
        </div>

        <!-- Bagian Gambar & Badge (Sembunyi di HP/Tablet, Muncul di Desktop) -->
        <div class="hero-visual hidden lg:flex relative w-[40%] p-0 items-center justify-center">
            <img src="../../assets/sepatu.png" alt="Shoes" class="">
            <span
                class="absolute z-0 top-[10%] -right-10 px-4 py-3 rounded-lg animate-float-bounce bg-white border border-red-100 shadow-sm text-sm font-bold text-red-700 whitespace-nowrap">
                Cuci dan Perbaiki Seperti Baru
            </span>

            <span
                class="absolute z-0 bottom-[10%] left-5 px-4 py-3 rounded-lg animate-bounce-float bg-white border border-red-100 shadow-sm text-sm font-bold text-red-700 whitespace-nowrap">
                Harga Murah Meriah
            </span>
        </div>

    </div>
</template>