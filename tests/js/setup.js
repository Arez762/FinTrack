import { config } from '@vue/test-utils';
import { vi } from 'vitest';

const routeMock = vi.fn((name, params) =>
    params ? `${name}?${JSON.stringify(params)}` : name,
);

global.route = routeMock;
global.confirm = vi.fn(() => true);

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