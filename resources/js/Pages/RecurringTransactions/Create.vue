<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
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

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">
                New Recurring Transaction
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <Card
                    title="Recurring Details"
                    subtitle="finTrack will create this transaction automatically on every run."
                >
                    <RecurringTransactionForm
                        :accounts="accounts"
                        :categories="categories"
                        :defaults="defaults"
                    />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
