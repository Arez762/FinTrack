<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <h1
                class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white"
            >
                <slot name="title">{{ title }}</slot>
            </h1>
            <p
                v-if="subtitle || $slots.subtitle"
                class="mt-1 text-sm text-slate-500 dark:text-slate-400"
            >
                <slot name="subtitle">{{ subtitle }}</slot>
            </p>
        </div>

        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>