import { TRPCError } from '@trpc/server';
import { and, eq } from 'drizzle-orm';
import type { SessionUser } from './session';
import { getDb } from '../db';
import { courseUsers, courses, semesters } from '../db/schema';

export async function getCourseBySlug(slug: string) {
  const [course] = await getDb().select({
    id: courses.id,
    slug: courses.slug,
    semesterId: courses.semesterId,
    isArchived: courses.isArchived,
    courseName: courses.courseName,
    classGroup: courses.classGroup,
    targetSemester: courses.targetSemester,
    dosenId: courses.dosenId,
    laboranId: courses.laboranId,
    aslabId: courses.aslabId,
    enrollmentCode: courses.enrollmentCode,
    semesterIsActive: semesters.isActive,
  }).from(courses).innerJoin(semesters, eq(courses.semesterId, semesters.id))
    .where(eq(courses.slug, slug)).limit(1);
  if (!course) throw new TRPCError({ code: 'NOT_FOUND', message: 'Kelas tidak ditemukan.' });
  return course;
}

export async function canViewCourse(user: SessionUser, course: Awaited<ReturnType<typeof getCourseBySlug>>) {
  if (user.role === 'Laboran') return true;
  if (user.role === 'Dosen') return course.dosenId === user.id;
  if (user.role === 'Aslab') return course.aslabId === user.id;
  const [membership] = await getDb().select({ id: courseUsers.id }).from(courseUsers)
    .where(and(eq(courseUsers.courseId, course.id), eq(courseUsers.userId, user.id))).limit(1);
  return Boolean(membership);
}

export async function requireCourseView(user: SessionUser, course: Awaited<ReturnType<typeof getCourseBySlug>>) {
  if (!(await canViewCourse(user, course))) throw new TRPCError({ code: 'FORBIDDEN', message: 'Anda tidak terdaftar pada kelas ini.' });
  return course;
}

export async function requireCourseManager(user: SessionUser, course: Awaited<ReturnType<typeof getCourseBySlug>>) {
  const allowed = user.role === 'Laboran' || (user.role === 'Dosen' && course.dosenId === user.id) || (user.role === 'Aslab' && course.aslabId === user.id);
  if (!allowed) throw new TRPCError({ code: 'FORBIDDEN', message: 'Anda tidak memiliki akses untuk mengelola kelas ini.' });
  return course;
}

export async function requireModuleManager(user: SessionUser, course: Awaited<ReturnType<typeof getCourseBySlug>>) {
  const allowed = user.role === 'Laboran' || (user.role === 'Aslab' && course.aslabId === user.id);
  if (!allowed) throw new TRPCError({ code: 'FORBIDDEN', message: 'Hanya Aslab atau Laboran kelas yang dapat mengatur modul.' });
  return course;
}

export async function requireCourseStudent(user: SessionUser, course: Awaited<ReturnType<typeof getCourseBySlug>>) {
  const [membership] = await getDb().select({ id: courseUsers.id }).from(courseUsers)
    .where(and(eq(courseUsers.courseId, course.id), eq(courseUsers.userId, user.id))).limit(1);
  if (user.role !== 'Mahasiswa' || !membership) {
    throw new TRPCError({ code: 'FORBIDDEN', message: 'Aksi ini hanya tersedia untuk mahasiswa kelas.' });
  }
  return course;
}

export function requireCourseWritable(course: Awaited<ReturnType<typeof getCourseBySlug>>) {
  if (course.isArchived || !course.semesterIsActive) {
    throw new TRPCError({ code: 'FORBIDDEN', message: 'Kelas arsip hanya dapat dibaca.' });
  }
  return course;
}
