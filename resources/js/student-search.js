export function studentSearch(searchUrl) {
    return {
        searchUrl, query: '', selectedId: '', suggestions: [], open: false,
        loading: false, failed: false, requestNumber: 0, activeIndex: -1, timer: null,
        changed() {
            this.selectedId = '';
            this.suggestions = [];
            this.activeIndex = -1;
            this.requestNumber++;
            this.failed = false;
            this.open = this.query.trim().length >= 3;
            this.loading = this.open;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.searchStudents(), 300);
        },
        async searchStudents() {
            const term = this.query.trim();
            const request = ++this.requestNumber;
            if (term.length < 3 || this.selectedId) { this.loading = false; return; }
            this.loading = true;
            this.open = true;
            this.failed = false;
            try {
                const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Pencarian gagal');
                const payload = await response.json();
                if (request === this.requestNumber) this.suggestions = payload.data ?? [];
            } catch {
                if (request === this.requestNumber) { this.suggestions = []; this.failed = true; }
            } finally {
                if (request === this.requestNumber) this.loading = false;
            }
        },
        move(direction) {
            if (!this.suggestions.length || this.loading) return;
            this.open = true;
            this.activeIndex = (this.activeIndex + direction + this.suggestions.length) % this.suggestions.length;
        },
        choose(student) {
            if (this.loading || !this.suggestions.some(item => item.id === student.id)) return;
            this.selectedId = student.id;
            this.query = `${student.id} — ${student.name}`;
            this.open = false;
            this.activeIndex = -1;
            this.requestNumber++;
            clearTimeout(this.timer);
        },
        selectActive() {
            if (this.open && this.activeIndex >= 0) this.choose(this.suggestions[this.activeIndex]);
        },
        destroy() { clearTimeout(this.timer); this.requestNumber++; },
    };
}
