<script setup>
import {
    Chart as ChartJS,
    ArcElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Doughnut } from 'vue-chartjs';
import { computed } from 'vue';

ChartJS.register(ArcElement, Title, Tooltip, Legend);

const props = defineProps({
    categories: {
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

const chartData = computed(() => ({
    labels: props.categories.map((category) => category.name),
    datasets: [
        {
            data: props.categories.map((category) => category.total),
            backgroundColor: props.categories.map(
                (category) => category.color || '#94a3b8',
            ),
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverOffset: 6,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '58%',
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 16,
            },
        },
        tooltip: {
            callbacks: {
                label: (context) => {
                    const total = context.dataset.data.reduce(
                        (sum, value) => sum + value,
                        0,
                    );
                    const percentage = total
                        ? ((context.parsed / total) * 100).toFixed(1)
                        : 0;

                    return ` ${context.label}: ${formatIDR(context.parsed)} (${percentage}%)`;
                },
            },
        },
    },
};
</script>

<template>
    <Doughnut :data="chartData" :options="chartOptions" />
</template>