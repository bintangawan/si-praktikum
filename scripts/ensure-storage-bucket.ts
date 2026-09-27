import { loadEnvConfig } from '@next/env';
import { createClient } from '@supabase/supabase-js';
import { z } from 'zod';

loadEnvConfig(process.cwd());

class SetupError extends Error {}

const envSchema = z.object({
  SUPABASE_URL: z.string().url().refine((value) => new URL(value).protocol === 'https:'),
  SUPABASE_SECRET_KEY: z.string().trim().startsWith('sb_secret_'),
  SUPABASE_STORAGE_BUCKET: z.string().trim().min(1).max(100),
});

function assertPrivateBucket(isPublic: boolean, bucketName: string) {
  if (isPublic) {
    throw new SetupError(`Bucket "${bucketName}" sudah ada sebagai public. Ubah menjadi private di Supabase Dashboard sebelum melanjutkan.`);
  }
}

async function main() {
  const parsed = envSchema.safeParse(process.env);
  if (!parsed.success) {
    throw new SetupError(`Periksa environment: ${parsed.error.issues.map((issue) => issue.path.join('.')).join(', ')}. SUPABASE_SECRET_KEY harus berupa secret key server (sb_secret_...).`);
  }

  const { SUPABASE_URL, SUPABASE_SECRET_KEY, SUPABASE_STORAGE_BUCKET } = parsed.data;
  if (process.argv.includes('--check')) {
    console.log('Environment Storage valid. Bucket belum diperiksa atau dibuat.');
    return;
  }

  const supabase = createClient(SUPABASE_URL, SUPABASE_SECRET_KEY, {
    auth: { persistSession: false, autoRefreshToken: false, detectSessionInUrl: false },
  });

  const { data: buckets, error: listError } = await supabase.storage.listBuckets();
  if (listError) {
    throw new SetupError('Tidak dapat memeriksa bucket Storage. Pastikan URL dan SUPABASE_SECRET_KEY benar serta key memiliki akses admin Storage.');
  }

  const existing = buckets.find((bucket) => bucket.id === SUPABASE_STORAGE_BUCKET || bucket.name === SUPABASE_STORAGE_BUCKET);
  if (existing) {
    assertPrivateBucket(existing.public, SUPABASE_STORAGE_BUCKET);
    console.log(`Bucket private "${SUPABASE_STORAGE_BUCKET}" sudah tersedia.`);
    return;
  }

  const { error: createError } = await supabase.storage.createBucket(SUPABASE_STORAGE_BUCKET, { public: false });
  if (createError) {
    // Another deploy/setup process may have created it after listBuckets(). Confirm before failing.
    const { data: refreshedBuckets, error: refreshError } = await supabase.storage.listBuckets();
    const createdConcurrently = !refreshError && refreshedBuckets.find(
      (bucket) => bucket.id === SUPABASE_STORAGE_BUCKET || bucket.name === SUPABASE_STORAGE_BUCKET,
    );
    if (!createdConcurrently) {
      throw new SetupError('Bucket gagal dibuat. Periksa akses Storage untuk SUPABASE_SECRET_KEY dan status layanan Supabase.');
    }
    assertPrivateBucket(createdConcurrently.public, SUPABASE_STORAGE_BUCKET);
    console.log(`Bucket private "${SUPABASE_STORAGE_BUCKET}" sudah tersedia.`);
    return;
  }

  console.log(`Bucket private "${SUPABASE_STORAGE_BUCKET}" berhasil dibuat.`);
}

main().catch((error: unknown) => {
  console.error(error instanceof SetupError ? error.message : 'Setup bucket gagal. Periksa koneksi dan konfigurasi Supabase Storage.');
  process.exitCode = 1;
});
