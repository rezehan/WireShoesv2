<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

// Data user dibagikan Inertia lewat HandleInertiaRequests (auth.user); null jika belum login.
const isLoggedIn = computed(() => !!page.props.auth?.user)
</script>

<template>
  <nav
    class="navbar fixed top-6 left-1/2 -translate-x-1/2 w-1/2 py-4 px-8 bg-white rounded-full border border-[#FF8787] z-50"
    id="navbar">
    <div class="flex items-center justify-between w-full">
      <Link href="/">
        <img src="../assets/WIRESHOES.png" alt="logo" class="w-32">
      </Link>

      <!-- Sudah login -->
      <ul v-if="isLoggedIn" class="flex items-center gap-4 font-normal text-slate-500 text-sm font-semibold">
        <li>
          <Link :href="route('dashboard')" class="transition hover:text-red-700"
            :class="{ 'text-red-700': route().current('dashboard') }">
            Dashboard
          </Link>
        </li>
        <!-- Tautan Layanan baru tampil setelah route 'services.index' dibuat -->
        <li v-if="route().has('services.index')">
          <Link :href="route('services.index')" class="transition hover:text-red-700"
            :class="{ 'text-red-700': route().current('services.*') }">
            Layanan
          </Link>
        </li>
        <li>
          <Link :href="route('logout')" method="post" as="button"
            class="rounded-lg bg-red-700 px-6 py-2.5 text-white transition hover:bg-red-800 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-red-300">
            Keluar
          </Link>
        </li>
      </ul>

      <!-- Belum login -->
      <ul v-else class="flex gap-3 font-normal text-slate-500 text-sm font-semibold">
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