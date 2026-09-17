import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

const { post, pageState } = vi.hoisted(() => ({
    post: vi.fn(),
    pageState: {
        props: {
            auth: {
                user: {
                    name: 'Ada Lovelace',
                    email: 'ada@fintrack.test',
                },
            },
            flash: {},
            errors: {},
        },
    },
}));

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        name: 'Link',
        props: ['href', 'as', 'method'],
        template: '<a :href="href"><slot /></a>',
    },
    router: { post },
    usePage: () => pageState,
}));

vi.mock('@/Composables/useSwal', () => ({
    confirmDelete: vi.fn(),
    confirmAction: vi.fn(),
    errorPopup: vi.fn(),
    toastSuccess: vi.fn(),
    toastError: vi.fn(),
    errorList: vi.fn(),
    useSwal: () => ({}),
}));

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const current = vi.fn(() => false);
const routeMock = vi.fn((name, params) => {
    if (!name) {
        return { current };
    }

    return params ? `${name}?${JSON.stringify(params)}` : name;
});

const mountLayout = () =>
    mount(AuthenticatedLayout, {
        global: {
            mocks: {
                route: routeMock,
                $page: {
                    props: {
                        auth: {
                            user: {
                                name: 'Ada Lovelace',
                                email: 'ada@fintrack.test',
                            },
                        },
                    },
                },
            },
        },
        slots: {
            header: '<h2>Page Heading</h2>',
            default: '<div>Page Content</div>',
        },
    });

const linkFor = (wrapper, label) =>
    wrapper.findAll('a').find((link) => link.text().includes(label));

describe('AuthenticatedLayout (sidebar)', () => {
    beforeEach(() => {
        post.mockClear();
        current.mockReset();
        current.mockReturnValue(false);
        window.localStorage.clear();
        document.documentElement.classList.remove('dark');
    });

    it('menampilkan brand, seluruh menu navigasi, dan informasi user', () => {
        const wrapper = mountLayout();

        expect(wrapper.text()).toContain('finTrack');

        ['Dashboard', 'Transactions', 'Recurring', 'Accounts', 'Categories', 'Budgets', 'Reports'].forEach(
            (label) => {
                expect(linkFor(wrapper, label)).toBeTruthy();
            },
        );

        expect(wrapper.text()).toContain('Ada Lovelace');
        expect(wrapper.text()).toContain('ada@fintrack.test');
        expect(wrapper.find('aside').exists()).toBe(true);
    });

    it('menyediakan link menuju route terkait', () => {
        const wrapper = mountLayout();

        expect(linkFor(wrapper, 'Dashboard').attributes('href')).toBe('dashboard');
        expect(linkFor(wrapper, 'Transactions').attributes('href')).toBe(
            'transactions.index',
        );
        expect(linkFor(wrapper, 'Reports').attributes('href')).toBe('reports.index');
        expect(linkFor(wrapper, 'Recurring').attributes('href')).toBe(
            'recurring-transactions.index',
        );
        expect(linkFor(wrapper, 'Budgets').attributes('href')).toBe('budgets.index');
        expect(linkFor(wrapper, 'Profile').attributes('href')).toBe('profile.edit');
    });

    it('menandai menu aktif berdasarkan route saat ini', () => {
        current.mockImplementation((pattern) => pattern === 'transactions.*');

        const wrapper = mountLayout();

        expect(linkFor(wrapper, 'Transactions').classes()).toContain('bg-primary-50');
        expect(linkFor(wrapper, 'Transactions').classes()).toContain('text-primary-700');
        expect(linkFor(wrapper, 'Dashboard').classes()).not.toContain('bg-primary-50');
    });

    it('membuka dan menutup drawer sidebar di layar kecil', async () => {
        const wrapper = mountLayout();

        expect(wrapper.find('aside').classes()).toContain('-translate-x-full');

        await wrapper.find('[aria-label="Open navigation"]').trigger('click');
        expect(wrapper.find('aside').classes()).toContain('translate-x-0');

        await wrapper.find('[aria-label="Close navigation"]').trigger('click');
        expect(wrapper.find('aside').classes()).toContain('-translate-x-full');
    });

    it('menampilkan bottom navigation berisi 8 menu utama (mobile)', () => {
        current.mockImplementation((pattern) => pattern === 'reports.*');

        const wrapper = mountLayout();
        const bottomNav = wrapper.findAll('nav')[1];

        expect(bottomNav).toBeTruthy();
        expect(bottomNav.find('div').classes()).toContain('grid-cols-4');

        const links = bottomNav.findAll('a');
        expect(links).toHaveLength(8);
        expect(links.map((link) => link.text())).toEqual([
            'Dashboard',
            'Transactions',
            'Recurring',
            'Accounts',
            'Categories',
            'Budgets',
            'Savings',
            'Reports',
        ]);
        expect(links[7].classes()).toContain('text-primary-600');
        expect(links[0].classes()).toContain('text-slate-400');
    });

    it('mengirim request logout saat tombol Log Out diklik', async () => {
        const wrapper = mountLayout();

        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Log Out'))
            .trigger('click');

        expect(post).toHaveBeenCalledWith('logout');
    });

    it('men-toggle dark mode lewat tombol di topbar', async () => {
        const wrapper = mountLayout();

        const toggle = wrapper.find('[aria-label="Toggle dark mode"]');
        expect(toggle.exists()).toBe(true);

        await toggle.trigger('click');
        await nextTick();

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(window.localStorage.getItem('theme')).toBe('dark');

        await toggle.trigger('click');
        await nextTick();

        expect(document.documentElement.classList.contains('dark')).toBe(false);
        expect(window.localStorage.getItem('theme')).toBe('light');
    });

    it('menampilkan page heading dan konten', () => {
        const wrapper = mountLayout();

        expect(wrapper.text()).toContain('Page Heading');
        expect(wrapper.text()).toContain('Page Content');
    });

    it('menampilkan footer dengan copyright tahun berjalan dan versi', () => {
        const wrapper = mountLayout();

        expect(wrapper.find('footer').exists()).toBe(true);
        expect(wrapper.find('footer').text()).toContain(
            `© ${new Date().getFullYear()} finTrack. All rights reserved.`,
        );
        expect(wrapper.find('footer').text()).toContain('v1.0.0');
    });
});
