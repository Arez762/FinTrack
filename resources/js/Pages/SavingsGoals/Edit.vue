<script setup>
import Card from '@/Components/Card.vue';
import PageHeader from '@/Components/PageHeader.vue';
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

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Edit Savings Goal"
                    subtitle="Perbarui informasi target tabunganmu."
                />

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