import { TRPCError } from '@trpc/server';
import { and, asc, eq, inArray, sql } from 'drizzle-orm';
import { z } from 'zod';
import { getCourseBySlug, requireCourseManager, requireCourseWritable } from '../auth/access';
import { getDb } from '../db';
import { attendances, courseUsers, courses, meetings, users } from '../db/schema';
import { roleProcedure, router } from '../trpc/init';

const attendanceStatus = z.enum(['Hadir', 'Sakit', 'Izin', 'Tanpa Keterangan', 'H', 'S', 'I', 'TK', 'Alpha']);

async function getManagedMeeting(user: Parameters<typeof requireCourseManager>[0], meetingId: number) {
  const [row] = await getDb().select({ meetingId: meetings.id, courseSlug: courses.slug }).from(meetings)
    .innerJoin(courses, eq(meetings.courseId, courses.id)).where(eq(meetings.id, meetingId)).limit(1);
  if (!row) throw new TRPCError({ code: 'NOT_FOUND', message: 'Modul tidak ditemukan.' });
  const course = await requireCourseManager(user, await getCourseBySlug(row.courseSlug));
  return { meetingId: row.meetingId, course };
}

export const attendanceRouter = router({
  byMeeting: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.object({ meetingId: z.number().int().positive() })).query(async ({ ctx, input }) => {
    const { meetingId, course } = await getManagedMeeting(ctx.user, input.meetingId);
    requireCourseWritable(course);
    return getDb().select({ studentId: users.id, name: users.name, status: attendances.status, attendanceDate: attendances.attendanceDate })
      .from(courseUsers).innerJoin(users, eq(courseUsers.userId, users.id))
      .leftJoin(attendances, and(eq(attendances.meetingId, meetingId), eq(attendances.studentId, users.id)))
      .where(eq(courseUsers.courseId, course.id)).orderBy(asc(users.id));
  }),

  save: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.object({ meetingId: z.number().int().positive(), date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/), rows: z.array(z.object({ studentId: z.string().min(1).max(20), status: attendanceStatus })).max(500) })).mutation(async ({ ctx, input }) => {
    const { meetingId, course } = await getManagedMeeting(ctx.user, input.meetingId);
    requireCourseWritable(course);
    const ids = input.rows.map((row) => row.studentId);
    if (ids.length !== new Set(ids).size) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Setiap mahasiswa hanya boleh muncul satu kali.' });
    if (input.rows.length === 0) return { success: true, message: 'Presensi berhasil disimpan.' };
    const registered = await getDb().select({ userId: courseUsers.userId }).from(courseUsers)
      .where(and(eq(courseUsers.courseId, course.id), inArray(courseUsers.userId, ids.length ? ids : ['__none__'])));
    if (registered.length !== new Set(ids).size) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Daftar mahasiswa presensi tidak sesuai peserta kelas.' });
    const updatedAt = new Date();
    await getDb().insert(attendances).values(input.rows.map((row) => ({
      meetingId, studentId: row.studentId, status: row.status, attendanceDate: input.date, updatedAt,
    }))).onConflictDoUpdate({
      target: [attendances.meetingId, attendances.studentId],
      set: { status: sql`excluded.status`, attendanceDate: sql`excluded.attendance_date`, updatedAt: sql`excluded.updated_at` },
    });
    return { success: true, message: 'Presensi berhasil disimpan.' };
  }),

  report: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.object({ slug: z.string().min(1) })).query(async ({ ctx, input }) => {
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(input.slug));
    const [roster, moduleRows, attendanceRows] = await Promise.all([
      getDb().select({ id: users.id, name: users.name }).from(courseUsers).innerJoin(users, eq(courseUsers.userId, users.id))
        .where(eq(courseUsers.courseId, course.id)).orderBy(asc(users.id)),
      getDb().select({ id: meetings.id, number: meetings.meetingNumber, title: meetings.title, publishedAt: meetings.publishedAt })
        .from(meetings).where(eq(meetings.courseId, course.id)).orderBy(asc(meetings.meetingNumber)),
      getDb().select({ studentId: attendances.studentId, meetingId: attendances.meetingId, status: attendances.status })
        .from(attendances).innerJoin(meetings, eq(attendances.meetingId, meetings.id)).where(eq(meetings.courseId, course.id)),
    ]);
    const conductedMeetingIds = new Set(attendanceRows.map((row) => row.meetingId));
    const attendanceByStudentMeeting = new Map(attendanceRows.map((row) => [`${row.studentId}:${row.meetingId}`, row.status]));
    const statusCode = (status: string | undefined) => {
      const normalized = (status ?? '').trim().toUpperCase();
      if (normalized === 'H' || normalized === 'HADIR') return 'H';
      if (normalized === 'S' || normalized === 'SAKIT') return 'S';
      if (normalized === 'I' || normalized === 'IZIN') return 'I';
      if (['TK', 'ALPHA', 'ALPA', 'TANPA KETERANGAN', 'A'].includes(normalized)) return 'TK';
      return 'TK';
    };
    const report = roster.map((student) => {
      const perMeetingStatus = Object.fromEntries(moduleRows.map((meeting) => {
        const recorded = attendanceByStudentMeeting.get(`${student.id}:${meeting.id}`);
        return [meeting.id, recorded ? statusCode(recorded) : conductedMeetingIds.has(meeting.id) ? 'TK' : '-'];
      }));
      const conductedCodes = moduleRows.filter((meeting) => conductedMeetingIds.has(meeting.id))
        .map((meeting) => perMeetingStatus[meeting.id]);
      const present = conductedCodes.filter((code) => code === 'H').length;
      const total = conductedCodes.length;
      return {
        ...student,
        perMeetingStatus,
        present,
        sick: conductedCodes.filter((code) => code === 'S').length,
        excused: conductedCodes.filter((code) => code === 'I').length,
        unexcused: conductedCodes.filter((code) => code === 'TK').length,
        conductedCount: total,
        percentage: total > 0 ? Math.round((present / total) * 100) : 0,
      };
    });
    return { courseSlug: course.slug, courseName: course.courseName, students: roster, report, meetings: moduleRows, attendances: attendanceRows };
  }),
});
