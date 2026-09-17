<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    savingsGoal: {
        type: Object,
        default: null,
    },
    accounts: {
        type: Array,
        required: true,
    },
});

const isEditing = !!props.savingsGoal;

const presetColors = [
    '#0ea5e9',
    '#3b82f6',
    '#6366f1',
    '#8b5cf6',
    '#22c55e',
    '#10b981',
    '#06b6d4',
    '#eab308',
    '#f97316',
    '#ef4444',
    '#ec4899',
    '#64748b',
];

const presetIcons = [
    '🎯',
    '🏠',
    '🚗',
    '✈️',
    '🏖️',
    '📱',
    '🎓',
    '💍',
    '🏥',
    '👶',
    '🐷',
    '💰',
    '🎁',
];

const form = useForm({
    name: props.savingsGoal?.name ?? '',
    target_amount: props.savingsGoal?.target_amount ?? '',
    target_date: props.savingsGoal?.target_date ?? '',
    account_id: props.savingsGoal?.account_id ?? '',
    icon: props.savingsGoal?.icon ?? '🎯',
    color: props.savingsGoal?.color ?? '#0ea5e9',
});

const submit = () => {
    if (isEditing) {
        form.put(route('savings-goals.update', props.savingsGoal.id));
    } else {
        form.post(route('savings-goals.store'));
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <InputLabel for="name" value="Nama Target" />

            <input
                id="name"
                type="text"
                class="mt-1 block w-full rounded-lg border-slate-300 bg-white dark:bg-slate-900 dark:text-slate-100 dark:border-slate-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                v-model="form.name"
                placeholder="Contoh: Dana Darurat, Liburan Bali"
                required
            />

            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="target_amount" value="Jumlah Target" />

                <input
                    id="target_amount"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white dark:bg-slate-900 dark:text-slate-100 dark:border-slate-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.target_amount"
                    required
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.target_amount"
                />
            </div>

            <div>
                <InputLabel for="target_date" value="Tanggal Target (opsional)" />

                <input
                    id="target_date"
                    type="date"
                    class="mt-1 block w-full rounded-lg border-slate-300 bg-white dark:bg-slate-900 dark:text-slate-100 dark:border-slate-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    v-model="form.target_date"
                />

                <InputError class="mt-2" :message="form.errors.target_date" />
            </div>
        </div>

        <div>
            <InputLabel for="account_id" value="Akun (opsional)" />

            <select
                id="account_id"
                class="mt-1 block w-full rounded-lg border-slate-300 bg-white dark:bg-slate-900 dark:text-slate-100 dark:border-slate-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                v-model="form.account_id"
            >
                <option value="">Tidak terkait akun</option>
                <option
                    v-for="account in accounts"
                    :key="account.id"
                    :value="account.id"
                >
                    {{ account.name }} ({{ account.type }})
                </option>
            </select>

            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Jika akun dipilih, dana yang ditabung akan dicatat sebagai
                pengeluaran di akun tersebut.
            </p>

            <InputError class="mt-2" :message="form.errors.account_id" />
        </div>

        <div>
            <InputLabel value="Ikon" />

            <div class="mt-2 flex flex-wrap gap-2">
                <button
                    v-for="icon in presetIcons"
                    :key="icon"
                    type="button"
                    @click="form.icon = icon"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border-2 text-lg transition duration-150 ease-in-out"
                    :class="
                        form.icon === icon
                            ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-300 dark:bg-primary-500/20 dark:ring-primary-500/50'
                            : 'border-slate-200 bg-white shadow-sm hover:scale-105 dark:border-slate-700 dark:bg-slate-800'
                    "
                    :aria-label="`Set icon to ${icon}`"
                >
                    {{ icon }}
                </button>
            </div>

            <InputError class="mt-2" :message="form.errors.icon" />
        </div>

        <div>
            <InputLabel value="Warna" />

            <div class="mt-2 flex flex-wrap gap-2">
                <button
                    v-for="color in presetColors"
                    :key="color"
                    type="button"
                    @click="form.color = color"
                    class="h-10 w-10 rounded-full border-2 transition duration-150 ease-in-out"
                    :style="{ backgroundColor: color }"
                    :class="
                        form.color === color
                            ? 'scale-110 border-gray-800 ring-2 ring-gray-300 dark:border-gray-200 dark:ring-gray-500'
                            : 'border-white shadow-sm hover:scale-105 dark:border-slate-600'
                    "
                    :aria-label="`Set color to ${color}`"
                ></button>
            </div>

            <InputError class="mt-2" :message="form.errors.color" />
        </div>

        <div class="flex items-center justify-end gap-4">
            <Link
                :href="route('savings-goals.index')"
                class="inline-flex items-center rounded-lg border border-primary-200 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-primary-700 dark:text-primary-300 shadow-sm transition duration-150 ease-in-out hover:bg-primary-50 dark:hover:bg-primary-500/10 dark:border-primary-800"
            >
                Batal
            </Link>

            <PrimaryButton :disabled="form.processing">
                {{ isEditing ? 'Simpan Perubahan' : 'Buat Target' }}
            </PrimaryButton>
        </div>
    </form>
</template>