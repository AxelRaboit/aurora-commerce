<script setup>
import { computed } from "vue";
import { Doughnut, Bar, Line } from "vue-chartjs";
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Filler,
} from "chart.js";

ChartJS.register(
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Filler,
);

const props = defineProps({
    type: { type: String, required: true, validator: (v) => ["doughnut", "bar", "line"].includes(v) },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
});

const component = computed(() => ({ doughnut: Doughnut, bar: Bar, line: Line })[props.type]);

const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: "#9CA3AF", font: { size: 11 } } },
        tooltip: { backgroundColor: "rgba(15, 23, 42, 0.9)", titleColor: "#F3F4F6", bodyColor: "#D1D5DB" },
    },
};

const mergedOptions = computed(() => ({ ...baseOptions, ...props.options }));
</script>

<template>
    <component :is="component" :data="data" :options="mergedOptions" />
</template>
