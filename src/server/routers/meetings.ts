import { TRPCError } from '@trpc/server';
import { eq } from 'drizzle-orm';
import { z } from 'zod';
import { getCourseBySlug, requireCourseManager, requireCourseWritable, requireModuleManager } from '../auth/access';
import { getDb } from '../db';
import { courses, finalTasks, meetings } from '../db/schema';
import { roleProcedure, router } from '../trpc/init';

const driveModuleUrl = z.string().trim().max(2048).url().refine((value) => {
  const url = new URL(value);
  return url.protocol === 'https:' && url.hostname === 'drive.google.com' && /^\/file\/d\/[A-Za-z0-9_-]+(?:\/(?:view|preview|edit))?\/?$/.test(url.pathname);
}, 'Gunakan link file Google Drive yang valid.');

async function loadMeeting(id: number) {
  const [row] = await getDb().select({ meeting: meetings, courseSlug: courses.slug }).from(meetings)
    .innerJoin(courses, eq(meetings.courseId, courses.id)).where(eq(meetings.id, id)).limit(1);
  if (!row) throw new TRPCError({ code: 'NOT_FOUND', message: 'Modul tidak ditemukan.' });
  return row;
}

export const meetingsRouter = router({
  update: roleProcedure('Laboran', 'Aslab').input(z.object({ id: z.number().int().positive(), title: z.string().trim().min(1).max(255),
    description: z.string().max(5000).nullable(), moduleDriveLink: driveModuleUrl.nullable().or(z.literal('').transform(() => null)) })).mutation(async ({ ctx, input }) => {
    const { meeting, courseSlug } = await loadMeeting(input.id);
    requireCourseWritable(await requireModuleManager(ctx.user, await getCourseBySlug(courseSlug)));
    await getDb().update(meetings).set({ title: input.title, description: input.description, moduleDriveLink: input.moduleDriveLink || null, updatedAt: new Date() })
      .where(eq(meetings.id, meeting.id));
    return { success: true };
  }),

  setDeadline: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.object({ id: z.number().int().positive(), deadline: z.date() })).mutation(async ({ ctx, input }) => {
    const { meeting, courseSlug } = await loadMeeting(input.id);
    requireCourseWritable(await requireCourseManager(ctx.user, await getCourseBySlug(courseSlug)));
    await getDb().update(meetings).set({ deadline: input.deadline, updatedAt: new Date() }).where(eq(meetings.id, meeting.id));
    return { success: true };
  }),

  finalTaskUpdate: roleProcedure('Dosen', 'Laboran', 'Aslab').input(z.object({ id: z.number().int().positive(), description: z.string().trim().min(1).max(10000).optional(), deadline: z.date().optional() })).mutation(async ({ ctx, input }) => {
    const [row] = await getDb().select({ task: finalTasks, courseSlug: courses.slug }).from(finalTasks)
      .innerJoin(courses, eq(finalTasks.courseId, courses.id)).where(eq(finalTasks.id, input.id)).limit(1);
    if (!row) throw new TRPCError({ code: 'NOT_FOUND', message: 'Tugas final tidak ditemukan.' });
    requireCourseWritable(await requireCourseManager(ctx.user, await getCourseBySlug(row.courseSlug)));
    const patch: Partial<typeof finalTasks.$inferInsert> = { updatedAt: new Date() };
    if (input.description !== undefined) patch.description = input.description;
    if (input.deadline !== undefined) patch.deadline = input.deadline;
    await getDb().update(finalTasks).set(patch).where(eq(finalTasks.id, input.id));
    return { success: true };
  }),

  createFinalTask: roleProcedure('Laboran').input(z.object({ slug: z.string().min(1), description: z.string().trim().min(1).max(10000) })).mutation(async ({ ctx, input }) => {
    const course = await requireCourseManager(ctx.user, await getCourseBySlug(input.slug));
    requireCourseWritable(course);
    const [existing] = await getDb().select({ id: finalTasks.id }).from(finalTasks).where(eq(finalTasks.courseId, course.id)).limit(1);
    if (existing) throw new TRPCError({ code: 'CONFLICT', message: 'Tugas final kelas sudah tersedia.' });
    const [task] = await getDb().insert(finalTasks).values({ courseId: course.id, description: input.description }).returning();
    return task;
  }),
});
