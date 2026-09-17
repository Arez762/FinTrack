<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit Account
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-medium text-gray-900">
                            Account Details
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Update the details of this account.
                        </p>
                    </div>

                    <div class="p-6">
                        <AccountForm :account="account" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>