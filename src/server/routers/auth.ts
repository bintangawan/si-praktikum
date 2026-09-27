import { TRPCError } from '@trpc/server';
import bcrypt from 'bcryptjs';
import { eq, or } from 'drizzle-orm';
import { z } from 'zod';
import { getDb } from '../db';
import { attendances, courseGrades, courseStaffHistories, courseUsers, courses, submissionHistories, submissions, tutorials, users } from '../db/schema';
import { getEnv } from '../env';
import { deleteStorageFile } from '../storage';
import { createSession, deleteCurrentSession } from '../auth/session';
import { protectedProcedure, publicProcedure, router, sessionProcedure } from '../trpc/init';

const passwordSchema = z.string().min(8, 'Password minimal 8 karakter.').max(128);

export const authRouter = router({
  login: publicProcedure.input(z.object({
    identifier: z.string().trim().min(1).max(255),
    password: z.string().min(1),
    remember: z.boolean().default(false),
  })).mutation(async ({ input }) => {
    const [user] = await getDb().select({
      id: users.id, email: users.email, password: users.password, approvedAt: users.approvedAt, isFirstLogin: users.isFirstLogin,
    }).from(users).where(or(eq(users.email, input.identifier.toLowerCase()), eq(users.id, input.identifier))).limit(1);
    const matches = user ? await bcrypt.compare(input.password, user.password).catch(() => false) : false;
    if (!user || !matches) throw new TRPCError({ code: 'UNAUTHORIZED', message: 'Email, NIM/NIP, atau password salah.' });
    await createSession(user.id, input.remember);
    if (!user.approvedAt) return { redirectTo: '/account/pending' };
    if (user.isFirstLogin) return { redirectTo: '/force-change-password' };
    return { redirectTo: '/dashboard' };
  }),

  register: publicProcedure.input(z.object({
    id: z.string().trim().min(1).max(20),
    name: z.string().trim().min(2).max(255),
    email: z.string().trim().email().max(255).transform((value) => value.toLowerCase()),
    password: passwordSchema,
    passwordConfirmation: z.string(),
  }).refine((value) => value.password === value.passwordConfirmation, {
    path: ['passwordConfirmation'], message: 'Konfirmasi password tidak sama.',
  })).mutation(async ({ input }) => {
    const db = getDb();
    const [user] = await db.insert(users).values({
      id: input.id,
      name: input.name,
      email: input.email,
      password: await bcrypt.hash(input.password, getEnv().BCRYPT_ROUNDS),
      role: 'Mahasiswa',
      isFirstLogin: true,
    }).onConflictDoNothing().returning({ id: users.id, name: users.name, email: users.email });
    if (!user) throw new TRPCError({ code: 'CONFLICT', message: 'ID mahasiswa atau email sudah terdaftar.' });

    return { message: 'Akun berhasil dibuat. Silakan tunggu pemeriksaan dan persetujuan Laboran atau Aslab sebelum masuk.' };
  }),

  logout: sessionProcedure.mutation(async () => {
    await deleteCurrentSession();
    return { success: true };
  }),

  current: sessionProcedure.query(({ ctx }) => ({ user: ctx.user })),

  updatePassword: sessionProcedure.input(z.object({
    currentPassword: z.string().min(1),
    password: passwordSchema,
    passwordConfirmation: z.string(),
  }).refine((value) => value.password === value.passwordConfirmation, {
    path: ['passwordConfirmation'], message: 'Konfirmasi password tidak sama.',
  })).mutation(async ({ input, ctx }) => {
    if (!ctx.user.approvedAt) {
      throw new TRPCError({ code: 'FORBIDDEN', message: 'Akun Anda belum disetujui.' });
    }
    const [user] = await getDb().select({ password: users.password }).from(users).where(eq(users.id, ctx.user.id)).limit(1);
    if (!user || !(await bcrypt.compare(input.currentPassword, user.password))) {
      throw new TRPCError({ code: 'BAD_REQUEST', message: 'Password saat ini tidak sesuai.' });
    }
    await getDb().update(users).set({
      password: await bcrypt.hash(input.password, getEnv().BCRYPT_ROUNDS),
      isFirstLogin: false,
      updatedAt: new Date(),
    }).where(eq(users.id, ctx.user.id));
    return { message: 'Password berhasil diperbarui.' };
  }),

  updateProfile: protectedProcedure.input(z.object({ name: z.string().trim().min(2).max(255), email: z.string().trim().email().max(255).transform((value) => value.toLowerCase()) }))
    .mutation(async ({ input, ctx }) => {
      const db = getDb();
      const [other] = await db.select({ id: users.id }).from(users).where(eq(users.email, input.email)).limit(1);
      if (other && other.id !== ctx.user.id) throw new TRPCError({ code: 'CONFLICT', message: 'Email sudah digunakan akun lain.' });
      await db.update(users).set({ name: input.name, email: input.email, updatedAt: new Date() }).where(eq(users.id, ctx.user.id));
      return { message: 'Profil berhasil diperbarui.' };
    }),

  deleteAccount: protectedProcedure.input(z.object({ password: z.string().min(1) })).mutation(async ({ input, ctx }) => {
    const db = getDb();
    const [user] = await db.select().from(users).where(eq(users.id, ctx.user.id)).limit(1);
    if (!user || !(await bcrypt.compare(input.password, user.password))) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Password tidak sesuai.' });
    const [
      [submission], [attendance], [assignment], [tutorial], [membership], [grade], [reviewHistory], [staffHistory], [approvedUsers],
    ] = await Promise.all([
      db.select({ id: submissions.id }).from(submissions).where(eq(submissions.studentId, user.id)).limit(1),
      db.select({ id: attendances.id }).from(attendances).where(eq(attendances.studentId, user.id)).limit(1),
      db.select({ id: courses.id }).from(courses).where(or(eq(courses.dosenId, user.id), eq(courses.laboranId, user.id), eq(courses.aslabId, user.id))).limit(1),
      db.select({ id: tutorials.id }).from(tutorials).where(eq(tutorials.createdBy, user.id)).limit(1),
      db.select({ id: courseUsers.id }).from(courseUsers).where(eq(courseUsers.userId, user.id)).limit(1),
      db.select({ id: courseGrades.id }).from(courseGrades).where(eq(courseGrades.studentId, user.id)).limit(1),
      db.select({ id: submissionHistories.id }).from(submissionHistories).where(eq(submissionHistories.reviewedBy, user.id)).limit(1),
      db.select({ id: courseStaffHistories.id }).from(courseStaffHistories).where(or(
        eq(courseStaffHistories.changedBy, user.id), eq(courseStaffHistories.previousDosenId, user.id),
        eq(courseStaffHistories.previousAslabId, user.id), eq(courseStaffHistories.dosenId, user.id), eq(courseStaffHistories.aslabId, user.id),
      )).limit(1),
      db.select({ id: users.id }).from(users).where(eq(users.approvedBy, user.id)).limit(1),
    ]);
    if (submission || attendance || assignment || tutorial || membership || grade || reviewHistory || staffHistory || approvedUsers) {
      throw new TRPCError({ code: 'CONFLICT', message: 'Akun yang memiliki data akademik, keanggotaan kelas, atau riwayat audit tidak dapat dihapus.' });
    }
    await db.delete(users).where(eq(users.id, user.id));
    if (user.avatar) await deleteStorageFile(user.avatar).catch(() => undefined);
    await deleteCurrentSession();
    return { success: true };
  }),
});
