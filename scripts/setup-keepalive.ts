import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { loadEnvConfig } from '@next/env';
import postgres from 'postgres';
import { z } from 'zod';

loadEnvConfig(process.cwd());

class SetupError extends Error {}

async function main() {
  const parsed = z.object({
    DATABASE_URL: z.string().url(),
    DATABASE_MIGRATION_URL: z.string().url().optional().or(z.literal('')),
    DB_POOL_MAX: z.coerce.number().int().positive(),
    DB_CONNECT_TIMEOUT: z.coerce.number().int().positive(),
    DB_IDLE_TIMEOUT: z.coerce.number().int().positive(),
    SUPABASE_URL: z.string().url().refine((value) => new URL(value).protocol === 'https:'),
    SUPABASE_PUBLISHABLE_KEY: z.string().startsWith('sb_publishable_'),
    SUPABASE_KEEPALIVE_CRON_UTC: z.string().trim().regex(/^\S+(?:\s+\S+){4}$/),
  }).safeParse(process.env);
  if (!parsed.success) {
    throw new SetupError(`Periksa konfigurasi: ${parsed.error.issues.map((issue) => issue.path.join('.')).join(', ')}`);
  }
  const env = parsed.data;
  const migration = await readFile(resolve('supabase/migrations/20260925000000_daily_keepalive.sql'), 'utf8');
  if (process.argv.includes('--check')) {
    console.log('Format konfigurasi keepalive valid dan file SQL tersedia. Tidak menghubungi database atau memvalidasi SQL di server.');
    return;
  }
  const connectionUrl = env.DATABASE_MIGRATION_URL || env.DATABASE_URL;
  const password = decodeURIComponent(new URL(connectionUrl).password);
  if (!password || password.includes('YOUR-PASSWORD')) {
    throw new SetupError('Isi password database pada environment sebelum mengaktifkan keepalive.');
  }
  const sql = postgres(connectionUrl, {
    max: env.DB_POOL_MAX, prepare: false, ssl: 'require',
    connect_timeout: env.DB_CONNECT_TIMEOUT, idle_timeout: env.DB_IDLE_TIMEOUT,
  });
  try {
    await sql.begin(async (tx) => {
      const secrets = [
        ['si_praktikum_supabase_url', env.SUPABASE_URL],
        ['si_praktikum_supabase_publishable_key', env.SUPABASE_PUBLISHABLE_KEY],
        ['si_praktikum_keepalive_cron_utc', env.SUPABASE_KEEPALIVE_CRON_UTC],
      ];
      for (const [name, value] of secrets) {
        const records = await tx`select id from vault.secrets where name = ${name}`;
        if (records.length > 1) throw new SetupError(`Secret Vault duplikat: ${name}`);
        if (records.length) await tx`select vault.update_secret(${records[0].id}::uuid, ${value})`;
        else await tx`select vault.create_secret(${value}, ${name})`;
      }
      // SQL is a repository-owned migration; environment values use bound parameters above.
      await tx.unsafe(migration);
    });
    const [job] = await sql`select schedule, active from cron.job where jobname = 'si-praktikum-daily-keepalive'`;
    if (!job || !job.active || job.schedule !== env.SUPABASE_KEEPALIVE_CRON_UTC) {
      throw new SetupError('Job keepalive belum aktif dengan jadwal yang diminta.');
    }
    const [cronSettings] = await sql`select pg_catalog.current_setting('cron.timezone', true) as timezone`;
    console.log(`Job keepalive harian aktif pada jadwal ${job.schedule} (${cronSettings?.timezone ?? 'zona waktu default Supabase'}). Periksa cron.job_run_details dan public.app_heartbeats setelah jadwal berjalan.`);
  } finally {
    await sql.end();
  }
}

main().catch((error: unknown) => {
  // Driver errors may contain connection/query details; only print our configuration errors.
  const code = typeof error === 'object' && error !== null && 'code' in error
    && typeof error.code === 'string' && /^[A-Z0-9_-]{2,16}$/.test(error.code)
    ? ` (${error.code})` : '';
  const message = error instanceof SetupError
    ? error.message : `Setup gagal${code}. Periksa koneksi database, ekstensi pg_cron/pg_net, dan Supabase Vault.`;
  console.error(message);
  process.exitCode = 1;
});
