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
        ? 'group flex items-center gap-3 rounded-lg bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-700 transition duration-150 ease-in-out'
        : 'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-900',
);

const iconClasses = computed(() =>
    props.active
        ? 'h-5 w-5 shrink-0 text-primary-600'
        : 'h-5 w-5 shrink-0 text-slate-400 group-hover:text-slate-500',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <component :is="icon" :class="iconClasses" />
        <span class="truncate"><slot /></span>
    </Link>
</template>
