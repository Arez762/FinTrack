<script setup>
import Card from '@/Components/Card.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BudgetForm from '@/Pages/Budgets/Partials/BudgetForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
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
        <Head title="New Budget" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">
                New Budget
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <Card
                    title="Budget Details"
                    subtitle="Set a spending limit for one of your expense categories."
                >
                    <BudgetForm :categories="categories" :defaults="defaults" />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
