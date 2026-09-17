<script setup>
import Card from '@/Components/Card.vue';
import RangeFilter from '@/Components/RangeFilter.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowTrendingDownIcon,
    ArrowTrendingUpIcon,
    BanknotesIcon,
    ChartPieIcon,
    ExclamationTriangleIcon,
    InboxIcon,
    WalletIcon,
} from '@heroicons/vue/24/outline';
import { defineAsyncComponent, computed } from 'vue';

const IncomeExpenseChart = defineAsyncComponent(
    () => import('@/Components/IncomeExpenseChart.vue'),
);
const ExpenseCategoryChart = defineAsyncComponent(
    () => import('@/Components/ExpenseCategoryChart.vue'),
);

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
    recent_transactions: {
        type: Array,
        required: true,
    },
    budget_alerts: {
        type: Array,
        default: () => [],
    },
    budgets: {
        type: Array,
        default: () => [],
    },
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
    category_expense: {
        type: Array,
        required: true,
    },
    monthLabel: {
        type: String,
        required: true,
    },
});

const typeLabels = {
    income: 'Income',
    expense: 'Expense',
    transfer: 'Transfer',
};

const typeBadgeClasses = {
    income: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    expense: 'border-red-200 bg-red-50 text-red-700',
    transfer: 'border-slate-300 bg-slate-100 text-slate-600',
};

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const formatDate = (value) =>
    new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));

const amountClass = (transaction) =>
    transaction.type === 'income'
        ? 'text-emerald-600'
        : transaction.type === 'expense'
          ? 'text-red-600'
          : 'text-slate-600';

const isTransfer = (transaction) =>
    transaction.type === 'transfer' && !!transaction.transfer_to_account;

const hasExpense = computed(() => props.category_expense.length > 0);

const alertSummary = computed(() =>
    props.budget_alerts
        .slice(0, 3)
        .map(
            (budget) =>
                `${budget.category?.name ?? 'Budget'} ${Math.round(budget.percentage)}%`,
        )
        .join(' · ') +
    (props.budget_alerts.length > 3
        ? ` · +${props.budget_alerts.length - 3} more`
        : ''),
);

const budgetBarClasses = {
    safe: 'bg-emerald-500',
    warning: 'bg-amber-400',
    over: 'bg-red-500',
};

const budgetBarWidth = (budget) =>
    `${Math.min(100, Math.max(0, budget.percentage))}%`;

