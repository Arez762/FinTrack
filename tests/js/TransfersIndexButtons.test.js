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
        Link: {
            name: 'Link',
            props: ['href'],
            template: '<a :href="href"><slot /></a>',
        },
        router: {
            get: vi.fn(),
            post: vi.fn(),
            delete: vi.fn(),
        },
    };
});

import { router } from '@inertiajs/vue3';
import TransfersIndex from '@/Pages/Transfers/Index.vue';

const transfer = {
    id: 9,
    amount: 200_000,
    description: 'Top up e-wallet',
    transaction_date: '2026-09-17',
    account: { id: 1, name: 'Cash', type: 'cash' },
    transfer_to_account: { id: 2, name: 'Bank Account', type: 'bank' },
};

const makePage = (overrides = {}) => ({
    data: [transfer],
    total: 1,
    per_page: 10,
    current_page: 1,
    last_page: 1,
    prev_page_url: null,
    next_page_url: null,
    ...overrides,
});

const mountPage = (page = makePage()) =>
    mount(TransfersIndex, {
        props: {
            transfers: page,
        },
    });

describe('Transfers Index buttons', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('menampilkan akun asal dan tujuan tiap transfer', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Cash');
        expect(wrapper.text()).toContain('Bank Account');
        expect(wrapper.text()).toContain('Top up e-wallet');
    });

    it('tombol "New Transfer" menautkan ke transfers.create', () => {
        const wrapper = mountPage();

        const link = wrapper.find('[data-testid="new-transfer"]');
        expect(link.exists()).toBe(true);
        expect(link.attributes('href')).toBe(global.route('transfers.create'));
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

    it('pagination Next memuat URL berikutnya', async () => {
        const wrapper = mountPage(
            makePage({
                total: 21,
                current_page: 2,
                last_page: 3,
                prev_page_url: '/transfers?page=1',
                next_page_url: '/transfers?page=3',
            }),
        );

        const nextButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Next');
        await nextButton.trigger('click');
        await flushPromises();

        expect(router.get).toHaveBeenCalledWith(
            '/transfers?page=3',
            {},
            expect.any(Object),
        );
    });

    it('menampilkan empty state saat belum ada transfer', () => {
        const wrapper = mountPage(
            makePage({ data: [], total: 0, last_page: 1 }),
        );

        expect(wrapper.text()).toContain('No transfers yet.');
    });
});
