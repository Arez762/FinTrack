<script setup>
import Card from '@/Components/Card.vue';
import PageHeader from '@/Components/PageHeader.vue';
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

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="New Savings Goal"
                    subtitle="Tentukan target nominal dan kapan kamu ingin mencapainya."
                />

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