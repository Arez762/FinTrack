<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
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

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">
                Edit Recurring Transaction
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <Card
                    title="Recurring Details"
                    subtitle="Update this template; already created transactions are untouched."
                >
                    <RecurringTransactionForm
                        :recurring-transaction="recurringTransaction"
                        :accounts="accounts"
                        :categories="categories"
                    />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
