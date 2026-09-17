<script setup>
import { WalletIcon } from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-gradient-to-b from-white via-blue-50 to-blue-100 px-4 py-10 sm:px-6 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950"
    >
        <div
            class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-primary-200/40 blur-3xl dark:bg-primary-500/10"
        ></div>
        <div
            class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-primary-100/60 blur-3xl dark:bg-primary-500/5"
        ></div>

        <div class="relative w-full max-w-md">
            <div class="mb-6 flex justify-center">
                <Link
                    href="/"
                    class="inline-flex items-center gap-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                >
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500 text-white shadow-sm shadow-primary-200"
                    >
                        <WalletIcon class="h-5 w-5" />
                    </span>
                    <span
                        class="text-xl font-bold tracking-tight text-slate-900 dark:text-white"
                    >
                        Fin<span class="text-primary-600">Track</span>
                    </span>
                </Link>
            </div>

            <div
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-xl shadow-primary-100/50 sm:p-8 dark:border-slate-700 dark:bg-slate-800 dark:shadow-none"
            >
                <slot />
            </div>

            <p class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
                &copy; {{ new Date().getFullYear() }} FinTrack. All rights
                reserved.
            </p>
        </div>
    </div>
</template>