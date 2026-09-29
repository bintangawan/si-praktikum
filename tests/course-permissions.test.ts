import assert from 'node:assert/strict';
import test from 'node:test';
import { canAssignCourseAslab, canCreateCourse, canViewEnrollmentCode, COURSE_CREATOR_ROLES, defaultCourseAslabId } from '../src/lib/course-permissions';
import { USER_ROLES } from '../src/lib/roles';

test('course creation is available to lab assistants and lab administrators only', () => {
  assert.deepEqual(COURSE_CREATOR_ROLES, ['Laboran', 'Aslab']);

  for (const role of USER_ROLES) {
    assert.equal(canCreateCourse(role), role === 'Aslab' || role === 'Laboran', `${role} course-creation access`);
  }
});

test('lab assistants can create a class only for their own assistant assignment', () => {
  assert.equal(defaultCourseAslabId('Aslab', 'aslab-1'), 'aslab-1');
  assert.equal(defaultCourseAslabId('Laboran', 'laboran-1'), null);
  assert.equal(canAssignCourseAslab('Aslab', 'aslab-1', 'aslab-1'), true);
  assert.equal(canAssignCourseAslab('Aslab', 'aslab-1', 'aslab-2'), false);
  assert.equal(canAssignCourseAslab('Laboran', 'laboran-1', 'aslab-2'), true);
});

test('enrollment codes are visible to lab assistants and lab administrators only', () => {
  for (const role of USER_ROLES) {
    assert.equal(canViewEnrollmentCode(role), role === 'Aslab' || role === 'Laboran', `${role} enrollment-code access`);
  }
});
