<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
    icon: {
        type: [Object, Function],
        required: true,
    },
});

const classes = computed(() =>
    props.active
        ? 'group relative flex items-center gap-3 rounded-lg bg-blue-100 px-4 py-2.5 text-sm font-semibold text-blue-600 transition-colors duration-150 dark:bg-blue-500/15 dark:text-blue-400'
        : 'group relative flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors duration-150 hover:bg-blue-50 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100',
);

const iconClasses = computed(() =>
    props.active
        ? 'h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400'
        : 'h-5 w-5 shrink-0 text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <span
            v-if="active"
            class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-blue-600 dark:bg-blue-400"
        />
        <component :is="icon" :class="iconClasses" />
        <span class="truncate"><slot /></span>
    </Link>
</template>
