<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AccountForm from '@/Pages/Accounts/Partials/AccountForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    account: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit Account" />

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader title="Edit Account" subtitle="Update the details of this account." />

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <AccountForm :account="account" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
