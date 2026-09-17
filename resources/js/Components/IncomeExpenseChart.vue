<script setup>
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Bar } from 'vue-chartjs';
import { computed } from 'vue';
import { isDark } from '@/Composables/useDarkMode';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
    monthly: {
        type: Array,
        required: true,
    },
});

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const formatCompact = (value) =>
    new Intl.NumberFormat('id-ID', {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(value);

const chartData = computed(() => ({
    labels: props.monthly.map((month) => month.label),
    datasets: [
        {
            label: 'Income',
            data: props.monthly.map((month) => month.income),
            backgroundColor: 'rgba(125, 211, 252, 0.85)',
            borderColor: '#0ea5e9',
            borderWidth: 1,
            borderRadius: 6,
        },
        {
            label: 'Expense',
            data: props.monthly.map((month) => month.expense),
            backgroundColor: 'rgba(253, 164, 175, 0.85)',
            borderColor: '#f43f5e',
            borderWidth: 1,
            borderRadius: 6,
        },
    ],
}));

const tickColor = () => (isDark.value ? '#94a3b8' : '#64748b');

const gridColor = () => (isDark.value ? 'rgba(148,163,184,0.2)' : 'rgba(2,132,199,0.1)');

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 20,
                color: tickColor(),
            },
        },
        tooltip: {
            callbacks: {
                label: (context) =>
                    ` ${context.dataset.label}: ${formatIDR(context.parsed.y)}`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: {
                color: tickColor(),
            },
        },
        y: {
            beginAtZero: true,
            grid: { color: gridColor() },
            ticks: {
                color: tickColor(),
                callback: (value) => formatCompact(value),
            },
        },
    },
}));
</script>

<template>
    <Bar :data="chartData" :options="chartOptions" />
</template>