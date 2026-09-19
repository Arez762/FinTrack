<script setup>
defineProps({
    icon: {
        type: [Object, Function],
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
        <span
            class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500"
        >
            <component :is="icon" class="h-7 w-7" />
        </span>

        <h3
            class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-100"
        >
            {{ title }}
        </h3>

        <p
            v-if="description"
            class="mt-1 max-w-sm text-xs leading-relaxed text-slate-500 dark:text-slate-400"
        >
            {{ description }}
        </p>

        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>