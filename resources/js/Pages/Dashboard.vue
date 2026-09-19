<script setup>
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
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
    CurrencyDollarIcon,
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
    account_count: {
        type: Number,
        default: 0,
    },
    budget_count: {
        type: Number,
        default: 0,
    },
    goal_summary: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            average_progress: 0,
        }),
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
    income: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400',
    expense: 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-500/10 dark:text-red-400',
    transfer: 'border-slate-300 bg-slate-100 text-slate-600 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300',
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
        ? 'text-emerald-600 dark:text-emerald-400'
        : transaction.type === 'expense'
          ? 'text-red-600 dark:text-red-400'
          : 'text-slate-600 dark:text-slate-300';

const isTransfer = (transaction) =>
    transaction.type === 'transfer' && !!transaction.transfer_to_account;

const hasExpense = computed(() => props.category_expense.length > 0);

const todayLabel = computed(() =>
    new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date()),
);

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

        <div class="py-4 md:py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    class="hidden md:flex"
                    title="Dashboard"
                    :subtitle="`Ringkasan keuanganmu untuk ${summary.month}`"
                />

                <div class="md:hidden">
                    <h1
                        class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                    >
                        Dashboard
                    </h1>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ todayLabel }}
                        </p>
                        <div class="ml-auto flex items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-500/20 dark:text-primary-400"
                            >
                                {{ account_count }} Akun
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400"
                            >
                                {{ budget_count }} Anggaran
                            </span>
                        </div>
                    </div>
                </div>

                <Link
                    v-if="budget_alerts.length"
                    :href="route('budgets.index')"
                    data-testid="budget-alert"
                    class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 shadow-sm transition duration-150 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-900/20 dark:hover:bg-amber-900/40 sm:px-5"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400"
                    >
                        <ExclamationTriangleIcon class="h-5 w-5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                            {{ budget_alerts.length }} budget{{
                                budget_alerts.length === 1 ? '' : 's'
                            }}
                            need{{ budget_alerts.length === 1 ? 's' : '' }}
                            attention
                        </p>
                        <p class="mt-0.5 truncate text-xs text-amber-700 dark:text-amber-300">
                            {{ alertSummary }}
                        </p>
                    </div>

                    <span
                        class="shrink-0 self-center text-xs font-semibold text-amber-700 dark:text-amber-300"
                    >
                        View
                    </span>
                </Link>

                <div class="space-y-4 md:hidden">
                    <div
                        class="rounded-2xl bg-white p-5 dark:bg-slate-800"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <p
                                class="text-sm font-medium text-slate-500 dark:text-slate-400"
                            >
                                Total Saldo
                            </p>
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-600 dark:bg-primary-500/20 dark:text-primary-400"
                            >
                                <WalletIcon class="h-6 w-6" />
                            </span>
                        </div>
                        <p
                            class="mt-3 truncate text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                            :class="
                                summary.total_balance >= 0
                                    ? 'text-slate-900 dark:text-white'
                                    : 'text-red-600 dark:text-red-400'
                            "
                        >
                            {{ formatIDR(summary.total_balance) }}
                        </p>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            {{ account_count }} akun terdaftar
                        </p>
                    </div>

                    <div
                        class="rounded-2xl bg-white p-5 dark:bg-slate-800"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <p
                                class="text-sm font-medium text-slate-500 dark:text-slate-400"
                            >
                                Pemasukan
                            </p>
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                            >
                                <ArrowTrendingUpIcon class="h-6 w-6" />
                            </span>
                        </div>
                        <p
                            class="mt-3 truncate text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400"
                        >
                            {{ formatIDR(summary.month_income) }}
                        </p>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            Bulan ini
                        </p>
                    </div>

                    <div
                        class="rounded-2xl bg-white p-5 dark:bg-slate-800"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <p
                                class="text-sm font-medium text-slate-500 dark:text-slate-400"
                            >
                                Pengeluaran
                            </p>
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400"
                            >
                                <ArrowTrendingDownIcon class="h-6 w-6" />
                            </span>
                        </div>
                        <p
                            class="mt-3 truncate text-2xl font-bold tracking-tight text-red-600 dark:text-red-400"
                        >
                            {{ formatIDR(summary.month_expense) }}
                        </p>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            Bulan ini
                        </p>
                    </div>
                </div>

                <div class="hidden gap-4 sm:grid-cols-2 md:grid lg:grid-cols-3">
                    <div
                        class="relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm transition duration-150 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                >
                                    Total Balance
                                </p>
                                <p
                                    class="mt-2 truncate text-2xl font-bold tracking-tight"
                                    :class="
                                        summary.total_balance >= 0
                                            ? 'text-primary-700 dark:text-primary-400'
                                            : 'text-red-600 dark:text-red-400'
                                    "
                                >
                                    {{ formatIDR(summary.total_balance) }}
                                </p>
                            </div>

                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                                :class="
                                    summary.total_balance >= 0
                                        ? 'bg-primary-50 text-primary-600 dark:bg-primary-500/20 dark:text-primary-400'
                                        : 'bg-red-50 text-red-600 dark:bg-red-500/20 dark:text-red-400'
                                "
                            >
                                <WalletIcon class="h-6 w-6" />
                            </span>
                        </div>

                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            All accounts combined
                        </p>
                    </div>

                    <div
                        class="relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm transition duration-150 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
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
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                            >
                                <ArrowTrendingUpIcon class="h-6 w-6" />
                            </span>
                        </div>

                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            {{ summary.month }}
                        </p>
                    </div>

                    <div
                        class="relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm transition duration-150 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
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
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/20 dark:text-red-400"
                            >
                                <ArrowTrendingDownIcon class="h-6 w-6" />
                            </span>
                        </div>

                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
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
                            class="flex flex-col rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-4 shadow-sm transition duration-150 hover:shadow-md"
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
                                            class="truncate text-sm font-medium text-slate-900 dark:text-slate-100"
                                        >
                                            {{
                                                budget.category?.name ?? 'Unknown'
                                            }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
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
                                    class="inline-flex shrink-0 items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300"
                                >
                                    <ExclamationTriangleIcon class="h-3 w-3" />
                                    Hampir Habis
                                </span>
                            </div>

                            <div class="mt-4">
                                <div
                                    data-testid="dashboard-budget-progress"
                                    class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
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

                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    {{ formatIDR(budget.spent) }} dari
                                    {{ formatIDR(budget.amount_limit) }}
                                    terpakai
                                </p>
                            </div>
                        </div>
                    </div>

                    <EmptyState
                        v-else
                        :icon="BanknotesIcon"
                        title="Belum ada budget dibuat"
                        description="Atur batas pengeluaran per kategori agar tetap terkontrol."
                    >
                        <template #action>
                            <Link
                                :href="route('budgets.create')"
                                class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                            >
                                Buat Budget
                            </Link>
                        </template>
                    </EmptyState>
                </Card>

                <Card
                    title="Ringkasan Tabungan"
                    :subtitle="`${goal_summary.active} target aktif dari ${goal_summary.total} total`"
                >
                    <template #actions>
                        <Link
                            :href="route('savings-goals.index')"
                            class="inline-flex items-center rounded-lg border border-primary-200 bg-white dark:border-primary-800 dark:bg-slate-800 px-3 py-1.5 text-xs font-semibold text-primary-700 dark:text-primary-300 shadow-sm transition duration-150 ease-in-out hover:bg-primary-50 dark:hover:bg-primary-500/10"
                        >
                            Kelola
                        </Link>
                    </template>

                    <div
                        v-if="goal_summary.total"
                        class="flex items-center gap-4"
                    >
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/20 dark:text-primary-400"
                        >
                            <CurrencyDollarIcon class="h-6 w-6" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Rata-rata progress semua target
                            </p>
                            <div
                                class="mt-1.5 flex items-center gap-3"
                            >
                                <div
                                    class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary-500 transition-all duration-300"
                                        :style="{
                                            width: `${Math.min(
                                                100,
                                                Math.max(
                                                    0,
                                                    goal_summary.average_progress,
                                                ),
                                            )}%`,
                                        }"
                                    ></div>
                                </div>
                                <span class="text-sm font-bold text-primary-700 dark:text-primary-300">
                                    {{ Math.round(goal_summary.average_progress) }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <EmptyState
                        v-else
                        :icon="CurrencyDollarIcon"
                        title="Belum ada target tabungan"
                        description="Mulai menabung untuk impianmu."
                    >
                        <template #action>
                            <Link
                                :href="route('savings-goals.create')"
                                class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                            >
                                Buat Target
                            </Link>
                        </template>
                    </EmptyState>
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
                        <EmptyState
                            v-else
                            :icon="ChartPieIcon"
                            title="No expense recorded in this period yet."
                            description="Try switching the period above."
                        />
                    </Card>

                    <Card title="Recent Transactions" subtitle="Your 5 latest transactions">
                        <div
                            v-if="recent_transactions.length"
                            class="-mx-5 -my-5 divide-y divide-slate-100 dark:divide-slate-700"
                        >
                            <div
                                v-for="transaction in recent_transactions"
                                :key="transaction.id"
                                class="flex items-center gap-3 px-5 py-3.5 transition duration-150 hover:bg-slate-50 dark:hover:bg-slate-700"
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
                                            class="truncate text-sm font-medium text-slate-900 dark:text-slate-100"
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
                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                        <template v-if="isTransfer(transaction)">
                                            {{ transaction.account?.name }}
                                            <span
                                                aria-hidden="true"
                                                class="text-slate-400 dark:text-slate-500"
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

                        <EmptyState
                            v-else
                            :icon="InboxIcon"
                            title="No transactions yet."
                            description="Your latest activity will show up here."
                        >
                            <template #action>
                                <Link
                                    :href="route('transactions.create')"
                                    class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                                >
                                    Create your first transaction
                                </Link>
                            </template>
                        </EmptyState>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>