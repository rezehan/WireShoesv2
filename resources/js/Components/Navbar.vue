<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import logo from '@/assets/WIRESHOES.png';

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <nav id="navbar"
        class="navbar fixed top-6 left-1/2 z-50 w-[calc(100%-1.5rem)] max-w-4xl -translate-x-1/2 rounded-full border border-[#FF8787] bg-white px-3 py-2.5 sm:px-8 sm:py-4">
        <div class="flex w-full items-center justify-between gap-2">
            <Link href="/" class="shrink-0">
                <img :src="logo" alt="WireShoes" class="w-20 sm:w-32" />
            </Link>

            <ul v-if="user"
                class="flex flex-wrap items-center justify-end gap-x-2 gap-y-1 text-xs font-semibold text-slate-500 sm:gap-x-4 sm:text-sm">
                <li v-if="['admin', 'kasir'].includes(user.role)">
                    <Link :href="route('dashboard')" class="transition hover:text-red-700"
                        :class="{ 'text-red-700': route().current('dashboard') }">
                        Dashboard
                    </Link>
                </li>

                <li v-if="user.role === 'pelanggan'">
                    <Link :href="route('services.index')" class="transition hover:text-red-700"
                        :class="{ 'text-red-700': route().current('services.*') }">
                        Layanan
                    </Link>
                </li>

                <li v-if="user.role === 'pelanggan'">
                    <Link :href="route('orders.mine')" class="transition hover:text-red-700"
                        :class="{ 'text-red-700': route().current('orders.mine') }">
                        Pesanan Saya
                    </Link>
                </li>

                <li>
                    <Link :href="route('logout')" method="post" as="button"
                        class="rounded-lg bg-red-700 px-2.5 py-2 text-white transition hover:bg-red-800 focus-visible:ring-4 focus-visible:ring-red-300 sm:px-6 sm:py-2.5">
                        Keluar
                    </Link>
                </li>
            </ul>

            <ul v-else class="flex gap-3 text-sm font-semibold text-slate-500">
                <li>
                    <Link :href="route('login')" class="transition hover:text-red-700"
                        :class="{ 'text-red-700': route().current('login') }">
                        Masuk
                    </Link>
                </li>
                <li>
                    <Link :href="route('register')" class="transition hover:text-red-700"
                        :class="{ 'text-red-700': route().current('register') }">
                        Daftar &amp; Pesan
                    </Link>
                </li>
            </ul>
        </div>
    </nav>
</template>