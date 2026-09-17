<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { confirmDelete } from '@/Composables/useSwal';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    ArrowsRightLeftIcon,
    PlusIcon,
    RectangleStackIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    transfers: {
        type: Object,
        required: true,
    },
});

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

const goTo = (url) => {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
};

const destroy = async (transfer) => {
    const confirmed = await confirmDelete({
        title: 'Hapus transfer ini?',
        text: `Transfer sebesar ${formatIDR(transfer.amount)} akan dihapus.`,
        confirmButtonText: 'Ya, Hapus',
    });

    if (confirmed) {
        router.delete(route('transactions.destroy', transfer.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Transfers" />

        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">
                    Transfers
                </h2>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('transactions.index')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50"
                    >
                        All Transactions
                    </Link>

                    <Link
                        :href="route('transfers.create')"
                        data-testid="new-transfer"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-primary-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:bg-primary-800"
                    >
                        <PlusIcon class="h-4 w-4" />
                        New Transfer
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm sm:px-5"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600"
                    >
                        <ArrowsRightLeftIcon class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-slate-800">
                            {{ transfers.total }} transfer(s) recorded
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Transfers move money between your own accounts and
                            never change your total balance.
                        </p>
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
                                        From
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                    >
                                        To
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
                                    v-for="transfer in transfers.data"
                                    :key="transfer.id"
                                    data-testid="transfer-row"
                                    class="transition duration-150 hover:bg-slate-50"
                                >
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-slate-600"
                                    >
                                        {{
                                            formatDate(
                                                transfer.transaction_date,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900"
                                    >
                                        {{ transfer.account?.name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <ArrowRightIcon
                                                class="h-3.5 w-3.5 text-slate-400"
                                            />
                                            {{
                                                transfer.transfer_to_account
                                                    ?.name ?? '-'
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="max-w-[16rem] truncate px-6 py-4 text-sm text-slate-600"
                                    >
                                        {{ transfer.description || '-' }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-700"
                                    >
                                        {{ formatIDR(transfer.amount) }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right text-sm"
                                    >
                                        <div
                                            class="flex items-center justify-end gap-1"
                                        >
                                            <Link
                                                :href="
                                                    route(
                                                        'transactions.index',
                                                        { type: 'transfer' },
                                                    )
                                                "
                                                class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 transition duration-150 hover:bg-primary-50 hover:text-primary-800"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                @click="destroy(transfer)"
                                                class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 transition duration-150 hover:bg-red-50 hover:text-red-800"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="transfers.data.length === 0">
                                    <td colspan="6" class="px-6 py-16">
                                        <div
                                            class="flex flex-col items-center justify-center text-center"
                                        >
                                            <span
                                                class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                                            >
                                                <ArrowRightIcon
                                                    class="h-6 w-6"
                                                />
                                            </span>
                                            <p
                                                class="mt-3 text-sm font-medium text-slate-700"
                                            >
                                                No transfers yet.
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-slate-500"
                                            >
                                                Move money between your accounts
                                                to see it here.
                                            </p>
                                            <Link
                                                :href="
                                                    route('transfers.create')
                                                "
                                                class="mt-4 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                                            >
                                                Create your first transfer
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile list -->
                    <ul class="divide-y divide-slate-100 md:hidden">
                        <li
                            v-for="transfer in transfers.data"
                            :key="`m-${transfer.id}`"
                            data-testid="transfer-card"
                            class="px-4 py-3.5"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
                                        >
                                            <ArrowsRightLeftIcon
                                                class="h-4 w-4"
                                            />
                                        </span>
                                        <p
                                            class="truncate text-sm font-medium text-slate-900"
                                        >
                                            {{ transfer.account?.name ?? '-' }}
                                            <span
                                                aria-hidden="true"
                                                class="text-slate-400"
                                                >→</span
                                            >
                                            {{
                                                transfer.transfer_to_account
                                                    ?.name ?? '-'
                                            }}
                                        </p>
                                    </div>
                                    <p
                                        class="mt-1 flex flex-wrap items-center gap-x-1.5 text-xs text-slate-500"
                                    >
                                        <span>{{
                                            formatDate(
                                                transfer.transaction_date,
                                            )
                                        }}</span>
                                        <template
                                            v-if="transfer.description"
                                        >
                                            <span aria-hidden="true">·</span>
                                            <span class="truncate">{{
                                                transfer.description
                                            }}</span>
                                        </template>
                                    </p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p
                                        class="text-sm font-semibold text-slate-700"
                                    >
                                        {{ formatIDR(transfer.amount) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-end gap-1">
                                <Link
                                    :href="
                                        route('transactions.index', {
                                            type: 'transfer',
                                        })
                                    "
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 transition duration-150 hover:bg-primary-50"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="destroy(transfer)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 transition duration-150 hover:bg-red-50"
                                >
                                    Delete
                                </button>
                            </div>
                        </li>

                        <li
                            v-if="transfers.data.length === 0"
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
                                    No transfers yet.
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Move money between your accounts.
                                </p>
                                <Link
                                    :href="route('transfers.create')"
                                    class="mt-4 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                                >
                                    Create your first transfer
                                </Link>
                            </div>
                        </li>
                    </ul>

                    <div
                        v-if="transfers.total > transfers.per_page"
                        class="flex items-center justify-between border-t border-slate-200 px-6 py-3"
                    >
                        <p class="text-sm text-slate-600">
                            Page {{ transfers.current_page }} of
                            {{ transfers.last_page }}
                        </p>

                        <div class="flex gap-2">
                            <button
                                @click="goTo(transfers.prev_page_url)"
                                :disabled="!transfers.prev_page_url"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Previous
                            </button>
                            <button
                                @click="goTo(transfers.next_page_url)"
                                :disabled="!transfers.next_page_url"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
