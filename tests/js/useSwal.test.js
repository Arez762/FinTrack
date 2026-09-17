import { beforeEach, describe, expect, it, vi } from 'vitest';

const swal = vi.hoisted(() => {
    const fire = vi.fn();
    const mixin = vi.fn((options) => {
        mixin.options = options;

        return { fire };
    });

    return { fire, mixin };
});

vi.mock('sweetalert2', () => ({
    default: {
        fire: swal.fire,
        mixin: swal.mixin,
        stopTimer: vi.fn(),
        resumeTimer: vi.fn(),
    },
}));

import {
    confirmAction,
    confirmDelete,
    errorList,
    errorPopup,
    swalTheme,
    toastError,
    toastSuccess,
} from '@/Composables/useSwal';

describe('useSwal helpers', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('errorList menggabungkan dan menghapus pesan duplikat', () => {
        const html = errorList({
            amount: ['Jumlah wajib diisi.'],
            name: ['Nama wajib diisi.', 'Jumlah wajib diisi.'],
        });

        expect(html).toContain('<ul class="fintrack-swal-list">');
        expect(html.match(/<li>/g)).toHaveLength(2);
        expect(html).toContain('Jumlah wajib diisi.');
        expect(html).toContain('Nama wajib diisi.');
    });

    it('errorList mengembalikan null saat tidak ada error', () => {
        expect(errorList({})).toBeNull();
        expect(errorList(undefined)).toBeNull();
    });

    it('errorList meng-escape HTML dari pesan error', () => {
        const html = errorList({ name: ['<script>alert(1)</script>'] });

        expect(html).not.toContain('<script>');
        expect(html).toContain('&lt;script&gt;');
    });

    it('confirmDelete mengembalikan true hanya saat dikonfirmasi', async () => {
        swal.fire.mockResolvedValue({ isConfirmed: true });

        await expect(confirmDelete({ title: 'Hapus?' })).resolves.toBe(true);

        const options = swal.fire.mock.calls.at(-1)[0];
        expect(options.icon).toBe('warning');
        expect(options.confirmButtonText).toBe('Ya, Hapus');
        expect(options.cancelButtonText).toBe('Batal');
        expect(options.confirmButtonColor).toBe(swalTheme.danger);
        expect(options.showCancelButton).toBe(true);
    });

    it('confirmDelete mengembalikan false saat dibatalkan', async () => {
        swal.fire.mockResolvedValue({ isConfirmed: false });

        await expect(confirmDelete()).resolves.toBe(false);
    });

    it('confirmAction memakai tombol konfirmasi netral', async () => {
        swal.fire.mockResolvedValue({ isConfirmed: true });

        await expect(
            confirmAction({ title: 'Lanjut?', confirmButtonText: 'Konfirmasi' }),
        ).resolves.toBe(true);

        const options = swal.fire.mock.calls.at(-1)[0];
        expect(options.icon).toBe('question');
        expect(options.confirmButtonColor).toBe(swalTheme.primary);
        expect(options.confirmButtonText).toBe('Konfirmasi');
    });

    it('errorPopup menampilkan ringkasan pesan error', async () => {
        swal.fire.mockResolvedValue({ isConfirmed: true });

        await errorPopup({ errors: { amount: ['Jumlah wajib diisi.'] } });

        const options = swal.fire.mock.calls.at(-1)[0];
        expect(options.icon).toBe('error');
        expect(options.html).toContain('Jumlah wajib diisi.');
        expect(options.confirmButtonText).toBe('Tutup');
    });

    it('toastSuccess dan toastError memakai toast yang sama', () => {
        toastSuccess('Data berhasil disimpan.');
        toastError('Gagal menyimpan.');

        expect(swal.mixin.options).toMatchObject({
            toast: true,
            position: swalTheme.toastPosition,
            timer: swalTheme.toastDuration,
            showConfirmButton: false,
        });
        expect(swal.fire).toHaveBeenCalledWith({
            icon: 'success',
            title: 'Data berhasil disimpan.',
        });
        expect(swal.fire).toHaveBeenCalledWith({
            icon: 'error',
            title: 'Gagal menyimpan.',
        });
    });
});
