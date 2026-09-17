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

import BudgetForm from '@/Pages/Budgets/Partials/BudgetForm.vue';

const categories = [
    { id: 10, name: 'Makan', color: '#ef4444' },
    { id: 20, name: 'Transport', color: '#3b82f6' },
];

const defaults = { month: 9, year: 2026 };

const mountForm = (props = {}) =>
    mount(BudgetForm, {
        props: {
            categories,
            defaults,
            ...props,
        },
    });

describe('BudgetForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        lastForm = null;
    });

    it('menampilkan dropdown kategori expense beserta bulan dan tahun default', () => {
        const wrapper = mountForm();

        const options = wrapper
            .findAll('#category_id option')
            .map((option) => option.text());
        expect(options).toContain('Makan');
        expect(options).toContain('Transport');

        expect(wrapper.find('#month').element.value).toBe('9');
        expect(wrapper.find('#year').element.value).toBe('2026');
    });

    it('menyembunyikan pilihan bulan saat period yearly', async () => {
        const wrapper = mountForm();

        expect(wrapper.find('#month').exists()).toBe(true);

        await wrapper.find('[data-testid="period-year"]').setValue();
        await flushPromises();

        expect(wrapper.find('#month').exists()).toBe(false);
        expect(lastForm.period).toBe('year');
        expect(lastForm.month).toBeNull();
    });

    it('mengembalikan bulan default saat period kembali ke monthly', async () => {
        const wrapper = mountForm();

        await wrapper.find('[data-testid="period-year"]').setValue();
        await flushPromises();

        await wrapper.find('[data-testid="period-month"]').setValue();
        await flushPromises();

        expect(wrapper.find('#month').exists()).toBe(true);
        expect(lastForm.month).toBe(9);
    });

    it('submit mengirim POST budgets.store dengan data isian', async () => {
        const wrapper = mountForm();

        await wrapper.find('#category_id').setValue('20');
        await wrapper.find('#amount_limit').setValue(750_000);

        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(lastForm.post).toHaveBeenCalledWith('budgets.store');
        expect(lastForm.category_id).toBe(20);
        expect(lastForm.amount_limit).toBe(750_000);
        expect(lastForm.period).toBe('month');
    });

    it('submit pada mode edit mengirim PUT budgets.update/{id}', async () => {
        const wrapper = mountForm({
            budget: {
                id: 7,
                category_id: 10,
                amount_limit: 1_000_000,
                period: 'month',
                month: 9,
                year: 2026,
            },
        });

        await wrapper.find('#amount_limit').setValue(2_000_000);

        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(lastForm.put).toHaveBeenCalledWith(
            expect.stringContaining('budgets.update'),
        );
        expect(lastForm.amount_limit).toBe(2_000_000);
    });
});
