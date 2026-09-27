import { randomUUID } from 'node:crypto';
import { NextResponse } from 'next/server';
import { eq } from 'drizzle-orm';
import { getCurrentUser } from '@/server/auth/session';
import { getDb } from '@/server/db';
import { users } from '@/server/db/schema';
import { deleteStorageFile, getSupabaseStorage } from '@/server/storage';

export const runtime = 'nodejs';

const mimeExtensions: Record<string, string> = {
  'image/jpeg': 'jpg',
  'image/png': 'png',
  'image/webp': 'webp',
};

export async function GET() {
  const user = await getCurrentUser();
  if (!user?.avatar) return new NextResponse(null, { status: user ? 404 : 401 });
  try {
    const { data, error } = await getSupabaseStorage().download(user.avatar);
    if (error || !data) return new NextResponse(null, { status: 404 });
    return new NextResponse(data, {
      headers: {
        'Content-Type': data.type || 'application/octet-stream',
        'Cache-Control': 'private, max-age=300',
        'X-Content-Type-Options': 'nosniff',
      },
    });
  } catch {
    return new NextResponse(null, { status: 404 });
  }
}

export async function POST(request: Request) {
  const user = await getCurrentUser();
  if (!user) return NextResponse.json({ error: 'Silakan masuk kembali.' }, { status: 401 });
  const origin = request.headers.get('origin');
  if (origin && origin !== new URL(request.url).origin) {
    return NextResponse.json({ error: 'Permintaan tidak valid.' }, { status: 403 });
  }
  const form = await request.formData();
  const file = form.get('avatar');
  if (!(file instanceof File) || !mimeExtensions[file.type] || file.size > 2 * 1024 * 1024) {
    return NextResponse.json({ error: 'Pilih gambar PNG, JPEG, atau WebP hingga 2 MB.' }, { status: 400 });
  }
  const path = `${user.id}/${randomUUID()}.${mimeExtensions[file.type]}`;
  const { error } = await getSupabaseStorage().upload(path, file, { contentType: file.type, upsert: false });
  if (error) return NextResponse.json({ error: 'Avatar gagal disimpan ke Supabase Storage.' }, { status: 502 });
  await getDb().update(users).set({ avatar: path, updatedAt: new Date() }).where(eq(users.id, user.id));
  if (user.avatar) await deleteStorageFile(user.avatar).catch(() => undefined);
  return NextResponse.json({ success: true });
}
