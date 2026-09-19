<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
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

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Reports"
                    subtitle="Visualize your income and expenses over time."
                />

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
                    <EmptyState
                        v-else
                        :icon="ChartPieIcon"
                        title="No expense recorded in this period yet."
                        description="Try switching the period above."
                    />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>