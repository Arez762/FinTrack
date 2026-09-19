import { flushPromises, mount } from '@vue/test-utils';
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('chart.js', () => ({
    Chart: { register: vi.fn(), registerables: [] },
    CategoryScale: {},
    LinearScale: {},
    BarElement: {},
    ArcElement: {},
    PointElement: {},
    LineElement: {},
    Title: {},
    Tooltip: {},
    Legend: {},
}));

vi.mock('vue-chartjs', () => ({
    Bar: {
        name: 'Bar',
        props: ['data', 'options'],
        template: '<div class="bar-chart" />',
    },
    Doughnut: {
        name: 'Doughnut',
        props: ['data', 'options'],
        template: '<div class="doughnut-chart" />',
    },
}));

vi.mock('@/Layouts/AuthenticatedLayout.vue', () => ({
    default: {
        name: 'AuthenticatedLayout',
        template: '<div><slot /></div>',
    },
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        Head: { name: 'Head', render: () => null },
        Link: { name: 'Link', render: () => null },
        router: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
    };
});

import Dashboard from '@/Pages/Dashboard.vue';
import RangeFilter from '@/Components/RangeFilter.vue';
import { router } from '@inertiajs/vue3';

const monthly = Array.from({ length: 12 }, (_, i) => ({
    period: `2026-${String(Math.max(1, 12 - i)).padStart(2, '0')}`,
    label: `Sep ${i}`,
    income: 1_000_000 * i,
    expense: 400_000 * i,
}));

const categoryExpense = [
    { name: 'Makan', color: '#ef4444', total: 300_000 },
];

const mountPage = (overrides = {}) =>
    mount(Dashboard, {
        props: {
            summary: {
                total_balance: 10_000_000,
                month_income: 5_000_000,
                month_expense: 1_500_000,
                month: 'September 2026',
            },
            recent_transactions: [],
            range: 'month',
            categoryRange: 'month',
            monthly,
            category_expense: categoryExpense,
            monthLabel: 'September 2026',
            ...overrides,
        },
    });

