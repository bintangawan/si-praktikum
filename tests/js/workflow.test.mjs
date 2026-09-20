import test from 'node:test';
import assert from 'node:assert/strict';
import { drivePreview } from '../../resources/js/drive-link.js';
import { studentSearch } from '../../resources/js/student-search.js';

test('Drive links are restricted to files and preserve resource keys', () => {
    assert.equal(drivePreview('https://drive.google.com/file/d/report-1/view?resourcekey=key_2'), 'https://drive.google.com/file/d/report-1/preview?resourcekey=key_2');
    assert.equal(drivePreview('https://drive.google.com/open?id=abc'), 'https://drive.google.com/file/d/abc/preview');
    for (const value of ['https://evil.test/file/d/id/view', 'https://drive.google.com.evil.test/file/d/id/view', 'https://drive.google.com/drive/folders/abc', 'javascript:alert(1)', 'https://user@drive.google.com/file/d/id/view']) {
        assert.equal(drivePreview(value), null);
    }
});

test('editing a selected student clears the ID synchronously before debounce', () => {
    const search = studentSearch('/search');
    search.suggestions = [{ id: '001', name: 'A' }];
    search.choose(search.suggestions[0]);
    assert.equal(search.selectedId, '001');
    search.query = 'Budi';
    search.changed();
    assert.equal(search.selectedId, '');
    assert.deepEqual(search.suggestions, []);
    search.choose({ id: '001', name: 'A' });
    assert.equal(search.selectedId, '');
    search.destroy();
});

test('out-of-order search responses cannot replace the latest suggestions', async () => {
    const original = globalThis.fetch;
    const resolvers = [];
    globalThis.fetch = () => new Promise(resolve => resolvers.push(resolve));
    const search = studentSearch('/search');
    try {
        search.query = 'Ahmad';
        const first = search.searchStudents();
        search.query = 'Budi';
        const second = search.searchStudents();
        resolvers[1]({ ok: true, json: async () => ({ data: [{ id: '002', name: 'Budi' }] }) });
        await second;
        resolvers[0]({ ok: true, json: async () => ({ data: [{ id: '001', name: 'Ahmad' }] }) });
        await first;
        assert.equal(search.suggestions[0].id, '002');
        search.move(1);
        search.selectActive();
        assert.equal(search.selectedId, '002');
        assert.equal(search.open, false);
    } finally { globalThis.fetch = original; search.destroy(); }
});

test('failed and short queries do not leave a selectable result', async () => {
    const original = globalThis.fetch;
    globalThis.fetch = async () => ({ ok: false });
    const search = studentSearch('/search');
    try {
        search.query = 'Ahmad';
        await search.searchStudents();
        assert.equal(search.failed, true);
        assert.equal(search.loading, false);
        assert.deepEqual(search.suggestions, []);
        search.query = 'Ab';
        search.changed();
        assert.equal(search.open, false);
        assert.equal(search.selectedId, '');
    } finally { globalThis.fetch = original; search.destroy(); }
});
