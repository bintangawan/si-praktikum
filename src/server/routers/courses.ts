import { randomBytes } from 'node:crypto';
import { TRPCError } from '@trpc/server';
import { and, asc, desc, eq, ilike, inArray, or, sql } from 'drizzle-orm';
import { z } from 'zod';
import { getCourseBySlug, requireCourseManager, requireCourseStudent, requireCourseView, requireCourseWritable, requireModuleManager } from '../auth/access';
import { canAssignCourseAslab, COURSE_CREATOR_ROLES, defaultCourseAslabId } from '@/lib/course-permissions';
import { getDb } from '../db';
import { attendances, courseGrades, courseStaffHistories, courseUsers, courses, finalTasks, meetings, semesters, submissionHistories, submissions, users } from '../db/schema';
import { protectedProcedure, roleProcedure, router } from '../trpc/init';

const slugify = (value: string) => value.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'kelas';
const nameInput = z.string().trim().min(2).max(255);

async function validStaff(id: string, role: 'Dosen' | 'Aslab' | 'Laboran') {
  const [user] = await getDb().select({ id: users.id }).from(users)
    .where(and(eq(users.id, id), eq(users.role, role), sql`${users.approvedAt} IS NOT NULL`)).limit(1);
  if (!user) throw new TRPCError({ code: 'BAD_REQUEST', message: `Akun ${role} tidak ditemukan atau belum disetujui.` });
}

