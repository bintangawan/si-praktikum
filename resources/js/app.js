import './bootstrap';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import translations from '../../lang/en.json';

import { driveSubmission } from './drive-link';
import { studentSearch } from './student-search';

Alpine.data('driveSubmission', driveSubmission);
Alpine.data('studentSearch', studentSearch);
window.Alpine = Alpine;
window.Swal = Swal;

const locale = document.documentElement.lang === 'en' ? 'en' : 'id';
const translationEntries = Object.entries(translations).sort(([left], [right]) => right.length - left.length);

function translate(value) {
    if (locale !== 'en' || typeof value !== 'string' || value.length === 0) return value;

    const trimmed = value.trim();
    let translated = translations[trimmed];

    if (translated === undefined) {
        translated = trimmed;
        for (const [source, target] of translationEntries) {
            if (source.length < 5 || !translated.includes(source)) continue;
            translated = translated.split(source).join(target);
        }
    }

    return value.replace(trimmed, translated);
}

function translateElement(element) {
    if (!(element instanceof Element)) return;
    for (const attribute of ['placeholder', 'title', 'aria-label', 'alt', 'data-confirm', 'data-confirm-title', 'data-confirm-button']) {
        if (element.hasAttribute(attribute)) element.setAttribute(attribute, translate(element.getAttribute(attribute)));
    }
    if (element instanceof HTMLMetaElement && element.name === 'description') {
        element.content = translate(element.content);
    }

    if (element instanceof HTMLInputElement && ['button', 'submit', 'reset'].includes(element.type)) {
        element.value = translate(element.value);
    }
}

function translateSubtree(root) {
    if (locale !== 'en' || !(root instanceof Node)) return;
    const scope = root instanceof Element ? root : root.parentElement;
    if (scope?.closest('script, style')) return;

    if (root instanceof Element) translateElement(root);
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let node;
    while ((node = walker.nextNode())) {
        if (node.parentElement?.closest('script, style')) continue;
        const translated = translate(node.nodeValue ?? '');
        if (translated !== node.nodeValue) node.nodeValue = translated;
    }

    if (root instanceof Element) root.querySelectorAll('*').forEach(translateElement);
}

function confirmationCopy(form) {
    const target = form.querySelector('[name="role"]')?.value;
    const userName = form.dataset.userName ?? '';

    if (target === 'Mahasiswa') {
        return locale === 'en'
            ? { title: 'Remove Lab Assistant role?', text: `The Lab Assistant role will be removed from ${userName}, and the account will return to Student.`, confirm: 'Yes, remove' }
            : { title: 'Cabut jabatan Aslab?', text: `Jabatan Aslab ${userName} akan dicabut dan dikembalikan menjadi Mahasiswa.`, confirm: 'Ya, cabut' };
    }

    if (target === 'Laboran') {
        return locale === 'en'
            ? { title: 'Promote to Laboratory Staff?', text: `${userName} will receive Laboratory Staff access.`, confirm: 'Yes, promote' }
            : { title: 'Angkat menjadi Laboran?', text: `${userName} akan memperoleh akses Laboran.`, confirm: 'Ya, angkat' };
    }

    return locale === 'en'
        ? { title: 'Promote to Lab Assistant?', text: `${userName} will receive Lab Assistant access.`, confirm: 'Yes, promote' }
        : { title: 'Angkat menjadi Aslab?', text: `${userName} akan memperoleh akses Asisten Laboratorium.`, confirm: 'Ya, angkat' };
}

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    translateSubtree(document.body);
    const metaDescription = document.querySelector('meta[name="description"]');
    if (metaDescription) metaDescription.content = translate(metaDescription.content);

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'characterData') translateSubtree(mutation.target);
            mutation.addedNodes.forEach(translateSubtree);
        }
    });
    observer.observe(document.body, { childList: true, characterData: true, subtree: true });

    const flash = document.getElementById('app-flash-message');
    if (flash?.dataset.message) {
        const type = flash.dataset.type ?? 'info';
        const titles = locale === 'en'
            ? { error: 'Unable to process', success: 'Success', info: 'Information' }
            : { error: 'Tidak dapat diproses', success: 'Berhasil', info: 'Informasi' };

        Swal.fire({
            icon: type === 'error' ? 'error' : type === 'success' ? 'success' : 'info',
            title: titles[type] ?? titles.info,
            text: translate(flash.dataset.message),
            confirmButtonText: locale === 'en' ? 'OK' : 'Oke',
            confirmButtonColor: '#047857',
            timer: type === 'success' ? 3200 : undefined,
            timerProgressBar: type === 'success',
        });
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const isRoleChange = form.matches('form[data-role-confirm]');
        if (!isRoleChange && !form.matches('form[data-confirm]')) return;

        event.preventDefault();

        if (isRoleChange && !form.querySelector('[name="role"]')?.value) {
            form.querySelector('[name="role"]')?.reportValidity();
            return;
        }

        const roleCopy = isRoleChange ? confirmationCopy(form) : null;
        const result = await Swal.fire({
            icon: isRoleChange ? 'question' : (form.dataset.confirmIcon ?? 'warning'),
            title: roleCopy?.title ?? translate(form.dataset.confirmTitle ?? 'Konfirmasi tindakan'),
            text: roleCopy?.text ?? translate(form.dataset.confirm ?? ''),
            showCancelButton: true,
            confirmButtonText: roleCopy?.confirm ?? translate(form.dataset.confirmButton ?? 'Ya, lanjutkan'),
            cancelButtonText: locale === 'en' ? 'Cancel' : 'Batal',
            confirmButtonColor: form.dataset.confirmColor ?? '#047857',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
            focusCancel: true,
            customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg', cancelButton: 'rounded-lg' },
        });

        if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
    });
});
