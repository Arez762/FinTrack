<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    account: {
        type: Object,
        default: null,
    },
});

const isEditing = !!props.account;

const form = useForm({
    name: props.account?.name ?? '',
    type: props.account?.type ?? 'cash',
    initial_balance: props.account?.initial_balance ?? '',
});

const submit = () => {
    if (isEditing) {
        form.put(route('accounts.update', props.account.id));
    } else {
        form.post(route('accounts.store'));
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <InputLabel for="name" value="Account Name" />

            <input
                id="name"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                v-model="form.name"
                required
                autofocus
            />

            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="type" value="Account Type" />

            <select
                id="type"
                class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                v-model="form.type"
                required
            >
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="ewallet">E-Wallet</option>
            </select>

            <InputError class="mt-2" :message="form.errors.type" />
        </div>

        <div>
            <InputLabel for="initial_balance" value="Initial Balance" />

            <input
                id="initial_balance"
                type="number"
                step="0.01"
                min="0"
                class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                v-model="form.initial_balance"
                required
            />

            <InputError class="mt-2" :message="form.errors.initial_balance" />
        </div>

        <div class="flex items-center justify-end gap-4">
            <Link
                :href="route('accounts.index')"
                class="inline-flex items-center rounded-md border border-primary-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-primary-700 shadow-sm transition duration-150 ease-in-out hover:bg-primary-50"
            >
                Cancel
            </Link>

            <PrimaryButton :disabled="form.processing">
                {{ isEditing ? 'Update' : 'Create' }}
            </PrimaryButton>
        </div>
    </form>
</template>