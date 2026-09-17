<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TransactionForm from '@/Pages/Transactions/Partials/TransactionForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    transaction: {
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
        <Head title="Edit Transaction" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit Transaction
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-medium text-gray-900">
                            Transaction Details
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Update the details of this transaction.
                        </p>
                    </div>

                    <div class="p-6">
                        <TransactionForm
                            :transaction="transaction"
                            :accounts="accounts"
                            :categories="categories"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>