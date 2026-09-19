<script setup>
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AddFundsForm from '@/Pages/SavingsGoals/Partials/AddFundsForm.vue';
import { confirmDelete, useSwal } from '@/Composables/useSwal';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CurrencyDollarIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { reactive, watch } from 'vue';

const props = defineProps({
    savingsGoals: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const { Swal, swalTheme } = useSwal();

const fundModal = reactive({ goal: null });

const openFundModal = (goal) => {
    fundModal.goal = goal;
};

const closeFundModal = () => {
    fundModal.goal = null;
};

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const isDone = (goal) =>
    goal.is_completed || goal.current_amount >= goal.target_amount;

const barClasses = (goal) =>
    isDone(goal) ? 'bg-emerald-500' : 'bg-primary-500';

const barWidth = (goal) =>
    `${Math.min(100, Math.max(0, goal.percentage))}%`;

const daysLabel = (goal) => {
    if (goal.days_left === null || goal.days_left === undefined) {
        return null;
    }

    if (goal.days_left > 0) {
        return `${goal.days_left} hari lagi`;
    }

    if (goal.days_left === 0) {
        return 'Hari ini';
    }

    return `Lewat ${Math.abs(goal.days_left)} hari`;
};

const destroy = async (goal) => {
    const confirmed = await confirmDelete({
        title: 'Hapus target tabungan ini?',
        text: `Target "${goal.name}" akan dihapus.`,
        confirmButtonText: 'Ya, Hapus',
    });

    if (confirmed) {
        router.delete(route('savings-goals.destroy', goal.id));
    }
};

watch(
    () => page?.props?.flash?.savings_goal_completed,
    (name) => {
        if (!name) {
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Target tercapai! 🎉',
            text: `Selamat, kamu berhasil mencapai target "${name}"!`,
            confirmButtonColor: swalTheme.primary,
            confirmButtonText: 'Mantap!',
        });
    },
    { immediate: true },
);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Savings Goals" />

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Savings Goals"
                    subtitle="Track your saving targets and their progress."
                >
                    <template #actions>
                        <Link
                            :href="route('savings-goals.create')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-primary-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 active:bg-primary-800"
                        >
                            <PlusIcon class="h-4 w-4" />
                            New Goal
                        </Link>
                    </template>
                </PageHeader>
                <div
                    v-if="savingsGoals.length"
                    class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                >
                    <div
                        v-for="goal in savingsGoals"
                        :key="goal.id"
                        data-testid="savings-goal-card"
                        class="flex flex-col rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm transition duration-150 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg shadow-sm"
                                    :style="{
                                        backgroundColor:
                                            goal.color || '#0ea5e9',
                                    }"
                                >
                                    {{
                                        goal.icon ||
                                        goal.name.charAt(0).toUpperCase()
                                    }}
                                </span>
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-medium text-slate-900 dark:text-slate-100"
                                    >
                                        {{ goal.name }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{
                                            goal.account
                                                ? goal.account.name
                                                : goal.target_date
                                                  ? goal.target_date
                                                  : 'Target tabungan'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <span
                                v-if="isDone(goal)"
                                data-testid="savings-goal-completed"
                                class="shrink-0 rounded-full border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400"
                            >
                                Tercapai!
                            </span>
                        </div>

                        <div class="mt-4">
                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                data-testid="savings-goal-progress"
                            >
                                <div
                                    data-testid="savings-goal-progress-fill"
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="barClasses(goal)"
                                    :style="{ width: barWidth(goal) }"
                                ></div>
                            </div>

                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                {{ formatIDR(goal.current_amount) }} dari
                                {{ formatIDR(goal.target_amount) }} ({{
                                    Math.round(goal.percentage)
                                }}%)
                            </p>

                            <div
                                class="mt-1 flex items-center justify-between gap-2 text-xs"
                            >
                                <span v-if="daysLabel(goal)" class="text-slate-500 dark:text-slate-400">
                                    {{ daysLabel(goal) }}
                                </span>
                                <span v-else class="text-slate-400 dark:text-slate-500">--</span>
                                <span class="font-medium text-slate-500 dark:text-slate-400">
                                    {{
                                        goal.remaining > 0
                                            ? `${formatIDR(goal.remaining)} lagi`
                                            : 'Selesai'
                                    }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="mt-4 flex items-center justify-between gap-2 border-t border-slate-100 dark:border-slate-700 pt-3"
                        >
                            <button
                                type="button"
                                data-testid="add-funds-button"
                                @click="openFundModal(goal)"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                            >
                                <PlusIcon class="h-4 w-4" />
                                Tambah Dana
                            </button>
                            <div class="flex items-center gap-1">
                                <Link
                                    :href="route('savings-goals.edit', goal.id)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-primary-600 dark:text-primary-400 transition duration-150 hover:bg-primary-50 dark:hover:bg-primary-500/10 hover:text-primary-800 dark:hover:text-primary-300"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="destroy(goal)"
                                    class="rounded-md px-3 py-2 text-xs font-semibold text-red-600 transition duration-150 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-500/10 dark:hover:text-red-300"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm"
                >
                    <EmptyState
                        :icon="CurrencyDollarIcon"
                        title="Belum ada target tabungan."
                        description="Tabung untuk dana darurat, liburan, atau hal lain yang kamu impikan."
                    >
                        <template #action>
                            <Link
                                :href="route('savings-goals.create')"
                                class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-primary-700"
                            >
                                Buat target pertama
                            </Link>
                        </template>
                    </EmptyState>
                </div>
            </div>
        </div>

        <Modal
            :show="!!fundModal.goal"
            @close="closeFundModal"
            max-width="md"
        >
            <div class="max-h-[85vh] overflow-y-auto">
                <div class="border-b border-slate-200 dark:border-slate-700 px-6 py-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                        Tambah Dana
                    </h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{
                            fundModal.goal
                                ? `Menabung untuk "${fundModal.goal.name}"`
                                : ''
                        }}
                    </p>
                </div>

                <div class="p-6">
                    <AddFundsForm
                        v-if="fundModal.goal"
                        :goal="fundModal.goal"
                        @close="closeFundModal"
                        @success="closeFundModal"
                    />
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>