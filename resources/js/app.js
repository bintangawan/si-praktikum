import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

import { driveSubmission } from './drive-link';
import { studentSearch } from './student-search';
Alpine.data('driveSubmission', driveSubmission);
Alpine.data('studentSearch', studentSearch);
window.Alpine = Alpine;
window.Swal = Swal;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const flash = document.getElementById('app-flash-message');

    if (flash?.dataset.message) {
        const type = flash.dataset.type ?? 'info';
        Swal.fire({
            icon: type === 'error' ? 'error' : type === 'success' ? 'success' : 'info',
            title: type === 'error' ? 'Tidak dapat diproses' : type === 'success' ? 'Berhasil' : 'Informasi',
            text: flash.dataset.message,
            confirmButtonText: 'Oke',
            confirmButtonColor: '#047857',
            timer: type === 'success' ? 3200 : undefined,
            timerProgressBar: type === 'success',
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const result = await Swal.fire({
                icon: form.dataset.confirmIcon ?? 'warning',
                title: form.dataset.confirmTitle ?? 'Konfirmasi tindakan',
                text: form.dataset.confirm,
                showCancelButton: true,
                confirmButtonText: form.dataset.confirmButton ?? 'Ya, lanjutkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: form.dataset.confirmColor ?? '#dc2626',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                focusCancel: true,
            });

            if (result.isConfirmed) form.submit();
        });
    });
});
