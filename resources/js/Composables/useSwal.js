import Swal from 'sweetalert2';

export const swalTheme = {
    primary: '#0284c7',
    danger: '#dc2626',
    neutral: '#64748b',
    toastDuration: 2500,
    toastPosition: 'top-end',
};

const baseOptions = {
    confirmButtonColor: swalTheme.primary,
    cancelButtonColor: swalTheme.neutral,
    customClass: {
        popup: 'fintrack-swal',
        confirmButton: 'fintrack-swal-confirm',
        cancelButton: 'fintrack-swal-cancel',
    },
};

const Toast = Swal.mixin({
    toast: true,
    position: swalTheme.toastPosition,
    showConfirmButton: false,
    timer: swalTheme.toastDuration,
    timerProgressBar: true,
    customClass: {
        popup: 'fintrack-swal fintrack-swal-toast',
    },
    didOpen: (element) => {
        element.addEventListener('mouseenter', Swal.stopTimer);
        element.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

const escapeHtml = (value) =>
    String(value).replace(
        /[&<>"']/g,
        (character) =>
            ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            })[character],
    );

export const errorList = (errors = {}) => {
    const messages = [
        ...new Set(Object.values(errors).flat().filter(Boolean)),
    ];

    if (messages.length === 0) {
        return null;
    }

    const items = messages
        .map((message) => `<li>${escapeHtml(message)}</li>`)
        .join('');

    return `<ul class="fintrack-swal-list">${items}</ul>`;
};

export const errorPopup = ({
    title = 'Terjadi kesalahan',
    text,
    errors,
    html,
    confirmButtonText = 'Tutup',
} = {}) => {
    const content = html ?? errorList(errors);

    return Swal.fire({
        ...baseOptions,
        icon: 'error',
        title,
        text: content ? undefined : text,
        html: content ?? undefined,
        confirmButtonText,
    });
};

export const confirmDelete = async ({
    title = 'Apakah kamu yakin ingin menghapus data ini?',
    text,
    html,
    confirmButtonText = 'Ya, Hapus',
} = {}) => {
    const result = await Swal.fire({
        ...baseOptions,
        icon: 'warning',
        title,
        text: html ? undefined : text,
        html: html ?? undefined,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Batal',
        confirmButtonColor: swalTheme.danger,
        reverseButtons: true,
        focusCancel: true,
    });

    return result.isConfirmed;
};

export const confirmAction = async ({
    title,
    text,
    html,
    icon = 'question',
    confirmButtonText = 'Konfirmasi',
} = {}) => {
    const result = await Swal.fire({
        ...baseOptions,
        icon,
        title,
        text: html ? undefined : text,
        html: html ?? undefined,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Batal',
        reverseButtons: true,
    });

    return result.isConfirmed;
};

export const toastSuccess = (title = 'Data berhasil disimpan') =>
    Toast.fire({ icon: 'success', title });

export const toastError = (title = 'Terjadi kesalahan') =>
    Toast.fire({ icon: 'error', title });

export const useSwal = () => ({
    Swal,
    swalTheme,
    errorList,
    errorPopup,
    confirmDelete,
    confirmAction,
    toastSuccess,
    toastError,
});

export default useSwal;
