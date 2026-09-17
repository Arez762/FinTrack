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

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        Head: { name: 'Head', render: () => null },
        Link: { name: 'Link', render: () => null },
        router: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
    };
});

import ReportsIndex from '@/Pages/Reports/Index.vue';
import RangeFilter from '@/Components/RangeFilter.vue';
import { router } from '@inertiajs/vue3';

const monthly = Array.from({ length: 12 }, (_, i) => ({
    period: `2026-${String(Math.max(1, 12 - i)).padStart(2, '0')}`,
    label: `Sep ${2026}`,
    income: 1_000_000 * i,
    expense: 400_000 * i,
}));

const categoryExpense = [
    { name: 'Makan', color: '#ef4444', total: 300_000 },
    { name: 'Transport', color: '#f97316', total: 200_000 },
];

const mountPage = (overrides = {}) =>
    mount(ReportsIndex, {
        props: {
            range: 'month',
            categoryRange: 'month',
            monthly,
            categoryExpense,
            monthLabel: 'September 2026',
            ...overrides,
        },
    });

describe('Reports page charts', () => {
    beforeAll(async () => {
        await import('@/Components/IncomeExpenseChart.vue');
        await import('@/Components/ExpenseCategoryChart.vue');
    });

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('meneruskan data bulanan ke Bar chart (income & expense)', async () => {
        const wrapper = mountPage();
        await flushPromises();

        const bar = wrapper.findComponent({ name: 'Bar' });
        expect(bar.exists()).toBe(true);

        const data = bar.props('data');
        expect(data.labels).toHaveLength(12);
        expect(data.datasets[0].label).toBe('Income');
        expect(data.datasets[0].data).toEqual(monthly.map((m) => m.income));
        expect(data.datasets[0].backgroundColor).toBe('rgba(125, 211, 252, 0.85)');
        expect(data.datasets[1].label).toBe('Expense');
        expect(data.datasets[1].data).toEqual(monthly.map((m) => m.expense));
        expect(data.datasets[1].backgroundColor).toBe('rgba(253, 164, 175, 0.85)');

        expect(bar.props('options').responsive).toBe(true);
        expect(bar.props('options').maintainAspectRatio).toBe(false);
    });

    it('meneruskan data pengeluaran per kategori ke Doughnut chart', async () => {
        const wrapper = mountPage();
        await flushPromises();

        const doughnut = wrapper.findComponent({ name: 'Doughnut' });
        const data = doughnut.props('data');

        expect(data.labels).toEqual(['Makan', 'Transport']);
        expect(data.datasets[0].data).toEqual([300_000, 200_000]);
        expect(data.datasets[0].backgroundColor).toEqual(['#ef4444', '#f97316']);

        expect(doughnut.props('options').cutout).toBe('58%');
    });

    it('menampilkan pesan kosong saat tidak ada pengeluaran di periode ini', () => {
        const wrapper = mountPage({ categoryExpense: [] });

        expect(wrapper.findComponent({ name: 'Doughnut' }).exists()).toBe(false);
        expect(wrapper.text()).toContain('No expense recorded in this period yet.');
    });

    it('menampilkan judul kartu dengan bulan berjalan', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Income vs Expense');
        expect(wrapper.text()).toContain('Expense per Category');
        expect(wrapper.text()).toContain('Current month (September 2026)');
    });

    it('filter bar (Income vs Expense) mengirim request range', async () => {
        const wrapper = mountPage();

        const barFilter = wrapper.findAllComponents(RangeFilter)[0];
        const buttons = barFilter.findAll('button');
        expect(buttons.length).toBe(3);

        await buttons.find((b) => b.text() === 'Per Year').trigger('click');

        expect(router.get).toHaveBeenCalledWith(
            'reports.index',
            { range: 'year' },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    it('filter donut (Expense per Category) mengirim request terpisah', async () => {
        const wrapper = mountPage();

        const donutFilter = wrapper.findAllComponents(RangeFilter)[1];
        const buttons = donutFilter.findAll('button');
        expect(buttons.length).toBe(3);

        await buttons.find((b) => b.text() === 'Per Week').trigger('click');

        expect(router.get).toHaveBeenCalledWith(
            'reports.index',
            { category_range: 'week' },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    it('tombol filter donut yang sedang aktif tidak mengulang request', async () => {
        const wrapper = mountPage();

        const donutFilter = wrapper.findAllComponents(RangeFilter)[1];
        await donutFilter.findAll('button')[1].trigger('click');

        expect(router.get).not.toHaveBeenCalled();
    });

    it('judul kartu menyesuaikan range masing-masing chart', () => {
        const barWeekPage = mountPage({ range: 'week' });
        expect(barWeekPage.text()).toContain('Last 12 weeks');

        const barYearPage = mountPage({ range: 'year' });
        expect(barYearPage.text()).toContain('Last 5 years');

        const donutWeekPage = mountPage({ categoryRange: 'week' });
        expect(donutWeekPage.text()).toContain('Current week');

        const donutYearPage = mountPage({ categoryRange: 'year' });
        expect(donutYearPage.text()).toContain('Current year');
    });

    it('Bar chart ikut ter-update saat prop monthly berubah', async () => {
        const wrapper = mountPage();
        await flushPromises();

        const updated = monthly.map((month) => ({
            ...month,
            income: month.income + 1,
            expense: month.expense + 2,
        }));

        await wrapper.setProps({ monthly: updated });
        await flushPromises();

        const bar = wrapper.findComponent({ name: 'Bar' });
        expect(bar.props('data').datasets[0].data).toEqual(
            updated.map((month) => month.income),
        );
        expect(bar.props('data').datasets[1].data).toEqual(
            updated.map((month) => month.expense),
        );
    });

    it('empty state muncul saat prop categoryExpense berubah jadi kosong', async () => {
        const wrapper = mountPage();
        await flushPromises();
        expect(wrapper.findComponent({ name: 'Doughnut' }).exists()).toBe(true);

        await wrapper.setProps({ categoryExpense: [] });
        await flushPromises();

        expect(wrapper.findComponent({ name: 'Doughnut' }).exists()).toBe(false);
        expect(wrapper.text()).toContain('No expense recorded in this period yet.');
    });
});