<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import TransferForm from '@/Pages/Transfers/Partials/TransferForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    accounts: {
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
        <Head title="New Transfer" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">
                New Transfer
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <Card
                    title="Transfer Details"
                    subtitle="Move money between two of your own accounts."
                >
                    <TransferForm
                        :accounts="accounts"
                        :defaults="defaults"
                    />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
