<script setup>
import PageHeader from '@/Components/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TransactionForm from '@/Pages/Transactions/Partials/TransactionForm.vue';
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
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Transaction" />

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Create Transaction"
                    subtitle="Record an income, expense, or transfer."
                />

                <div
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <TransactionForm
                        :accounts="accounts"
                        :categories="categories"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>