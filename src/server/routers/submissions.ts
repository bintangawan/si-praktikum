import { TRPCError } from '@trpc/server';
import { and, asc, desc, eq, inArray, isNotNull, max, or, sql } from 'drizzle-orm';
import { z } from 'zod';
import { getCourseBySlug, requireCourseManager, requireCourseStudent, requireCourseView, requireCourseWritable } from '../auth/access';
import { getDb } from '../db';
import { courseUsers, courses, finalTasks, meetings, semesters, submissionHistories, submissions, users } from '../db/schema';
import { roleProcedure, router } from '../trpc/init';

const driveFileUrl = z.string().trim().min(1).max(2048).url().refine((value) => {
  try {
    const url = new URL(value);
    if (url.protocol !== 'https:' || url.hostname !== 'drive.google.com' || url.username || url.password || url.port) return false;
    if (/^\/file\/d\/[A-Za-z0-9_-]+(?:\/(?:view|preview|edit))?\/?$/.test(url.pathname)) return true;
    return ['/open', '/uc'].includes(url.pathname) && /^[A-Za-z0-9_-]+$/.test(url.searchParams.get('id') ?? '');
  } catch { return false; }
}, 'Gunakan link file Google Drive yang valid, bukan link folder.');

const submissionInput = z.object({ driveLink: driveFileUrl, notes: z.string().trim().max(5000).nullable().optional() });
const reviewInput = z.object({ id: z.number().int().positive(), documentVersion: z.number().int().positive(),
  status: z.enum(['ACC', 'REVISI', 'DITOLAK']), score: z.number().min(0).max(100).optional(),
  feedback: z.string().trim().max(5000).nullable().optional(), notes: z.string().trim().max(5000).nullable().optional() });

async function getMeeting(meetingId: number) {
  const [row] = await getDb().select({ id: meetings.id, courseId: courses.id, slug: courses.slug,
    deadline: meetings.deadline, publishedAt: meetings.publishedAt, meetingNumber: meetings.meetingNumber,
    title: meetings.title, description: meetings.description })
    .from(meetings).innerJoin(courses, eq(meetings.courseId, courses.id)).where(eq(meetings.id, meetingId)).limit(1);
  if (!row) throw new TRPCError({ code: 'NOT_FOUND', message: 'Modul tidak ditemukan.' });
  return row;
}

async function getFinalTask(taskId: number) {
  const [row] = await getDb().select({ id: finalTasks.id, courseId: courses.id, slug: courses.slug, deadline: finalTasks.deadline })
    .from(finalTasks).innerJoin(courses, eq(finalTasks.courseId, courses.id)).where(eq(finalTasks.id, taskId)).limit(1);
  if (!row) throw new TRPCError({ code: 'NOT_FOUND', message: 'Tugas final tidak ditemukan.' });
  return row;
}

async function getSubmission(id: number) {
  const [row] = await getDb().select({
    submission: submissions,
    courseId: sql<number>`COALESCE(${meetings.courseId}, ${finalTasks.courseId})`,
    slug: courses.slug,
    courseName: courses.courseName,
    classGroup: courses.classGroup,
    isArchived: courses.isArchived,
    semesterIsActive: semesters.isActive,
    meetingNumber: meetings.meetingNumber,
    taskTitle: sql<string>`COALESCE(${meetings.title}, 'Laporan Final')`,
    taskDescription: sql<string | null>`COALESCE(${meetings.description}, ${finalTasks.description})`,
    deadline: sql<Date | null>`COALESCE(${meetings.deadline}, ${finalTasks.deadline})`,
    publishedAt: meetings.publishedAt,
  }).from(submissions)
    .leftJoin(meetings, eq(submissions.meetingId, meetings.id))
    .leftJoin(finalTasks, eq(submissions.finalTaskId, finalTasks.id))
    .innerJoin(courses, or(eq(meetings.courseId, courses.id), eq(finalTasks.courseId, courses.id)))
    .innerJoin(semesters, eq(courses.semesterId, semesters.id))
    .where(eq(submissions.id, id)).limit(1);
  if (!row) throw new TRPCError({ code: 'NOT_FOUND', message: 'Pengumpulan tidak ditemukan.' });
  return row;
}

