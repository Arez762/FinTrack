<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AccountForm from '@/Pages/Accounts/Partials/AccountForm.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Account" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-slate-100">
                Create Account
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white dark:bg-slate-800 shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 dark:border-slate-700 px-6 py-4">
                        <h3 class="text-base font-medium text-gray-900 dark:text-slate-100">
                            Account Details
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">
                            Add a new cash, bank, or e-wallet account.
                        </p>
                    </div>

                    <div class="p-6">
                        <AccountForm />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>