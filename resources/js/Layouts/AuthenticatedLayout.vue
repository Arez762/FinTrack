<script setup>
import { computed, ref, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SidebarLink from '@/Components/SidebarLink.vue';
import { errorPopup, toastError, toastSuccess } from '@/Composables/useSwal';
import { isDark, toggleDarkMode } from '@/Composables/useDarkMode';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowPathIcon,
    ArrowRightOnRectangleIcon,
    ArrowsRightLeftIcon,
    BanknotesIcon,
    Bars3Icon,
    ChartBarIcon,
    ClipboardDocumentListIcon,
    CurrencyDollarIcon,
    HomeIcon,
    MoonIcon,
    PlusIcon,
    SunIcon,
    TagIcon,
    UserCircleIcon,
    WalletIcon,
} from '@heroicons/vue/24/outline';

const moreOpen = ref(false);
const page = usePage();

const notifyFromPage = (props) => {
    if (props?.flash?.success) {
        toastSuccess(props.flash.success);
    }

    if (props?.flash?.error) {
        toastError(props.flash.error);
    }

    if (props?.errors && Object.keys(props.errors).length > 0) {
        errorPopup({ errors: props.errors });
    }
};

watch(() => page.props, notifyFromPage, { immediate: true });

const navigation = [
    {
        name: 'Dashboard',
        route: 'dashboard',
        pattern: 'dashboard',
        icon: HomeIcon,
    },
    {
        name: 'Transactions',
        route: 'transactions.index',
        pattern: 'transactions.*',
        icon: ArrowsRightLeftIcon,
    },
    {
        name: 'Recurring',
        route: 'recurring-transactions.index',
        pattern: 'recurring-transactions.*',
        icon: ArrowPathIcon,
    },
    {
        name: 'Accounts',
        route: 'accounts.index',
        pattern: 'accounts.*',
        icon: WalletIcon,
    },
    {
        name: 'Categories',
        route: 'categories.index',
        pattern: 'categories.*',
        icon: TagIcon,
    },
    {
        name: 'Budgets',
        route: 'budgets.index',
        pattern: 'budgets.*',
        icon: BanknotesIcon,
    },
    {
        name: 'Savings',
        route: 'savings-goals.index',
        pattern: 'savings-goals.*',
        icon: CurrencyDollarIcon,
    },
    {
        name: 'Reports',
        route: 'reports.index',
        pattern: 'reports.*',
        icon: ChartBarIcon,
    },
];

const mainNavigation = [
    'dashboard',
    'transactions.*',
    'recurring-transactions.*',
    'accounts.*',
    'categories.*',
];

const otherNavigation = ['budgets.*', 'savings-goals.*', 'reports.*'];

const mainMenuItems = computed(() =>
    navigation.filter((item) => mainNavigation.includes(item.pattern)),
);

const otherMenuItems = computed(() =>
    navigation.filter((item) => otherNavigation.includes(item.pattern)),
);

const mobileNavigation = [
    {
        kind: 'link',
        name: 'Beranda',
        route: 'dashboard',
        pattern: 'dashboard',
        icon: HomeIcon,
    },
    {
        kind: 'link',
        name: 'Transaksi',
        route: 'transactions.index',
        pattern: 'transactions.*',
        icon: ClipboardDocumentListIcon,
    },
    {
        kind: 'add',
    },
    {
        kind: 'link',
        name: 'Akun',
        route: 'accounts.index',
        pattern: 'accounts.*',
        icon: WalletIcon,
    },
    {
        kind: 'more',
    },
];

const moreMenu = [
    {
        name: 'Kategori',
        route: 'categories.index',
        pattern: 'categories.*',
        icon: TagIcon,
    },
    {
        name: 'Budget',
        route: 'budgets.index',
        pattern: 'budgets.*',
        icon: BanknotesIcon,
    },
    {
        name: 'Target Tabungan',
        route: 'savings-goals.index',
        pattern: 'savings-goals.*',
        icon: CurrencyDollarIcon,
    },
    {
        name: 'Reports',
        route: 'reports.index',
        pattern: 'reports.*',
        icon: ChartBarIcon,
    },
];

