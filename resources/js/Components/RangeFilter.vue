<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    routeName: {
        type: String,
        required: true,
    },
    param: {
        type: String,
        default: 'range',
    },
    range: {
        type: String,
        default: 'month',
    },
});

const options = [
    { value: 'week', label: 'Per Week' },
    { value: 'month', label: 'Per Month' },
    { value: 'year', label: 'Per Year' },
];

const apply = (value) => {
    if (value === props.range) {
        return;
    }

    router.get(
        route(props.routeName),
        { [props.param]: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};
</script>

<template>
    <div
        class="inline-flex w-full rounded-lg border border-primary-200 bg-white p-0.5 shadow-sm dark:border-primary-800 dark:bg-slate-800 sm:w-auto"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            @click="apply(option.value)"
            class="flex-1 rounded-md px-3 py-2 text-xs font-semibold transition duration-150 ease-in-out sm:flex-none sm:px-4"
            :class="
                option.value === range
                    ? 'bg-primary-500 text-white shadow'
                    : 'text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-500/10'
            "
        >
            {{ option.label }}
        </button>
    </div>
</template>