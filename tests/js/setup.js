import { config } from '@vue/test-utils';
import { vi } from 'vitest';

const routeMock = vi.fn((name, params) =>
    params ? `${name}?${JSON.stringify(params)}` : name,
);

global.route = routeMock;
global.confirm = vi.fn(() => true);

const createStorage = () => {
    const store = new Map();

    return {
        getItem: (key) => (store.has(key) ? store.get(key) : null),
        setItem: (key, value) => store.set(key, String(value)),
        removeItem: (key) => store.delete(key),
        clear: () => store.clear(),
        key: (index) => Array.from(store.keys())[index] ?? null,
        get length() {
            return store.size;
        },
    };
};

Object.defineProperty(window, 'localStorage', {
    configurable: true,
    value: createStorage(),
});

config.global.mocks.route = routeMock;

config.global.stubs = {
    Head: { name: 'Head', template: '<div />' },
    Link: {
        name: 'Link',
        props: ['href', 'as', 'method'],
        template: '<a :href="href"><slot /></a>',
    },
    AuthenticatedLayout: {
        name: 'AuthenticatedLayout',
        template: '<div><slot name="header" /><slot /></div>',
    },
};