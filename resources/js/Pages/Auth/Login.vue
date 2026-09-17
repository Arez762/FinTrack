<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk — FinTrack" />

        <div class="mb-6">
            <h2 class="text-center text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Masuk ke finTrack
            </h2>
            <p class="mt-2 text-center text-sm text-slate-500 dark:text-slate-400">
                Kelola keuanganmu dengan mudah
            </p>
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1.5 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1.5 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-slate-600 dark:text-slate-300">
                        Ingat saya
                    </span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm font-medium text-primary-600 transition duration-150 hover:text-primary-500 hover:underline focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                >
                    Lupa password?
                </Link>
            </div>

            <div>
                <PrimaryButton class="w-full" :disabled="form.processing">
                    Masuk
                </PrimaryButton>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-300">
            Belum punya akun?
            <Link
                :href="route('register')"
                class="font-medium text-primary-600 transition duration-150 hover:text-primary-500 hover:underline"
            >
                Daftar di sini
            </Link>
        </p>
    </GuestLayout>
</template>