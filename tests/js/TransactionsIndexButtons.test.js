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

import { router } from '@inertiajs/vue3';
import TransactionForm from '@/Pages/Transactions/Partials/TransactionForm.vue';
import TransactionsIndex from '@/Pages/Transactions/Index.vue';

const accounts = [
    { id: 1, name: 'Cash', type: 'cash' },
    { id: 2, name: 'Bank Account', type: 'bank' },
];

const categories = [
    { id: 10, name: 'Gaji', type: 'income', color: '#22c55e' },
    { id: 11, name: 'Makan', type: 'expense', color: '#ef4444' },
];

const transaction = {
    id: 5,
    account_id: 1,
    category_id: 11,
    type: 'expense',
    amount: 25_000,
    description: null,
    transaction_date: '2026-09-16 00:00:00',
    account: { name: 'Cash' },
    category: { name: 'Makan', color: '#ef4444' },
};

const makePage = (overrides = {}) => ({
    data: [transaction],
    total: 1,
    per_page: 10,
    current_page: 1,
    last_page: 1,
    prev_page_url: null,
    next_page_url: null,
    ...overrides,
});

const mountPage = (page = makePage(), filters = {}) =>
    mount(TransactionsIndex, {
        props: {
            transactions: page,
            accounts,
            categories,
            totals: { income: 100, expense: 50 },
            filters,
        },
    });

describe('Transactions Index buttons', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('"+ New Transaction" membuka modal create', async () => {
        const wrapper = mountPage();

        expect(wrapper.findComponent(TransactionForm).exists()).toBe(false);

        const newButton = wrapper
            .findAll('button')
            .find((b) => b.text().includes('New Transaction'));
        expect(newButton).toBeTruthy();

        await newButton.trigger('click');
        await flushPromises();

        const form = wrapper.findComponent(TransactionForm);
        expect(form.exists()).toBe(true);
        expect(form.props('transaction')).toBeNull();
        expect(wrapper.text()).toContain('New Transaction');
    });

    it('tombol "Edit" per baris membuka modal dengan data terisi', async () => {
        const wrapper = mountPage();

        const editButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Edit');
        await editButton.trigger('click');
        await flushPromises();

        const form = wrapper.findComponent(TransactionForm);
        expect(form.exists()).toBe(true);
        expect(form.props('transaction')).toMatchObject({
            id: 5,
            account_id: 1,
            category_id: 11,
            type: 'expense',
            amount: 25_000,
            transaction_date: '2026-09-16',
        });
    });

    it('tombol Delete memanggil confirmDelete lalu router.delete', async () => {
        swal.confirmDelete.mockResolvedValue(true);
        const wrapper = mountPage();

        const deleteButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Delete');
        await deleteButton.trigger('click');
        await flushPromises();

        expect(swal.confirmDelete).toHaveBeenCalled();
        expect(router.delete).toHaveBeenCalledWith(
            expect.stringContaining('transactions.destroy'),
        );
    });

    it('tombol Delete dibatalkan tidak memanggil router.delete', async () => {
        swal.confirmDelete.mockResolvedValue(false);
        const wrapper = mountPage();

        const deleteButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Delete');
        await deleteButton.trigger('click');
        await flushPromises();

        expect(router.delete).not.toHaveBeenCalled();
    });

    it('tombol "Terapkan Filter" mengirim query parameter dari form', async () => {
        const wrapper = mountPage(makePage(), {});

        await wrapper.find('#filter-type').setValue('income');
        await wrapper.find('#filter-account').setValue('2');

        const form = wrapper.find('form');
        await form.trigger('submit');
        await flushPromises();

        const [url, query] = router.get.mock.calls.at(-1);
        expect(url).toBe('transactions.index');
        expect(query).toMatchObject({ type: 'income', account_id: 2 });
    });

    it('tombol "Reset Filter" mengirim query kosong', async () => {
        const wrapper = mountPage(makePage(), {});

        const applyButton = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Reset Filter'));
        await applyButton.trigger('click');

        const [, query] = router.get.mock.calls.at(-1);
        expect(router.get).toHaveBeenCalled();
        expect(query).toEqual({});
    });

    it('tombol pagination Next memuat URL berikutnya; Previous aktif saat ada url', async () => {
        const wrapper = mountPage(
            makePage({
                total: 21,
                current_page: 2,
                last_page: 3,
                prev_page_url: '/transactions?page=1',
                next_page_url: '/transactions?page=3',
            }),
        );

        const prevButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Previous');
        const nextButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Next');

        expect(prevButton.attributes('disabled')).toBeUndefined();

        await nextButton.trigger('click');
        expect(router.get).toHaveBeenCalledWith(
            '/transactions?page=3',
            {},
            expect.any(Object),
        );
    });

    it('tombol Next nonaktif di halaman terakhir', async () => {
        const wrapper = mountPage(
            makePage({
                total: 100,
                per_page: 10,
                current_page: 10,
                last_page: 10,
                prev_page_url: '/transactions?page=9',
                next_page_url: null,
            }),
        );

        const nextButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Next');
        const prevButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Previous');

        expect(nextButton).toBeTruthy();
        expect(prevButton).toBeTruthy();
        expect(prevButton.attributes('disabled')).toBeUndefined();
        expect(nextButton.attributes('disabled')).toBeDefined();
    });

    it('ringkasan total muncul hanya saat ada filter aktif', async () => {
        const wrapper = mountPage(makePage(), {});

        expect(wrapper.text()).not.toContain('Total Income (filtered)');

        const withFilter = mountPage(
            makePage(),
            { type: 'income' },
        );
        expect(withFilter.text()).toContain('Total Income (filtered)');
        expect(withFilter.text()).toContain('Total Expense (filtered)');
    });

    it('tombol Export CSV membawa filter yang sedang aktif', () => {
        const wrapper = mountPage(makePage(), { type: 'income', account_id: 2 });

        const link = wrapper.find('[data-testid="export-csv"]');
        expect(link.exists()).toBe(true);
        expect(link.attributes('href')).toBe(
            global.route('transactions.export.csv', { type: 'income', account_id: 2 }),
        );
    });

    it('tombol Export PDF tanpa filter tetap dapat diakses', () => {
        const wrapper = mountPage(makePage(), {});

        const link = wrapper.find('[data-testid="export-pdf"]');
        expect(link.exists()).toBe(true);
        expect(link.attributes('href')).toBe(
            global.route('transactions.export.pdf', {}),
        );
    });
});