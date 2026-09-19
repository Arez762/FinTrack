<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { confirmAction, confirmDelete } from '@/Composables/useSwal';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowPathIcon,
    PauseIcon,
    PlayIcon,
    PlusIcon,
    RectangleStackIcon,
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    recurringTransactions: {
        type: Array,
        required: true,
    },
});

const activeCount = computed(
    () => props.recurringTransactions.filter((item) => item.is_active).length,
);

const frequencyLabels = {
    daily: 'Daily',
    weekly: 'Weekly',
    monthly: 'Monthly',
    yearly: 'Yearly',
};

const frequencyHints = {
    daily: 'Every day',
    weekly: 'Every week',
    monthly: 'Every month',
    yearly: 'Every year',
};

const typeLabels = {
    income: 'Income',
    expense: 'Expense',
};

const typeBadgeClasses = {
    income: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400',
    expense: 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-500/10 dark:text-red-400',
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

const amountClass = (item) =>
    item.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';

const title = (item) =>
    item.description || item.category?.name || 'Recurring transaction';

const toggleActive = async (item) => {
    const confirmed = await confirmAction({
        icon: item.is_active ? 'warning' : 'question',
        title: item.is_active
            ? 'Jeda transaksi berulang ini?'
            : 'Aktifkan kembali transaksi berulang ini?',
        text: `"${title(item)}" akan ${
            item.is_active ? 'dijeda' : 'diaktifkan kembali'
        }.`,
        confirmButtonText: item.is_active ? 'Ya, Jeda' : 'Ya, Aktifkan',
    });

    if (confirmed) {
        router.patch(route('recurring-transactions.toggle', item.id));
    }
};

const destroy = async (item) => {
    const confirmed = await confirmDelete({
        title: 'Hapus transaksi berulang ini?',
        text: `"${title(item)}" akan dihapus.`,
        confirmButtonText: 'Ya, Hapus',
    });

    if (confirmed) {
        router.delete(route('recurring-transactions.destroy', item.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Recurring Transactions" />

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Recurring Transactions"
                    subtitle="Manage templates that auto-create transactions on schedule."
                >
                    <template #actions>
                        <Link
                            :href="route('recurring-transactions.create')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-primary-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:bg-primary-800 dark:focus:ring-offset-slate-900"
                        >
                            <PlusIcon class="h-4 w-4" />
                            New Recurring
                        </Link>
                    </template>
                </PageHeader>
                <div
                    class="flex items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3.5 shadow-sm sm:px-5"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/20 dark:text-primary-400"
                    >
                        <ArrowPathIcon class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-100">
                            {{ activeCount }} active of
                            {{ recurringTransactions.length }} template(s)
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Due templates are turned into transactions
                            automatically once per day. Pause one to stop it
                            without losing the setup.
                        </p>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm"
                >
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-800">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                    >
                                        Description
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                    >
                                        Account
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                    >
                                        Category
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                    >
                                        Frequency
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                    >
                                        Next Run
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                    >
                                        Status
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                    >
                                        Amount
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                <tr
                                    v-for="item in recurringTransactions"
                                    :key="item.id"
                                    data-testid="recurring-row"
                                    class="transition duration-150 hover:bg-slate-50 dark:hover:bg-slate-800"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white shadow-sm"
                                                :style="{
                                                    backgroundColor:
                                                        item.category?.color ||
                                                        '#94a3b8',
                                                }"
                                            >
                                                {{
                                                    (
                                                        item.category?.name ??
                                                        '?'
                                                    )
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </span>
                                            <div class="min-w-0">
                                                <p
                                                    class="max-w-[16rem] truncate text-sm font-medium text-slate-900 dark:text-slate-100"
                                                >
                                                    {{ title(item) }}
                                                </p>
                                                <span
                                                    class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-[11px] font-medium"
                                                    :class="
                                                        typeBadgeClasses[
                                                            item.type
                                                        ]
                                                    "
                                                >
                                                    {{ typeLabels[item.type] }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-slate-600 dark:text-slate-300"
                                    >
                                        {{ item.account?.name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                        {{ item.category?.name ?? '-' }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-slate-600 dark:text-slate-300"
                                    >
                                        {{ frequencyLabels[item.frequency] }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-slate-600 dark:text-slate-300"
                                    >
                                        {{ formatDate(item.next_run_date) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            data-testid="status-badge"
                                            class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium"
                                            :class="
                                                item.is_active
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400'
                                                    : 'border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'
                                            "
                                        >
                                            {{
                                                item.is_active
                                                    ? 'Active'
                                                    : 'Paused'
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold"
                                        :class="amountClass(item)"
                                    >
                                        {{ formatIDR(item.amount) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <div
                                            class="flex items-center justify-end gap-1"
                                        >
                                            <button
                                                data-testid="toggle-active"
                                                @click="toggleActive(item)"
                                                class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-xs font-semibold transition duration-150"
                                                :class="
                                                    item.is_active
                                                        ? 'text-amber-600 hover:bg-amber-50 hover:text-amber-800'
                                                        : 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 hover:text-emerald-800'
                                                "
                                            >
                                                <PauseIcon
                                                    v-if="item.is_active"
                                                    class="h-3.5 w-3.5"
                                                />
                                                <PlayIcon
                                                    v-else
                                                    class="h-3.5 w-3.5"
                                                />
                                                {{
                                                    item.is_active
                                                        ? 'Pause'
                                                        : 'Resume'
                                                }}
                                            </button>
                                            <Link
                                                :href="
                                                    route(
                                                        'recurring-transactions.edit',
                                                        item.id,
                                                    )
                                                "
                                                class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 dark:text-primary-400 transition duration-150 hover:bg-primary-50 dark:hover:bg-primary-500/10 hover:text-primary-800 dark:hover:text-primary-300"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                @click="destroy(item)"
                                                class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400 transition duration-150 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-800 dark:hover:text-red-300"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="recurringTransactions.length === 0">
                                    <td colspan="8">
                                        <EmptyState
                                            :icon="ArrowPathIcon"
                                            title="No recurring transactions yet."
                                            description="Automate your regular income and expenses."
                                        >
                                            <template #action>
                                                <Link
                                                    :href="route('recurring-transactions.create')"
                                                    class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                                                >
                                                    Create your first recurring transaction
                                                </Link>
                                            </template>
                                        </EmptyState>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile list -->
                    <ul class="divide-y divide-slate-100 dark:divide-slate-700 md:hidden">
                        <li
                            v-for="item in recurringTransactions"
                            :key="`m-${item.id}`"
                            data-testid="recurring-card"
                            class="px-4 py-3.5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-2.5 w-2.5 shrink-0 rounded-full"
                                            :style="{
                                                backgroundColor:
                                                    item.category?.color ||
                                                    '#94a3b8',
                                            }"
                                        ></span>
                                        <p
                                            class="truncate text-sm font-medium text-slate-900 dark:text-slate-100"
                                        >
                                            {{ title(item) }}
                                        </p>
                                    </div>
                                    <p
                                        class="mt-1 flex flex-wrap items-center gap-x-1.5 text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        <span>{{
                                            frequencyHints[item.frequency]
                                        }}</span>
                                        <span aria-hidden="true">·</span>
                                        <span>{{
                                            item.account?.name ?? '-'
                                        }}</span>
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Next:
                                        {{ formatDate(item.next_run_date) }}
                                    </p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p
                                        class="text-sm font-semibold"
                                        :class="amountClass(item)"
                                    >
                                        {{ formatIDR(item.amount) }}
                                    </p>
                                    <span
                                        data-testid="status-badge"
                                        class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-[11px] font-medium"
                                        :class="
                                            item.is_active
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400'
                                                : 'border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'
                                        "
                                    >
                                        {{
                                            item.is_active ? 'Active' : 'Paused'
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-end gap-1">
                                <button
                                    data-testid="toggle-active"
                                    @click="toggleActive(item)"
                                    class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-xs font-semibold transition duration-150"
                                    :class="
                                        item.is_active
                                            ? 'text-amber-600 hover:bg-amber-50'
                                            : 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50'
                                    "
                                >
                                    <PauseIcon
                                        v-if="item.is_active"
                                        class="h-3.5 w-3.5"
                                    />
                                    <PlayIcon v-else class="h-3.5 w-3.5" />
                                    {{ item.is_active ? 'Pause' : 'Resume' }}
                                </button>
                                <Link
                                    :href="
                                        route(
                                            'recurring-transactions.edit',
                                            item.id,
                                        )
                                    "
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 dark:text-primary-400 transition duration-150 hover:bg-primary-50 dark:hover:bg-primary-500/10"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="destroy(item)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400 transition duration-150 hover:bg-red-50"
                                >
                                    Delete
                                </button>
                            </div>
                        </li>

                        <li
                            v-if="recurringTransactions.length === 0"
                            class="px-4 py-12"
                        >
                            <EmptyState
                                :icon="RectangleStackIcon"
                                title="No recurring transactions yet."
                                description="Automate your regular income and expenses."
                            >
                                <template #action>
                                    <Link
                                        :href="route('recurring-transactions.create')"
                                        class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                                    >
                                        Create your first recurring transaction
                                    </Link>
                                </template>
                            </EmptyState>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
