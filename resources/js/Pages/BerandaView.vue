<script setup>
import { Head } from '@inertiajs/vue3'
import Navbar from '@/Components/Navbar.vue'
import HeroSectionLayout from '@/Components/layout/HeroSectionLayout.vue'
import HowSectionLayout from '@/Components/layout/HowSectionLayout.vue'
import FeaturesSectionLayout from '@/Components/layout/FeaturesSectionLayout.vue'
import CtaSectionLayout from '@/Components/layout/CtaSectionLayout.vue'
import FooterLayout from '@/Components/layout/FooterLayout.vue'

// Props dari route '/' di web.php. Dideklarasikan agar Vue tidak memberi peringatan
// "extraneous attrs" (template ini punya banyak root). Boleh dihapus bersama props-nya di web.php.
defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
})
</script>

<template>
    <Head title="Beranda" />

    <Navbar />
    <HeroSectionLayout />
    <HowSectionLayout />
    <FeaturesSectionLayout />
    <CtaSectionLayout />
    <FooterLayout />
</template>