const pageTitles = {
    dashboard: 'Dashboard',
    'transactions.index': 'Transactions',
    'transactions.create': 'New Transaction',
    'transactions.edit': 'Edit Transaction',
    'transfers.index': 'Transfers',
    'transfers.create': 'New Transfer',
    'recurring-transactions.index': 'Recurring Transactions',
    'recurring-transactions.create': 'New Recurring Transaction',
    'recurring-transactions.edit': 'Edit Recurring Transaction',
    'accounts.index': 'Accounts',
    'accounts.create': 'New Account',
    'accounts.edit': 'Edit Account',
    'categories.index': 'Categories',
    'categories.create': 'New Category',
    'categories.edit': 'Edit Category',
    'budgets.index': 'Budgets',
    'budgets.create': 'New Budget',
    'budgets.edit': 'Edit Budget',
    'savings-goals.index': 'Savings Goals',
    'savings-goals.create': 'New Savings Goal',
    'savings-goals.edit': 'Edit Savings Goal',
    'reports.index': 'Reports',
    'profile.edit': 'Profile',
};

const userName = computed(() => page.props.auth?.user?.name ?? '');

const initials = computed(() => {
    const parts = userName.value.trim().split(/\s+/).filter(Boolean);
    const first = parts[0]?.[0] ?? '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';

    return (first + last).toUpperCase();
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50 dark:bg-slate-950">
        <!-- Sidebar (desktop) -->
        <aside
            class="fixed inset-y-0 left-0 z-50 hidden w-64 flex-col border-r border-slate-200 bg-white shadow-sm md:flex dark:border-slate-700 dark:bg-slate-900"
        >
            <div
                class="flex shrink-0 items-center gap-2.5 border-b border-slate-100 px-6 py-5 dark:border-slate-700"
            >
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-primary-50 dark:bg-primary-500/20"
                >
                    <ApplicationLogo class="h-8 w-auto" />
                </span>
                <Link :href="route('dashboard')" class="flex items-center">
                    <span
                        class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100"
                        >finTrack</span
                    >
                </Link>
            </div>

            <nav
                class="flex-1 space-y-1 overflow-y-auto overscroll-contain px-3 pb-4 pt-4"
            >
                <p
                    class="px-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                >
                    Menu Utama
                </p>
                <div class="space-y-1">
                    <SidebarLink
                        v-for="item in mainMenuItems"
                        :key="item.name"
                        :href="route(item.route)"
                        :active="route().current(item.pattern)"
                        :icon="item.icon"
                    >
                        {{ item.name }}
                    </SidebarLink>
                </div>

                <p
                    class="px-4 pb-1.5 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                >
                    Lainnya
                </p>
                <div class="space-y-1">
                    <SidebarLink
                        v-for="item in otherMenuItems"
                        :key="item.name"
                        :href="route(item.route)"
                        :active="route().current(item.pattern)"
                        :icon="item.icon"
                    >
                        {{ item.name }}
                    </SidebarLink>
                </div>
            </nav>

            <div class="border-t border-slate-100 p-4 dark:border-slate-700">
                <div class="mb-1 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-500 text-sm font-bold uppercase text-white shadow-sm"
                    >
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div
                            class="truncate text-sm font-medium text-slate-900 dark:text-slate-100"
                        >
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div
                            class="truncate text-xs text-slate-500 dark:text-slate-400"
                        >
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition-colors duration-150 hover:bg-red-50 hover:text-red-600 focus:outline-none dark:hover:bg-red-500/10 dark:hover:text-red-400"
                        aria-label="Log Out"
                        @click="router.post(route('logout'))"
                    >
                        <ArrowRightOnRectangleIcon class="h-5 w-5" />
                    </button>
                </div>

                <SidebarLink
                    :href="route('profile.edit')"
                    :active="route().current('profile.*')"
                    :icon="UserCircleIcon"
                >
                    Profile
                </SidebarLink>
            </div>
        </aside>

        <!-- Content -->
        <div class="flex flex-1 flex-col pb-16 md:pb-0 md:pl-64">
            <!-- Mobile header -->
            <header
                class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur md:hidden dark:border-slate-700 dark:bg-slate-900/95"
            >
                <div
                    class="flex h-14 items-center justify-between gap-3 px-4"
                >
                    <Link
                        :href="route('dashboard')"
                        class="text-lg font-bold text-slate-900 dark:text-white"
                    >
                        FinTrack
                    </Link>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            aria-label="Toggle dark mode"
                            class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-700 focus:outline-none dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                            @click="toggleDarkMode"
                        >
                            <SunIcon v-if="isDark" class="h-5 w-5" />
                            <MoonIcon v-else class="h-5 w-5" />
                        </button>

                        <button
                            type="button"
                            aria-label="Log Out"
                            class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-700 focus:outline-none dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                            @click="router.post(route('logout'))"
                        >
                            <ArrowRightOnRectangleIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </header>

            <!-- Topbar (desktop) -->
            <header
                class="sticky top-0 z-20 hidden border-b border-slate-200 bg-white/95 backdrop-blur md:block dark:border-slate-700 dark:bg-slate-900/95"
            >
                <div
                    class="flex min-h-16 items-center gap-3 px-4 py-2 sm:px-6 lg:px-8"
                >
                    <div
                        v-if="$slots.header"
                        class="flex flex-1 items-center gap-4 [&>*]:flex-1"
                    >
                        <slot name="header" />
                    </div>
                    <h1
                        v-else
                        class="truncate text-base font-semibold text-slate-800 dark:text-slate-100"
                    >
                        {{ pageTitles[route().current()] ?? 'finTrack' }}
                    </h1>

                    <button
                        type="button"
                        class="ml-auto inline-flex items-center justify-center rounded-md border border-slate-200 bg-white p-2 text-slate-500 shadow-sm transition-colors duration-200 ease-in-out hover:bg-slate-50 hover:text-slate-700 focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                        aria-label="Toggle dark mode"
                        @click="toggleDarkMode"
                    >
                        <SunIcon v-if="isDark" class="h-5 w-5" />
                        <MoonIcon v-else class="h-5 w-5" />
                    </button>
                </div>
            </header>

            <main class="flex-1">
                <slot />
            </main>

            <footer
                class="border-t border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8 dark:border-slate-700 dark:bg-slate-900"
            >
                <div
                    class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-1.5 text-sm text-slate-500 sm:flex-row dark:text-slate-400"
                >
                    <p>
                        &copy; {{ new Date().getFullYear() }} finTrack. All
                        rights reserved.
                    </p>
                    <p>v1.0.0</p>
                </div>
            </footer>
        </div>

        <!-- Bottom sheet: menu lainnya (mobile) -->
        <div
            v-if="moreOpen"
            class="fixed inset-0 z-40 md:hidden"
        >
            <div
                data-testid="more-sheet-overlay"
                class="absolute inset-0 bg-slate-900/50"
                @click="moreOpen = false"
            />

            <div
                data-testid="more-sheet"
                class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-white pb-6 shadow-2xl dark:bg-slate-800"
            >
                <div
                    class="mx-auto mt-3 h-1.5 w-10 rounded-full bg-slate-200 dark:bg-slate-700"
                />

                <nav
                    aria-label="Menu lainnya"
                    class="mt-4 space-y-1 px-3"
                >
                    <Link
                        v-for="item in moreMenu"
                        :key="item.name"
                        :href="route(item.route)"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 transition duration-150 ease-in-out hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700"
                        @click="moreOpen = false"
                    >
                        <component
                            :is="item.icon"
                            class="h-5 w-5 shrink-0 text-slate-400 dark:text-slate-400"
                        />
                        {{ item.name }}
                    </Link>
                </nav>
            </div>
        </div>

        <!-- Mobile bottom navigation -->
        <nav
            aria-label="Navigasi bawah"
            class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur dark:border-slate-700 dark:bg-slate-900/95 md:hidden"
        >
            <div class="grid grid-cols-5 px-1">
                <template
                    v-for="item in mobileNavigation"
                    :key="item.kind === 'link' ? item.name : item.kind"
                >
                    <Link
                        v-if="item.kind === 'link'"
                        :href="route(item.route)"
                        class="flex flex-col items-center gap-0.5 pb-1.5 pt-3"
                        :class="
                            route().current(item.pattern)
                                ? 'text-primary-600 dark:text-primary-400'
                                : 'text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300'
                        "
                    >
                        <component :is="item.icon" class="h-6 w-6" />
                        <span class="text-xs font-medium">{{ item.name }}</span>
                    </Link>

                    <div
                        v-else-if="item.kind === 'add'"
                        class="flex flex-col items-center pb-1.5"
                    >
                        <Link
                            :href="route('transactions.create')"
                            aria-label="Tambah transaksi"
                            class="-mt-5 mb-0.5 flex h-14 w-14 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg ring-4 ring-slate-50 transition duration-150 ease-in-out hover:bg-primary-700 focus:outline-none dark:ring-slate-950"
                        >
                            <PlusIcon class="h-7 w-7" />
                        </Link>
                        <span class="text-xs font-medium text-slate-400">
                            Tambah
                        </span>
                    </div>

                    <button
                        v-else-if="item.kind === 'more'"
                        type="button"
                        class="flex flex-col items-center gap-0.5 pb-1.5 pt-3 text-slate-400 transition duration-150 ease-in-out hover:text-slate-600 focus:outline-none dark:text-slate-500 dark:hover:text-slate-300"
                        @click="moreOpen = true"
                    >
                        <Bars3Icon class="h-6 w-6" />
                        <span class="text-xs font-medium">Lainnya</span>
                    </button>
                </template>
            </div>
        </nav>
    </div>
</template>
