<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import CategoryForm from '@/Pages/Categories/Partials/CategoryForm.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Category" />

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader title="Create Category" subtitle="Add a new income or expense category." />

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <CategoryForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>