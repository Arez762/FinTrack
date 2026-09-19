<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    goal: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'success']);

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const form = useForm({
    amount: '',
});

const submit = () => {
    form.post(route('savings-goals.addFunds', props.goal.id), {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-5">
        <div>
            <InputLabel for="amount" value="Jumlah Dana" />

            <input
                id="amount"
                type="number"
                step="0.01"
                min="0"
                class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                v-model="form.amount"
                placeholder="Masukkan jumlah yang ingin ditabung"
                required
            />

            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Saat ini {{ formatIDR(goal.current_amount) }} dari
                {{ formatIDR(goal.target_amount) }}
                ({{ Math.round(goal.percentage) }}%)
            </p>

            <InputError class="mt-2" :message="form.errors.amount" />
        </div>

        <div class="flex items-center justify-end gap-4">
            <button
                type="button"
                @click="emit('close')"
                class="inline-flex items-center rounded-lg border border-primary-200 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-primary-700 dark:text-primary-300 shadow-sm transition duration-150 ease-in-out hover:bg-primary-50 dark:hover:bg-primary-500/10 dark:border-primary-800"
            >
                Batal
            </button>

            <PrimaryButton :disabled="form.processing">
                Simpan Dana
            </PrimaryButton>
        </div>
    </form>
</template>