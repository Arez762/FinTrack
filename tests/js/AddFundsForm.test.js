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

import AddFundsForm from '@/Pages/SavingsGoals/Partials/AddFundsForm.vue';

const formatIDR = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

const goal = {
    id: 4,
    name: 'Dana Darurat',
    target_amount: 1_000_000,
    current_amount: 250_000,
    percentage: 25,
};

const mountForm = (props = {}) =>
    mount(AddFundsForm, {
        props: {
            goal,
            ...props,
        },
    });

describe('AddFundsForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        lastForm = null;
    });

    it('menampilkan progres saat ini dan mengirim POST savings-goals.addFunds/{id}', async () => {
        const wrapper = mountForm();

        expect(wrapper.text()).toContain(
            `Saat ini ${formatIDR(250_000)} dari ${formatIDR(1_000_000)} (25%)`,
        );

        await wrapper.find('#amount').setValue(100_000);

        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();

        expect(lastForm.post).toHaveBeenCalledWith(
            expect.stringContaining('savings-goals.addFunds'),
            expect.objectContaining({
                preserveScroll: true,
                onSuccess: expect.any(Function),
            }),
        );
        expect(lastForm.amount).toBe(100_000);
    });

    it('tombol Batal memanggil emit close', async () => {
        const wrapper = mountForm();

        const cancelButton = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Batal');
        expect(cancelButton).toBeTruthy();

        await cancelButton.trigger('click');

        expect(wrapper.emitted('close')).toBeTruthy();
    });
});