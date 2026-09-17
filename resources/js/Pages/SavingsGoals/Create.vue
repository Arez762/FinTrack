<script setup>
import Card from '@/Components/Card.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SavingsGoalForm from '@/Pages/SavingsGoals/Partials/SavingsGoalForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    accounts: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="New Savings Goal" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">
                New Savings Goal
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <Card
                    title="Detail Target"
                    subtitle="Tentukan target nominal dan kapan kamu ingin mencapainya."
                >
                    <SavingsGoalForm :accounts="accounts" />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>