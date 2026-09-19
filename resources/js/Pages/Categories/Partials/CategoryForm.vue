<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    category: {
        type: Object,
        default: null,
    },
});

const isEditing = !!props.category;

const presetColors = [
    '#ef4444',
    '#f97316',
    '#eab308',
    '#22c55e',
    '#10b981',
    '#06b6d4',
    '#3b82f6',
    '#6366f1',
    '#8b5cf6',
    '#ec4899',
    '#64748b',
    '#0ea5e9',
];

const form = useForm({
    name: props.category?.name ?? '',
    type: props.category?.type ?? 'expense',
    color: props.category?.color ?? '#3b82f6',
});

const submit = () => {
    if (isEditing) {
        form.put(route('categories.update', props.category.id));
    } else {
        form.post(route('categories.store'));
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-5">
        <div>
            <InputLabel for="name" value="Category Name" />

            <input
                id="name"
                type="text"
                class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
                v-model="form.name"
                required
                autofocus
            />

            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel value="Category Type" />

            <div class="mt-2 grid grid-cols-2 gap-3">
                <label
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition duration-150 ease-in-out"
                    :class="
                        form.type === 'expense'
                            ? 'border-red-400 bg-red-50 text-red-700 dark:text-red-400'
                            : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                    "
                >
                    <input
                        type="radio"
                        value="expense"
                        v-model="form.type"
                        class="h-4 w-4 border-slate-300 dark:bg-slate-900 text-red-500 focus:ring-red-500"
                    />
                    Expense
                </label>

                <label
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition duration-150 ease-in-out"
                    :class="
                        form.type === 'income'
                            ? 'border-emerald-400 bg-emerald-50 text-emerald-700 dark:text-emerald-400'
                            : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                    "
                >
                    <input
                        type="radio"
                        value="income"
                        v-model="form.type"
                        class="h-4 w-4 border-slate-300 dark:bg-slate-900 text-emerald-500 focus:ring-emerald-500"
                    />
                    Income
                </label>
            </div>

            <InputError class="mt-2" :message="form.errors.type" />
        </div>

        <div>
            <InputLabel value="Color" />

            <div class="mt-2 flex flex-wrap gap-2">
                <button
                    v-for="color in presetColors"
                    :key="color"
                    type="button"
                    @click="form.color = color"
                    class="h-10 w-10 rounded-full border-2 transition duration-150 ease-in-out"
                    :style="{ backgroundColor: color }"
                    :class="
                        form.color === color
                            ? 'scale-110 border-primary-600 ring-2 ring-primary-500 dark:border-primary-400 dark:ring-primary-500'
                            : 'border-white dark:border-slate-600 shadow-sm hover:scale-105'
                    "
                    :aria-label="`Set color to ${color}`"
                ></button>

                <input
                    type="color"
                    class="h-10 w-10 cursor-pointer rounded-full border-2 border-white dark:border-slate-600 bg-transparent shadow-sm"
                    v-model="form.color"
                    aria-label="Pick custom color"
                />
            </div>

            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Selected:
                <span
                    class="inline-block h-3 w-3 rounded-full align-middle"
                    :style="{ backgroundColor: form.color }"
                ></span>
                <code class="ms-1 align-middle">{{ form.color }}</code>
            </p>

            <InputError class="mt-2" :message="form.errors.color" />
        </div>

        <div class="flex items-center justify-end gap-4">
            <Link
                :href="route('categories.index')"
                class="inline-flex items-center rounded-md border border-primary-200 dark:border-primary-800 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-primary-700 dark:text-primary-300 shadow-sm transition duration-150 ease-in-out hover:bg-primary-50 dark:hover:bg-primary-500/10"
            >
                Cancel
            </Link>

            <PrimaryButton :disabled="form.processing">
                {{ isEditing ? 'Update' : 'Create' }}
            </PrimaryButton>
        </div>
    </form>
</template>