<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import RangeFilter from '@/Components/RangeFilter.vue';
import { Head } from '@inertiajs/vue3';
import { ChartPieIcon } from '@heroicons/vue/24/outline';
import { defineAsyncComponent, computed } from 'vue';

const IncomeExpenseChart = defineAsyncComponent(
    () => import('@/Components/IncomeExpenseChart.vue'),
);
const ExpenseCategoryChart = defineAsyncComponent(
    () => import('@/Components/ExpenseCategoryChart.vue'),
);

const props = defineProps({
    range: {
        type: String,
        required: true,
    },
    categoryRange: {
        type: String,
        required: true,
    },
    monthly: {
        type: Array,
        required: true,
    },
    categoryExpense: {
        type: Array,
        required: true,
    },
    monthLabel: {
        type: String,
        required: true,
    },
});

const hasExpense = computed(() => props.categoryExpense.length > 0);

const barTitles = {
    week: { title: 'Income vs Expense', subtitle: 'Last 12 weeks' },
    month: { title: 'Income vs Expense', subtitle: 'Last 12 months' },
    year: { title: 'Income vs Expense', subtitle: 'Last 5 years' },
};

const donutTitles = {
    week: { title: 'Expense per Category', subtitle: 'Current week' },
    month: { title: 'Expense per Category', subtitle: 'Current month' },
    year: { title: 'Expense per Category', subtitle: 'Current year' },
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Reports" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Reports
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <Card
                    :title="barTitles[range].title"
                    :subtitle="barTitles[range].subtitle"
                >
                    <template #actions>
                        <RangeFilter route-name="reports.index" :range="range" />
                    </template>

                    <div class="relative h-72 sm:h-80">
                        <IncomeExpenseChart :monthly="monthly" />
                    </div>
                </Card>

                <Card
                    :title="donutTitles[categoryRange].title"
                    :subtitle="`${donutTitles[categoryRange].subtitle} (${monthLabel})`"
                >
                    <template #actions>
                        <RangeFilter
                            route-name="reports.index"
                            param="category_range"
                            :range="categoryRange"
                        />
                    </template>

                    <div v-if="hasExpense" class="relative h-72 sm:h-80">
                        <ExpenseCategoryChart :categories="categoryExpense" />
                    </div>
                    <div
                        v-else
                        class="flex flex-col items-center justify-center py-12 text-center"
                    >
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                        >
                            <ChartPieIcon class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-slate-700">
                            No expense recorded in this period yet.
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Try switching the period above.
                        </p>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>