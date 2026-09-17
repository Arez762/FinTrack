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

import SavingsGoalForm from '@/Pages/SavingsGoals/Partials/SavingsGoalForm.vue';

const accounts = [
    { id: 1, name: 'Kas', type: 'cash' },
    { id: 2, name: 'Bank Mandiri', type: 'bank' },
];

const mountForm = (props = {}) =>
    mount(SavingsGoalForm, {
        props: {
            accounts,
            ...props,
        },
    });

describe('SavingsGoalForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        lastForm = null;
    });

    it('mode baru mengirim POST savings-goals.store dengan isian', async () => {
        const wrapper = mountForm();

        await wrapper.find('#name').setValue('Dana Darurat');
        await wrapper.find('#target_amount').setValue(5_000_000);
        await wrapper.find('#target_date').setValue('2026-12-31');
        await wrapper.find('#account_id').setValue('2');
        await wrapper.find('[aria-label="Set icon to 🏠"]').trigger('click');

        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(lastForm.post).toHaveBeenCalledWith('savings-goals.store');
        expect(lastForm.name).toBe('Dana Darurat');
        expect(lastForm.target_amount).toBe(5_000_000);
        expect(lastForm.target_date).toBe('2026-12-31');
        expect(lastForm.account_id).toBe(2);
        expect(lastForm.icon).toBe('🏠');
    });

    it('mode edit mengirim PUT savings-goals.update/{id} dan memuat nilai lama', async () => {
        const wrapper = mountForm({
            savingsGoal: {
                id: 9,
                name: 'Dana Darurat',
                target_amount: 1_000_000,
                target_date: '2026-10-01',
                account_id: 1,
                icon: '💪',
                color: '#3b82f6',
            },
        });

        expect(wrapper.find('#name').element.value).toBe('Dana Darurat');
        expect(wrapper.find('#target_amount').element.value).toBe('1000000');

        await wrapper.find('#target_amount').setValue(1_500_000);

        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(lastForm.put).toHaveBeenCalledWith(
            expect.stringContaining('savings-goals.update'),
        );
        expect(lastForm.target_amount).toBe(1_500_000);
        expect(lastForm.account_id).toBe(1);
    });

    it('dropdown akun menampilkan opsi tanpa akun dan semua akun', () => {
        const wrapper = mountForm();

        const options = wrapper
            .findAll('#account_id option')
            .map((option) => option.text());
        expect(options).toContain('Tidak terkait akun');
        expect(options).toContain('Kas (cash)');
        expect(options).toContain('Bank Mandiri (bank)');
    });

    it('tombol ikon preset memperbarui form.icon', async () => {
        const wrapper = mountForm();

        await wrapper.find('[aria-label="Set icon to 🚗"]').trigger('click');

        expect(lastForm.icon).toBe('🚗');
    });

    it('menampilkan semua preset warna', () => {
        const wrapper = mountForm();

        const swatches = wrapper
            .findAll('button')
            .filter((b) => b.exists() && b.attributes('aria-label')?.startsWith('Set color to'));
        expect(swatches).toHaveLength(12);
    });
});