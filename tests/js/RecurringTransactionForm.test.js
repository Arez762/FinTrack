import { flushPromises, mount } from '@vue/test-utils';
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
        router: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
    };
});

import RecurringTransactionForm from '@/Pages/RecurringTransactions/Partials/RecurringTransactionForm.vue';

const accounts = [
    { id: 1, name: 'Cash', type: 'cash' },
    { id: 2, name: 'Bank Account', type: 'bank' },
];

const categories = [
    { id: 10, name: 'Gaji', type: 'income', color: '#22c55e' },
    { id: 20, name: 'Makan', type: 'expense', color: '#ef4444' },
];

const defaults = { start_date: '2026-09-17' };

const mountForm = (props = {}) =>
    mount(RecurringTransactionForm, {
        props: {
            accounts,
            categories,
            defaults,
            ...props,
        },
    });

describe('RecurringTransactionForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        lastForm = null;
    });

    it('menampilkan pilihan frequency lengkap dan tanggal mulai default', () => {
        const wrapper = mountForm();

        const frequencies = wrapper
            .findAll('#frequency option')
            .map((option) => option.element.value);

        expect(frequencies).toEqual(['daily', 'weekly', 'monthly', 'yearly']);
        expect(wrapper.find('#start_date').element.value).toBe('2026-09-17');
        expect(wrapper.find('#frequency').element.value).toBe('monthly');
    });

    it('dropdown kategori terfilter sesuai type yang dipilih', async () => {
        const wrapper = mountForm();

        let options = wrapper
            .findAll('#category_id option')
            .map((option) => option.text());
        expect(options).toContain('Makan');
        expect(options).not.toContain('Gaji');

        await wrapper.find('[data-testid="type-income"]').setValue();
        await flushPromises();

        options = wrapper
            .findAll('#category_id option')
            .map((option) => option.text());
        expect(options).toContain('Gaji');
        expect(options).not.toContain('Makan');
    });

    it('mengosongkan kategori saat type berubah dan kategori lama tidak cocok', async () => {
        const wrapper = mountForm();

        await wrapper.find('#category_id').setValue('20');
        await wrapper.vm.$nextTick();
        expect(wrapper.find('#category_id').element.value).toBe('20');

        await wrapper.find('[data-testid="type-income"]').setValue();
        await flushPromises();

        expect(wrapper.find('#category_id').element.value).toBe('');
    });

    it('submit create mengirim POST recurring-transactions.store', async () => {
        const wrapper = mountForm();

        await wrapper.find('#account_id').setValue('1');
        await wrapper.find('#category_id').setValue('20');
        await wrapper.find('#amount').setValue(350_000);
        await wrapper.find('#frequency').setValue('weekly');
        await wrapper.find('#start_date').setValue('2026-09-20');
        await wrapper.find('#description').setValue('Bayar langganan');

        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(lastForm.post).toHaveBeenCalledWith('recurring-transactions.store');
        expect(lastForm.account_id).toBe(1);
        expect(lastForm.category_id).toBe(20);
        expect(lastForm.amount).toBe(350_000);
        expect(lastForm.frequency).toBe('weekly');
        expect(lastForm.start_date).toBe('2026-09-20');
    });

    it('submit edit mengirim PUT recurring-transactions.update/{id}', async () => {
        const wrapper = mountForm({
            recurringTransaction: {
                id: 7,
                account_id: 2,
                category_id: 20,
                type: 'expense',
                amount: 54_000,
                description: 'Langganan streaming',
                frequency: 'yearly',
                start_date: '2026-03-01',
                next_run_date: '2026-03-01',
                is_active: false,
                account: { id: 2, name: 'Bank Account', type: 'bank' },
                category: { id: 20, name: 'Makan', color: '#ef4444' },
            },
        });

        expect(wrapper.find('#frequency').element.value).toBe('yearly');
        expect(wrapper.find('#start_date').element.value).toBe('2026-03-01');
        expect(wrapper.find('#account_id').element.value).toBe('2');

        await wrapper.find('#amount').setValue(75_000);
        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(lastForm.put).toHaveBeenCalledWith(
            expect.stringContaining('recurring-transactions.update'),
        );
        expect(lastForm.amount).toBe(75_000);
    });
});
