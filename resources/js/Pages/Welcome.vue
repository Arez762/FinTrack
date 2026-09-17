<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowPathIcon,
    ArrowTrendingUpIcon,
    ChartBarIcon,
    ChartPieIcon,
    CheckIcon,
    PencilSquareIcon,
    WalletIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const features = [
    {
        icon: PencilSquareIcon,
        title: 'Pencatatan Transaksi',
        description: 'Catat pemasukan dan pengeluaran dengan cepat dan sederhana.',
    },
    {
        icon: ChartPieIcon,
        title: 'Budgeting',
        description: 'Kontrol pengeluaran per kategori dengan anggaran bulanan.',
    },
    {
        icon: ArrowTrendingUpIcon,
        title: 'Target Tabungan',
        description: 'Capai tujuan finansial dengan target tabungan yang jelas.',
    },
    {
        icon: ChartBarIcon,
        title: 'Laporan & Grafik',
        description: 'Visualisasi keuangan bulanan yang mudah dipahami.',
    },
    {
        icon: WalletIcon,
        title: 'Multi Akun/Dompet',
        description: 'Kelola cash, bank, dan e-wallet dalam satu tempat.',
    },
    {
        icon: ArrowPathIcon,
        title: 'Transaksi Berulang',
        description: 'Otomatis catat tagihan dan pemasukan rutin setiap bulan.',
    },
];
</script>

