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

const bottomNav = (wrapper) => {
    const navs = wrapper.findAll('nav');
    return navs[navs.length - 1];
};

describe('AuthenticatedLayout (sidebar desktop)', () => {
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

        expect(linkFor(wrapper, 'Transactions').classes()).toContain('bg-blue-100');
        expect(linkFor(wrapper, 'Transactions').classes()).toContain('text-blue-600');
        expect(linkFor(wrapper, 'Dashboard').classes()).not.toContain('bg-blue-100');
    });

    it('menampilkan header mobile dengan brand dan ikon aksi', () => {
        const wrapper = mountLayout();
        const header = wrapper.find('header');

        expect(header.text()).toContain('FinTrack');
        expect(wrapper.find('[aria-label="Toggle dark mode"]').exists()).toBe(true);
        expect(wrapper.find('[aria-label="Log Out"]').exists()).toBe(true);
        expect(wrapper.find('[aria-label="Notifikasi"]').exists()).toBe(false);
    });

    it('menampilkan bottom navigation berisi 5 item (mobile)', () => {
        current.mockImplementation((pattern) => pattern === 'transactions.*');

        const wrapper = mountLayout();
        const nav = bottomNav(wrapper);

        expect(nav.exists()).toBe(true);
        expect(nav.find('div').classes()).toContain('grid-cols-5');

        const links = nav.findAll('a');
        expect(links.map((link) => link.attributes('href'))).toEqual([
            'dashboard',
            'transactions.index',
            'transactions.create',
            'accounts.index',
        ]);

        expect(nav.text()).toContain('Beranda');
        expect(nav.text()).toContain('Transaksi');
        expect(nav.text()).toContain('Tambah');
        expect(nav.text()).toContain('Akun');
        expect(nav.text()).toContain('Lainnya');

        expect(links[1].classes()).toContain('text-primary-600');
        expect(links[0].classes()).toContain('text-slate-400');
    });

    it('membuka bottom sheet "Lainnya" berisi halaman yang tidak ada di bottom nav', async () => {
        const wrapper = mountLayout();

        await bottomNav(wrapper).findAll('button')[0].trigger('click');

        const sheet = wrapper.find('[data-testid="more-sheet"]');
        expect(sheet.exists()).toBe(true);

        const links = sheet.findAll('a');
        expect(links.map((link) => link.text())).toEqual([
            'Kategori',
            'Budget',
            'Target Tabungan',
            'Reports',
        ]);
        expect(links[0].attributes('href')).toBe('categories.index');
        expect(links[1].attributes('href')).toBe('budgets.index');
        expect(links[2].attributes('href')).toBe('savings-goals.index');
        expect(links[3].attributes('href')).toBe('reports.index');

        await wrapper.find('[data-testid="more-sheet-overlay"]').trigger('click');
        expect(wrapper.find('[data-testid="more-sheet"]').exists()).toBe(false);
    });

    it('mengirim request logout saat tombol Log Out diklik', async () => {
        const wrapper = mountLayout();

        await wrapper
            .find('button[aria-label="Log Out"]')
            .trigger('click');

        expect(post).toHaveBeenCalledWith('logout');
    });

    it('men-toggle dark mode lewat tombol di header mobile', async () => {
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