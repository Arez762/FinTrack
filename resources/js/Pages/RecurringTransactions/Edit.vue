<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import RecurringTransactionForm from '@/Pages/RecurringTransactions/Partials/RecurringTransactionForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    recurringTransaction: {
        type: Object,
        required: true,
    },
    accounts: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit Recurring Transaction" />

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Edit Recurring Transaction"
                    subtitle="Update this template; already created transactions are untouched."
                />

                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <RecurringTransactionForm
                        :recurring-transaction="recurringTransaction"
                        :accounts="accounts"
                        :categories="categories"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
