<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { confirmDelete } from '@/Composables/useSwal';
import { Head, Link, router } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { BanknotesIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    budgets: {
        type: Array,
        required: true,
    },
    period: {
        type: String,
        required: true,
    },
    periodLabel: {
        type: String,
        required: true,
    },
});

const periodOptions = [
    { value: 'month', label: 'This Month' },
    { value: 'year', label: 'This Year' },
];

const statusBarClasses = {
    safe: 'bg-emerald-500',
    warning: 'bg-amber-400',
    over: 'bg-red-500',
};

const statusBadgeClasses = {
    safe: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:text-emerald-400',
    warning: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:text-amber-400',
    over: 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:text-red-400',
};

const statusLabels = {
    safe: 'On track',
    warning: 'Near limit',
    over: 'Over budget',
};

const applyPeriod = (value) => {
    if (value === props.period) {
        return;
    }

    router.get(
        route('budgets.index'),
        { period: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const periodTypeLabel = (budget) =>
    budget.period === 'year' ? 'Yearly budget' : 'Monthly budget';

const percentageLabel = (budget) => `${Math.round(budget.percentage)}%`;

const barWidth = (budget) => `${Math.min(100, Math.max(0, budget.percentage))}%`;

const remainingLabel = (budget) =>
    budget.remaining >= 0
        ? `${formatIDR(budget.remaining)} left`
        : `Over by ${formatIDR(Math.abs(budget.remaining))}`;

const destroy = async (budget) => {
    const name = budget.category?.name ?? 'ini';

    const confirmed = await confirmDelete({
        title: 'Hapus budget ini?',
        text: `Budget untuk kategori "${name}" akan dihapus.`,
        confirmButtonText: 'Ya, Hapus',
    });

    if (confirmed) {
        router.delete(route('budgets.destroy', budget.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Budgets" />

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Budgets" subtitle="Set spending limits per category and track them over time.">
                    <template #actions>
                        <Link
                            :href="route('budgets.create')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-primary-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 active:bg-primary-800"
                        >
                            <PlusIcon class="h-4 w-4" />
                            New Budget
                        </Link>
                    </template>
                </PageHeader>
                <div
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3.5 shadow-sm sm:px-5"
                >
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-100">
                            Showing budgets for {{ periodLabel }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Progress is based on this category's expenses in the
                            selected period.
                        </p>
                    </div>

                    <div
                        class="inline-flex w-full rounded-lg border border-primary-200 dark:border-primary-800 bg-white dark:bg-slate-800 p-0.5 shadow-sm sm:w-auto"
                    >
                        <button
                            v-for="option in periodOptions"
                            :key="option.value"
                            type="button"
                            :data-testid="`period-filter-${option.value}`"
                            @click="applyPeriod(option.value)"
                            class="flex-1 rounded-md px-3 py-1.5 text-xs font-semibold transition duration-150 ease-in-out sm:flex-none sm:px-4"
                            :class="
                                option.value === period
                                    ? 'bg-primary-500 text-white shadow'
                                    : 'text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-500/10'
                            "
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="budgets.length"
                    class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                >
                    <div
                        v-for="budget in budgets"
                        :key="budget.id"
                        data-testid="budget-card"
                        class="flex flex-col rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm transition duration-150 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white shadow-sm"
                                    :style="{
                                        backgroundColor:
                                            budget.category?.color || '#64748b',
                                    }"
                                >
                                    {{
                                        (budget.category?.name ?? '?')
                                            .charAt(0)
                                            .toUpperCase()
                                    }}
                                </span>
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-medium text-slate-900 dark:text-slate-100"
                                    >
                                        {{ budget.category?.name ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ periodTypeLabel(budget) }} ·
                                        {{ periodLabel }}
                                    </p>
                                </div>
                            </div>

                            <span
                                class="shrink-0 rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                :class="statusBadgeClasses[budget.status]"
                            >
                                {{ percentageLabel(budget) }}
                            </span>
                        </div>

                        <div class="mt-4">
                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                data-testid="budget-progress"
                            >
                                <div
                                    data-testid="budget-progress-fill"
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="statusBarClasses[budget.status]"
                                    :style="{ width: barWidth(budget) }"
                                ></div>
                            </div>

                            <div
                                class="mt-2 flex items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400"
                            >
                                <span>
                                    Spent
                                    <span class="font-semibold text-slate-700 dark:text-slate-200">
                                        {{ formatIDR(budget.spent) }}
                                    </span>
                                </span>
                                <span>
                                    Limit
                                    <span class="font-semibold text-slate-700 dark:text-slate-200">
                                        {{ formatIDR(budget.amount_limit) }}
                                    </span>
                                </span>
                            </div>

                            <p
                                class="mt-1.5 text-xs font-medium"
                                :class="
                                    budget.status === 'over'
                                        ? 'text-red-600 dark:text-red-400'
                                        : 'text-slate-500 dark:text-slate-400'
                                "
                            >
                                {{ statusLabels[budget.status] }} ·
                                {{ remainingLabel(budget) }}
                            </p>
                        </div>

                        <div
                            class="mt-4 flex items-center justify-end gap-1 border-t border-slate-100 dark:border-slate-700 pt-3"
                        >
                            <Link
                                :href="route('budgets.edit', budget.id)"
                                class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 dark:text-primary-400 transition duration-150 hover:bg-primary-50 dark:hover:bg-primary-500/10 hover:text-primary-800 dark:hover:text-primary-300"
                            >
                                Edit
                            </Link>
                            <button
                                @click="destroy(budget)"
                                class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 transition duration-150 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-500/10 dark:hover:text-red-300"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm"
                >
                    <EmptyState
                        :icon="BanknotesIcon"
                        title="No budgets for this period yet."
                        description="Set a limit per category to keep your spending on track."
                    >
                        <template #action>
                            <Link
                                :href="route('budgets.create')"
                                class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                            >
                                Create your first budget
                            </Link>
                        </template>
                    </EmptyState>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
