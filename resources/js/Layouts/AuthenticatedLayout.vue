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
    CurrencyDollarIcon,
    HomeIcon,
    MoonIcon,
    SunIcon,
    TagIcon,
    UserCircleIcon,
    WalletIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const sidebarOpen = ref(false);
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

const pageTitle = computed(() => pageTitles[route().current()] ?? 'finTrack');

const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50 dark:bg-slate-950">
        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-900/50 md:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-in-out dark:border-slate-700 dark:bg-slate-900 md:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div
                class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-slate-200 px-5 dark:border-slate-700"
            >
                <Link :href="route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo
                        class="h-8 w-auto fill-current text-primary-600 dark:text-primary-400"
                    />
                    <span
                        class="text-lg font-bold text-slate-900 dark:text-slate-100"
                        >finTrack</span
                    >
                </Link>

                <button
                    type="button"
                    class="-me-1 inline-flex items-center justify-center rounded-md p-1.5 text-slate-400 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-500 focus:outline-none dark:hover:bg-slate-800 dark:hover:text-slate-300 md:hidden"
                    aria-label="Close navigation"
                    @click="sidebarOpen = false"
                >
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <nav
                class="flex-1 space-y-1 overflow-y-auto overscroll-contain px-3 py-4"
            >
                <SidebarLink
                    v-for="item in navigation"
                    :key="item.name"
                    :href="route(item.route)"
                    :active="route().current(item.pattern)"
                    :icon="item.icon"
                    @click="sidebarOpen = false"
                >
                    {{ item.name }}
                </SidebarLink>
            </nav>

            <div class="border-t border-slate-200 p-3 dark:border-slate-700">
                <div class="mb-2 flex items-center gap-3 px-3 py-1">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300"
                    >
                        <UserCircleIcon class="h-6 w-6" />
                    </div>
                    <div class="min-w-0">
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
                </div>

                <SidebarLink
                    :href="route('profile.edit')"
                    :active="route().current('profile.*')"
                    :icon="UserCircleIcon"
                    @click="sidebarOpen = false"
                >
                    Profile
                </SidebarLink>

                <button
                    type="button"
                    class="group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-900 focus:outline-none dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                    @click="logout"
                >
                    <ArrowRightOnRectangleIcon
                        class="h-5 w-5 shrink-0 text-slate-400 group-hover:text-slate-500 dark:text-slate-500 dark:group-hover:text-slate-300"
                    />
                    <span class="truncate">Log Out</span>
                </button>
            </div>
        </aside>

        <!-- Content -->
        <div class="flex flex-1 flex-col pb-24 md:pb-0 md:pl-64">
            <!-- Topbar -->
            <header
                class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-700 dark:bg-slate-900/95"
            >
                <div
                    class="flex min-h-16 items-center gap-3 px-4 py-2 sm:px-6 lg:px-8"
                >
                    <button
                        type="button"
                        class="-ms-1 inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-500 focus:outline-none dark:hover:bg-slate-800 dark:hover:text-slate-300 md:hidden"
                        aria-label="Open navigation"
                        @click="sidebarOpen = true"
                    >
                        <Bars3Icon class="h-6 w-6" />
                    </button>

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
                        {{ pageTitle }}
                    </h1>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white p-2 text-slate-500 shadow-sm transition-colors duration-200 ease-in-out hover:bg-slate-50 hover:text-slate-700 focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100"
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

        <!-- Mobile bottom navigation -->
        <nav
            class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur dark:border-slate-700 dark:bg-slate-900/95 md:hidden"
        >
            <div class="grid grid-cols-4">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="route(item.route)"
                    class="flex flex-col items-center justify-center gap-0.5 px-1 py-2 transition duration-150"
                    :class="
                        route().current(item.pattern)
                            ? 'text-primary-600 dark:text-primary-400'
                            : 'text-slate-400 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-200'
                    "
                >
                    <component
                        :is="item.icon"
                        class="h-6 w-6"
                        :class="
                            route().current(item.pattern)
                                ? 'text-primary-600 dark:text-primary-400'
                                : 'text-slate-400'
                        "
                    />
                    <span class="w-full truncate text-center text-[10px] font-medium">
                        {{ item.name }}
                    </span>
                </Link>
            </div>
        </nav>
    </div>
</template>