const isBudgetAlmostFull = (budget) => budget.percentage >= 90;

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
        <Head title="Dashboard" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Dashboard
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <Link
                    v-if="budget_alerts.length"
                    :href="route('budgets.index')"
                    data-testid="budget-alert"
                    class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 shadow-sm transition duration-150 hover:bg-amber-100 sm:px-5"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600"
                    >
                        <ExclamationTriangleIcon class="h-5 w-5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-amber-900">
                            {{ budget_alerts.length }} budget{{
                                budget_alerts.length === 1 ? '' : 's'
                            }}
                            need{{ budget_alerts.length === 1 ? 's' : '' }}
                            attention
                        </p>
                        <p class="mt-0.5 truncate text-xs text-amber-700">
                            {{ alertSummary }}
                        </p>
                    </div>

                    <span
                        class="shrink-0 self-center text-xs font-semibold text-amber-700"
                    >
                        View
                    </span>
                </Link>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Total Balance
                                </p>
                                <p
                                    class="mt-2 truncate text-2xl font-bold tracking-tight"
                                    :class="
                                        summary.total_balance >= 0
                                            ? 'text-primary-700'
                                            : 'text-red-600'
                                    "
                                >
                                    {{ formatIDR(summary.total_balance) }}
                                </p>
                            </div>

                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                                :class="
                                    summary.total_balance >= 0
                                        ? 'bg-primary-50 text-primary-600'
                                        : 'bg-red-50 text-red-600'
                                "
                            >
                                <WalletIcon class="h-6 w-6" />
                            </span>
                        </div>

                        <p class="mt-3 text-xs text-slate-500">
                            All accounts combined
                        </p>
                    </div>

                    <div
                        class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Income
                                </p>
                                <p
                                    class="mt-2 truncate text-2xl font-bold tracking-tight text-emerald-600"
                                >
                                    {{ formatIDR(summary.month_income) }}
                                </p>
                            </div>

                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                            >
                                <ArrowTrendingUpIcon class="h-6 w-6" />
                            </span>
                        </div>

                        <p class="mt-3 text-xs text-slate-500">
                            {{ summary.month }}
                        </p>
                    </div>

                    <div
                        class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Expense
                                </p>
                                <p
                                    class="mt-2 truncate text-2xl font-bold tracking-tight text-red-600"
                                >
                                    {{ formatIDR(summary.month_expense) }}
                                </p>
                            </div>

                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600"
                            >
                                <ArrowTrendingDownIcon class="h-6 w-6" />
                            </span>
                        </div>

                        <p class="mt-3 text-xs text-slate-500">
                            {{ summary.month }}
                        </p>
                    </div>
                </div>

                <Card
                    title="Ringkasan Budget"
                    :subtitle="`Budgets aktif untuk ${summary.month}`"
                >
                    <div
                        v-if="budgets.length"
                        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    >
                        <div
                            v-for="budget in budgets"
                            :key="budget.id"
                            data-testid="dashboard-budget-card"
                            class="flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition duration-150 hover:shadow-md"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white shadow-sm"
                                        :style="{
                                            backgroundColor:
                                                budget.category?.color ||
                                                '#64748b',
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
                                            class="truncate text-sm font-medium text-slate-900"
                                        >
                                            {{
                                                budget.category?.name ?? 'Unknown'
                                            }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{
                                                budget.period === 'year'
                                                    ? 'Tahunan'
                                                    : 'Bulanan'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <span
                                    v-if="isBudgetAlmostFull(budget)"
                                    data-testid="dashboard-budget-almost-full"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700"
                                >
                                    <ExclamationTriangleIcon class="h-3 w-3" />
                                    Hampir Habis
                                </span>
                            </div>

                            <div class="mt-4">
                                <div
                                    data-testid="dashboard-budget-progress"
                                    class="h-2 w-full overflow-hidden rounded-full bg-slate-100"
                                >
                                    <div
                                        data-testid="dashboard-budget-progress-fill"
                                        class="h-full rounded-full transition-all duration-300"
                                        :class="budgetBarClasses[budget.status]"
                                        :style="{
                                            width: budgetBarWidth(budget),
                                        }"
                                    ></div>
                                </div>

                                <p class="mt-2 text-xs text-slate-500">
                                    {{ formatIDR(budget.spent) }} dari
                                    {{ formatIDR(budget.amount_limit) }}
                                    terpakai
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex flex-col items-center justify-center py-12 text-center"
                    >
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                        >
                            <BanknotesIcon class="h-6 w-6" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-slate-700">
                            Belum ada budget dibuat
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Atur batas pengeluaran per kategori agar tetap
                            terkontrol.
                        </p>
                        <Link
                            :href="route('budgets.create')"
                            class="mt-4 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        >
                            Buat Budget
                        </Link>
                    </div>
                </Card>

                <Card
                    :title="barTitles[range].title"
                    :subtitle="barTitles[range].subtitle"
                >
                    <template #actions>
                        <RangeFilter route-name="dashboard" :range="range" />
                    </template>

                    <div class="relative h-72 sm:h-80">
                        <IncomeExpenseChart :monthly="monthly" />
                    </div>
                </Card>

                <div class="grid gap-6 lg:grid-cols-2">
                    <Card
                        :title="donutTitles[categoryRange].title"
                        :subtitle="`${donutTitles[categoryRange].subtitle} (${monthLabel})`"
                    >
                        <template #actions>
                            <RangeFilter
                                route-name="dashboard"
                                param="category_range"
                                :range="categoryRange"
                            />
                        </template>

                        <div v-if="hasExpense" class="relative h-72 sm:h-80">
                            <ExpenseCategoryChart
                                :categories="category_expense"
                            />
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

                    <Card title="Recent Transactions" subtitle="Your 5 latest transactions">
                        <div
                            v-if="recent_transactions.length"
                            class="-mx-5 -my-5 divide-y divide-slate-100"
                        >
                            <div
                                v-for="transaction in recent_transactions"
                                :key="transaction.id"
                                class="flex items-center gap-3 px-5 py-3.5 transition duration-150 hover:bg-slate-50"
                            >
                                <span
                                    class="h-9 w-9 shrink-0 rounded-full"
                                    :class="
                                        transaction.category?.color
                                            ? ''
                                            : 'bg-slate-200'
                                    "
                                    :style="
                                        transaction.category?.color
                                            ? {
                                                  backgroundColor:
                                                      transaction.category.color,
                                              }
                                            : null
                                    "
                                />

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p
                                            class="truncate text-sm font-medium text-slate-900"
                                        >
                                            {{
                                                transaction.category?.name ||
                                                transaction.description ||
                                                'No description'
                                            }}
                                        </p>
                                        <span
                                            class="inline-flex shrink-0 rounded-full border px-2 py-0.5 text-[11px] font-medium"
                                            :class="
                                                typeBadgeClasses[
                                                    transaction.type
                                                ]
                                            "
                                        >
                                            {{ typeLabels[transaction.type] }}
                                        </span>
                                    </div>
                                    <p class="truncate text-xs text-slate-500">
                                        <template v-if="isTransfer(transaction)">
                                            {{ transaction.account?.name }}
                                            <span
                                                aria-hidden="true"
                                                class="text-slate-400"
                                                >→</span
                                            >
                                            {{
                                                transaction
                                                    .transfer_to_account?.name
                                            }}
                                        </template>
                                        <template v-else>
                                            {{ transaction.account?.name }}
                                        </template>
                                        ·
                                        {{
                                            formatDate(
                                                transaction.transaction_date,
                                            )
                                        }}
                                    </p>
                                </div>

                                <span
                                    class="shrink-0 text-sm font-semibold"
                                    :class="amountClass(transaction)"
                                >
                                    {{ formatIDR(transaction.amount) }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex flex-col items-center justify-center py-12 text-center"
                        >
                            <span
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                            >
                                <InboxIcon class="h-6 w-6" />
                            </span>
                            <p class="mt-3 text-sm font-medium text-slate-700">
                                No transactions yet.
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Your latest activity will show up here.
                            </p>
                            <Link
                                :href="route('transactions.create')"
                                class="mt-4 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                            >
                                Create your first transaction
                            </Link>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>