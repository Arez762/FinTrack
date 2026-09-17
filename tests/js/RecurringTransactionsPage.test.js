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
            patch: vi.fn(),
            delete: vi.fn(),
        },
    };
});

import { router } from '@inertiajs/vue3';
import RecurringTransactionsIndex from '@/Pages/RecurringTransactions/Index.vue';

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const formatDate = (value) =>
    new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));

const templates = [
    {
        id: 1,
        account_id: 1,
        category_id: 10,
        type: 'income',
        amount: 8_000_000,
        description: 'Gaji bulanan otomatis',
        frequency: 'monthly',
        start_date: '2026-09-01',
        next_run_date: '2026-09-25',
        is_active: true,
        account: { id: 1, name: 'Bank Account', type: 'bank' },
        category: { id: 10, name: 'Gaji', color: '#22c55e' },
    },
    {
        id: 2,
        account_id: 2,
        category_id: 20,
        type: 'expense',
        amount: 54_000,
        description: 'Langganan streaming',
        frequency: 'monthly',
        start_date: '2026-09-01',
        next_run_date: '2026-09-18',
        is_active: false,
        account: { id: 2, name: 'E-Wallet', type: 'ewallet' },
        category: { id: 20, name: 'Hiburan', color: '#ec4899' },
    },
    {
        id: 3,
        account_id: 2,
        category_id: 30,
        type: 'expense',
        amount: 60_000,
        description: null,
        frequency: 'weekly',
        start_date: '2026-09-01',
        next_run_date: '2026-09-21',
        is_active: true,
        account: { id: 2, name: 'E-Wallet', type: 'ewallet' },
        category: { id: 30, name: 'Transport', color: '#f97316' },
    },
];

const mountPage = (overrides = {}) =>
    mount(RecurringTransactionsIndex, {
        props: {
            recurringTransactions: templates,
            ...overrides,
        },
    });

describe('Recurring transactions index', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('menampilkan satu baris per template dengan frequency, next run, dan nominal', () => {
        const wrapper = mountPage();

        const rows = wrapper.findAll('[data-testid="recurring-row"]');
        expect(rows).toHaveLength(3);

        expect(wrapper.text()).toContain('Gaji bulanan otomatis');
        expect(wrapper.text()).toContain('Monthly');
        expect(wrapper.text()).toContain('Weekly');
        expect(wrapper.text()).toContain(formatIDR(8_000_000));
        expect(wrapper.text()).toContain('Bank Account');
        expect(wrapper.text()).toContain(formatDate('2026-09-25'));
    });

    it('memakai nama kategori saat deskripsi kosong', () => {
        const wrapper = mountPage();

        const rows = wrapper.findAll('[data-testid="recurring-row"]');
        expect(rows[2].text()).toContain('Transport');
    });

    it('menampilkan badge status Active dan Paused', () => {
        const wrapper = mountPage();

        const rows = wrapper.findAll('[data-testid="recurring-row"]');

        expect(rows[0].find('[data-testid="status-badge"]').text()).toBe('Active');
        expect(rows[1].find('[data-testid="status-badge"]').text()).toBe('Paused');
    });

    it('menampilkan ringkasan jumlah template aktif', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('2 active of 3 template(s)');
    });

    it('tombol Pause meminta konfirmasi lalu mengirim PATCH', async () => {
        swal.confirmAction.mockResolvedValue(true);
        const wrapper = mountPage();

        const rows = wrapper.findAll('[data-testid="recurring-row"]');
        const pause = rows[0].find('[data-testid="toggle-active"]');

        expect(pause.text()).toContain('Pause');

        await pause.trigger('click');
        await flushPromises();

        expect(swal.confirmAction).toHaveBeenCalledWith(
            expect.objectContaining({
                confirmButtonText: 'Ya, Jeda',
            }),
        );
        expect(router.patch).toHaveBeenCalledWith(
            expect.stringContaining('recurring-transactions.toggle'),
        );
    });

    it('template nonaktif menampilkan tombol Resume dan mengirim PATCH', async () => {
        swal.confirmAction.mockResolvedValue(true);
        const wrapper = mountPage();

        const rows = wrapper.findAll('[data-testid="recurring-row"]');
        const resume = rows[1].find('[data-testid="toggle-active"]');

        expect(resume.text()).toContain('Resume');

        await resume.trigger('click');
        await flushPromises();

        expect(swal.confirmAction).toHaveBeenCalledWith(
            expect.objectContaining({
                confirmButtonText: 'Ya, Aktifkan',
            }),
        );
        expect(router.patch).toHaveBeenCalledWith(
            expect.stringContaining('recurring-transactions.toggle'),
        );
    });

    it('toggle yang dibatalkan tidak mengirim PATCH', async () => {
        swal.confirmAction.mockResolvedValue(false);
        const wrapper = mountPage();

        const rows = wrapper.findAll('[data-testid="recurring-row"]');
        await rows[0].find('[data-testid="toggle-active"]').trigger('click');
        await flushPromises();

        expect(router.patch).not.toHaveBeenCalled();
    });

    it('tombol Delete memanggil confirmDelete lalu router.delete', async () => {
        swal.confirmDelete.mockResolvedValue(true);
        const wrapper = mountPage();

        const rows = wrapper.findAll('[data-testid="recurring-row"]');
        const deleteButton = rows[0]
            .findAll('button')
            .find((button) => button.text().trim() === 'Delete');

        await deleteButton.trigger('click');
        await flushPromises();

        expect(swal.confirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                text: expect.stringContaining('Gaji bulanan otomatis'),
            }),
        );
        expect(router.delete).toHaveBeenCalledWith(
            expect.stringContaining('recurring-transactions.destroy'),
        );
    });

    it('Delete yang dibatalkan tidak memanggil router.delete', async () => {
        swal.confirmDelete.mockResolvedValue(false);
        const wrapper = mountPage();

        const rows = wrapper.findAll('[data-testid="recurring-row"]');
        const deleteButton = rows[0]
            .findAll('button')
            .find((button) => button.text().trim() === 'Delete');

        await deleteButton.trigger('click');
        await flushPromises();

        expect(router.delete).not.toHaveBeenCalled();
    });

    it('menampilkan empty state saat belum ada template', () => {
        const wrapper = mountPage({ recurringTransactions: [] });

        expect(wrapper.text()).toContain('No recurring transactions yet.');
        expect(wrapper.text()).toContain('Create your first recurring transaction');
        expect(wrapper.findAll('[data-testid="recurring-row"]')).toHaveLength(0);
    });
});
