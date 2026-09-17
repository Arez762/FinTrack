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
import AccountsIndex from '@/Pages/Accounts/Index.vue';
import CategoriesIndex from '@/Pages/Categories/Index.vue';
import CategoryForm from '@/Pages/Categories/Partials/CategoryForm.vue';

const accounts = [
    { id: 1, name: 'Cash', type: 'cash', initial_balance: 1000, balance: 1250 },
    { id: 2, name: 'Bank Account', type: 'bank', initial_balance: 5000, balance: 900 },
];

const categories = [
    { id: 3, name: 'Makan', type: 'expense', icon: null, color: '#ef4444' },
    { id: 4, name: 'Gaji', type: 'income', icon: null, color: '#22c55e' },
];

describe('Delete buttons (Account & Category indexes)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('tombol Delete akun memanggil confirmDelete lalu router.delete', async () => {
        swal.confirmDelete.mockResolvedValue(true);
        const wrapper = mount(AccountsIndex, { props: { accounts } });

        const deleteButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Delete');
        await deleteButton.trigger('click');
        await flushPromises();

        expect(swal.confirmDelete).toHaveBeenCalledWith(
            expect.objectContaining({
                title: 'Hapus akun ini?',
                text: expect.stringContaining('Cash'),
            }),
        );
        expect(router.delete).toHaveBeenCalledWith(
            expect.stringContaining('accounts.destroy'),
        );
    });

    it('tombol Delete akun dibatalkan tidak memanggil router.delete', async () => {
        swal.confirmDelete.mockResolvedValue(false);
        const wrapper = mount(AccountsIndex, { props: { accounts } });

        const deleteButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Delete');
        await deleteButton.trigger('click');
        await flushPromises();

        expect(swal.confirmDelete).toHaveBeenCalled();
        expect(router.delete).not.toHaveBeenCalled();
    });

    it('tombol Delete kategori memanggil confirmDelete lalu router.delete', async () => {
        swal.confirmDelete.mockResolvedValue(true);
        const wrapper = mount(CategoriesIndex, { props: { categories } });

        const deleteButton = wrapper
            .findAll('button')
            .find(
                (b) =>
                    b.text().trim() === 'Delete' &&
                    wrapper.text().includes('Makan'),
            );
        await deleteButton.trigger('click');
        await flushPromises();

        expect(router.delete).toHaveBeenCalledWith(
            expect.stringContaining('categories.destroy'),
        );
    });
});

describe('Category form color swatches', () => {
    it('klik preset swatch mengubah warna terpilih', async () => {
        const wrapper = mount(CategoryForm, {
            props: { category: null },
        });

        expect(wrapper.find('code').text()).toBe('#3b82f6');

        const target = wrapper.find('button[aria-label="Set color to #22c55e"]');
        expect(target.classes()).not.toContain('scale-110');

        await target.trigger('click');
        await flushPromises();

        expect(wrapper.find('code').text()).toBe('#22c55e');
        expect(target.classes()).toContain('scale-110');
    });
});