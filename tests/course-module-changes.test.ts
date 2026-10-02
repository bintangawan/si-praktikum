import assert from 'node:assert/strict';
import test from 'node:test';
import { planCourseModuleChanges, resetCourseModule, type ExistingCourseModule } from '../src/lib/course-module-changes';

const openedAt = new Date('2026-09-01T00:00:00.000Z');
const modules: ExistingCourseModule[] = [
  { id: 11, meetingNumber: 1, publishedAt: openedAt },
  { id: 12, meetingNumber: 2, publishedAt: null },
];

test('deleting an unopened module removes its slot while retaining the other module', () => {
  assert.deepEqual(planCourseModuleChanges(modules, [11], [12], []), {
    deletedIds: [12],
    resetIds: [],
  });
});

test('an opened module can be reset while its Coming soon slot remains', () => {
  assert.deepEqual(planCourseModuleChanges(modules, [11, 12], [], [11]), {
    deletedIds: [],
    resetIds: [11],
  });
  assert.deepEqual(resetCourseModule(modules[0], openedAt), {
    title: 'Modul 1',
    description: null,
    moduleDriveLink: null,
    deadline: null,
    publishedAt: null,
    updatedAt: openedAt,
  });
});

test('opened modules can also be deleted completely', () => {
  assert.deepEqual(planCourseModuleChanges(modules, [12], [11], []), {
    deletedIds: [11],
    resetIds: [],
  });
});

test('module change plans reject missing, duplicate, and stale IDs', () => {
  assert.throws(() => planCourseModuleChanges(modules, [11], [], []), /Daftar modul kelas berubah/);
  assert.throws(() => planCourseModuleChanges(modules, [11, 11, 12], [], []), /ID duplikat/);
  assert.throws(() => planCourseModuleChanges(modules, [11, 12], [11], []), /Daftar modul kelas berubah/);
  assert.throws(() => planCourseModuleChanges(modules, [11, 12], [], [12]), /Coming soon/);
});
