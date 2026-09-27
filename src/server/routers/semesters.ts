import { TRPCError } from '@trpc/server';
import { and, asc, eq, ne } from 'drizzle-orm';
import { z } from 'zod';
import { getDb } from '../db';
import { semesters } from '../db/schema';
import { roleProcedure, router } from '../trpc/init';

export const semestersRouter = router({
  list: roleProcedure('Laboran').query(() => getDb().select().from(semesters).orderBy(asc(semesters.createdAt))),
  active: roleProcedure('Dosen', 'Mahasiswa', 'Laboran', 'Aslab').query(async () => {
    const [semester] = await getDb().select().from(semesters).where(eq(semesters.isActive, true)).limit(1);
    return semester ?? null;
  }),
  create: roleProcedure('Laboran').input(z.object({ name: z.string().trim().min(3).max(100) })).mutation(async ({ input }) => {
    const [semester] = await getDb().insert(semesters).values({ name: input.name }).returning();
    return semester;
  }),
  update: roleProcedure('Laboran').input(z.object({ id: z.number().int().positive(), name: z.string().trim().min(3).max(100) })).mutation(async ({ input }) => {
    const [semester] = await getDb().update(semesters).set({ name: input.name, updatedAt: new Date() })
      .where(eq(semesters.id, input.id)).returning();
    if (!semester) throw new TRPCError({ code: 'NOT_FOUND', message: 'Semester tidak ditemukan.' });
    return semester;
  }),
  setActive: roleProcedure('Laboran').input(z.object({ id: z.number().int().positive() })).mutation(async ({ input }) => {
    const db = getDb();
    const [existing] = await db.select({ id: semesters.id }).from(semesters).where(eq(semesters.id, input.id)).limit(1);
    if (!existing) throw new TRPCError({ code: 'NOT_FOUND', message: 'Semester tidak ditemukan.' });
    await db.transaction(async (tx) => {
      await tx.update(semesters).set({ isActive: false, updatedAt: new Date() }).where(ne(semesters.id, input.id));
      await tx.update(semesters).set({ isActive: true, updatedAt: new Date() }).where(eq(semesters.id, input.id));
    });
    return { success: true };
  }),
  delete: roleProcedure('Laboran').input(z.object({ id: z.number().int().positive() })).mutation(async ({ input }) => {
    const [semester] = await getDb().select({ id: semesters.id, isActive: semesters.isActive }).from(semesters).where(eq(semesters.id, input.id)).limit(1);
    if (!semester) throw new TRPCError({ code: 'NOT_FOUND', message: 'Semester tidak ditemukan.' });
    if (semester.isActive) throw new TRPCError({ code: 'BAD_REQUEST', message: 'Aktifkan semester lain sebelum menghapus semester ini.' });
    await getDb().delete(semesters).where(and(eq(semesters.id, input.id), eq(semesters.isActive, false)));
    return { success: true };
  }),
});
