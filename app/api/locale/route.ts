import { NextResponse } from 'next/server';
import { getEnv } from '@/server/env';

export async function POST(request: Request) {
  const body: unknown = await request.json().catch(() => null);
  const locale = typeof body === 'object' && body !== null && 'locale' in body ? body.locale : null;
  if (locale !== 'id' && locale !== 'en') return NextResponse.json({ error: 'Locale tidak didukung.' }, { status: 400 });
  const response = NextResponse.json({ success: true });
  const env = getEnv();
  response.cookies.set(env.NEXT_PUBLIC_LOCALE_COOKIE_NAME, locale, { httpOnly: false, sameSite: 'lax', path: '/', maxAge: env.LOCALE_COOKIE_TTL_DAYS * 60 * 60 * 24 });
  return response;
}
