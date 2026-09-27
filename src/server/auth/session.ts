import 'server-only';
import { createHash, randomBytes } from 'node:crypto';
import { and, eq, gt } from 'drizzle-orm';
import { cookies, headers } from 'next/headers';
import { cache } from 'react';
import { getDb } from '../db';
import { sessions, users } from '../db/schema';
import { getEnv } from '../env';

export type SessionUser = Pick<typeof users.$inferSelect, 'id' | 'name' | 'email' | 'role' | 'avatar' | 'isFirstLogin' | 'approvedAt'>;

const hash = (token: string) => createHash('sha256').update(token).digest('hex');

async function readCurrentUser(): Promise<SessionUser | null> {
  const cookieStore = await cookies();
  const cookieName = getEnv().SESSION_COOKIE_NAME;
  const token = cookieStore.get(cookieName)?.value;
  if (!token) return null;
  const [row] = await getDb().select({
    id: users.id,
    name: users.name,
    email: users.email,
    role: users.role,
    avatar: users.avatar,
    isFirstLogin: users.isFirstLogin,
    approvedAt: users.approvedAt,
  }).from(sessions)
    .innerJoin(users, eq(sessions.userId, users.id))
    .where(and(eq(sessions.tokenHash, hash(token)), gt(sessions.expiresAt, new Date())))
    .limit(1);
  return row ?? null;
}

export const getCurrentUser = cache(readCurrentUser);

export async function createSession(userId: string, remember = true) {
  const env = getEnv();
  const token = randomBytes(32).toString('base64url');
  const expiresAt = new Date(Date.now() + env.SESSION_TTL_DAYS * 24 * 60 * 60 * 1000);
  const requestHeaders = await headers();
  const forwardedFor = requestHeaders.get('x-forwarded-for');
  const ipAddress = forwardedFor?.split(',')[0]?.trim() ?? null;

  await getDb().insert(sessions).values({
    tokenHash: hash(token),
    userId,
    expiresAt,
    ipAddress,
    userAgent: requestHeaders.get('user-agent'),
  });

  const cookieStore = await cookies();
  cookieStore.set(env.SESSION_COOKIE_NAME, token, {
    httpOnly: true,
    secure: env.NODE_ENV === 'production',
    sameSite: 'lax',
    path: '/',
    ...(remember ? { expires: expiresAt } : {}),
  });
}

export async function deleteCurrentSession() {
  const env = getEnv();
  const cookieStore = await cookies();
  const token = cookieStore.get(env.SESSION_COOKIE_NAME)?.value;
  if (token) await getDb().delete(sessions).where(eq(sessions.tokenHash, hash(token)));
  cookieStore.delete(env.SESSION_COOKIE_NAME);
}
