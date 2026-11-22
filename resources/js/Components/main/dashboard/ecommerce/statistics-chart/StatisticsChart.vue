<script setup>
import { computed, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts/core';
import 'apexcharts/area';
import 'apexcharts/features/legend';
import CardHeader from './CardHeader.vue';

const props = defineProps({
    days: { type: Array, required: true },
});

const selectedTab = ref('overview');

const channels = [
    { key: 'online', name: 'Online', color: '#465FFF' },
    { key: 'toko', name: 'Toko', color: '#9CB9FF' },
];

const dateFormat = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    timeZone: 'Asia/Makassar',
});
const numberFormat = new Intl.NumberFormat('id-ID');

const visibleChannels = computed(() => channels.filter(
    ({ key }) => selectedTab.value === 'overview' || selectedTab.value === key,
));

const series = computed(() => visibleChannels.value.map(({ key, name }) => ({
    name,
    data: props.days.map((day) => day[key]),
})));

const total = computed(() => series.value
    .flatMap(({ data }) => data)
    .reduce((sum, value) => sum + value, 0));

const axis = computed(() => {
    const max = Math.max(1, ...props.days.flatMap(({ online, toko }) => [online, toko]));
    const step = Math.ceil(max / 4);
    const ticks = Math.ceil(max / step);

    return { max: ticks * step, tickAmount: ticks };
});

const options = computed(() => ({
    chart: {
        fontFamily: 'Inter, sans-serif',
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    colors: visibleChannels.value.map(({ color }) => color),
    stroke: { curve: 'straight', width: 2 },
    fill: {
        type: 'gradient',
        gradient: { opacityFrom: 0.55, opacityTo: 0 },
    },
    dataLabels: { enabled: false },
    markers: { size: 0, hover: { size: 5 } },
    grid: { borderColor: '#E2E8F0' },
    legend: {
        position: 'bottom',
        onItemClick: { toggleDataSeries: false },
    },
    xaxis: {
        categories: props.days.map(({ tanggal }) =>
            dateFormat.format(new Date(`${tanggal}T00:00:00+08:00`)),
        ),
        axisBorder: { show: false },
        axisTicks: { show: false },
        tooltip: { enabled: false },
    },
    yaxis: {
        min: 0,
        ...axis.value,
        decimalsInFloat: 0,
    },
    tooltip: {
        shared: true,
        intersect: false,
        y: { formatter: (value) => `${numberFormat.format(value)} pesanan` },
    },
}));
</script>

<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6" aria-label="Statistik pesanan">
        <CardHeader
            :selected-tab="selectedTab"
            :total="total"
            @tab-change="selectedTab = $event"
        />

        <div class="overflow-x-auto">
            <div class="min-w-[400px] sm:min-w-0">
                <VueApexCharts type="area" :height="300" :options="options" :series="series" />
            </div>
        </div>
    </section>
</template>