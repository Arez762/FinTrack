import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

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
        Link: { name: 'Link', render: () => null },
        router: {
            get: vi.fn(),
            post: vi.fn(),
            put: vi.fn(),
            delete: vi.fn(),
        },
    };
});

import TransactionForm from '@/Pages/Transactions/Partials/TransactionForm.vue';

const accounts = [
    { id: 1, name: 'Cash', type: 'cash' },
    { id: 2, name: 'Bank Account', type: 'bank' },
];

const categories = [
    { id: 10, name: 'Gaji', type: 'income', color: '#22c55e' },
    { id: 20, name: 'Bonus', type: 'income', color: '#06b6d4' },
    { id: 30, name: 'Makan', type: 'expense', color: '#ef4444' },
    { id: 40, name: 'Transport', type: 'expense', color: '#f97316' },
];

const mountForm = (props = {}) =>
    mount(TransactionForm, {
        props: {
            accounts,
            categories,
            ...props,
        },
    });

describe('TransactionForm buttons', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        lastForm = null;
    });

    it('tombol Cancel pada mode modal memanggil emit close', async () => {
        const wrapper = mountForm({ modal: true });

        const cancelButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Cancel');
        expect(cancelButton).toBeTruthy();

        await cancelButton.trigger('click');

        expect(wrapper.emitted('close')).toBeTruthy();
    });

    it('tombol Create mengirim POST transactions.store dengan data isian', async () => {
        const wrapper = mountForm();

        await wrapper.find('#account_id').setValue('1');
        await wrapper.find('#category_id').setValue('30');
        await wrapper.find('#amount').setValue(50_000);

        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(lastForm.post).toHaveBeenCalledWith(
            'transactions.store',
            expect.objectContaining({ onSuccess: expect.any(Function) }),
        );
        expect(lastForm.account_id).toBe(1);
        expect(lastForm.category_id).toBe(30);
        expect(lastForm.amount).toBe(50_000);
    });

    it('tombol Update mengirim PUT transactions.update/{id}', async () => {
        const wrapper = mountForm({
            transaction: {
                id: 7,
                account_id: 1,
                category_id: 30,
                type: 'expense',
                amount: 10_000,
                description: null,
                transaction_date: '2026-09-16',
            },
        });

        await wrapper.find('#amount').setValue(12_500);

        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(lastForm.put).toHaveBeenCalledWith(
            expect.stringContaining('transactions.update'),
            expect.objectContaining({ onSuccess: expect.any(Function) }),
        );
        expect(lastForm.amount).toBe(12_500);
    });

    it('dropdown kategori terfilter ulang sesuai type yang dipilih', async () => {
        const wrapper = mountForm();

        await wrapper.find('#category_id').setValue('30');
        await wrapper.vm.$nextTick();
        expect(wrapper.find('#category_id').element.value).toBe('30');

        await wrapper.find('input[value="income"]').setValue();
        await wrapper.vm.$nextTick();

        expect(wrapper.find('#category_id').element.value).toBe('');

        const options = wrapper
            .findAll('#category_id option')
            .map((option) => option.text());
        expect(options).not.toContain('Makan');
        expect(options).toContain('Gaji');
        expect(options).toContain('Bonus');
    });

    it('select account menampilkan semua akun', async () => {
        const wrapper = mountForm();

        const options = wrapper
            .findAll('#account_id option')
            .map((option) => option.text());
        expect(options).toContain('Cash (cash)');
        expect(options).toContain('Bank Account (bank)');
    });
});