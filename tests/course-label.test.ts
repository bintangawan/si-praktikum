import assert from 'node:assert/strict';
import test from 'node:test';
import { formatSemesterClassLabel } from '../src/lib/course-label';

test('formats academic semester, student semester, and class group together', () => {
  assert.equal(formatSemesterClassLabel('Ganjil 2026/2027', 7, 'IK-1'), 'Ganjil 2026/2027, 7/IK-1');
});

test('omits missing label parts without leaving separators', () => {
  assert.equal(formatSemesterClassLabel('Ganjil 2026/2027', 7), 'Ganjil 2026/2027, 7');
  assert.equal(formatSemesterClassLabel(null, 7, 'IK-1'), '7/IK-1');
  assert.equal(formatSemesterClassLabel('Ganjil 2026/2027', null, null), 'Ganjil 2026/2027');
});
