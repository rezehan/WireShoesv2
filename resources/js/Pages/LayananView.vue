<script setup>
import { reactive } from 'vue'
import Navbar from '../components/Navbar.vue'
import BaseInput from '../components/ui/BaseInput.vue'
import { useAuth } from '../composables/useAuth'

const { isLoggedIn } = useAuth()

const form = reactive({
    nama: '',
    alamat: '',
    noHP: '',
    layanan: '',
    notes: '',
})

const layananOptions = [
    { label: 'Cuci', value: 'cuci' },
    { label: 'Reparasi', value: 'reparasi' },
    { label: 'Cuci dan Reparasi', value: 'cuci_reparasi' },
]

const submit = () => {
    console.log(form) // { nama: '...', alamat: '...', layanan: 'cuci_reparasi' }
}
</script>

<template>
    <Navbar />
    <div v-if="isLoggedIn" class="container mx-auto py-36">
        <div class="container max-w-3/4 mx-auto flex flex-col gap-5">
            <BaseInput v-model="form.nama" name="nama" label="Nama" placeholder="Masukkan nama Anda" />
            <BaseInput v-model="form.alamat" name="alamat" label="Alamat" placeholder="Masukkan alamat Anda" />
            <BaseInput v-model="form.noHP" name="noHP" label="Nomor HP" placeholder="Masukkan nomor HP Anda" />
            <BaseInput
                v-model="form.layanan"
                name="layanan"
                label="Layanan"
                type="dropdown"
                placeholder="Pilih layanan yang Anda inginkan"
                :options="layananOptions"
            />
            <BaseInput v-model="form.notes" name="notes" label="Catatan" placeholder="Masukkan catatan tambahan" />
            <button type="button" @click="submit"
                class="rounded-2xl bg-red-700 px-5 py-3.5 font-semibold text-white transition hover:bg-red-800">
                Kirim
            </button>
        </div>
    </div>
</template>