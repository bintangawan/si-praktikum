import { TRPCError } from '@trpc/server';
import { desc, eq, ilike, or } from 'drizzle-orm';
import { z } from 'zod';
import { getDb } from '../db';
import { tutorials, users } from '../db/schema';
import { protectedProcedure, roleProcedure, router } from '../trpc/init';

const safeHttpUrl = z.string().url().refine((value) => ['https:', 'http:'].includes(new URL(value).protocol), 'URL harus menggunakan HTTP atau HTTPS.');

export const tutorialsRouter = router({
  list: protectedProcedure.input(z.object({ search: z.string().trim().max(100).default('') }).optional()).query(async ({ input }) => {
    const search = input?.search;
    const filters = search ? or(ilike(tutorials.title, `%${search}%`), ilike(tutorials.description, `%${search}%`)) : undefined;
    return getDb().select({ id: tutorials.id, title: tutorials.title, description: tutorials.description, type: tutorials.type,
      url: tutorials.url, createdAt: tutorials.createdAt, authorName: users.name })
      .from(tutorials).innerJoin(users, eq(tutorials.createdBy, users.id))
      .where(filters).orderBy(desc(tutorials.createdAt)).limit(100);
  }),
  create: roleProcedure('Laboran').input(z.object({ title: z.string().trim().min(2).max(255), description: z.string().max(5000).nullable(),
    type: z.enum(['youtube', 'gdrive_pdf']), url: safeHttpUrl })).mutation(async ({ input, ctx }) => {
    const [tutorial] = await getDb().insert(tutorials).values({ ...input, createdBy: ctx.user.id }).returning();
    return tutorial;
  }),
  delete: roleProcedure('Laboran').input(z.object({ id: z.number().int().positive() })).mutation(async ({ input }) => {
    const [deleted] = await getDb().delete(tutorials).where(eq(tutorials.id, input.id)).returning({ id: tutorials.id });
    if (!deleted) throw new TRPCError({ code: 'NOT_FOUND', message: 'Tutorial tidak ditemukan.' });
    return { success: true };
  }),
});
