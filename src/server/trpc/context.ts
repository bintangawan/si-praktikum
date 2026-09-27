import { getCurrentUser } from '../auth/session';

export async function createTRPCContext() {
  return { user: await getCurrentUser() };
}

export type TRPCContext = Awaited<ReturnType<typeof createTRPCContext>>;
