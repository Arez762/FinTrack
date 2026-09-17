import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const swal = vi.hoisted(() => ({
    confirmDelete: vi.fn(),
    Swal: { fire: vi.fn() },
    swalTheme: { primary: '#0ea5e9' },
}));

const routerPage = vi.hoisted(() => ({
    props: {},
    url: '/savings-goals',
}));

vi.mock('@/Composables/useSwal', () => ({
    confirmDelete: swal.confirmDelete,
    useSwal: () => ({ Swal: swal.Swal, swalTheme: swal.swalTheme }),
    errorPopup: vi.fn(),
    toastSuccess: vi.fn(),
    toastError: vi.fn(),
    errorList: vi.fn(),
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
            page: routerPage,
        },
        usePage: () => routerPage,
    };
});

import SavingsGoalsIndex from '@/Pages/SavingsGoals/Index.vue';
import { router } from '@inertiajs/vue3';

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const goals = [
    {
        id: 1,
        name: 'Dana Darurat',
        target_amount: 1_000_000,
        current_amount: 250_000,
        remaining: 750_000,
        percentage: 25,
        days_left: 45,
        icon: '🏠',
        color: '#0ea5e9',
        is_completed: false,
        target_date: null,
        account: null,
    },
    {
        id: 2,
        name: 'Liburan Bali',
        target_amount: 2_000_000,
        current_amount: 2_000_000,
        remaining: 0,
        percentage: 100,
        days_left: 0,
        icon: '🏖️',
        color: '#f97316',
        is_completed: true,
        target_date: '2026-12-01',
        account: { id: 5, name: 'Bank Mandiri' },
    },
    {
        id: 3,
        name: 'Mobil Baru',
        target_amount: 100_000_000,
        current_amount: 20_000_000,
        remaining: 80_000_000,
        percentage: 20,
        days_left: -3,
        icon: '🚗',
        color: '#3b82f6',
        is_completed: false,
        target_date: '2026-09-14',
        account: null,
    },
];

const mountPage = (props = {}) =>
    mount(SavingsGoalsIndex, {
        props: {
            savingsGoals: goals,
            ...props,
        },
    });

describe('SavingsGoals index', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        routerPage.props = {};
    });

    it('menampilkan satu kartu per target dengan nomimal terformat', () => {
        const wrapper = mountPage();

        expect(
            wrapper.findAll('[data-testid="savings-goal-card"]'),
        ).toHaveLength(3);
        expect(wrapper.text()).toContain('Dana Darurat');
        expect(wrapper.text()).toContain(formatIDR(250_000));
        expect(wrapper.text()).toContain(formatIDR(1_000_000));
        expect(wrapper.text()).toContain('25%');
    });

    it('mewarnai progress bar emerald saat tercapai dan primary saat aktif', () => {
        const wrapper = mountPage();

        const bars = wrapper.findAll(
            '[data-testid="savings-goal-progress-fill"]',
        );

        expect(bars[0].classes()).toContain('bg-primary-500');
        expect(bars[1].classes()).toContain('bg-emerald-500');
        expect(bars[2].classes()).toContain('bg-primary-500');
    });

    it('membatasi lebar progress bar maksimal 100 persen', () => {
        const wrapper = mountPage();

        const bars = wrapper.findAll(
            '[data-testid="savings-goal-progress-fill"]',
        );

        expect(bars[0].attributes('style')).toContain('width: 25%');
        expect(bars[1].attributes('style')).toContain('width: 100%');
        expect(bars[2].attributes('style')).toContain('width: 20%');
    });

    it('menampilkan badge Tercapai hanya pada target selesai', () => {
        const wrapper = mountPage();

        expect(
            wrapper.findAll('[data-testid="savings-goal-completed"]'),
        ).toHaveLength(1);
        expect(wrapper.text()).toContain('Tercapai!');
        expect(wrapper.text()).toContain('Selesai');
        expect(wrapper.text()).toContain(`${formatIDR(80_000_000)} lagi`);
    });

    it('menampilkan label hari tersisa sesuai days_left', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('45 hari lagi');
        expect(wrapper.text()).toContain('Lewat 3 hari');

        const withoutDate = mountPage({
            savingsGoals: [
                goals[0],
                { ...goals[1], days_left: null, is_completed: false, current_amount: 100 },
            ],
        });
        expect(withoutDate.text()).toContain('--');
    });

    it('menampilkan nama akun saat target terkait akun', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Bank Mandiri');
    });

    it('tombol Tambah Dana membuka modal dan Batal menutupnya', async () => {
        const wrapper = mountPage();

        await wrapper
            .findAll('[data-testid="add-funds-button"]')[0]
            .trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('Menabung untuk "Dana Darurat"');
        expect(wrapper.text()).toContain('Jumlah Dana');

        const cancelButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Batal');
        expect(cancelButton).toBeTruthy();

        await cancelButton.trigger('click');
        await flushPromises();

        expect(wrapper.text()).not.toContain('Jumlah Dana');
    });

    it('tombol Delete memanggil confirmDelete lalu router.delete', async () => {
        swal.confirmDelete.mockResolvedValue(true);
        const wrapper = mountPage();

        await wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Delete')
            .trigger('click');
        await flushPromises();

        expect(swal.confirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                text: expect.stringContaining('Dana Darurat'),
            }),
        );
        expect(router.delete).toHaveBeenCalledWith(
            expect.stringContaining('savings-goals.destroy'),
        );
    });

    it('delete yang dibatalkan tidak memanggil router.delete', async () => {
        swal.confirmDelete.mockResolvedValue(false);
        const wrapper = mountPage();

        await wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Delete')
            .trigger('click');
        await flushPromises();

        expect(router.delete).not.toHaveBeenCalled();
    });

    it('menampilkan perayaan saat status target tercapai masuk ke flash', () => {
        routerPage.props.flash = { savings_goal_completed: 'Dana Darurat' };

        mountPage();

        expect(swal.Swal.fire).toHaveBeenCalledWith(
            expect.objectContaining({
                icon: 'success',
                title: 'Target tercapai! 🎉',
                text: expect.stringContaining('Dana Darurat'),
            }),
        );
    });

    it('menampilkan empty state saat belum ada target', () => {
        const wrapper = mountPage({ savingsGoals: [] });

        expect(wrapper.text()).toContain('Belum ada target tabungan.');
        expect(wrapper.text()).toContain('Buat target pertama');
        expect(
            wrapper.findAll('[data-testid="savings-goal-card"]'),
        ).toHaveLength(0);
    });
});