export const coursesRouter = router({
  createOptions: roleProcedure(...COURSE_CREATOR_ROLES).query(async ({ ctx }) => {
    const [[semester], staff] = await Promise.all([
      getDb().select({ id: semesters.id, name: semesters.name }).from(semesters).where(eq(semesters.isActive, true)).limit(1),
      getDb().select({ id: users.id, name: users.name, role: users.role }).from(users)
        .where(and(inArray(users.role, ['Dosen', 'Laboran', 'Aslab']), sql`${users.approvedAt} IS NOT NULL`)).orderBy(asc(users.name)),
    ]);
    return {
      semester: semester ?? null,
      dosens: staff.filter((user) => user.role === 'Dosen'),
      laborans: staff.filter((user) => user.role === 'Laboran'),
      aslabs: staff.filter((user) => user.role === 'Aslab'),
      currentAslabId: defaultCourseAslabId(ctx.user.role, ctx.user.id),
    };
  }),

  archives: protectedProcedure.query(async ({ ctx }) => {
    const user = ctx.user;
    const filters = [or(eq(courses.isArchived, true), eq(semesters.isActive, false))];
    if (user.role === 'Dosen') filters.push(eq(courses.dosenId, user.id));
    if (user.role === 'Aslab') filters.push(eq(courses.aslabId, user.id));
    if (user.role === 'Mahasiswa') filters.push(sql`EXISTS (SELECT 1 FROM course_user cu WHERE cu.course_id = ${courses.id} AND cu.user_id = ${user.id})` as never);
    return getDb().select({ id: courses.id, slug: courses.slug, name: courses.courseName, group: courses.classGroup,
      semesterName: semesters.name, semesterActive: semesters.isActive, isArchived: courses.isArchived,
      dosenName: sql<string>`dosen.name`, aslabName: sql<string>`aslab.name`, laboranName: sql<string>`laboran.name` })
      .from(courses).innerJoin(semesters, eq(courses.semesterId, semesters.id))
      .innerJoin(sql`users dosen`, sql`dosen.id = ${courses.dosenId}`).innerJoin(sql`users aslab`, sql`aslab.id = ${courses.aslabId}`)
      .innerJoin(sql`users laboran`, sql`laboran.id = ${courses.laboranId}`).where(and(...filters)).orderBy(desc(courses.createdAt)).limit(200);
  }),

  list: protectedProcedure.input(z.object({ view: z.enum(['my_classes', 'all']).default('my_classes') }).optional()).query(async ({ ctx, input }) => {
    const user = ctx.user;
    const [activeSemester] = await getDb().select().from(semesters).where(eq(semesters.isActive, true)).limit(1);
    if (!activeSemester) return { activeSemester: null, courses: [] };
    const filters = [eq(courses.semesterId, activeSemester.id), eq(courses.isArchived, false)];
    if (user.role === 'Mahasiswa') filters.push(sql`EXISTS (SELECT 1 FROM course_user cu WHERE cu.course_id = ${courses.id} AND cu.user_id = ${user.id})` as never);
    if (user.role === 'Dosen') filters.push(eq(courses.dosenId, user.id));
    if (user.role === 'Aslab') filters.push(eq(courses.aslabId, user.id));
    if (user.role === 'Laboran' && input?.view !== 'all') filters.push(eq(courses.laboranId, user.id));

    const rows = await getDb().select({
      id: courses.id, slug: courses.slug, name: courses.courseName, group: courses.classGroup,
      targetSemester: courses.targetSemester, enrollmentCode: courses.enrollmentCode,
      dosenName: sql<string>`dosen.name`, aslabName: sql<string>`aslab.name`, laboranName: sql<string>`laboran.name`,
      studentCount: sql<number>`(SELECT count(*)::int FROM course_user cu WHERE cu.course_id = ${courses.id})`,
    }).from(courses)
      .innerJoin(sql`users dosen`, sql`dosen.id = ${courses.dosenId}`)
      .innerJoin(sql`users aslab`, sql`aslab.id = ${courses.aslabId}`)
      .innerJoin(sql`users laboran`, sql`laboran.id = ${courses.laboranId}`)
      .where(and(...filters)).orderBy(desc(courses.createdAt)).limit(100);
    return { activeSemester, courses: rows };
  }),

  get: protectedProcedure.input(z.object({ slug: z.string().min(1) })).query(async ({ ctx, input }) => {
    const course = await getCourseBySlug(input.slug);
    await requireCourseView(ctx.user, course);
    const db = getDb();
    const [[staff], moduleRows, [finalTask]] = await Promise.all([
      db.select({
      dosenName: sql<string>`dosen.name`, aslabName: sql<string>`aslab.name`, laboranName: sql<string>`laboran.name`, semesterName: semesters.name,
    }).from(courses)
      .innerJoin(sql`users dosen`, sql`dosen.id = ${courses.dosenId}`)
      .innerJoin(sql`users aslab`, sql`aslab.id = ${courses.aslabId}`)
      .innerJoin(sql`users laboran`, sql`laboran.id = ${courses.laboranId}`)
      .innerJoin(semesters, eq(courses.semesterId, semesters.id))
      .where(eq(courses.id, course.id)).limit(1),
      db.select({
        meeting: meetings,
        submissionsCount: sql<number>`(SELECT count(*)::int FROM submissions WHERE submissions.meeting_id = ${meetings.id})`,
        attendancesCount: sql<number>`(SELECT count(*)::int FROM attendances WHERE attendances.meeting_id = ${meetings.id})`,
      }).from(meetings).where(eq(meetings.courseId, course.id)).orderBy(asc(meetings.meetingNumber)),
      db.select().from(finalTasks).where(eq(finalTasks.courseId, course.id)).limit(1),
    ]);
    const meetingsForUser = moduleRows.map(({ meeting, submissionsCount, attendancesCount }) => ({
      ...meeting,
      submissionsCount,
      attendancesCount,
    }));
    if (ctx.user.role === 'Mahasiswa' && meetingsForUser.length) {
      const meetingIds = meetingsForUser.map((meeting) => meeting.id);
      const [studentSubmissions, studentAttendances] = await Promise.all([
        db.select().from(submissions).where(and(
          inArray(submissions.meetingId, meetingIds),
          eq(submissions.studentId, ctx.user.id),
          eq(submissions.isFinal, false),
        )),
        db.select({ meetingId: attendances.meetingId, status: attendances.status }).from(attendances).where(and(
          inArray(attendances.meetingId, meetingIds),
          eq(attendances.studentId, ctx.user.id),
        )),
      ]);
      const submissionByMeeting = new Map(studentSubmissions.map((submission) => [submission.meetingId, submission]));
      const attendanceByMeeting = new Map(studentAttendances.map((attendance) => [attendance.meetingId, attendance.status]));
      const historyRows = studentSubmissions.length
        ? await db.select({ history: submissionHistories, submissionId: submissionHistories.submissionId })
          .from(submissionHistories).where(inArray(submissionHistories.submissionId, studentSubmissions.map((row) => row.id)))
          .orderBy(desc(submissionHistories.iteration))
        : [];
      const feedbackBySubmission = new Map<number, string>();
      for (const row of historyRows) {
        if (row.history.feedback && !feedbackBySubmission.has(row.submissionId)) feedbackBySubmission.set(row.submissionId, row.history.feedback);
      }
      return {
        ...course,
        ...staff,
        evaluatedAt: new Date(),
        meetings: meetingsForUser.map((meeting) => {
          const submission = submissionByMeeting.get(meeting.id) ?? null;
          return {
            ...meeting,
            studentSubmission: submission ? { ...submission, latestFeedback: feedbackBySubmission.get(submission.id) ?? null } : null,
            studentAttendanceStatus: attendanceByMeeting.get(meeting.id) ?? null,
          };
        }),
        finalTask: finalTask ?? null,
      };
    }
    return { ...course, ...staff, evaluatedAt: new Date(), meetings: meetingsForUser, finalTask: finalTask ?? null };
  }),

  printCardData: roleProcedure('Mahasiswa').input(z.object({ slug: z.string().min(1) })).query(async ({ ctx, input }) => {
    const course = await requireCourseStudent(ctx.user, await getCourseBySlug(input.slug));
    const [staff] = await getDb().select({
      dosenName: sql<string>`dosen.name`, laboranName: sql<string>`laboran.name`, semesterName: semesters.name,
    }).from(courses)
      .innerJoin(sql`users dosen`, sql`dosen.id = ${courses.dosenId}`)
      .innerJoin(sql`users laboran`, sql`laboran.id = ${courses.laboranId}`)
      .innerJoin(semesters, eq(courses.semesterId, semesters.id))
      .where(eq(courses.id, course.id)).limit(1);
    const moduleRows = await getDb().select({
      meeting: meetings, attendanceStatus: attendances.status, isCompleted: submissions.isCompleted,
      aslabAccAt: submissions.aslabAccAt, updatedAt: submissions.updatedAt,
    }).from(meetings)
      .leftJoin(attendances, and(eq(attendances.meetingId, meetings.id), eq(attendances.studentId, ctx.user.id)))
      .leftJoin(submissions, and(eq(submissions.meetingId, meetings.id), eq(submissions.studentId, ctx.user.id), eq(submissions.isFinal, false)))
      .where(eq(meetings.courseId, course.id)).orderBy(asc(meetings.meetingNumber));
    return { course: { ...course, ...staff }, student: { id: ctx.user.id, name: ctx.user.name, avatar: ctx.user.avatar }, meetings: moduleRows };
  }),

  create: roleProcedure(...COURSE_CREATOR_ROLES).input(z.object({
    courseName: nameInput,
    classGroup: z.string().trim().min(1).max(50).transform((value) => value.toUpperCase()),
    targetSemester: z.number().int().min(1).max(14),
    dosenId: z.string().min(1).max(20),
    laboranId: z.string().min(1).max(20),
    aslabId: z.string().min(1).max(20),
    moduleCount: z.number().int().min(1).max(16),
  })).mutation(async ({ input, ctx }) => {
    const db = getDb();
    if (!canAssignCourseAslab(ctx.user.role, ctx.user.id, input.aslabId)) {
      throw new TRPCError({ code: 'FORBIDDEN', message: 'Aslab hanya dapat membuat kelas untuk dirinya sendiri.' });
    }
    const [activeSemester] = await db.select().from(semesters).where(eq(semesters.isActive, true)).limit(1);
    if (!activeSemester) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Aktifkan semester sebelum membuat kelas.' });
    await Promise.all([validStaff(input.dosenId, 'Dosen'), validStaff(input.laboranId, 'Laboran'), validStaff(input.aslabId, 'Aslab')]);
    const [duplicate] = await db.select({ id: courses.id }).from(courses).where(and(
      eq(courses.semesterId, activeSemester.id), eq(courses.courseName, input.courseName), eq(courses.classGroup, input.classGroup),
    )).limit(1);
    if (duplicate) throw new TRPCError({ code: 'CONFLICT', message: 'Kelas tersebut sudah ada pada semester aktif.' });

    const baseSlug = slugify(`${input.courseName} ${input.classGroup}`);
    const slugExists = await db.select({ id: courses.id }).from(courses).where(ilike(courses.slug, `${baseSlug}%`));
    const slug = slugExists.length ? `${baseSlug}-${slugExists.length + 1}` : baseSlug;
    const enrollmentCode = randomBytes(6).toString('hex').slice(0, 8).toUpperCase();
    return db.transaction(async (tx) => {
      const [course] = await tx.insert(courses).values({
        slug, semesterId: activeSemester.id, courseName: input.courseName, classGroup: input.classGroup,
        targetSemester: input.targetSemester, dosenId: input.dosenId, laboranId: input.laboranId,
        aslabId: input.aslabId, enrollmentCode,
      }).returning();
      await tx.insert(meetings).values(Array.from({ length: input.moduleCount }, (_, index) => ({
        courseId: course.id, meetingNumber: index + 1, title: `Modul ${index + 1}`,
      })));
      return course;
    });
  }),

  update: roleProcedure('Laboran').input(z.object({
    id: z.number().int().positive(), courseName: nameInput, classGroup: z.string().trim().min(1).max(50).transform((v) => v.toUpperCase()),
    targetSemester: z.number().int().min(1).max(14), dosenId: z.string().min(1).max(20), laboranId: z.string().min(1).max(20), aslabId: z.string().min(1).max(20),
  })).mutation(async ({ input, ctx }) => {
    const [course] = await getDb().select().from(courses).where(eq(courses.id, input.id)).limit(1);
    if (!course) throw new TRPCError({ code: 'NOT_FOUND', message: 'Kelas tidak ditemukan.' });
    await requireCourseManager(ctx.user, await getCourseBySlug(course.slug));
    await Promise.all([validStaff(input.dosenId, 'Dosen'), validStaff(input.laboranId, 'Laboran'), validStaff(input.aslabId, 'Aslab')]);
    const baseSlug = slugify(`${input.courseName} ${input.classGroup}`);
    let slug = baseSlug;
    let suffix = 2;
    while (true) {
      const [used] = await getDb().select({ id: courses.id }).from(courses).where(and(eq(courses.slug, slug), sql`${courses.id} <> ${input.id}`)).limit(1);
      if (!used) break;
      slug = `${baseSlug}-${suffix++}`;
    }
    const { id, ...changes } = input;
    await getDb().update(courses).set({ ...changes, slug, updatedAt: new Date() }).where(eq(courses.id, id));
    return { success: true };
  }),

  delete: roleProcedure('Laboran').input(z.object({ slug: z.string().min(1) })).mutation(async ({ ctx, input }) => {
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(input.slug));
    await getDb().delete(courses).where(eq(courses.id, course.id));
    return { success: true };
  }),

  archive: roleProcedure('Laboran').input(z.object({ slug: z.string().min(1), archived: z.boolean() })).mutation(async ({ input }) => {
    const course = await getCourseBySlug(input.slug);
    if (input.archived && !course.semesterIsActive) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Kelas sudah berada di semester yang diarsipkan.' });
    if (!input.archived && !course.semesterIsActive) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Kelas tetap diarsipkan karena semesternya belum aktif.' });
    await getDb().update(courses).set({ isArchived: input.archived, updatedAt: new Date() }).where(eq(courses.id, course.id));
    return { success: true };
  }),

  enroll: roleProcedure('Mahasiswa').input(z.object({ enrollmentCode: z.string().trim().min(1).max(20).transform((value) => value.toUpperCase()) })).mutation(async ({ ctx, input }) => {
    const [course] = await getDb().select().from(courses).innerJoin(semesters, eq(courses.semesterId, semesters.id))
      .where(and(eq(courses.enrollmentCode, input.enrollmentCode), eq(courses.isArchived, false), eq(semesters.isActive, true))).limit(1);
    if (!course) throw new TRPCError({ code: 'NOT_FOUND', message: 'Kode kelas tidak valid atau semester sudah berakhir.' });
    await getDb().insert(courseUsers).values({ courseId: course.courses.id, userId: ctx.user.id }).onConflictDoNothing();
    return { success: true, message: 'Berhasil bergabung ke kelas.' };
  }),

  students: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.object({ slug: z.string().min(1) })).query(async ({ ctx, input }) => {
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(input.slug));
    const students = await getDb().select({ id: users.id, name: users.name, email: users.email, avatar: users.avatar }).from(courseUsers)
      .innerJoin(users, eq(courseUsers.userId, users.id)).where(eq(courseUsers.courseId, course.id)).orderBy(asc(users.id));
    return {
      courseName: course.courseName,
      classGroup: course.classGroup,
      isArchived: course.isArchived || !course.semesterIsActive,
      students,
    };
  }),

  searchStudents: roleProcedure('Laboran', 'Aslab').input(z.object({ slug: z.string().min(1), query: z.string().trim().min(3).max(100) })).query(async ({ ctx, input }) => {
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(input.slug));
    const query = `%${input.query.replace(/[\\%_]/g, '\\$&')}%`;
    return getDb().select({ id: users.id, name: users.name, email: users.email }).from(users)
      .where(and(eq(users.role, 'Mahasiswa'), sql`${users.approvedAt} IS NOT NULL`, or(ilike(users.id, query), ilike(users.name, query)),
        sql`NOT EXISTS (SELECT 1 FROM course_user cu WHERE cu.user_id = ${users.id} AND cu.course_id = ${course.id})`))
      .orderBy(asc(users.name), asc(users.id)).limit(10);
  }),

  addStudent: roleProcedure('Laboran', 'Aslab').input(z.object({ slug: z.string().min(1), studentId: z.string().min(1).max(20) })).mutation(async ({ ctx, input }) => {
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(input.slug));
    requireCourseWritable(course);
    const [student] = await getDb().select({ id: users.id }).from(users).where(and(eq(users.id, input.studentId), eq(users.role, 'Mahasiswa'), sql`${users.approvedAt} IS NOT NULL`)).limit(1);
    if (!student) throw new TRPCError({ code: 'NOT_FOUND', message: 'Mahasiswa belum disetujui atau tidak ditemukan.' });
    await getDb().insert(courseUsers).values({ courseId: course.id, userId: student.id }).onConflictDoNothing();
    return { success: true };
  }),

  removeStudent: roleProcedure('Laboran').input(z.object({ slug: z.string().min(1), studentId: z.string().min(1).max(20) })).mutation(async ({ ctx, input }) => {
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(input.slug));
    requireCourseWritable(course);
    const [records] = await getDb().select({ id: submissions.id }).from(submissions)
      .leftJoin(meetings, eq(submissions.meetingId, meetings.id)).leftJoin(finalTasks, eq(submissions.finalTaskId, finalTasks.id))
      .where(and(eq(submissions.studentId, input.studentId), or(eq(meetings.courseId, course.id), eq(finalTasks.courseId, course.id)))).limit(1);
    const [attendance] = await getDb().select({ id: attendances.id }).from(attendances).innerJoin(meetings, eq(attendances.meetingId, meetings.id))
      .where(and(eq(attendances.studentId, input.studentId), eq(meetings.courseId, course.id))).limit(1);
    if (records || attendance) throw new TRPCError({ code: 'CONFLICT', message: 'Mahasiswa yang sudah memiliki presensi atau pengumpulan tidak dapat dikeluarkan.' });
    await getDb().delete(courseUsers).where(and(eq(courseUsers.courseId, course.id), eq(courseUsers.userId, input.studentId)));
    return { success: true };
  }),

  updateStaff: roleProcedure('Laboran').input(z.object({ slug: z.string().min(1), dosenId: z.string().min(1).max(20), aslabId: z.string().min(1).max(20) })).mutation(async ({ ctx, input }) => {
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(input.slug));
    requireCourseWritable(course);
    await Promise.all([validStaff(input.dosenId, 'Dosen'), validStaff(input.aslabId, 'Aslab')]);
    await getDb().transaction(async (tx) => {
      await tx.insert(courseStaffHistories).values({ courseId: course.id, changedBy: ctx.user.id,
        previousDosenId: course.dosenId, previousAslabId: course.aslabId, dosenId: input.dosenId, aslabId: input.aslabId });
      await tx.update(courses).set({ dosenId: input.dosenId, aslabId: input.aslabId, updatedAt: new Date() }).where(eq(courses.id, course.id));
    });
    return { success: true };
  }),

  updateModules: roleProcedure('Laboran', 'Aslab').input(z.object({ slug: z.string().min(1), modules: z.array(z.object({
    id: z.number().int().positive().nullable(), title: z.string().trim().min(1).max(255), description: z.string().max(10000).nullable(),
    moduleDriveLink: z.string().url().nullable(), deadline: z.date().nullable(), published: z.boolean(),
  })).min(1).max(16) })).mutation(async ({ ctx, input }) => {
    const course = await requireModuleManager(ctx.user, await getCourseBySlug(input.slug));
    requireCourseWritable(course);
    const existing = await getDb().select({ id: meetings.id }).from(meetings).where(eq(meetings.courseId, course.id));
    const expected = new Set(existing.map((meeting) => meeting.id));
    const submittedIds = input.modules.map((module) => module.id).filter((id): id is number => id !== null).sort((a, b) => a - b);
    const expectedIds = [...expected].sort((a, b) => a - b);
    if (submittedIds.length !== expectedIds.length || submittedIds.some((id, index) => id !== expectedIds[index])) {
      throw new TRPCError({ code: 'BAD_REQUEST', message: 'Daftar modul kelas berubah. Muat ulang halaman lalu coba lagi.' });
    }
    await getDb().transaction(async (tx) => {
      const [numberRow] = await tx.select({ maximum: sql<number>`COALESCE(MAX(${meetings.meetingNumber}), 0)` }).from(meetings).where(eq(meetings.courseId, course.id));
      let nextNumber = Number(numberRow?.maximum ?? 0) + 1;
      for (const moduleItem of input.modules) {
        const publishedAt = moduleItem.published ? new Date() : null;
        const values = { title: moduleItem.title, description: moduleItem.description, moduleDriveLink: moduleItem.moduleDriveLink,
          deadline: moduleItem.deadline, publishedAt, updatedAt: new Date() };
        if (moduleItem.id !== null) await tx.update(meetings).set({ ...values, publishedAt: moduleItem.published ? sql`COALESCE(${meetings.publishedAt}, now())` : null })
          .where(and(eq(meetings.id, moduleItem.id), eq(meetings.courseId, course.id)));
        else await tx.insert(meetings).values({ ...values, courseId: course.id, meetingNumber: nextNumber++ });
      }
    });
    return { success: true };
  }),

  grades: roleProcedure('Dosen', 'Aslab', 'Laboran').input(z.object({ slug: z.string().min(1) })).query(async ({ ctx, input }) => {
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(input.slug));
    const [roster, moduleRows, submissionRows, grades] = await Promise.all([
      getDb().select({ id: users.id, name: users.name, email: users.email }).from(courseUsers).innerJoin(users, eq(courseUsers.userId, users.id))
        .where(eq(courseUsers.courseId, course.id)).orderBy(asc(users.name)),
      getDb().select().from(meetings).where(eq(meetings.courseId, course.id)).orderBy(asc(meetings.meetingNumber)),
      getDb().select().from(submissions).where(and(eq(submissions.isFinal, false), sql`EXISTS (
        SELECT 1 FROM meetings m WHERE m.id = ${submissions.meetingId} AND m.course_id = ${course.id}
      )`)),
      getDb().select().from(courseGrades).where(eq(courseGrades.courseId, course.id)),
    ]);
    const submissionByStudentAndMeeting = new Map(submissionRows.map((item) => [`${item.studentId}:${item.meetingId}`, item]));
    const gradeByStudent = new Map(grades.map((item) => [item.studentId, item]));
    const letter = (score: number | null) => score === null ? null : score >= 86 ? 'A' : score >= 75 ? 'B' : score >= 60 ? 'C' : score >= 40 ? 'D' : 'E';
    return roster.map((student) => {
      const modules = moduleRows.map((meeting) => {
        const submission = submissionByStudentAndMeeting.get(`${student.id}:${meeting.id}`);
        const moduleScore = submission && submission.aslabScore !== null && submission.laboranScore !== null
          ? Math.round((Number(submission.aslabScore) * 0.8 + Number(submission.laboranScore) * 0.2) * 100) / 100 : null;
        return { meetingNumber: meeting.meetingNumber, meetingTitle: meeting.title, hasSubmission: Boolean(submission),
          isCompleted: Boolean(submission?.isCompleted), hasAslabScore: submission?.aslabScore !== null && submission?.aslabScore !== undefined,
          hasLaboranScore: submission?.laboranScore !== null && submission?.laboranScore !== undefined,
          aslabScore: submission?.aslabScore, laboranScore: submission?.laboranScore, score: moduleScore };
      });
      const ready = moduleRows.length > 0 && moduleRows.every((meeting) => meeting.publishedAt !== null) && modules.every((module) =>
        module.isCompleted && module.hasAslabScore && module.hasLaboranScore);
      const laprak = ready ? Math.round((modules.reduce((total, module) => total + Number(module.score), 0) / modules.length) * 100) / 100 : null;
      const grade = gradeByStudent.get(student.id);
      const uts = grade ? Number(grade.utsScore) : null;
      const uas = grade ? Number(grade.uasScore) : null;
      const final = ready && uts !== null && uas !== null ? Math.round(((laprak! + uts + uas) / 3) * 100) / 100 : null;
      return { ...student, modules, ready, laprak, laprakLetter: letter(laprak), grade: grade ?? null,
        uts, utsLetter: letter(uts), uas, uasLetter: letter(uas), final, finalLetter: letter(final) };
    });
  }),

  updateGrade: roleProcedure('Dosen').input(z.object({ slug: z.string().min(1), studentId: z.string().min(1).max(20), utsScore: z.number().min(0).max(100), uasScore: z.number().min(0).max(100) })).mutation(async ({ ctx, input }) => {
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(input.slug));
    requireCourseWritable(course);
    const [student] = await getDb().select({ id: users.id, role: users.role }).from(users).where(eq(users.id, input.studentId)).limit(1);
    if (!student || student.role !== 'Mahasiswa') throw new TRPCError({ code: 'NOT_FOUND', message: 'Mahasiswa tidak ditemukan.' });
    const [membership] = await getDb().select({ id: courseUsers.id }).from(courseUsers)
      .where(and(eq(courseUsers.courseId, course.id), eq(courseUsers.userId, input.studentId))).limit(1);
    if (!membership) {
      throw new TRPCError({ code: 'NOT_FOUND', message: 'Mahasiswa bukan peserta kelas ini.' });
    }
    const [existing] = await getDb().select({ id: courseGrades.id }).from(courseGrades)
      .where(and(eq(courseGrades.courseId, course.id), eq(courseGrades.studentId, input.studentId))).limit(1);
    if (existing) await getDb().update(courseGrades).set({ utsScore: String(input.utsScore), uasScore: String(input.uasScore), updatedAt: new Date() }).where(eq(courseGrades.id, existing.id));
    else await getDb().insert(courseGrades).values({ courseId: course.id, studentId: input.studentId, utsScore: String(input.utsScore), uasScore: String(input.uasScore) });
    return { success: true };
  }),
});