<template>
    <Head title="finTrack — Kelola Keuangan Pribadimu" />

    <div class="flex min-h-screen flex-col bg-white font-sans text-slate-900 antialiased">
        <header
            class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <Link href="/" class="flex items-center gap-2.5">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500 text-white shadow-sm shadow-primary-200"
                    >
                        <WalletIcon class="h-5 w-5" />
                    </span>
                    <span class="text-lg font-bold tracking-tight text-primary-600">
                        FinTrack
                    </span>
                </Link>

                <nav class="flex items-center gap-2 sm:gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="route('dashboard')"
                            class="inline-flex items-center rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition duration-150 hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        >
                            Buka Dashboard
                        </Link>
                    </template>

                    <template v-else>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="rounded-md px-3 py-2 text-sm font-semibold text-slate-600 transition duration-150 hover:text-primary-600"
                        >
                            Masuk
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        >
                            Daftar Gratis
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <section
                class="overflow-hidden bg-gradient-to-b from-white to-blue-50"
            >
                <div
                    class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:py-24 lg:px-8"
                >
                    <div>
                        <p
                            class="inline-flex items-center rounded-full border border-primary-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wider text-primary-600"
                        >
                            Personal Finance Tracker
                        </p>

                        <h1
                            class="mt-5 text-4xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl"
                        >
                            Catat Keuanganmu Lebih Mudah,
                            <span class="text-primary-500">Kelola Lebih Cerdas.</span>
                        </h1>

                        <p class="mt-6 max-w-xl text-lg text-slate-600">
                            finTrack adalah aplikasi pencatatan keuangan pribadi
                            yang membantumu mengatur pemasukan, pengeluaran,
                            anggaran, hingga target tabungan — semuanya dalam
                            satu tempat yang rapi dan mudah dipahami.
                        </p>

                        <div
                            class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center"
                        >
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="inline-flex items-center justify-center rounded-lg bg-primary-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                            >
                                Mulai Gratis
                            </Link>

                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-primary-600 transition duration-150 hover:border-primary-300 hover:bg-primary-50"
                            >
                                Sudah Punya Akun? Masuk
                            </Link>
                        </div>

                        <ul class="mt-8 flex flex-col gap-2 sm:flex-row sm:gap-6">
                            <li class="flex items-center gap-2 text-sm text-slate-500">
                                <CheckIcon class="h-4 w-4 text-emerald-500" />
                                Gratis untuk memulai
                            </li>
                            <li class="flex items-center gap-2 text-sm text-slate-500">
                                <CheckIcon class="h-4 w-4 text-emerald-500" />
                                Tanpa kartu kredit
                            </li>
                            <li class="flex items-center gap-2 text-sm text-slate-500">
                                <CheckIcon class="h-4 w-4 text-emerald-500" />
                                Data Anda aman
                            </li>
                        </ul>
                    </div>

                    <div class="relative mx-auto w-full max-w-md">
                        <div
                            class="absolute -inset-6 rounded-3xl bg-gradient-to-br from-primary-100 via-blue-50 to-primary-200/60"
                        ></div>

                        <div
                            class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-primary-100/60"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-slate-500">
                                        Saldo Total
                                    </p>
                                    <p class="mt-1 text-2xl font-bold text-slate-900">
                                        Rp 24.500.000
                                    </p>
                                </div>
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600"
                                >
                                    <WalletIcon class="h-5 w-5" />
                                </span>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div
                                    class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2"
                                >
                                    <p class="text-[11px] font-medium text-emerald-700">
                                        Pemasukan
                                    </p>
                                    <p class="mt-0.5 text-sm font-semibold text-emerald-600">
                                        +Rp 4.200.000
                                    </p>
                                </div>
                                <div
                                    class="rounded-lg border border-red-100 bg-red-50 px-3 py-2"
                                >
                                    <p class="text-[11px] font-medium text-red-700">
                                        Pengeluaran
                                    </p>
                                    <p class="mt-0.5 text-sm font-semibold text-red-600">
                                        -Rp 2.800.000
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-medium text-slate-500">
                                        Pengeluaran 6 Bulan Terakhir
                                    </p>
                                    <p class="text-xs font-semibold text-primary-600">
                                        Detail
                                    </p>
                                </div>
                                <div class="mt-3 flex h-24 items-end gap-2">
                                    <div
                                        class="w-full rounded-t-md bg-primary-200"
                                        style="height: 35%"
                                    ></div>
                                    <div
                                        class="w-full rounded-t-md bg-primary-200"
                                        style="height: 50%"
                                    ></div>
                                    <div
                                        class="w-full rounded-t-md bg-primary-300"
                                        style="height: 40%"
                                    ></div>
                                    <div
                                        class="w-full rounded-t-md bg-primary-300"
                                        style="height: 62%"
                                    ></div>
                                    <div
                                        class="w-full rounded-t-md bg-primary-400"
                                        style="height: 55%"
                                    ></div>
                                    <div
                                        class="w-full rounded-t-md bg-primary-500"
                                        style="height: 78%"
                                    ></div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-medium text-slate-500">
                                        Budget Bulan Ini
                                    </p>
                                    <p class="text-xs font-semibold text-slate-700">
                                        72%
                                    </p>
                                </div>
                                <div
                                    class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100"
                                >
                                    <div
                                        class="h-2 w-3/4 rounded-full bg-primary-500"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="absolute -left-8 top-1/2 hidden w-48 -translate-y-1/2 rounded-xl border border-slate-200 bg-white p-4 shadow-md sm:block"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-100 text-primary-600"
                                >
                                    <ArrowTrendingUpIcon class="h-4 w-4" />
                                </span>
                                <div>
                                    <p class="text-xs font-medium text-slate-500">
                                        Target Liburan
                                    </p>
                                    <p class="text-sm font-bold text-slate-900">
                                        Rp 5.000.000
                                    </p>
                                </div>
                            </div>
                            <div
                                class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-100"
                            >
                                <div
                                    class="h-1.5 w-2/5 rounded-full bg-emerald-500"
                                ></div>
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-400">
                                dari Rp 8.000.000
                            </p>
                        </div>

                        <div
                            class="absolute -right-4 -bottom-5 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-md"
                        >
                            <p class="text-[11px] text-slate-500">
                                Pemasukan bulan ini
                            </p>
                            <p class="text-sm font-bold text-emerald-600">
                                +Rp 4.200.000
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white py-16 lg:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2
                            class="text-3xl font-bold tracking-tight text-slate-900"
                        >
                            Fitur Lengkap untuk Mengatur Keuangan
                        </h2>
                        <p class="mt-4 text-lg text-slate-600">
                            Semua yang kamu butuhkan untuk mengontrol keuangan
                            pribadi dalam satu aplikasi yang sederhana.
                        </p>
                    </div>

                    <div
                        class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="feature in features"
                            :key="feature.title"
                            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-md"
                        >
                            <span
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600"
                            >
                                <component :is="feature.icon" class="h-5 w-5" />
                            </span>
                            <h3 class="mt-4 text-base font-semibold text-slate-900">
                                {{ feature.title }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-4 pb-16 sm:px-6 lg:px-8">
                <div
                    class="mx-auto max-w-7xl rounded-2xl bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-12 text-center shadow-lg sm:px-12 lg:py-16"
                >
                    <h2
                        class="text-3xl font-bold tracking-tight text-white"
                    >
                        Mulai kelola keuanganmu hari ini
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-primary-100">
                        Bergabunglah secara gratis dan ambil kendali atas
                        pemasukan, pengeluaran, dan target keuanganmu.
                    </p>

                    <div class="mt-8">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-8 py-3.5 text-sm font-semibold text-primary-600 shadow-sm transition duration-150 hover:bg-primary-50"
                        >
                            Daftar Sekarang
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 sm:flex-row sm:px-6 lg:px-8"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-md bg-primary-500 text-white"
                    >
                        <WalletIcon class="h-3.5 w-3.5" />
                    </span>
                    <p class="text-sm text-slate-500">
                        © {{ new Date().getFullYear() }} finTrack. All rights
                        reserved.
                    </p>
                </div>

                <nav v-if="!$page.props.auth.user" class="flex items-center gap-4">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="text-sm font-medium text-slate-600 transition duration-150 hover:text-primary-600"
                    >
                        Masuk
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="text-sm font-medium text-slate-600 transition duration-150 hover:text-primary-600"
                    >
                        Daftar
                    </Link>
                </nav>
            </div>
        </footer>
    </div>
</template>
