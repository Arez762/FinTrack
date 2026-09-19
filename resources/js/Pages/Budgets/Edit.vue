<script setup>
import Card from '@/Components/Card.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BudgetForm from '@/Pages/Budgets/Partials/BudgetForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    budget: {
        type: Object,
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
        <Head title="Edit Budget" />

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader title="Edit Budget" subtitle="Update the limit or the period of this budget." />

                <Card
                    title="Budget Details"
                    subtitle="Update the limit or the period of this budget."
                >
                    <BudgetForm
                        :budget="budget"
                        :categories="categories"
                        :defaults="defaults"
                    />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
