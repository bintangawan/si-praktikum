import 'server-only';
import { createClient } from '@supabase/supabase-js';
import { getEnv } from './env';

type SupabaseClient = ReturnType<typeof createClient>;
const globalSupabase = globalThis as typeof globalThis & {
  __siPraktikumSupabase?: { url: string; key: string; client: SupabaseClient };
};

export function getSupabaseStorage() {
  const env = getEnv();
  if (!env.SUPABASE_URL || !env.SUPABASE_SECRET_KEY || !env.SUPABASE_STORAGE_BUCKET) {
    throw new Error('Supabase Storage requires URL, secret key, and bucket environment values.');
  }
  let cached = globalSupabase.__siPraktikumSupabase;
  if (!cached || cached.url !== env.SUPABASE_URL || cached.key !== env.SUPABASE_SECRET_KEY) {
    cached = {
      url: env.SUPABASE_URL,
      key: env.SUPABASE_SECRET_KEY,
      client: createClient(env.SUPABASE_URL, env.SUPABASE_SECRET_KEY, {
        auth: { persistSession: false, autoRefreshToken: false, detectSessionInUrl: false },
      }),
    };
    globalSupabase.__siPraktikumSupabase = cached;
  }
  return cached.client.storage.from(env.SUPABASE_STORAGE_BUCKET);
}

export async function downloadSubmissionFile(path: string) {
  const { data, error } = await getSupabaseStorage().download(path);
  if (error || !data) return null;
  return data;
}

export async function deleteStorageFile(path: string) {
  return getSupabaseStorage().remove([path]);
}
