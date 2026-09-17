<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    budget: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        required: true,
    },
    defaults: {
        type: Object,
        default: () => ({
            month: new Date().getMonth() + 1,
            year: new Date().getFullYear(),
        }),
    },
});

const isEditing = !!props.budget;

const monthOptions = Array.from({ length: 12 }, (_, index) => ({
    value: index + 1,
    label: new Intl.DateTimeFormat('en', { month: 'long' }).format(
        new Date(2000, index, 1),
    ),
}));

const currentYear = new Date().getFullYear();

const yearOptions = computed(() => {
    const selected = Number(form.year);
    const years = [
        currentYear - 1,
        currentYear,
        currentYear + 1,
        currentYear + 2,
    ];

    if (Number.isInteger(selected) && selected > 0) {
        years.push(selected);
    }

    return [...new Set(years)].sort((a, b) => a - b);
});

const form = useForm({
    category_id: props.budget?.category_id ?? '',
    amount_limit: props.budget?.amount_limit ?? '',
    period: props.budget?.period ?? 'month',
    month: props.budget?.month ?? props.defaults.month,
    year: props.budget?.year ?? props.defaults.year,
});

const isYearly = computed(() => form.period === 'year');

const periodOptions = [
    { value: 'month', label: 'Monthly', hint: 'Resets every month' },
    { value: 'year', label: 'Yearly', hint: 'Resets every year' },
];

watch(isYearly, (yearly) => {
    if (yearly) {
        form.month = null;
    } else if (!form.month) {
        form.month = props.defaults.month;
    }
});

const submit = () => {
    if (isEditing) {
        form.put(route('budgets.update', props.budget.id));
    } else {
        form.post(route('budgets.store'));
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <InputLabel for="category_id" value="Expense Category" />

            <select
                id="category_id"
                class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                v-model="form.category_id"
                required
            >
                <option value="" disabled>Select category...</option>
                <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                >
                    {{ category.name }}
                </option>
            </select>

            <p v-if="categories.length === 0" class="mt-2 text-xs text-slate-500">
                You need an expense category before creating a budget.
            </p>

            <InputError class="mt-2" :message="form.errors.category_id" />
        </div>

        <div>
            <InputLabel for="amount_limit" value="Budget Limit" />

            <input
                id="amount_limit"
                type="number"
                step="0.01"
                min="0"
                class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                v-model="form.amount_limit"
                required
            />

            <InputError class="mt-2" :message="form.errors.amount_limit" />
        </div>

        <div>
            <InputLabel value="Period" />

            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                <label
                    v-for="option in periodOptions"
                    :key="option.value"
                    class="flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-sm font-medium transition duration-150 ease-in-out"
                    :class="
                        form.period === option.value
                            ? 'border-primary-400 bg-primary-50 text-primary-700'
                            : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'
                    "
                >
                    <input
                        type="radio"
                        :value="option.value"
                        v-model="form.period"
                        :data-testid="`period-${option.value}`"
                        class="h-4 w-4 border-slate-300 text-primary-500 focus:ring-primary-500"
                    />
                    <span>
                        {{ option.label }}
                        <span class="block text-xs font-normal opacity-70">
                            {{ option.hint }}
                        </span>
                    </span>
                </label>
            </div>

            <InputError class="mt-2" :message="form.errors.period" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div v-if="!isYearly">
                <InputLabel for="month" value="Month" />

                <select
                    id="month"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.month"
                >
                    <option
                        v-for="month in monthOptions"
                        :key="month.value"
                        :value="month.value"
                    >
                        {{ month.label }}
                    </option>
                </select>

                <InputError class="mt-2" :message="form.errors.month" />
            </div>

            <div>
                <InputLabel for="year" value="Year" />

                <select
                    id="year"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.year"
                >
                    <option v-for="year in yearOptions" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>

                <InputError class="mt-2" :message="form.errors.year" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <Link
                :href="route('budgets.index')"
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
