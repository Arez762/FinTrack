<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    recurringTransaction: {
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
    defaults: {
        type: Object,
        default: () => ({
            start_date: new Date().toISOString().slice(0, 10),
        }),
    },
});

const isEditing = !!props.recurringTransaction;

const form = useForm({
    type: props.recurringTransaction?.type ?? 'expense',
    account_id: props.recurringTransaction?.account_id ?? '',
    category_id: props.recurringTransaction?.category_id ?? '',
    amount: props.recurringTransaction?.amount ?? '',
    frequency: props.recurringTransaction?.frequency ?? 'monthly',
    start_date:
        props.recurringTransaction?.start_date ?? props.defaults.start_date,
    description: props.recurringTransaction?.description ?? '',
});

const filteredCategories = computed(() =>
    props.categories.filter((category) => category.type === form.type),
);

watch(
    () => form.type,
    () => {
        const stillValid = filteredCategories.value.some(
            (category) => category.id === Number(form.category_id),
        );

        if (!stillValid) {
            form.category_id = '';
        }
    },
);

const typeOptions = [
    {
        value: 'income',
        label: 'Income',
        active: 'border-emerald-400 bg-emerald-50 text-emerald-700',
        radio: 'text-emerald-500 focus:ring-emerald-500',
    },
    {
        value: 'expense',
        label: 'Expense',
        active: 'border-red-400 bg-red-50 text-red-700',
        radio: 'text-red-500 focus:ring-red-500',
    },
];

const frequencyOptions = [
    { value: 'daily', label: 'Daily', hint: 'Every day' },
    { value: 'weekly', label: 'Weekly', hint: 'Every week' },
    { value: 'monthly', label: 'Monthly', hint: 'Every month' },
    { value: 'yearly', label: 'Yearly', hint: 'Every year' },
];

const submit = () => {
    if (isEditing) {
        form.put(
            route(
                'recurring-transactions.update',
                props.recurringTransaction.id,
            ),
        );
    } else {
        form.post(route('recurring-transactions.store'));
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <InputLabel value="Transaction Type" />

            <div class="mt-2 grid grid-cols-2 gap-3">
                <label
                    v-for="option in typeOptions"
                    :key="option.value"
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition duration-150 ease-in-out"
                    :class="
                        form.type === option.value
                            ? option.active
                            : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'
                    "
                >
                    <input
                        type="radio"
                        :value="option.value"
                        v-model="form.type"
                        :data-testid="`type-${option.value}`"
                        class="h-4 w-4 border-slate-300"
                        :class="option.radio"
                    />
                    {{ option.label }}
                </label>
            </div>

            <InputError class="mt-2" :message="form.errors.type" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="account_id" value="Account" />

                <select
                    id="account_id"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.account_id"
                    required
                >
                    <option value="" disabled>Select account...</option>
                    <option
                        v-for="account in accounts"
                        :key="account.id"
                        :value="account.id"
                    >
                        {{ account.name }} ({{ account.type }})
                    </option>
                </select>

                <InputError class="mt-2" :message="form.errors.account_id" />
            </div>

            <div>
                <InputLabel for="category_id" value="Category" />

                <select
                    id="category_id"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.category_id"
                    required
                >
                    <option value="" disabled>Select category...</option>
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
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.amount"
                    required
                />

                <InputError class="mt-2" :message="form.errors.amount" />
            </div>

            <div>
                <InputLabel for="frequency" value="Frequency" />

                <select
                    id="frequency"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.frequency"
                    required
                >
                    <option
                        v-for="option in frequencyOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }} — {{ option.hint }}
                    </option>
                </select>

                <InputError class="mt-2" :message="form.errors.frequency" />
            </div>

            <div class="sm:col-span-2">
                <InputLabel for="start_date" value="Start Date" />

                <input
                    id="start_date"
                    type="date"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:max-w-xs"
                    v-model="form.start_date"
                    required
                />

                <p class="mt-1.5 text-xs text-slate-500">
                    The first transaction is created on this date (or on the
                    next scheduler run).
                </p>

                <InputError class="mt-2" :message="form.errors.start_date" />
            </div>
        </div>

        <div>
            <InputLabel for="description" value="Description (optional)" />

            <textarea
                id="description"
                rows="3"
                class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                v-model="form.description"
                placeholder="Add a note..."
            ></textarea>

            <InputError class="mt-2" :message="form.errors.description" />
        </div>

        <div class="flex items-center justify-end gap-4">
            <Link
                :href="route('recurring-transactions.index')"
                class="inline-flex items-center rounded-lg border border-primary-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-primary-700 shadow-sm transition duration-150 ease-in-out hover:bg-primary-50"
            >
                Cancel
            </Link>

            <PrimaryButton :disabled="form.processing">
                {{ isEditing ? 'Update' : 'Create' }}
            </PrimaryButton>
        </div>
    </form>
</template>
