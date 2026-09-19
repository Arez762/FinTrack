<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { confirmAction } from '@/Composables/useSwal';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowsRightLeftIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    accounts: {
        type: Array,
        required: true,
    },
    defaults: {
        type: Object,
        default: () => ({
            account_id: null,
            transaction_date: new Date().toISOString().slice(0, 10),
        }),
    },
});

const form = useForm({
    account_id: props.defaults.account_id ?? '',
    transfer_to_account_id: '',
    amount: '',
    transaction_date: props.defaults.transaction_date,
    description: '',
});

const destinationAccounts = computed(() =>
    props.accounts.filter(
        (account) => account.id !== Number(form.account_id),
    ),
);

const canSubmit = computed(
    () =>
        !!form.account_id &&
        !!form.transfer_to_account_id &&
        Number(form.amount) > 0,
);

const swap = () => {
    const from = form.account_id;
    form.account_id = form.transfer_to_account_id;
    form.transfer_to_account_id = from;
};

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(value) || 0);

const submit = async () => {
    const from = props.accounts.find(
        (account) => account.id === Number(form.account_id),
    );
    const to = props.accounts.find(
        (account) => account.id === Number(form.transfer_to_account_id),
    );

    const confirmed = await confirmAction({
        icon: 'question',
        title: `Transfer ${formatIDR(form.amount)} dari ${
            from?.name ?? '-'
        } ke ${to?.name ?? '-'}?`,
        confirmButtonText: 'Konfirmasi',
    });

    if (confirmed) {
        form.post(route('transfers.store'));
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-5">
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="account_id" value="From Account" />

                <select
                    id="account_id"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
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
                <InputLabel for="transfer_to_account_id" value="To Account" />

                <select
                    id="transfer_to_account_id"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
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
        </div>

        <div class="flex justify-center">
            <button
                type="button"
                @click="swap"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-slate-600 dark:text-slate-300 shadow-sm transition duration-150 ease-in-out hover:bg-slate-50 dark:hover:bg-slate-800"
            >
                <ArrowsRightLeftIcon class="h-4 w-4" />
                Swap
            </button>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="amount" value="Amount" />

                <input
                    id="amount"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
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
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
                    v-model="form.transaction_date"
                    required
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.transaction_date"
                />
            </div>
        </div>

        <div>
            <InputLabel for="description" value="Description (optional)" />

            <textarea
                id="description"
                rows="3"
                class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
                v-model="form.description"
                placeholder="Add a note..."
            ></textarea>

            <InputError class="mt-2" :message="form.errors.description" />
        </div>

        <div class="flex items-center justify-end gap-4">
            <Link
                :href="route('transfers.index')"
                class="inline-flex items-center rounded-lg border border-primary-200 dark:border-primary-800 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-primary-700 dark:text-primary-300 shadow-sm transition duration-150 ease-in-out hover:bg-primary-50 dark:hover:bg-primary-500/10"
            >
                Cancel
            </Link>

            <PrimaryButton :disabled="form.processing || !canSubmit">
                Save Transfer
            </PrimaryButton>
        </div>
    </form>
</template>
