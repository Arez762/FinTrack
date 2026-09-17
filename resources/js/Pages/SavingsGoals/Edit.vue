<script setup>
import Card from '@/Components/Card.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SavingsGoalForm from '@/Pages/SavingsGoals/Partials/SavingsGoalForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    savingsGoal: {
        type: Object,
        required: true,
    },
    accounts: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit Savings Goal" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">
                Edit Savings Goal
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <Card
                    title="Detail Target"
                    subtitle="Perbarui informasi target tabunganmu."
                >
                    <SavingsGoalForm
                        :savings-goal="savingsGoal"
                        :accounts="accounts"
                    />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>