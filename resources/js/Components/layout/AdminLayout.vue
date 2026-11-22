<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import logo from '@/assets/WIRESHOES.png';

defineProps({
    menu: { type: Object, required: true },
    bagian: { type: String, required: true },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const initial = computed(() => user.value?.name?.trim().charAt(0).toUpperCase() || 'W');
const role = computed(() => ({ admin: 'Admin', kasir: 'Kasir' })[user.value?.role]);
const mobileOpen = ref(false);
const desktopCollapsed = ref(false);
const profileMenu = ref(null);
const query = ref('');

const icons = {
    ringkasan: 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',
    pesanan: 'M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10',
    voucher: 'M3 6h18v4a2 2 0 0 0 0 4v4H3v-4a2 2 0 0 0 0-4zM14 6v2m0 3v2m0 3v2',
    pelanggan: 'M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M14 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM20 21v-2a4 4 0 0 0-3-4m0-12a4 4 0 0 1 0 8',
    layanan: 'M4 6h16M4 12h16M4 18h16M8 3v6m8 0v6m-5 0v6',
    tim: 'M4 7h16a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1zM8 7V4h8v3M3 13h18m-9 0v3',
    laporan: 'M4 3v17h17M8 16v-4m5 4V8m5 8V5',
};

function searchOrders() {
    const q = query.value.trim();
    if (q) router.get(route('dashboard'), { bagian: 'pesanan', q });
}

function closeProfile() {
    profileMenu.value?.removeAttribute('open');
}
</script>

<template>
    <div class="min-h-screen bg-[#f9fafb] font-sans text-slate-900 lg:flex" @keydown.esc="mobileOpen = false">
        <button v-if="mobileOpen" type="button" aria-label="Tutup navigasi"
            class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="mobileOpen = false" />

        <aside id="menu-staf"
            class="fixed inset-y-0 left-0 z-40 w-64 shrink-0 overflow-y-auto border-r border-slate-200 bg-white px-4 lg:sticky lg:top-0 lg:h-dvh"
            :class="[mobileOpen ? 'block' : 'hidden', desktopCollapsed ? 'lg:hidden' : 'lg:block']">
            <div class="flex h-[76px] items-center justify-between px-3">
                <Link :href="route('dashboard')" @click="mobileOpen = false">
                    <img :src="logo" alt="WireShoes" class="h-7 w-auto object-contain" />
                </Link>
                <button type="button" aria-label="Tutup menu"
                    class="p-2 text-xl text-slate-600 lg:hidden" @click="mobileOpen = false">
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            <nav aria-label="Menu staf" class="pb-6 pt-3">
                <ul class="space-y-1">
                    <li v-for="(label, key) in menu" :key="key">
                        <Link
                            :href="key === 'ringkasan' ? route('dashboard') : route('dashboard', { bagian: key })"
                            :aria-current="key === bagian ? 'page' : undefined"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                            :class="key === bagian ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                            @click="mobileOpen = false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65"
                                stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true">
                                <path :d="icons[key]" />
                            </svg>
                            {{ label }}
                        </Link>
                    </li>
                </ul>
            </nav>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-[1600px] flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 sm:flex-nowrap sm:px-6 lg:min-h-[72px]">
                    <button v-for="mode in ['mobile', 'desktop']" :key="mode" type="button"
                        aria-controls="menu-staf" aria-label="Alihkan menu"
                        :aria-expanded="mode === 'mobile' ? mobileOpen : !desktopCollapsed"
                        class="size-10 shrink-0 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                        :class="mode === 'mobile' ? 'grid lg:hidden' : 'hidden lg:grid'"
                        @click="mode === 'mobile' ? mobileOpen = !mobileOpen : desktopCollapsed = !desktopCollapsed">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                            stroke-linecap="round" class="size-5" aria-hidden="true">
                            <path d="M4 6h16M4 12h11M4 18h16" />
                        </svg>
                    </button>

                    <Link :href="route('dashboard')" class="sm:hidden">
                        <img :src="logo" alt="WireShoes" class="h-5 w-auto object-contain" />
                    </Link>

                    <form v-if="menu.pesanan" class="order-last w-full sm:order-0 sm:max-w-xs"
                        role="search" @submit.prevent="searchOrders">
                        <div class="relative">
                            <input v-model="query" type="search" maxlength="30" aria-label="Cari nomor pesanan"
                                placeholder="Cari nomor pesanan…"
                                class="w-full rounded-lg border-slate-200 bg-white py-2 pl-3 pr-11 text-sm text-slate-900 placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500" />
                            <button type="submit" aria-label="Cari"
                                class="absolute inset-y-0 right-0 grid w-10 place-items-center rounded-r-lg text-slate-500 hover:text-indigo-700">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                                    stroke-linecap="round" class="size-[18px]" aria-hidden="true">
                                    <circle cx="10.5" cy="10.5" r="6" />
                                    <path d="m15 15 4 4" />
                                </svg>
                            </button>
                        </div>
                    </form>

                    <details ref="profileMenu" class="relative ml-auto shrink-0" @keydown.esc="closeProfile">
                        <summary aria-label="Menu akun"
                            class="flex cursor-pointer list-none items-center gap-2 rounded-lg p-1 text-sm [&::-webkit-details-marker]:hidden">
                            <span aria-hidden="true"
                                class="grid size-9 shrink-0 place-items-center rounded-full bg-indigo-100 font-semibold text-indigo-700">
                                {{ initial }}
                            </span>
                            <span class="hidden text-left sm:block">
                                <span class="block max-w-36 truncate font-semibold">{{ user?.name }}</span>
                                <span v-if="role" class="block text-xs text-slate-500">{{ role }}</span>
                            </span>
                            <span class="mt-[-4px] size-1.5 rotate-45 border-b border-r border-slate-500" aria-hidden="true" />
                        </summary>

                        <div class="absolute right-0 top-full mt-2 w-44 rounded-xl border border-slate-200 bg-white p-1.5 text-sm shadow-lg">
                            <Link :href="route('profile.edit')" class="block rounded-lg px-3 py-2 hover:bg-slate-50"
                                @click="closeProfile">
                                Profil
                            </Link>
                            <Link :href="route('logout')" method="post" as="button"
                                class="block w-full rounded-lg px-3 py-2 text-left text-red-700 hover:bg-red-50"
                                @click="closeProfile">
                                Keluar
                            </Link>
                        </div>
                    </details>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:py-8">
                <slot />
            </main>
        </div>
    </div>
</template>