<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gradient-to-b from-primary-50 via-background to-background-soft px-4 pt-6 sm:justify-center sm:pt-0"
    >
        <div class="w-full max-w-md">
            <div class="mb-8 flex justify-center sm:mb-10">
                <Link href="/">
                    <ApplicationLogo class="h-16 w-16 fill-current text-primary-600" />
                </Link>
            </div>

            <div
                class="overflow-hidden rounded-lg border border-white bg-white shadow-lg shadow-primary-100/60 sm:rounded-xl sm:border-primary-100 sm:shadow-[0_10px_40px_-12px_rgba(2,132,199,0.25)]"
            >
                <div class="border-b border-primary-100 bg-gradient-to-r from-primary-50 to-primary-100/50 px-6 py-4 sm:px-8">
                    <h1 class="text-lg font-semibold text-primary-800">FinTrack</h1>
                    <p class="mt-0.5 text-sm text-primary-600">
                        Your trusted companion for managing finances.
                    </p>
                </div>

                <div class="px-6 py-6 sm:px-8">
                    <slot />
                </div>

                <div class="border-t border-primary-100 bg-background-soft px-6 py-3 text-center text-xs text-primary-900/50 sm:px-8">
                    &copy; {{ new Date().getFullYear() }} FinTrack. All rights reserved.
                </div>
            </div>
        </div>
    </div>
</template>