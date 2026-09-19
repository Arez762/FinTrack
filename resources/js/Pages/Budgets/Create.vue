<script setup>
import Card from '@/Components/Card.vue';
import PageHeader from '@/Components/PageHeader.vue';
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

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader title="New Budget" subtitle="Set a spending limit for one of your expense categories." />

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
