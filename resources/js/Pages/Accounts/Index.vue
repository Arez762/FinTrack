<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NavLink from '@/Components/NavLink.vue';
import { confirmDelete } from '@/Composables/useSwal';
import { Head, Link, router } from '@inertiajs/vue3';
import { WalletIcon, PlusIcon } from '@heroicons/vue/24/outline';

defineProps({
    accounts: {
        type: Array,
        required: true,
    },
});

const typeLabels = {
    cash: 'Cash',
    bank: 'Bank',
    ewallet: 'E-Wallet',
};

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const destroy = async (account) => {
    const confirmed = await confirmDelete({
        title: 'Hapus akun ini?',
        text: `Akun "${account.name}" beserta seluruh transaksinya akan dihapus.`,
        confirmButtonText: 'Ya, Hapus',
    });

    if (confirmed) {
        router.delete(route('accounts.destroy', account.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Accounts" />

        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">
                    Accounts
                </h2>

                <Link
                    :href="route('accounts.create')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-primary-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 active:bg-primary-800"
                >
                    <PlusIcon class="h-4 w-4" />
                    New Account
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
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
                                    Name
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                >
                                    Type
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                >
                                    Initial Balance
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                                >
                                    Current Balance
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
                                v-for="account in accounts"
                                :key="account.id"
                                class="transition duration-150 hover:bg-slate-50 dark:hover:bg-slate-800"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/20 text-xs font-bold uppercase text-primary-700 dark:text-primary-300"
                                        >
                                            {{ account.name.charAt(0) }}
                                        </span>
                                        <span class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                            {{ account.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    <span
                                        class="inline-flex rounded-full border border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-500/10 px-2.5 py-0.5 text-xs font-medium text-primary-700 dark:text-primary-300"
                                    >
                                        {{ typeLabels[account.type] ?? account.type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-slate-600 dark:text-slate-300">
                                    {{ formatIDR(account.initial_balance) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-right text-sm font-semibold"
                                    :class="
                                        account.balance >= 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-red-600 dark:text-red-400'
                                    "
                                >
                                    {{ formatIDR(account.balance) }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Link
                                            :href="
                                                route('transfers.create', {
                                                    from: account.id,
                                                })
                                            "
                                            class="rounded-md px-3 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 transition duration-150 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-100"
                                        >
                                            Transfer
                                        </Link>
                                        <Link
                                            :href="route('accounts.edit', account.id)"
                                            class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 dark:text-primary-400 transition duration-150 hover:bg-primary-50 dark:hover:bg-primary-500/10 hover:text-primary-800 dark:hover:text-primary-300"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="destroy(account)"
                                            class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 transition duration-150 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-800 dark:hover:text-red-300"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="accounts.length === 0">
                                <td colspan="5" class="px-6 py-16">
                                    <div
                                        class="flex flex-col items-center justify-center text-center"
                                    >
                                        <span
                                            class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500"
                                        >
                                            <WalletIcon class="h-6 w-6" />
                                        </span>
                                        <p
                                            class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200"
                                        >
                                            No accounts yet.
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Add a wallet or bank account to start
                                            tracking your money.
                                        </p>
                                        <Link
                                            :href="route('accounts.create')"
                                            class="mt-4 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                                        >
                                            Create your first account
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        </table>
                    </div>

                    <!-- Mobile list -->
                    <ul class="divide-y divide-slate-100 dark:divide-slate-700 md:hidden">
                        <li
                            v-for="account in accounts"
                            :key="`m-${account.id}`"
                            class="px-4 py-3.5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 dark:bg-primary-500/20 text-sm font-bold uppercase text-primary-700 dark:text-primary-300"
                                    >
                                        {{ account.name.charAt(0) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-medium text-slate-900 dark:text-slate-100"
                                        >
                                            {{ account.name }}
                                        </p>
                                        <span
                                            class="mt-0.5 inline-flex rounded-full border border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-500/10 px-2 py-0.5 text-[11px] font-medium text-primary-700 dark:text-primary-300"
                                        >
                                            {{
                                                typeLabels[account.type] ??
                                                account.type
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p
                                        class="text-sm font-semibold"
                                        :class="
                                            account.balance >= 0
                                                ? 'text-emerald-600 dark:text-emerald-400'
                                                : 'text-red-600 dark:text-red-400'
                                        "
                                    >
                                        {{ formatIDR(account.balance) }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                        Initial
                                        {{ formatIDR(account.initial_balance) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-end gap-1">
                                <Link
                                    :href="
                                        route('transfers.create', {
                                            from: account.id,
                                        })
                                    "
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 transition duration-150 hover:bg-slate-100 dark:hover:bg-slate-800"
                                >
                                    Transfer
                                </Link>
                                <Link
                                    :href="route('accounts.edit', account.id)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 dark:text-primary-400 transition duration-150 hover:bg-primary-50 dark:hover:bg-primary-500/10"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="destroy(account)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 transition duration-150 hover:bg-red-50 dark:hover:bg-red-500/10"
                                >
                                    Delete
                                </button>
                            </div>
                        </li>

                        <li v-if="accounts.length === 0" class="px-4 py-12">
                            <div
                                class="flex flex-col items-center justify-center text-center"
                            >
                                <span
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500"
                                >
                                    <WalletIcon class="h-6 w-6" />
                                </span>
                                <p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                                    No accounts yet.
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Add a wallet or bank account to start
                                    tracking your money.
                                </p>
                                <Link
                                    :href="route('accounts.create')"
                                    class="mt-4 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                                >
                                    Create your first account
                                </Link>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>