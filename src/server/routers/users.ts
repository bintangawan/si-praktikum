import { TRPCError } from '@trpc/server';
import bcrypt from 'bcryptjs';
import { and, asc, count, eq, ilike, inArray, isNull, ne, or } from 'drizzle-orm';
import { z } from 'zod';
import { getDb } from '../db';
import { attendances, courseGrades, courseStaffHistories, courseUsers, courses, semesters, sessions, submissionHistories, submissions, tutorials, users } from '../db/schema';
import { getEnv } from '../env';
import { deleteStorageFile } from '../storage';
import { roleProcedure, router } from '../trpc/init';
import { USER_ROLES } from '@/lib/roles';

async function mapWithConcurrency<T, R>(items: T[], concurrency: number, map: (item: T) => Promise<R>) {
  const results = new Array<R>(items.length);
  let cursor = 0;
  await Promise.all(Array.from({ length: Math.min(concurrency, items.length) }, async () => {
    while (cursor < items.length) {
      const index = cursor++;
      results[index] = await map(items[index]);
    }
  }));
  return results;
}

export const usersRouter = router({
  pendingApprovals: roleProcedure('Laboran', 'Aslab').query(async () => {
    const [{ value: total }] = await getDb().select({ value: count() }).from(users)
      .where(and(eq(users.role, 'Mahasiswa'), isNull(users.approvedAt)));
    const rows = await getDb().select({ id: users.id, name: users.name, email: users.email, createdAt: users.createdAt })
      .from(users).where(and(eq(users.role, 'Mahasiswa'), isNull(users.approvedAt))).orderBy(asc(users.createdAt)).limit(250);
    return { total, users: rows };
  }),

  approve: roleProcedure('Laboran', 'Aslab').input(z.object({ id: z.string().min(1).max(20) })).mutation(async ({ input, ctx }) => {
    const [user] = await getDb().update(users).set({ approvedAt: new Date(), approvedBy: ctx.user.id, updatedAt: new Date() })
      .where(and(eq(users.id, input.id), eq(users.role, 'Mahasiswa'), isNull(users.approvedAt))).returning({ id: users.id });
    if (!user) throw new TRPCError({ code: 'NOT_FOUND', message: 'Akun belum disetujui tidak ditemukan.' });
    return { success: true };
  }),

  approveAll: roleProcedure('Laboran', 'Aslab').mutation(async ({ ctx }) => {
    await getDb().update(users).set({ approvedAt: new Date(), approvedBy: ctx.user.id, updatedAt: new Date() })
      .where(and(eq(users.role, 'Mahasiswa'), isNull(users.approvedAt)));
    return { success: true };
  }),

  deletePending: roleProcedure('Laboran', 'Aslab').input(z.object({ id: z.string().min(1).max(20) })).mutation(async ({ input }) => {
    const [user] = await getDb().select({ id: users.id }).from(users).where(and(eq(users.id, input.id), eq(users.role, 'Mahasiswa'), isNull(users.approvedAt))).limit(1);
    if (!user) throw new TRPCError({ code: 'NOT_FOUND', message: 'Akun mahasiswa belum disetujui tidak ditemukan.' });
    const [academic] = await getDb().select({ id: submissions.id }).from(submissions).where(eq(submissions.studentId, user.id)).limit(1);
    const [attendance] = await getDb().select({ id: attendances.id }).from(attendances).where(eq(attendances.studentId, user.id)).limit(1);
    const [grade] = await getDb().select({ id: courseGrades.id }).from(courseGrades).where(eq(courseGrades.studentId, user.id)).limit(1);
    if (academic || attendance || grade) throw new TRPCError({ code: 'CONFLICT', message: 'Akun tidak dapat dihapus karena memiliki data akademik.' });
    await getDb().delete(users).where(eq(users.id, user.id));
    return { success: true };
  }),

  list: roleProcedure('Laboran').input(z.object({ search: z.string().trim().max(255).default(''), roles: z.array(z.enum(USER_ROLES)).default([]), limit: z.union([z.literal(10), z.literal(25), z.literal(50), z.literal(100)]).default(10), offset: z.number().int().nonnegative().default(0) }).optional()).query(async ({ input }) => {
    const query = input?.search ?? '';
    const selectedRoles = input?.roles ?? [];
    const roleFilter = selectedRoles.length ? inArray(users.role, selectedRoles.includes('Mahasiswa') ? [...new Set([...selectedRoles, 'Aslab' as const])] : selectedRoles) : undefined;
    const searchFilter = query ? or(ilike(users.id, `%${query}%`), ilike(users.name, `%${query}%`), ilike(users.email, `%${query}%`)) : undefined;
    const filters = [roleFilter, searchFilter].filter(Boolean);
    const rows = await getDb().select({ id: users.id, name: users.name, email: users.email, role: users.role,
      approvedAt: users.approvedAt, isFirstLogin: users.isFirstLogin }).from(users)
      .where(filters.length ? and(...filters) : undefined).orderBy(asc(users.name)).limit(input?.limit ?? 10).offset(input?.offset ?? 0);
    const [{ value: total }] = await getDb().select({ value: count() }).from(users).where(filters.length ? and(...filters) : undefined);
    return { users: rows, total, limit: input?.limit ?? 10, offset: input?.offset ?? 0 };
  }),

  resetPassword: roleProcedure('Laboran').input(z.object({ id: z.string().min(1).max(20) })).mutation(async ({ input }) => {
    const passwordHash = await bcrypt.hash(input.id, getEnv().BCRYPT_ROUNDS);
    const [user] = await getDb().update(users).set({ password: passwordHash, isFirstLogin: true, updatedAt: new Date() })
      .where(eq(users.id, input.id)).returning({ id: users.id });
    if (!user) throw new TRPCError({ code: 'NOT_FOUND', message: 'Pengguna tidak ditemukan.' });
    await getDb().delete(sessions).where(eq(sessions.userId, user.id));
    return { success: true, message: `Password berhasil direset menggunakan ID ${input.id}.` };
  }),

  updateRole: roleProcedure('Laboran').input(z.object({ id: z.string().min(1).max(20), role: z.enum(USER_ROLES) })).mutation(async ({ input, ctx }) => {
    if (input.id === ctx.user.id) throw new TRPCError({ code: 'FORBIDDEN', message: 'Role akun sendiri tidak dapat diubah.' });
    const db = getDb();
    const [target] = await db.select({ id: users.id, role: users.role, approvedAt: users.approvedAt }).from(users).where(eq(users.id, input.id)).limit(1);
    if (!target) throw new TRPCError({ code: 'NOT_FOUND', message: 'Pengguna tidak ditemukan.' });
    if (target.role === input.role) throw new TRPCError({ code: 'CONFLICT', message: 'Role pengguna sudah menggunakan jabatan tersebut.' });
    if (target.role === 'Mahasiswa' && !target.approvedAt && input.role !== 'Mahasiswa') {
      throw new TRPCError({ code: 'BAD_REQUEST', message: 'Akun mahasiswa harus disetujui sebelum dapat diangkat.' });
    }

    const [activeAssignment] = await db.select({ id: courses.id }).from(courses)
      .innerJoin(semesters, eq(courses.semesterId, semesters.id))
      .where(and(
        eq(courses.isArchived, false),
        eq(semesters.isActive, true),
        or(eq(courses.dosenId, target.id), eq(courses.laboranId, target.id), eq(courses.aslabId, target.id)),
      )).limit(1);
    if (activeAssignment) {
      throw new TRPCError({ code: 'CONFLICT', message: 'Pengguna masih ditugaskan pada kelas aktif. Ganti penugasan terlebih dahulu.' });
    }

    if (target.role === 'Laboran' && input.role !== 'Laboran') {
      const [anotherLaboran] = await db.select({ id: users.id }).from(users)
        .where(and(eq(users.role, 'Laboran'), ne(users.id, target.id))).limit(1);
      if (!anotherLaboran) throw new TRPCError({ code: 'CONFLICT', message: 'Jabatan akun Laboran terakhir tidak dapat diubah atau dihapus.' });
    }

    await db.update(users).set({ role: input.role, updatedAt: new Date() }).where(eq(users.id, target.id));
    return { success: true };
  }),

  updateAccount: roleProcedure('Laboran').input(z.object({
    id: z.string().trim().min(1).max(20),
    name: z.string().trim().min(2).max(255),
    email: z.string().trim().email().max(255).transform((value) => value.toLowerCase()),
  })).mutation(async ({ input }) => {
    const db = getDb();
    const [target] = await db.select({ id: users.id }).from(users).where(eq(users.id, input.id)).limit(1);
    if (!target) throw new TRPCError({ code: 'NOT_FOUND', message: 'Pengguna tidak ditemukan.' });

    const [existingEmail] = await db.select({ id: users.id }).from(users).where(eq(users.email, input.email)).limit(1);
    if (existingEmail && existingEmail.id !== target.id) {
      throw new TRPCError({ code: 'CONFLICT', message: 'Email sudah digunakan akun lain.' });
    }

    try {
      await db.update(users).set({ name: input.name, email: input.email, updatedAt: new Date() }).where(eq(users.id, target.id));
    } catch (error) {
      if (typeof error === 'object' && error !== null && 'code' in error && error.code === '23505') {
        throw new TRPCError({ code: 'CONFLICT', message: 'Email sudah digunakan akun lain.' });
      }
      throw error;
    }
    return { success: true };
  }),

  deleteAccount: roleProcedure('Laboran').input(z.object({ id: z.string().trim().min(1).max(20) })).mutation(async ({ input, ctx }) => {
    if (input.id === ctx.user.id) throw new TRPCError({ code: 'FORBIDDEN', message: 'Akun sendiri tidak dapat dihapus melalui manajemen pengguna.' });

    const db = getDb();
    const [target] = await db.select({ id: users.id, role: users.role, avatar: users.avatar }).from(users)
      .where(eq(users.id, input.id)).limit(1);
    if (!target) throw new TRPCError({ code: 'NOT_FOUND', message: 'Pengguna tidak ditemukan.' });

    if (target.role === 'Laboran') {
      const [anotherLaboran] = await db.select({ id: users.id }).from(users)
        .where(and(eq(users.role, 'Laboran'), ne(users.id, target.id))).limit(1);
      if (!anotherLaboran) throw new TRPCError({ code: 'CONFLICT', message: 'Jabatan akun Laboran terakhir tidak dapat diubah atau dihapus.' });
    }

    const [
      [submission], [attendance], [assignment], [tutorial], [membership], [grade], [reviewHistory], [staffHistory], [approvedUsers],
    ] = await Promise.all([
      db.select({ id: submissions.id }).from(submissions).where(eq(submissions.studentId, target.id)).limit(1),
      db.select({ id: attendances.id }).from(attendances).where(eq(attendances.studentId, target.id)).limit(1),
      db.select({ id: courses.id }).from(courses).where(or(
        eq(courses.dosenId, target.id), eq(courses.laboranId, target.id), eq(courses.aslabId, target.id),
      )).limit(1),
      db.select({ id: tutorials.id }).from(tutorials).where(eq(tutorials.createdBy, target.id)).limit(1),
      db.select({ id: courseUsers.id }).from(courseUsers).where(eq(courseUsers.userId, target.id)).limit(1),
      db.select({ id: courseGrades.id }).from(courseGrades).where(eq(courseGrades.studentId, target.id)).limit(1),
      db.select({ id: submissionHistories.id }).from(submissionHistories).where(eq(submissionHistories.reviewedBy, target.id)).limit(1),
      db.select({ id: courseStaffHistories.id }).from(courseStaffHistories).where(or(
        eq(courseStaffHistories.changedBy, target.id), eq(courseStaffHistories.previousDosenId, target.id),
        eq(courseStaffHistories.previousAslabId, target.id), eq(courseStaffHistories.dosenId, target.id),
        eq(courseStaffHistories.aslabId, target.id),
      )).limit(1),
      db.select({ id: users.id }).from(users).where(eq(users.approvedBy, target.id)).limit(1),
    ]);

    if (submission || attendance || assignment || tutorial || membership || grade || reviewHistory || staffHistory || approvedUsers) {
      throw new TRPCError({
        code: 'CONFLICT',
        message: 'Akun yang memiliki data akademik, keanggotaan kelas, atau riwayat audit tidak dapat dihapus.',
      });
    }

    try {
      await db.delete(users).where(eq(users.id, target.id));
    } catch (error) {
      if (typeof error === 'object' && error !== null && 'code' in error && error.code === '23503') {
        throw new TRPCError({
          code: 'CONFLICT',
          message: 'Akun yang memiliki data akademik, keanggotaan kelas, atau riwayat audit tidak dapat dihapus.',
        });
      }
      throw error;
    }

    if (target.avatar) await deleteStorageFile(target.avatar).catch(() => undefined);
    return { success: true };
  }),

  importUsers: roleProcedure('Laboran').input(z.object({ rows: z.array(z.object({
    id: z.string().trim().min(1).max(20), name: z.string().trim().min(2).max(255),
    email: z.string().trim().email().max(255).transform((value) => value.toLowerCase()), role: z.enum(USER_ROLES),
  })).max(1000) })).mutation(async ({ input, ctx }) => {
    const db = getDb();
    const success: Array<{ id: string; name: string }> = [];
    const failures: Array<{ id: string; message: string }> = [];
    const candidates: typeof input.rows = [];
    const seenIds = new Set<string>();
    const seenEmails = new Set<string>();
    for (const row of input.rows) {
      if (seenIds.has(row.id) || seenEmails.has(row.email)) {
        failures.push({ id: row.id, message: 'ID atau email duplikat pada file import.' });
        continue;
      }
      seenIds.add(row.id);
      seenEmails.add(row.email);
      candidates.push(row);
    }

    if (candidates.length) {
      const existing = await db.select({ id: users.id, email: users.email }).from(users).where(or(
        inArray(users.id, candidates.map((row) => row.id)),
        inArray(users.email, candidates.map((row) => row.email)),
      ));
      const existingIds = new Set(existing.map((row) => row.id));
      const existingEmails = new Set(existing.map((row) => row.email));
      const insertable = candidates.filter((row) => {
        if (existingIds.has(row.id) || existingEmails.has(row.email)) {
          failures.push({ id: row.id, message: 'ID atau email sudah terdaftar.' });
          return false;
        }
        return true;
      });
      const rounds = getEnv().BCRYPT_ROUNDS;
      const prepared = await mapWithConcurrency(insertable, 8, async (row) => ({
        ...row,
        password: await bcrypt.hash(row.id, rounds),
        approvedAt: row.role === 'Mahasiswa' ? null : new Date(),
        approvedBy: row.role === 'Mahasiswa' ? null : ctx.user.id,
        isFirstLogin: true,
      }));

      for (let offset = 0; offset < prepared.length; offset += 200) {
        const batch = prepared.slice(offset, offset + 200);
        const inserted = await db.insert(users).values(batch).onConflictDoNothing().returning({ id: users.id });
        const insertedIds = new Set(inserted.map((row) => row.id));
        for (const row of batch) {
          if (insertedIds.has(row.id)) success.push({ id: row.id, name: row.name });
          else failures.push({ id: row.id, message: 'ID atau email sudah terdaftar.' });
        }
      }
    }
    return { success, failures };
  }),
});
