import { initTRPC, TRPCError } from '@trpc/server';
import superjson from 'superjson';
import type { TRPCContext } from './context';
import type { UserRole } from '@/lib/roles';

const t = initTRPC.context<TRPCContext>().create({ transformer: superjson });

export const router = t.router;
export const publicProcedure = t.procedure;
export const sessionProcedure = t.procedure.use(({ ctx, next }) => {
  if (!ctx.user) throw new TRPCError({ code: 'UNAUTHORIZED', message: 'Silakan masuk terlebih dahulu.' });
  return next({ ctx: { ...ctx, user: ctx.user } });
});

export const protectedProcedure = sessionProcedure.use(({ ctx, next }) => {
  if (!ctx.user.approvedAt) {
    throw new TRPCError({ code: 'FORBIDDEN', message: 'Akun Anda belum disetujui.' });
  }
  if (ctx.user.isFirstLogin) {
    throw new TRPCError({ code: 'FORBIDDEN', message: 'Ganti password awal sebelum menggunakan aplikasi.' });
  }
  return next({ ctx });
});

export const roleProcedure = (...roles: UserRole[]) =>
  protectedProcedure.use(({ ctx, next }) => {
    if (!roles.includes(ctx.user.role)) {
      throw new TRPCError({ code: 'FORBIDDEN', message: 'Anda tidak memiliki hak akses untuk tindakan ini.' });
    }
    return next({ ctx });
  });
