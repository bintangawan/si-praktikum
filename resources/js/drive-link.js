export function drivePreview(value) {
    try {
        const url = new URL(value);
        if (url.protocol !== 'https:' || url.hostname !== 'drive.google.com' || url.username || url.password || url.port) return null;
        const match = url.pathname.match(/^\/file\/d\/([A-Za-z0-9_-]+)(?:\/(?:view|preview|edit))?\/?$/);
        const id = match?.[1] ?? (['/open', '/uc'].includes(url.pathname) ? url.searchParams.get('id') : null);
        if (!id || !/^[A-Za-z0-9_-]+$/.test(id)) return null;
        let preview = `https://drive.google.com/file/d/${id}/preview`;
        if (url.searchParams.has('resourcekey')) {
            const key = url.searchParams.get('resourcekey');
            if (!/^[A-Za-z0-9_-]+$/.test(key)) return null;
            preview += `?resourcekey=${encodeURIComponent(key)}`;
        }
        return preview;
    } catch { return null; }
}

export function driveSubmission(link = '') {
    return {
        link,
        preview() { this.$dispatch('document-preview', { url: drivePreview(this.link) }); },
    };
}
