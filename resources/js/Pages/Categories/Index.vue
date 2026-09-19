<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { confirmDelete } from '@/Composables/useSwal';
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusIcon, TagIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
});

const grouped = computed(() => ({
    income: props.categories.filter((category) => category.type === 'income'),
    expense: props.categories.filter((category) => category.type === 'expense'),
}));

const destroy = async (category) => {
    const confirmed = await confirmDelete({
        title: 'Hapus kategori ini?',
        text: `Kategori "${category.name}" akan dihapus.`,
        confirmButtonText: 'Ya, Hapus',
    });

    if (confirmed) {
        router.delete(route('categories.destroy', category.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Categories" />

        <div class="py-8">
            <div
                class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 md:grid-cols-2 lg:px-8"
            >
                <div class="md:col-span-2">
                    <PageHeader title="Categories" subtitle="Organize your income and expenses with categories.">
                        <template #actions>
                            <Link
                                :href="route('categories.create')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-primary-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 active:bg-primary-800"
                            >
                                <PlusIcon class="h-4 w-4" />
                                New Category
                            </Link>
                        </template>
                    </PageHeader>
                </div>

                <div v-for="group in ['expense', 'income']" :key="group">
                    <div class="mb-3 flex items-center justify-between">
                        <h3
                            class="text-xs font-semibold uppercase tracking-wider"
                            :class="
                                group === 'expense'
                                    ? 'text-red-700 dark:text-red-400'
                                    : 'text-emerald-700 dark:text-emerald-400'
                            "
                        >
                            {{ group === 'expense' ? 'Expenses' : 'Income' }}
                        </h3>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="
                                group === 'expense'
                                    ? 'bg-red-50 dark:bg-red-500/20 text-red-700 dark:text-red-400'
                                    : 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400'
                            "
                        >
                            {{ grouped[group].length }} category{{
                                grouped[group].length === 1 ? '' : 'ies'
                            }}
                        </span>
                    </div>

                    <div
                        class="divide-y divide-slate-100 dark:divide-slate-700 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm"
                    >
                        <div
                            v-for="category in grouped[group]"
                            :key="category.id"
                            class="flex items-center justify-between gap-4 px-5 py-3.5 transition duration-150 hover:bg-slate-50 dark:hover:bg-slate-800"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-bold text-white shadow-sm"
                                    :style="{
                                        backgroundColor: category.color || '#64748b',
                                    }"
                                >
                                    {{ category.name.charAt(0).toUpperCase() }}
                                </span>
                                <span class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">
                                    {{ category.name }}
                                </span>
                            </div>

                            <div class="flex shrink-0 items-center gap-1 text-sm">
                                <Link
                                    :href="route('categories.edit', category.id)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 dark:text-primary-400 transition duration-150 hover:bg-primary-50 dark:hover:bg-primary-500/10 hover:text-primary-800 dark:hover:text-primary-300"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="destroy(category)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400 transition duration-150 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-800 dark:hover:text-red-300"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="grouped[group].length === 0"
                            class="px-5 py-12"
                        >
                            <EmptyState :icon="TagIcon" :title="`No ${group} categories yet.`" :description="`Group your ${group} transactions with a category.`" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>