import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

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

let lastForm = null;

const makeForm = (initial) => {
    const form = reactive({
        ...initial,
        errors: {},
        processing: false,
        set: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
        reset: vi.fn(),
        transform: vi.fn(() => form),
    });
    lastForm = form;
    return form;
};

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        useForm: vi.fn((data) => makeForm(data)),
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

import TransferForm from '@/Pages/Transfers/Partials/TransferForm.vue';

const accounts = [
    { id: 1, name: 'Cash', type: 'cash' },
    { id: 2, name: 'Bank Account', type: 'bank' },
    { id: 3, name: 'E-Wallet', type: 'ewallet' },
];

const mountForm = (props = {}) =>
    mount(TransferForm, {
        props: {
            accounts,
            defaults: { account_id: 1, transaction_date: '2026-09-17' },
            ...props,
        },
    });

describe('TransferForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        swal.confirmAction.mockResolvedValue(true);
        lastForm = null;
    });

    it('menampilkan akun asal sebagai default dan mengecualikannya dari akun tujuan', () => {
        const wrapper = mountForm();

        expect(wrapper.find('#account_id').element.value).toBe('1');

        const options = wrapper
            .findAll('#transfer_to_account_id option')
            .map((option) => option.text());
        expect(options).toContain('Bank Account (bank)');
        expect(options).toContain('E-Wallet (ewallet)');
        expect(options).not.toContain('Cash (cash)');
    });

    it('mengecualikan akun asal yang dipilih dari daftar akun tujuan', async () => {
        const wrapper = mountForm();

        await wrapper.find('#account_id').setValue('2');
        await wrapper.vm.$nextTick();

        const options = wrapper
            .findAll('#transfer_to_account_id option')
            .map((option) => option.text());
        expect(options).toContain('Cash (cash)');
        expect(options).not.toContain('Bank Account (bank)');
    });

    it('tombol Swap menukar akun asal dan tujuan', async () => {
        const wrapper = mountForm();

        await wrapper.find('#account_id').setValue('1');
        await wrapper.find('#transfer_to_account_id').setValue('2');
        await wrapper.vm.$nextTick();

        const swapButton = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Swap'));
        await swapButton.trigger('click');
        await wrapper.vm.$nextTick();

        expect(lastForm.account_id).toBe(2);
        expect(lastForm.transfer_to_account_id).toBe(1);
    });

    it('submit meminta konfirmasi ringkasan lalu POST transfers.store', async () => {
        const wrapper = mountForm();

        await wrapper.find('#transfer_to_account_id').setValue('2');
        await wrapper.find('#amount').setValue(150_000);

        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(swal.confirmAction).toHaveBeenCalledWith(
            expect.objectContaining({
                title: expect.stringContaining('Cash'),
                confirmButtonText: 'Konfirmasi',
            }),
        );
        expect(lastForm.post).toHaveBeenCalledWith('transfers.store');
        expect(lastForm.account_id).toBe(1);
        expect(lastForm.transfer_to_account_id).toBe(2);
        expect(lastForm.amount).toBe(150_000);
    });

    it('submit yang dibatalkan tidak mengirim POST', async () => {
        swal.confirmAction.mockResolvedValue(false);
        const wrapper = mountForm();

        await wrapper.find('#transfer_to_account_id').setValue('2');
        await wrapper.find('#amount').setValue(150_000);

        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(lastForm.post).not.toHaveBeenCalled();
    });
});
