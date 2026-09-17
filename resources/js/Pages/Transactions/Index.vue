<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import TransactionForm from '@/Pages/Transactions/Partials/TransactionForm.vue';
import { confirmDelete } from '@/Composables/useSwal';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDownTrayIcon,
    ArrowRightIcon,
    ArrowsRightLeftIcon,
    ArrowTrendingDownIcon,
    ArrowTrendingUpIcon,
    FunnelIcon,
    PlusIcon,
    RectangleStackIcon,
} from '@heroicons/vue/24/outline';
import { computed, reactive } from 'vue';

const props = defineProps({
    transactions: {
        type: Object,
        required: true,
    },
    accounts: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    totals: {
        type: Object,
        default: () => ({
            income: 0,
            expense: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            account_id: null,
            category_id: null,
            type: null,
            date_from: null,
            date_to: null,
        }),
    },
});

const filters = reactive({
    account_id: props.filters.account_id ?? '',
    category_id: props.filters.category_id ?? '',
    type: props.filters.type ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const applyFilters = () => {
    router.get(
        route('transactions.index'),
        {
            account_id: filters.account_id || undefined,
            category_id: filters.category_id || undefined,
            type: filters.type || undefined,
            date_from: filters.date_from || undefined,
            date_to: filters.date_to || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const resetFilters = () => {
    router.get(route('transactions.index'), {}, { preserveState: true, preserveScroll: true });
};

const hasActiveFilters = () =>
    Boolean(filters.account_id || filters.category_id || filters.type || filters.date_from || filters.date_to);

const exportParams = computed(() =>
    Object.fromEntries(
        Object.entries(props.filters ?? {}).filter(
            ([, value]) => value !== null && value !== undefined && value !== '',
        ),
    ),
);

const exportUrl = (format) =>
    route(`transactions.export.${format}`, exportParams.value);

const modal = reactive({
    open: false,
    transaction: null,
});

const openCreate = () => {
    modal.transaction = null;
    modal.open = true;
};

const openEdit = (transaction) => {
    modal.transaction = {
        id: transaction.id,
        account_id: transaction.account_id,
        transfer_to_account_id: transaction.transfer_to_account_id,
        category_id: transaction.category_id,
        type: transaction.type,
        amount: transaction.amount,
        description: transaction.description,
        transaction_date: transaction.transaction_date?.slice(0, 10),
    };
    modal.open = true;
};

const closeModal = () => {
    modal.open = false;
};

const typeLabels = {
    income: 'Income',
    expense: 'Expense',
    transfer: 'Transfer',
};

const typeBadgeClasses = {
    income:
        'border-emerald-200 bg-emerald-50 text-emerald-700',
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

const goTo = (url) => {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
};

const destroy = async (transaction) => {
    const confirmed = await confirmDelete({
        title: 'Hapus transaksi ini?',
        text: `Transaksi sebesar ${formatIDR(transaction.amount)} akan dihapus.`,
        confirmButtonText: 'Ya, Hapus',
    });

    if (confirmed) {
        router.delete(route('transactions.destroy', transaction.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Transactions" />

        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">
                    Transactions
                </h2>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="exportUrl('csv')"
                        data-testid="export-csv"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Export CSV
                    </a>

                    <a
                        :href="exportUrl('pdf')"
                        data-testid="export-pdf"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Export PDF
                    </a>

                    <Link
                        :href="route('transfers.create')"
                        data-testid="new-transfer"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50"
                    >
                        <ArrowsRightLeftIcon class="h-4 w-4" />
                        Transfer
                    </Link>

                    <button
                        @click="openCreate"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-primary-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:bg-primary-800"
                    >
                        <PlusIcon class="h-4 w-4" />
                        New Transaction
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <form
                    @submit.prevent="applyFilters"
                    class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="mb-4 flex items-center gap-2">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600"
                        >
                            <FunnelIcon class="h-4 w-4" />
                        </span>
                        <h3 class="text-sm font-semibold text-slate-900">
                            Filters
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                        <div>
                            <label
                                for="filter-account"
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                            >
                                Account
                            </label>
                            <select
                                id="filter-account"
                                v-model="filters.account_id"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">All accounts</option>
                                <option
                                    v-for="account in accounts"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="filter-category"
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                            >
                                Category
                            </label>
                            <select
                                id="filter-category"
                                v-model="filters.category_id"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">All categories</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="filter-type"
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                            >
                                Type
                            </label>
                            <select
                                id="filter-type"
                                v-model="filters.type"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">All types</option>
                                <option
                                    v-for="(label, value) in typeLabels"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                for="filter-from"
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                            >
                                From Date
                            </label>
                            <input
                                id="filter-from"
                                v-model="filters.date_from"
                                type="date"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            />
                        </div>

                        <div>
                            <label
                                for="filter-to"
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                            >
                                To Date
                            </label>
                            <input
                                id="filter-to"
                                v-model="filters.date_to"
                                type="date"
                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            />
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50"
                        >
                            Reset Filter
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg border border-transparent bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:bg-primary-800"
                        >
                            Terapkan Filter
                        </button>
                    </div>
                </form>

                <div
                    v-if="hasActiveFilters()"
                    class="mb-6 grid gap-4 sm:grid-cols-2"
                >
                    <div
                        class="flex items-center gap-4 rounded-xl border border-emerald-200 bg-white px-5 py-4 shadow-sm"
                    >
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                        >
                            <ArrowTrendingUpIcon class="h-6 w-6" />
                        </span>
                        <div class="min-w-0">
                            <p
                                class="text-xs font-semibold uppercase tracking-wider text-emerald-700"
                            >
                                Total Income (filtered)
                            </p>
                            <p
                                class="mt-1 truncate text-xl font-bold text-emerald-600"
                            >
                                {{ formatIDR(totals.income) }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-4 rounded-xl border border-red-200 bg-white px-5 py-4 shadow-sm"
                    >
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600"
                        >
                            <ArrowTrendingDownIcon class="h-6 w-6" />
                        </span>
                        <div class="min-w-0">
                            <p
                                class="text-xs font-semibold uppercase tracking-wider text-red-700"
                            >
                                Total Expense (filtered)
                            </p>
                            <p class="mt-1 truncate text-xl font-bold text-red-600">
                                {{ formatIDR(totals.expense) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Date
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Type
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Account
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Category
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Description
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Amount
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr
                                v-for="transaction in transactions.data"
                                :key="transaction.id"
                                class="transition duration-150 hover:bg-slate-50"
                            >
                                <td
                                    class="whitespace-nowrap px-6 py-4 text-sm text-slate-600"
                                >
                                    {{ formatDate(transaction.transaction_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                        :class="
                                            typeBadgeClasses[transaction.type]
                                        "
                                    >
                                        {{ typeLabels[transaction.type] }}
                                    </span>
                                </td>
                                <td
                                    class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900"
                                >
                                    <span
                                        v-if="isTransfer(transaction)"
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        {{ transaction.account?.name }}
                                        <ArrowRightIcon
                                            class="h-3.5 w-3.5 text-slate-400"
                                        />
                                        {{
                                            transaction.transfer_to_account
                                                ?.name
                                        }}
                                    </span>
                                    <span v-else>
                                        {{ transaction.account?.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <span
                                        v-if="transaction.category"
                                        class="inline-flex items-center gap-2"
                                    >
                                        <span
                                            class="inline-block h-2.5 w-2.5 rounded-full"
                                            :style="{
                                                backgroundColor:
                                                    transaction.category.color ||
                                                    '#64748b',
                                            }"
                                        ></span>
                                        {{ transaction.category.name }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="max-w-[16rem] truncate px-6 py-4 text-sm text-slate-600">
                                    {{ transaction.description || '-' }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold"
                                    :class="amountClass(transaction)"
                                >
                                    {{ formatIDR(transaction.amount) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <button
                                            @click="openEdit(transaction)"
                                            class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 transition duration-150 hover:bg-primary-50 hover:text-primary-800"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="destroy(transaction)"
                                            class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 transition duration-150 hover:bg-red-50 hover:text-red-800"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="transactions.data.length === 0">
                                <td colspan="7" class="px-6 py-16">
                                    <div
                                        class="flex flex-col items-center justify-center text-center"
                                    >
                                        <span
                                            class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                                        >
                                            <RectangleStackIcon class="h-6 w-6" />
                                        </span>
                                        <p
                                            class="mt-3 text-sm font-medium text-slate-700"
                                        >
                                            No transactions yet.
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Start recording your income and
                                            expenses to see them here.
                                        </p>
                                        <button
                                            @click="openCreate"
                                            class="mt-4 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                                        >
                                            Create your first transaction
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        </table>
                    </div>

                    <!-- Mobile list -->
                    <ul class="divide-y divide-slate-100 md:hidden">
                        <li
                            v-for="transaction in transactions.data"
                            :key="`m-${transaction.id}`"
                            class="px-4 py-3.5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-2.5 w-2.5 shrink-0 rounded-full"
                                            :style="{
                                                backgroundColor:
                                                    transaction.category?.color ||
                                                    '#94a3b8',
                                            }"
                                        ></span>
                                        <p
                                            class="truncate text-sm font-medium text-slate-900"
                                        >
                                            {{
                                                transaction.category?.name ||
                                                transaction.description ||
                                                'No description'
                                            }}
                                        </p>
                                    </div>
                                    <p
                                        class="mt-1 flex flex-wrap items-center gap-x-1.5 text-xs text-slate-500"
                                    >
                                        <span>{{
                                            formatDate(
                                                transaction.transaction_date,
                                            )
                                        }}</span>
                                        <span aria-hidden="true">·</span>
                                        <span
                                            v-if="isTransfer(transaction)"
                                            class="inline-flex items-center gap-1"
                                        >
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
                                        </span>
                                        <span v-else>{{
                                            transaction.account?.name
                                        }}</span>
                                    </p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p
                                        class="text-sm font-semibold"
                                        :class="amountClass(transaction)"
                                    >
                                        {{ formatIDR(transaction.amount) }}
                                    </p>
                                    <span
                                        class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-[11px] font-medium"
                                        :class="
                                            typeBadgeClasses[transaction.type]
                                        "
                                    >
                                        {{ typeLabels[transaction.type] }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-end gap-1">
                                <button
                                    @click="openEdit(transaction)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 transition duration-150 hover:bg-primary-50"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="destroy(transaction)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 transition duration-150 hover:bg-red-50"
                                >
                                    Delete
                                </button>
                            </div>
                        </li>

                        <li
                            v-if="transactions.data.length === 0"
                            class="px-4 py-12"
                        >
                            <div
                                class="flex flex-col items-center justify-center text-center"
                            >
                                <span
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                                >
                                    <RectangleStackIcon class="h-6 w-6" />
                                </span>
                                <p class="mt-3 text-sm font-medium text-slate-700">
                                    No transactions yet.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Start recording your income and expenses.
                                </p>
                                <button
                                    @click="openCreate"
                                    class="mt-4 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                                >
                                    Create your first transaction
                                </button>
                            </div>
                        </li>
                    </ul>

                    <div
                        v-if="transactions.total > transactions.per_page"
                        class="flex items-center justify-between border-t border-slate-200 px-6 py-3"
                    >
                        <p class="text-sm text-slate-600">
                            Page {{ transactions.current_page }} of
                            {{ transactions.last_page }}
                        </p>

                        <div class="flex gap-2">
                            <button
                                @click="goTo(transactions.prev_page_url)"
                                :disabled="!transactions.prev_page_url"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Previous
                            </button>
                            <button
                                @click="goTo(transactions.next_page_url)"
                                :disabled="!transactions.next_page_url"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="modal.open" @close="closeModal" max-width="2xl">
            <div class="max-h-[85vh] overflow-y-auto">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900">
                        {{ modal.transaction ? 'Edit Transaction' : 'New Transaction' }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Record an income, expense, or transfer.
                    </p>
                </div>

                <div class="p-6">
                    <TransactionForm
                        v-if="modal.open"
                        :transaction="modal.transaction"
                        :accounts="accounts"
                        :categories="categories"
                        modal
                        @close="closeModal"
                        @success="closeModal"
                    />
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>