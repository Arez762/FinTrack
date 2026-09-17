import { ref, watch } from 'vue';

const STORAGE_KEY = 'theme';

const isSystemDark = () =>
    typeof window !== 'undefined' &&
    (window.matchMedia?.('(prefers-color-scheme: dark)')?.matches ?? false);

const resolveInitial = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    const stored = window.localStorage.getItem(STORAGE_KEY);

    if (stored === 'dark') {
        return true;
    }

    if (stored === 'light') {
        return false;
    }

    return isSystemDark();
};

const isDark = ref(resolveInitial());

const applyTheme = (dark) => {
    document.documentElement.classList.toggle('dark', dark);
};

if (typeof window !== 'undefined') {
    applyTheme(isDark.value);
}

watch(isDark, (dark) => {
    applyTheme(dark);
    window.localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light');
});

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
};

export { isDark, toggleDarkMode, STORAGE_KEY };