async function recordHistory(tx: Parameters<Parameters<ReturnType<typeof getDb>['transaction']>[0]>[0], data: {
  submissionId: number; documentVersion: number; driveLink: string; actionType: 'Upload' | 'Revision' | 'Rejected' | 'ACC';
  feedback?: string | null; reviewerId?: string | null;
}) {
  const [last] = await tx.select({ iteration: max(submissionHistories.iteration) }).from(submissionHistories)
    .where(eq(submissionHistories.submissionId, data.submissionId));
  await tx.insert(submissionHistories).values({
    submissionId: data.submissionId,
    documentVersion: data.documentVersion,
    driveLink: data.driveLink,
    iteration: (last?.iteration ?? 0) + 1,
    feedback: data.feedback ?? null,
    actionType: data.actionType,
    reviewedBy: data.reviewerId ?? null,
  });
}

function canResubmit(submission: typeof submissions.$inferSelect) {
  return [submission.aslabStatus, submission.laboranStatus, submission.dosenStatus].includes('Revisi') ||
    [submission.aslabStatus, submission.laboranStatus, submission.dosenStatus].includes('Ditolak');
}

export const submissionsRouter = router({
  listForMeeting: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.object({ meetingId: z.number().int().positive() })).query(async ({ ctx, input }) => {
    const meeting = await getMeeting(input.meetingId);
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(meeting.slug));
    const db = getDb();
    const [courseMeetings, students] = await Promise.all([
      db.select().from(meetings).where(eq(meetings.courseId, course.id)).orderBy(asc(meetings.meetingNumber)),
      db.select({ id: users.id, name: users.name }).from(courseUsers).innerJoin(users, eq(courseUsers.userId, users.id))
        .where(eq(courseUsers.courseId, course.id)).orderBy(asc(users.name), asc(users.id)),
    ]);
    const meetingIds = courseMeetings.map((row) => row.id);
    const records = meetingIds.length ? await db.select({
      submission: submissions,
      historiesCount: sql<number>`(SELECT count(*)::int FROM submission_histories WHERE submission_histories.submission_id = ${submissions.id})`,
    }).from(submissions).where(and(eq(submissions.isFinal, false), inArray(submissions.meetingId, meetingIds))) : [];
    const submissionsByMeeting = new Map<number, typeof records>();
    for (const record of records) {
      if (record.submission.meetingId === null) continue;
      const bucket = submissionsByMeeting.get(record.submission.meetingId) ?? [];
      bucket.push(record);
      submissionsByMeeting.set(record.submission.meetingId, bucket);
    }
    return {
      course: {
        slug: course.slug,
        courseName: course.courseName,
        classGroup: course.classGroup,
        isArchived: course.isArchived,
        semesterIsActive: course.semesterIsActive,
        dosenId: course.dosenId,
        aslabId: course.aslabId,
      },
      meeting,
      meetings: courseMeetings.map((row) => ({
        ...row,
        submissions: (submissionsByMeeting.get(row.id) ?? []).map((item) => ({ ...item.submission, historiesCount: item.historiesCount })),
      })),
      students,
    };
  }),

  mine: roleProcedure('Mahasiswa').input(z.object({ archive: z.boolean().default(false) }).optional()).query(async ({ ctx, input }) => {
    const archiveFilter = input?.archive
      ? or(eq(courses.isArchived, true), eq(semesters.isActive, false))
      : and(eq(courses.isArchived, false), eq(semesters.isActive, true));
    const db = getDb();
    const weekly = await db.select({
      taskId: meetings.id, meetingId: meetings.id, finalTaskId: sql<number | null>`NULL::bigint`,
      isFinal: sql<boolean>`false`, taskTitle: meetings.title, meetingNumber: meetings.meetingNumber,
      deadline: meetings.deadline, courseName: courses.courseName, classGroup: courses.classGroup,
      courseSlug: courses.slug, submission: submissions,
    }).from(courseUsers).innerJoin(courses, eq(courseUsers.courseId, courses.id))
      .innerJoin(semesters, eq(courses.semesterId, semesters.id)).innerJoin(meetings, eq(meetings.courseId, courses.id))
      .leftJoin(submissions, and(eq(submissions.meetingId, meetings.id), eq(submissions.studentId, ctx.user.id)))
      .where(and(eq(courseUsers.userId, ctx.user.id), isNotNull(meetings.publishedAt), archiveFilter));
    const finals = await db.select({
      taskId: finalTasks.id, meetingId: sql<number | null>`NULL::bigint`, finalTaskId: finalTasks.id,
      isFinal: sql<boolean>`true`, taskTitle: sql<string>`'Laporan Final'`, meetingNumber: sql<number | null>`NULL::integer`,
      deadline: finalTasks.deadline, courseName: courses.courseName, classGroup: courses.classGroup,
      courseSlug: courses.slug, submission: submissions,
    }).from(courseUsers).innerJoin(courses, eq(courseUsers.courseId, courses.id))
      .innerJoin(semesters, eq(courses.semesterId, semesters.id)).innerJoin(finalTasks, eq(finalTasks.courseId, courses.id))
      .leftJoin(submissions, and(eq(submissions.finalTaskId, finalTasks.id), eq(submissions.studentId, ctx.user.id)))
      .where(and(eq(courseUsers.userId, ctx.user.id), archiveFilter));
    return [...weekly, ...finals].sort((left, right) =>
      (right.deadline?.getTime() ?? 0) - (left.deadline?.getTime() ?? 0));
  }),

  studentContext: roleProcedure('Mahasiswa').input(z.object({ meetingId: z.number().int().positive().optional(), finalTaskId: z.number().int().positive().optional() })
    .refine((value) => Boolean(value.meetingId) !== Boolean(value.finalTaskId), 'Pilih tepat satu jenis tugas.')).query(async ({ ctx, input }) => {
    const task = input.meetingId ? await getMeeting(input.meetingId) : await getFinalTask(input.finalTaskId!);
    const course = await requireCourseStudent(ctx.user, await getCourseBySlug(task.slug));
    if (input.meetingId && !(task as Awaited<ReturnType<typeof getMeeting>>).publishedAt) throw new TRPCError({ code: 'NOT_FOUND', message: 'Pengumpulan untuk modul ini belum dibuka.' });
    const [submission] = await getDb().select().from(submissions).where(and(
      input.meetingId ? eq(submissions.meetingId, input.meetingId) : eq(submissions.finalTaskId, input.finalTaskId!),
      eq(submissions.studentId, ctx.user.id),
    )).limit(1);
    const histories = submission ? await getDb().select({ history: submissionHistories, reviewerName: users.name, reviewerRole: users.role })
      .from(submissionHistories).leftJoin(users, eq(submissionHistories.reviewedBy, users.id))
      .where(eq(submissionHistories.submissionId, submission.id)).orderBy(desc(submissionHistories.iteration)) : [];
    return { course, task, submission: submission ?? null, histories };
  }),

  submit: roleProcedure('Mahasiswa').input(submissionInput.extend({ meetingId: z.number().int().positive() })).mutation(async ({ ctx, input }) => {
    const meeting = await getMeeting(input.meetingId);
    requireCourseWritable(await requireCourseStudent(ctx.user, await getCourseBySlug(meeting.slug)));
    if (!meeting.publishedAt) throw new TRPCError({ code: 'NOT_FOUND', message: 'Pengumpulan untuk modul ini belum dibuka.' });
    if (meeting.deadline && meeting.deadline < new Date()) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Waktu pengumpulan sudah ditutup.' });
    const db = getDb();
    const [existing] = await db.select({ id: submissions.id }).from(submissions).where(and(eq(submissions.meetingId, input.meetingId), eq(submissions.studentId, ctx.user.id))).limit(1);
    if (existing) throw new TRPCError({ code: 'CONFLICT', message: 'Laporan sudah dikirim. Buka ulang halaman untuk mengirim versi baru.' });
    await db.transaction(async (tx) => {
      const [record] = await tx.insert(submissions).values({ studentId: ctx.user.id, meetingId: input.meetingId, submissionLink: input.driveLink,
        notes: input.notes ?? null, isFinal: false, documentVersion: 1, firstUploadAt: new Date(), lastUploadAt: new Date() }).returning();
      await recordHistory(tx, { submissionId: record.id, documentVersion: 1, driveLink: input.driveLink, actionType: 'Upload' });
    });
    return { success: true, message: 'Tugas berhasil dikirim.' };
  }),

  submitFinal: roleProcedure('Mahasiswa').input(submissionInput.extend({ finalTaskId: z.number().int().positive() })).mutation(async ({ ctx, input }) => {
    const task = await getFinalTask(input.finalTaskId);
    requireCourseWritable(await requireCourseStudent(ctx.user, await getCourseBySlug(task.slug)));
    if (task.deadline && task.deadline < new Date()) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Waktu pengumpulan sudah ditutup.' });
    const db = getDb();
    const [existing] = await db.select({ id: submissions.id }).from(submissions).where(and(eq(submissions.finalTaskId, input.finalTaskId), eq(submissions.studentId, ctx.user.id))).limit(1);
    if (existing) throw new TRPCError({ code: 'CONFLICT', message: 'Laporan final sudah dikirim.' });
    await db.transaction(async (tx) => {
      const [record] = await tx.insert(submissions).values({ studentId: ctx.user.id, finalTaskId: input.finalTaskId, submissionLink: input.driveLink,
        notes: input.notes ?? null, isFinal: true, documentVersion: 1, dosenStatus: 'Pending', firstUploadAt: new Date(), lastUploadAt: new Date() }).returning();
      await recordHistory(tx, { submissionId: record.id, documentVersion: 1, driveLink: input.driveLink, actionType: 'Upload' });
    });
    return { success: true, message: 'Laporan final berhasil dikumpulkan.' };
  }),

  revise: roleProcedure('Mahasiswa').input(submissionInput.extend({ id: z.number().int().positive(), documentVersion: z.number().int().positive() })).mutation(async ({ ctx, input }) => {
    const context = await getSubmission(input.id);
    if (context.submission.studentId !== ctx.user.id || context.submission.isCompleted) throw new TRPCError({ code: 'FORBIDDEN', message: 'Pengumpulan ini tidak dapat diubah.' });
    requireCourseWritable(await requireCourseStudent(ctx.user, await getCourseBySlug(context.slug)));
    const allowed = canResubmit(context.submission);
    if (!allowed && context.deadline && context.deadline < new Date()) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Waktu pengumpulan sudah ditutup.' });
    const nextVersion = context.submission.documentVersion + 1;
    await getDb().transaction(async (tx) => {
      const [locked] = await tx.select().from(submissions).where(eq(submissions.id, input.id)).for('update');
      if (!locked || locked.documentVersion !== input.documentVersion) throw new TRPCError({ code: 'CONFLICT', message: 'Versi dokumen berubah. Muat ulang halaman sebelum mengirim.' });
      if (!canResubmit(locked) && context.deadline && context.deadline < new Date()) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Waktu pengumpulan sudah ditutup.' });
      await tx.update(submissions).set({ submissionLink: input.driveLink, notes: input.notes ?? null,
        documentVersion: nextVersion, aslabStatus: 'Pending', laboranStatus: 'Pending', dosenStatus: locked.isFinal ? 'Pending' : 'N/A',
        aslabAccAt: null, laboranAccAt: null, dosenAccAt: null, isCompleted: false, lastUploadAt: new Date(), updatedAt: new Date(),
      }).where(eq(submissions.id, input.id));
      await recordHistory(tx, { submissionId: input.id, documentVersion: nextVersion, driveLink: input.driveLink, actionType: 'Revision' });
    });
    return { success: true, message: 'Perbaikan laporan berhasil dikirim.' };
  }),

  reviewDetail: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.union([
    z.object({ id: z.number().int().positive() }),
    z.object({ courseSlug: z.string().min(1), meetingNumber: z.number().int().positive(), studentId: z.string().min(1).max(20) }),
  ])).query(async ({ ctx, input }) => {
    let submissionId: number;
    if ('id' in input) {
      submissionId = input.id;
    } else {
      const course = await getCourseBySlug(input.courseSlug);
      await requireCourseManager(ctx.user, course);
      const [meeting] = await getDb().select({ id: meetings.id }).from(meetings).where(and(
        eq(meetings.courseId, course.id), eq(meetings.meetingNumber, input.meetingNumber),
      )).limit(1);
      if (!meeting) throw new TRPCError({ code: 'NOT_FOUND', message: 'Modul tidak ditemukan.' });
      const [submission] = await getDb().select({ id: submissions.id }).from(submissions).where(and(
        eq(submissions.meetingId, meeting.id), eq(submissions.studentId, input.studentId), eq(submissions.isFinal, false),
      )).limit(1);
      if (!submission) throw new TRPCError({ code: 'NOT_FOUND', message: 'Pengumpulan mahasiswa tidak ditemukan.' });
      submissionId = submission.id;
    }
    const context = await getSubmission(submissionId);
    await requireCourseManager(ctx.user, await getCourseBySlug(context.slug));
    if (ctx.user.role === 'Dosen' && !context.submission.isFinal) throw new TRPCError({ code: 'NOT_FOUND' });
    const [student] = await getDb().select({ id: users.id, name: users.name, email: users.email }).from(users).where(eq(users.id, context.submission.studentId)).limit(1);
    const histories = await getDb().select({ history: submissionHistories, reviewerName: users.name, reviewerRole: users.role })
      .from(submissionHistories).leftJoin(users, eq(submissionHistories.reviewedBy, users.id))
      .where(eq(submissionHistories.submissionId, submissionId)).orderBy(desc(submissionHistories.iteration));
    return { ...context, student, histories };
  }),

  pending: roleProcedure('Dosen', 'Laboran', 'Aslab').query(async ({ ctx }) => {
    const role = ctx.user.role;
    const status = role === 'Dosen' ? and(eq(submissions.dosenStatus, 'Pending'), eq(submissions.isFinal, true), eq(submissions.aslabStatus, 'ACC'), eq(submissions.laboranStatus, 'ACC')) :
      role === 'Aslab' ? eq(submissions.aslabStatus, 'Pending') : and(eq(submissions.aslabStatus, 'ACC'), eq(submissions.laboranStatus, 'Pending'));
    const assigned = role === 'Dosen' ? eq(courses.dosenId, ctx.user.id) : role === 'Aslab' ? eq(courses.aslabId, ctx.user.id) : undefined;
    return getDb().select({ submission: submissions, studentName: users.name, studentId: users.id, courseSlug: courses.slug,
      courseName: courses.courseName, group: courses.classGroup, meetingNumber: meetings.meetingNumber })
      .from(submissions).innerJoin(users, eq(submissions.studentId, users.id))
      .leftJoin(meetings, eq(submissions.meetingId, meetings.id)).leftJoin(finalTasks, eq(submissions.finalTaskId, finalTasks.id))
      .innerJoin(courses, or(eq(meetings.courseId, courses.id), eq(finalTasks.courseId, courses.id)))
      .innerJoin(semesters, eq(courses.semesterId, semesters.id))
      .where(and(eq(submissions.isCompleted, false), status, assigned, eq(courses.isArchived, false), eq(semesters.isActive, true)))
      .orderBy(desc(submissions.createdAt)).limit(250);
  }),

  review: roleProcedure('Dosen', 'Laboran', 'Aslab').input(reviewInput).mutation(async ({ ctx, input }) => {
    const context = await getSubmission(input.id);
    requireCourseWritable(await requireCourseManager(ctx.user, await getCourseBySlug(context.slug)));
    if (ctx.user.role === 'Dosen' && !context.submission.isFinal) throw new TRPCError({ code: 'NOT_FOUND' });
    const now = new Date();
    const status = input.status === 'ACC' ? 'ACC' : input.status === 'DITOLAK' ? 'Ditolak' : 'Revisi';
    await getDb().transaction(async (tx) => {
      const [locked] = await tx.select().from(submissions).where(eq(submissions.id, input.id)).for('update');
      if (!locked || locked.isCompleted) throw new TRPCError({ code: 'CONFLICT', message: 'Laporan telah selesai diverifikasi.' });
      if (locked.documentVersion !== input.documentVersion) throw new TRPCError({ code: 'CONFLICT', message: 'Versi dokumen berubah. Muat ulang halaman sebelum memeriksa.' });
      const feedback = locked.isFinal ? input.notes : input.feedback;
      if (status === 'ACC' && !locked.isFinal && input.score === undefined) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Nilai wajib diisi saat memberikan ACC.' });
      const patch: Partial<typeof submissions.$inferInsert> = { updatedAt: now };
      if (ctx.user.role === 'Aslab') {
        if (locked.aslabStatus === 'ACC') throw new TRPCError({ code: 'CONFLICT', message: 'Laporan sudah mendapat ACC Aslab.' });
        patch.aslabStatus = status;
        patch.aslabAccAt = status === 'ACC' ? now : null;
        if (!locked.isFinal) patch.aslabScore = status === 'ACC' ? String(input.score) : null;
        if (status !== 'ACC') { patch.laboranStatus = 'Pending'; patch.laboranAccAt = null; patch.laboranScore = null; if (locked.isFinal) { patch.dosenStatus = 'Pending'; patch.dosenAccAt = null; } }
      } else if (ctx.user.role === 'Laboran') {
        if (locked.aslabStatus !== 'ACC') throw new TRPCError({ code: 'BAD_REQUEST', message: 'Menunggu verifikasi Aslab.' });
        if (locked.laboranStatus === 'ACC') throw new TRPCError({ code: 'CONFLICT', message: 'Laporan sudah mendapat ACC Laboran.' });
        patch.laboranStatus = status;
        patch.laboranAccAt = status === 'ACC' ? now : null;
        if (!locked.isFinal) patch.laboranScore = status === 'ACC' ? String(input.score) : null;
        if (status !== 'ACC' && locked.isFinal) { patch.dosenStatus = 'Pending'; patch.dosenAccAt = null; }
      } else {
        if (locked.aslabStatus !== 'ACC' || locked.laboranStatus !== 'ACC') throw new TRPCError({ code: 'BAD_REQUEST', message: 'Menunggu verifikasi Aslab dan Laboran.' });
        if (locked.dosenStatus === 'ACC') throw new TRPCError({ code: 'CONFLICT', message: 'Laporan sudah mendapat ACC Dosen.' });
        patch.dosenStatus = status;
        patch.dosenAccAt = status === 'ACC' ? now : null;
      }
      const aslab = patch.aslabStatus ?? locked.aslabStatus;
      const laboran = patch.laboranStatus ?? locked.laboranStatus;
      const dosen = patch.dosenStatus ?? locked.dosenStatus;
      patch.isCompleted = aslab === 'ACC' && laboran === 'ACC' && (!locked.isFinal || dosen === 'ACC');
      await tx.update(submissions).set(patch).where(eq(submissions.id, input.id));
      const actionType = status === 'ACC' ? 'ACC' : status === 'Ditolak' ? 'Rejected' : 'Revision';
      await recordHistory(tx, { submissionId: input.id, documentVersion: locked.documentVersion,
        driveLink: locked.submissionLink ?? '', actionType, feedback, reviewerId: ctx.user.id });
    });
    return { success: true, message: `Status ${input.status} berhasil disimpan.` };
  }),

  setDeadline: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.object({ meetingId: z.number().int().positive(), deadline: z.date() })).mutation(async ({ ctx, input }) => {
    const meeting = await getMeeting(input.meetingId);
    requireCourseWritable(await requireCourseManager(ctx.user, await getCourseBySlug(meeting.slug)));
    await getDb().update(meetings).set({ deadline: input.deadline, updatedAt: new Date() }).where(eq(meetings.id, meeting.id));
    return { success: true };
  }),

  setScore: roleProcedure('Laboran', 'Aslab').input(z.object({ id: z.number().int().positive(), score: z.number().min(0).max(100) })).mutation(async ({ ctx, input }) => {
    const context = await getSubmission(input.id);
    requireCourseWritable(await requireCourseManager(ctx.user, await getCourseBySlug(context.slug)));
    if (context.submission.isFinal || !context.submission.isCompleted) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Nilai modul dapat diberikan setelah laporan mingguan selesai diverifikasi.' });
    const field = ctx.user.role === 'Aslab' ? 'aslabScore' : 'laboranScore';
    await getDb().update(submissions).set({ [field]: String(input.score), updatedAt: new Date() }).where(eq(submissions.id, input.id));
    return { success: true };
  }),

  finalTask: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.object({ finalTaskId: z.number().int().positive() })).query(async ({ ctx, input }) => {
    const task = await getFinalTask(input.finalTaskId);
    const course = await getCourseBySlug(task.slug);
    await requireCourseView(ctx.user, course);
    const roster = await getDb().select({ id: users.id, name: users.name, email: users.email }).from(courseUsers)
      .innerJoin(users, eq(courseUsers.userId, users.id)).where(eq(courseUsers.courseId, course.id)).orderBy(asc(users.name));
    const rows = await getDb().select().from(submissions).where(eq(submissions.finalTaskId, task.id));
    return { task, course, students: roster, submissions: rows };
  }),

  updateFinalTask: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.object({ finalTaskId: z.number().int().positive(), description: z.string().trim().min(1).max(10000).optional(), deadline: z.date().optional() })).mutation(async ({ ctx, input }) => {
    const task = await getFinalTask(input.finalTaskId);
    requireCourseWritable(await requireCourseManager(ctx.user, await getCourseBySlug(task.slug)));
    const patch: Partial<typeof finalTasks.$inferInsert> = { updatedAt: new Date() };
    if (input.description !== undefined) patch.description = input.description;
    if (input.deadline !== undefined) patch.deadline = input.deadline;
    await getDb().update(finalTasks).set(patch).where(eq(finalTasks.id, task.id));
    return { success: true };
  }),
});
