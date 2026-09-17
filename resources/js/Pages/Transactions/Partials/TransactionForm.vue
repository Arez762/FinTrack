<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    transaction: {
        type: Object,
        default: null,
    },
    accounts: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
    modal: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'success']);

const isEditing = !!props.transaction;

const today = () => {
    const date = new Date();
    const pad = (value) => String(value).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

const form = useForm({
    type: props.transaction?.type ?? 'expense',
    account_id: props.transaction?.account_id ?? '',
    transfer_to_account_id: props.transaction?.transfer_to_account_id ?? '',
    category_id: props.transaction?.category_id ?? '',
    amount: props.transaction?.amount ?? '',
    transaction_date: props.transaction?.transaction_date ?? today(),
    description: props.transaction?.description ?? '',
});

const isTransfer = computed(() => form.type === 'transfer');

const filteredCategories = computed(() =>
    props.categories.filter((category) => category.type === form.type),
);

const destinationAccounts = computed(() =>
    props.accounts.filter((account) => account.id !== Number(form.account_id)),
);

const watchType = () => {
    if (!filteredCategories.value.some((category) => category.id === Number(form.category_id))) {
        form.category_id = '';
    }

    if (!isTransfer.value) {
        form.transfer_to_account_id = '';
    }
};

const typeOptions = [
    { value: 'income', label: 'Income', active: 'border-emerald-400 bg-emerald-50 text-emerald-700', radio: 'text-emerald-500 focus:ring-emerald-500' },
    { value: 'expense', label: 'Expense', active: 'border-red-400 bg-red-50 text-red-700', radio: 'text-red-500 focus:ring-red-500' },
    { value: 'transfer', label: 'Transfer', active: 'border-primary-400 bg-primary-50 text-primary-700', radio: 'text-primary-500 focus:ring-primary-500' },
];

const submit = () => {
    const options = { onSuccess: () => emit('success') };

    if (isEditing) {
        form.put(route('transactions.update', props.transaction.id), options);
    } else {
        form.post(route('transactions.store'), options);
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <InputLabel value="Transaction Type" />

            <div class="mt-2 grid grid-cols-3 gap-3">
                <label
                    v-for="option in typeOptions"
                    :key="option.value"
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition duration-150 ease-in-out"
                    :class="
                        form.type === option.value
                            ? option.active
                            : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50'
                    "
                >
                    <input
                        type="radio"
                        :value="option.value"
                        v-model="form.type"
                        @change="watchType"
                        class="h-4 w-4 border-gray-300"
                        :class="option.radio"
                    />
                    {{ option.label }}
                </label>
            </div>

            <InputError class="mt-2" :message="form.errors.type" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel
                    for="account_id"
                    :value="isTransfer ? 'From Account' : 'Account'"
                />

                <select
                    id="account_id"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.account_id"
                    required
                >
                    <option value="" disabled>Select account...</option>
                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                        {{ account.name }} ({{ account.type }})
                    </option>
                </select>

                <InputError class="mt-2" :message="form.errors.account_id" />
            </div>

            <div v-if="isTransfer">
                <InputLabel for="transfer_to_account_id" value="To Account" />

                <select
                    id="transfer_to_account_id"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.transfer_to_account_id"
                    required
                >
                    <option value="" disabled>Select account...</option>
                    <option
                        v-for="account in destinationAccounts"
                        :key="account.id"
                        :value="account.id"
                    >
                        {{ account.name }} ({{ account.type }})
                    </option>
                </select>

                <InputError
                    class="mt-2"
                    :message="form.errors.transfer_to_account_id"
                />
            </div>

            <div>
                <InputLabel for="category_id" value="Category" />

                <select
                    id="category_id"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.category_id"
                    :required="!isTransfer"
                    :disabled="isTransfer"
                >
                    <option value="" disabled>
                        {{ isTransfer ? 'N/A for transfer' : 'Select category...' }}
                    </option>
                    <option
                        v-for="category in filteredCategories"
                        :key="category.id"
                        :value="category.id"
                    >
                        {{ category.name }}
                    </option>
                </select>

                <InputError class="mt-2" :message="form.errors.category_id" />
            </div>

            <div>
                <InputLabel for="amount" value="Amount" />

                <input
                    id="amount"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.amount"
                    required
                />

                <InputError class="mt-2" :message="form.errors.amount" />
            </div>

            <div>
                <InputLabel for="transaction_date" value="Date" />

                <input
                    id="transaction_date"
                    type="date"
                    :max="form.type === 'expense' ? today() : undefined"
                    class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.transaction_date"
                    required
                />

                <InputError class="mt-2" :message="form.errors.transaction_date" />
            </div>
        </div>

        <div>
            <InputLabel for="description" value="Description (optional)" />

            <textarea
                id="description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                v-model="form.description"
                placeholder="Add a note..."
            ></textarea>

            <InputError class="mt-2" :message="form.errors.description" />
        </div>

        <div class="flex items-center justify-end gap-4">
            <button
                v-if="modal"
                type="button"
                @click="emit('close')"
                class="inline-flex items-center rounded-md border border-primary-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-primary-700 shadow-sm transition duration-150 ease-in-out hover:bg-primary-50"
            >
                Cancel
            </button>
            <Link
                v-else
                :href="route('transactions.index')"
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