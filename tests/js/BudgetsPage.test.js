import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const swal = vi.hoisted(() => ({
    confirmDelete: vi.fn(),
    confirmAction: vi.fn(),
}));

vi.mock('@/Composables/useSwal', () => ({
    confirmDelete: swal.confirmDelete,
    confirmAction: swal.confirmAction,
    errorPopup: vi.fn(),
    toastSuccess: vi.fn(),
    toastError: vi.fn(),
    errorList: vi.fn(),
    useSwal: () => swal,
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        Head: { name: 'Head', render: () => null },
        Link: { name: 'Link', render: () => null },
        router: {
            get: vi.fn(),
            post: vi.fn(),
            put: vi.fn(),
            delete: vi.fn(),
        },
    };
});

import BudgetsIndex from '@/Pages/Budgets/Index.vue';
import { router } from '@inertiajs/vue3';

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const budgets = [
    {
        id: 1,
        category_id: 10,
        category: { id: 10, name: 'Makan', color: '#ef4444' },
        amount_limit: 1_000_000,
        spent: 400_000,
        remaining: 600_000,
        percentage: 40,
        status: 'safe',
        period: 'month',
        month: 9,
        year: 2026,
    },
    {
        id: 2,
        category_id: 20,
        category: { id: 20, name: 'Transport', color: '#3b82f6' },
        amount_limit: 500_000,
        spent: 400_000,
        remaining: 100_000,
        percentage: 80,
        status: 'warning',
        period: 'month',
        month: 9,
        year: 2026,
    },
    {
        id: 3,
        category_id: 30,
        category: { id: 30, name: 'Belanja', color: '#eab308' },
        amount_limit: 200_000,
        spent: 250_000,
        remaining: -50_000,
        percentage: 125,
        status: 'over',
        period: 'month',
        month: 9,
        year: 2026,
    },
];

const mountPage = (overrides = {}) =>
    mount(BudgetsIndex, {
        props: {
            budgets,
            period: 'month',
            periodLabel: 'September 2026',
            ...overrides,
        },
    });

describe('Budgets index', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('menampilkan satu kartu per budget dengan nominal terformat', () => {
        const wrapper = mountPage();

        expect(wrapper.findAll('[data-testid="budget-card"]')).toHaveLength(3);
        expect(wrapper.text()).toContain('Makan');
        expect(wrapper.text()).toContain('Transport');
        expect(wrapper.text()).toContain(formatIDR(1_000_000));
        expect(wrapper.text()).toContain(formatIDR(400_000));
        expect(wrapper.text()).toContain('Showing budgets for September 2026');
    });

    it('mewarnai progress bar sesuai status budget', () => {
        const wrapper = mountPage();

        const bars = wrapper.findAll('[data-testid="budget-progress-fill"]');

        expect(bars[0].classes()).toContain('bg-emerald-500');
        expect(bars[1].classes()).toContain('bg-amber-400');
        expect(bars[2].classes()).toContain('bg-red-500');
    });

    it('membatasi lebar progress bar maksimal 100 persen', () => {
        const wrapper = mountPage();

        const bars = wrapper.findAll('[data-testid="budget-progress-fill"]');

        expect(bars[0].attributes('style')).toContain('width: 40%');
        expect(bars[1].attributes('style')).toContain('width: 80%');
        expect(bars[2].attributes('style')).toContain('width: 100%');
    });

    it('menampilkan persentase, sisa, dan status over budget', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('40%');
        expect(wrapper.text()).toContain('80%');
        expect(wrapper.text()).toContain('125%');
        expect(wrapper.text()).toContain('On track');
        expect(wrapper.text()).toContain('Near limit');
        expect(wrapper.text()).toContain('Over budget');
        expect(wrapper.text()).toContain(`Over by ${formatIDR(50_000)}`);
    });

    it('tombol period meminta data periode lain', async () => {
        const wrapper = mountPage();

        await wrapper.find('[data-testid="period-filter-year"]').trigger('click');

        expect(router.get).toHaveBeenCalledWith(
            'budgets.index',
            { period: 'year' },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    it('tidak meminta ulang saat memilih period yang sedang aktif', async () => {
        const wrapper = mountPage();

        await wrapper.find('[data-testid="period-filter-month"]').trigger('click');

        expect(router.get).not.toHaveBeenCalled();
    });

    it('tombol Delete memanggil confirmDelete lalu router.delete', async () => {
        swal.confirmDelete.mockResolvedValue(true);
        const wrapper = mountPage();

        await wrapper
            .findAll('button')
            .find((button) => button.text().trim() === 'Delete')
            .trigger('click');
        await flushPromises();

        expect(swal.confirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                text: expect.stringContaining('Makan'),
            }),
        );
        expect(router.delete).toHaveBeenCalledWith(
            expect.stringContaining('budgets.destroy'),
        );
    });

    it('menampilkan empty state saat belum ada budget', () => {
        const wrapper = mountPage({ budgets: [] });

        expect(wrapper.text()).toContain('No budgets for this period yet.');
        expect(wrapper.text()).toContain('Create your first budget');
        expect(wrapper.findAll('[data-testid="budget-card"]')).toHaveLength(0);
    });
});
