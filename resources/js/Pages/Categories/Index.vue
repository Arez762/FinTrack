<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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

        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">
                    Categories
                </h2>

                <Link
                    :href="route('categories.create')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-primary-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:bg-primary-800"
                >
                    <PlusIcon class="h-4 w-4" />
                    New Category
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div
                class="mx-auto grid max-w-7xl gap-6 sm:px-6 md:grid-cols-2 lg:px-8"
            >
                <div v-for="group in ['expense', 'income']" :key="group">
                    <div class="mb-3 flex items-center justify-between">
                        <h3
                            class="text-xs font-semibold uppercase tracking-wider"
                            :class="
                                group === 'expense'
                                    ? 'text-red-700'
                                    : 'text-emerald-700'
                            "
                        >
                            {{ group === 'expense' ? 'Expenses' : 'Income' }}
                        </h3>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="
                                group === 'expense'
                                    ? 'bg-red-50 text-red-700'
                                    : 'bg-emerald-50 text-emerald-700'
                            "
                        >
                            {{ grouped[group].length }} category{{
                                grouped[group].length === 1 ? '' : 'ies'
                            }}
                        </span>
                    </div>

                    <div
                        class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div
                            v-for="category in grouped[group]"
                            :key="category.id"
                            class="flex items-center justify-between gap-4 px-5 py-3.5 transition duration-150 hover:bg-slate-50"
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
                                <span class="truncate text-sm font-medium text-slate-900">
                                    {{ category.name }}
                                </span>
                            </div>

                            <div class="flex shrink-0 items-center gap-1 text-sm">
                                <Link
                                    :href="route('categories.edit', category.id)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 transition duration-150 hover:bg-primary-50 hover:text-primary-800"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="destroy(category)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 transition duration-150 hover:bg-red-50 hover:text-red-800"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="grouped[group].length === 0"
                            class="flex flex-col items-center justify-center px-5 py-12 text-center"
                        >
                            <span
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                            >
                                <TagIcon class="h-6 w-6" />
                            </span>
                            <p class="mt-3 text-sm font-medium text-slate-700">
                                No {{ group }} categories yet.
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Group your {{ group }} transactions with a
                                category.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>