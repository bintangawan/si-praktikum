import 'server-only';
import { z } from 'zod';

const optionalUrl = z.preprocess(
  (value) => value === '' ? undefined : value,
  z.string().url().refine((value) => new URL(value).protocol === 'https:').optional(),
);
const optionalSecretKey = z.preprocess(
  (value) => value === '' ? undefined : value,
  z.string().trim().startsWith('sb_secret_').optional(),
);
const envSchema = z.object({
  NODE_ENV: z.enum(['development', 'test', 'production']),
  NEXT_PUBLIC_APP_NAME: z.string().trim().min(1),
  APP_DESCRIPTION: z.string().trim().min(1),
  APP_URL: z.string().url(),
  DATABASE_URL: z.string().trim().min(1).refine((value) => {
    try {
      return ['postgres:', 'postgresql:'].includes(new URL(value).protocol);
    } catch {
      return false;
    }
  }, 'must be a PostgreSQL connection URL'),
  DATABASE_MIGRATION_URL: z.preprocess(
    (value) => value === '' ? undefined : value,
    z.string().trim().min(1).optional().refine((value) => {
      if (!value) return true;
      try {
        return ['postgres:', 'postgresql:'].includes(new URL(value).protocol);
      } catch {
        return false;
      }
    }, 'must be a PostgreSQL connection URL'),
  ),
  DB_POOL_MAX: z.coerce.number().int().positive(),
  DB_CONNECT_TIMEOUT: z.coerce.number().int().positive(),
  DB_IDLE_TIMEOUT: z.coerce.number().int().positive(),
  SESSION_COOKIE_NAME: z.string().trim().min(1),
  SESSION_TTL_DAYS: z.coerce.number().int().positive(),
  BCRYPT_ROUNDS: z.coerce.number().int().min(10).max(15),
  NEXT_PUBLIC_DEFAULT_LOCALE: z.enum(['id', 'en']),
  NEXT_PUBLIC_LOCALE_COOKIE_NAME: z.string().trim().min(1),
  LOCALE_COOKIE_TTL_DAYS: z.coerce.number().int().positive(),
  SUPABASE_URL: optionalUrl,
  SUPABASE_SECRET_KEY: optionalSecretKey,
  SUPABASE_STORAGE_BUCKET: z.preprocess(
    (value) => value === '' ? undefined : value,
    z.string().trim().min(1).optional(),
  ),
});

export function getEnv() {
  const parsed = envSchema.safeParse(process.env);
  if (!parsed.success) {
    const details = parsed.error.issues.map((issue) => `${issue.path.join('.')}: ${issue.message}`).join('; ');
    throw new Error(`Invalid server environment: ${details}`);
  }
  return parsed.data;
}
