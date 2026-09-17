<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
    >
        <div
            v-if="title || subtitle || $slots.actions"
            class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-slate-700 dark:bg-slate-800/60"
        >
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                    {{ title }}
                </h3>
                <p
                    v-if="subtitle"
                    class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                >
                    {{ subtitle }}
                </p>
            </div>

            <div v-if="$slots.actions" class="w-full shrink-0 sm:w-auto">
                <slot name="actions" />
            </div>
        </div>

        <div class="p-5">
            <slot />
        </div>
    </div>
</template>