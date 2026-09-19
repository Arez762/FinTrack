<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import RecurringTransactionForm from '@/Pages/RecurringTransactions/Partials/RecurringTransactionForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    accounts: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
    defaults: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New Recurring Transaction" />

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="New Recurring Transaction"
                    subtitle="Set up a template that repeats automatically."
                />

                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <RecurringTransactionForm
                        :accounts="accounts"
                        :categories="categories"
                        :defaults="defaults"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