describe('Dashboard chart filters', () => {
    beforeAll(async () => {
        await import('@/Components/IncomeExpenseChart.vue');
        await import('@/Components/ExpenseCategoryChart.vue');
    });

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('menampilkan kedua chart dengan data dari props', async () => {
        const wrapper = mountPage();
        await flushPromises();

        expect(wrapper.findComponent({ name: 'Bar' }).props('data').labels).toHaveLength(12);
        expect(wrapper.findComponent({ name: 'Doughnut' }).props('data').labels).toEqual([
            'Makan',
        ]);
    });

    it('filter bar mengirim request ke route dashboard', async () => {
        const wrapper = mountPage();
        await flushPromises();

        const barFilter = wrapper.findAllComponents(RangeFilter)[0];
        await barFilter.findAll('button')[2].trigger('click');

        expect(router.get).toHaveBeenCalledWith(
            'dashboard',
            { range: 'year' },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    it('filter donut mengirim request terpisah', async () => {
        const wrapper = mountPage();
        await flushPromises();

        const donutFilter = wrapper.findAllComponents(RangeFilter)[1];
        await donutFilter.findAll('button')[0].trigger('click');

        expect(router.get).toHaveBeenCalledWith(
            'dashboard',
            { category_range: 'week' },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    it('chart bar ikut ter-update saat prop monthly berubah', async () => {
        const wrapper = mountPage();
        await flushPromises();

        const updated = monthly.map((month) => ({ ...month, income: 7_000_000 }));
        await wrapper.setProps({ monthly: updated });
        await flushPromises();

        expect(
            wrapper.findComponent({ name: 'Bar' }).props('data').datasets[0].data,
        ).toEqual(updated.map((month) => month.income));
    });
});

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const recentTransactions = [
    {
        id: 1,
        type: 'expense',
        amount: 250_000,
        description: 'Makan siang',
        transaction_date: '2026-09-10',
        account: { id: 1, name: 'Dompet' },
        category: { id: 2, name: 'Makan', color: '#ef4444' },
    },
    {
        id: 2,
        type: 'income',
        amount: 5_000_000,
        description: 'Gaji September',
        transaction_date: '2026-09-09',
        account: { id: 1, name: 'Dompet' },
        category: null,
    },
];

describe('Dashboard tampilan', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('menampilkan kartu ringkasan dengan label dan nominal terformat', async () => {
        const wrapper = mountPage();
        await flushPromises();

        expect(wrapper.text()).toContain('Total Balance');
        expect(wrapper.text()).toContain('All accounts combined');
        expect(wrapper.text()).toContain(formatIDR(10_000_000));
        expect(wrapper.text()).toContain(formatIDR(5_000_000));
        expect(wrapper.text()).toContain(formatIDR(1_500_000));
        expect(wrapper.findAll('p').filter((p) => p.text() === 'Income')).toHaveLength(1);
    });

    it('menampilkan transaksi terbaru beserta kategori, akun, dan nominal', async () => {
        const wrapper = mountPage({ recent_transactions: recentTransactions });
        await flushPromises();

        expect(wrapper.text()).toContain('Makan');
        expect(wrapper.text()).toContain('Dompet');
        expect(wrapper.text()).toContain('Expense');
        expect(wrapper.text()).toContain(formatIDR(250_000));
        expect(wrapper.text()).toContain('Gaji September');
        expect(wrapper.text()).toContain(formatIDR(5_000_000));
        expect(wrapper.text()).not.toContain('No transactions yet.');
    });

    it('menampilkan empty state saat belum ada transaksi', async () => {
        const wrapper = mountPage({ recent_transactions: [] });
        await flushPromises();

        expect(wrapper.text()).toContain('No transactions yet.');
        expect(wrapper.text()).toContain('Create your first transaction');
    });
});

describe('Dashboard tampilan mobile', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('menampilkan judul, tanggal hari ini, dan badge jumlah akun/anggaran', async () => {
        const wrapper = mountPage({ account_count: 3, budget_count: 5 });
        await flushPromises();

        expect(wrapper.text()).toContain('Dashboard');
        expect(wrapper.text()).toContain('3 Akun');
        expect(wrapper.text()).toContain('5 Anggaran');

        const today = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date());
        expect(wrapper.text()).toContain(today);
    });

    it('menampilkan kartu ringkasan mobile dengan label dan subteks', async () => {
        const wrapper = mountPage({ account_count: 3 });
        await flushPromises();

        expect(wrapper.text()).toContain('Total Saldo');
        expect(wrapper.text()).toContain('3 akun terdaftar');
        expect(wrapper.text()).toContain('Pemasukan');
        expect(wrapper.text()).toContain('Pengeluaran');
        expect(wrapper.text()).toContain('Bulan ini');
    });
});

const budgetAlerts = [
    {
        id: 1,
        category: { id: 10, name: 'Makan', color: '#ef4444' },
        amount_limit: 100_000,
        spent: 95_000,
        remaining: 5_000,
        percentage: 95,
        status: 'warning',
        period: 'month',
        month: 9,
        year: 2026,
    },
];

describe('Dashboard budget alert', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('menampilkan badge peringatan saat ada budget yang sudah >= 90%', async () => {
        const wrapper = mountPage({ budget_alerts: budgetAlerts });
        await flushPromises();

        expect(wrapper.text()).toContain('1 budget needs attention');
        expect(wrapper.text()).toContain('Makan 95%');
        expect(
            wrapper
                .findAll('a')
                .some((link) => link.attributes('href') === 'budgets.index'),
        ).toBe(true);
    });

    it('tidak menampilkan badge saat tidak ada budget kritis', async () => {
        const wrapper = mountPage();
        await flushPromises();

        expect(wrapper.text()).not.toContain('attention');
    });
});

const dashboardBudgets = [
    {
        id: 1,
        category: { id: 2, name: 'Makan', color: '#ef4444' },
        amount_limit: 300_000,
        spent: 150_000,
        remaining: 150_000,
        percentage: 50,
        status: 'safe',
        period: 'month',
        month: 9,
        year: 2026,
    },
    {
        id: 2,
        category: { id: 3, name: 'Transport', color: '#3b82f6' },
        amount_limit: 1_000_000,
        spent: 950_000,
        remaining: 50_000,
        percentage: 95,
        status: 'warning',
        period: 'year',
        month: null,
        year: 2026,
    },
    {
        id: 3,
        category: { id: 4, name: 'Hiburan', color: '#ec4899' },
        amount_limit: 500_000,
        spent: 520_000,
        remaining: -20_000,
        percentage: 104,
        status: 'over',
        period: 'month',
        month: 9,
        year: 2026,
    },
];

describe('Dashboard ringkasan budget', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('menampilkan section dengan progress bar dan nominal terpakai', async () => {
        const wrapper = mountPage({ budgets: dashboardBudgets });
        await flushPromises();

        expect(wrapper.text()).toContain('Ringkasan Budget');
        expect(wrapper.text()).toContain('Makan');
        expect(wrapper.text()).toContain(formatIDR(150_000));
        expect(wrapper.text()).toContain('dari');
        expect(wrapper.text()).toContain(formatIDR(300_000));
        expect(wrapper.text()).toContain('terpakai');

        const fills = wrapper.findAll(
            '[data-testid="dashboard-budget-progress-fill"]',
        );
        expect(fills).toHaveLength(3);
        expect(fills[0].attributes('style')).toContain('width: 50%');
    });

    it('warna progress bar mengikuti tingkat pemakaian', async () => {
        const wrapper = mountPage({ budgets: dashboardBudgets });
        await flushPromises();

        const fills = wrapper.findAll(
            '[data-testid="dashboard-budget-progress-fill"]',
        );
        expect(fills[0].classes()).toContain('bg-emerald-500');
        expect(fills[1].classes()).toContain('bg-amber-400');
        expect(fills[2].classes()).toContain('bg-red-500');
    });

    it('menampilkan badge Hampir Habis saat budget sudah >= 90%', async () => {
        const wrapper = mountPage({ budgets: dashboardBudgets });
        await flushPromises();

        expect(wrapper.text()).toContain('Hampir Habis');
        expect(
            wrapper.findAll('[data-testid="dashboard-budget-almost-full"]'),
        ).toHaveLength(2);
    });

    it('menampilkan empty state dengan link buat budget saat tidak ada budget', async () => {
        const wrapper = mountPage({ budgets: [] });
        await flushPromises();

        expect(wrapper.text()).toContain('Belum ada budget dibuat');
        expect(
            wrapper
                .findAll('a')
                .some((link) => link.attributes('href') === 'budgets.create'),
        ).toBe(true);
    });
});