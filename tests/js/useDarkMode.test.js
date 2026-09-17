import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

const importComposable = () => import('@/Composables/useDarkMode');

const mockSystemDark = (matches) => {
    window.matchMedia = vi.fn().mockReturnValue({
        matches,
        media: '(prefers-color-scheme: dark)',
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    });
};

describe('useDarkMode', () => {
    beforeEach(() => {
        window.localStorage.clear();
        document.documentElement.classList.remove('dark');
        vi.resetModules();
    });

    afterEach(() => {
        window.localStorage.clear();
        document.documentElement.classList.remove('dark');
    });

    it('menerapkan class dark pada <html> saat prefensi tersimpan "dark"', async () => {
        window.localStorage.setItem('theme', 'dark');

        const { isDark } = await importComposable();

        expect(isDark.value).toBe(true);
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('tidak menambahkan class dark saat prefensi tersimpan "light"', async () => {
        window.localStorage.setItem('theme', 'light');

        const { isDark } = await importComposable();

        expect(isDark.value).toBe(false);
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('menurunkan preferensi dari sistem saat localStorage belum diisi', async () => {
        mockSystemDark(true);

        const { isDark } = await importComposable();
        expect(isDark.value).toBe(true);

        window.localStorage.clear();
        document.documentElement.classList.remove('dark');
        vi.resetModules();

        mockSystemDark(false);

        const { isDark: isDarkLight } = await importComposable();
        expect(isDarkLight.value).toBe(false);
    });

    it('toggleDarkMode membalik isDark, memperbarui class <html>, dan menyimpan ke localStorage', async () => {
        window.localStorage.setItem('theme', 'light');

        const { isDark, toggleDarkMode, STORAGE_KEY } = await importComposable();

        expect(isDark.value).toBe(false);

        toggleDarkMode();
        await nextTick();

        expect(isDark.value).toBe(true);
        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(window.localStorage.getItem(STORAGE_KEY)).toBe('dark');

        toggleDarkMode();
        await nextTick();

        expect(isDark.value).toBe(false);
        expect(document.documentElement.classList.contains('dark')).toBe(false);
        expect(window.localStorage.getItem(STORAGE_KEY)).toBe('light');
    });
});