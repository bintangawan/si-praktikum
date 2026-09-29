import type { UserRole } from './roles';

export const COURSE_CREATOR_ROLES = ['Laboran', 'Aslab'] as const satisfies readonly UserRole[];

export function canCreateCourse(role: UserRole): boolean {
  return COURSE_CREATOR_ROLES.some((allowedRole) => allowedRole === role);
}

export function canViewEnrollmentCode(role: UserRole): boolean {
  return role === 'Aslab' || role === 'Laboran';
}

export function defaultCourseAslabId(creatorRole: UserRole, creatorId: string): string | null {
  return creatorRole === 'Aslab' ? creatorId : null;
}

export function canAssignCourseAslab(creatorRole: UserRole, creatorId: string, aslabId: string): boolean {
  return creatorRole !== 'Aslab' || creatorId === aslabId;
}
