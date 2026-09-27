import 'server-only';
import postgres from 'postgres';
import { drizzle, type PostgresJsDatabase } from 'drizzle-orm/postgres-js';
import * as schema from './schema';
import { getEnv } from '../env';

type Database = PostgresJsDatabase<typeof schema>;
const globalConnection = globalThis as typeof globalThis & {
  __siPraktikumSql?: ReturnType<typeof postgres>;
  __siPraktikumDb?: Database;
};

export function getDb(): Database {
  if (globalConnection.__siPraktikumDb) return globalConnection.__siPraktikumDb;

  const env = getEnv();
  const client = globalConnection.__siPraktikumSql ?? postgres(env.DATABASE_URL, {
    max: env.DB_POOL_MAX,
    connect_timeout: env.DB_CONNECT_TIMEOUT,
    idle_timeout: env.DB_IDLE_TIMEOUT,
    prepare: false,
    ssl: 'require',
  });
  const db = drizzle(client, { schema });

  globalConnection.__siPraktikumSql = client;
  globalConnection.__siPraktikumDb = db;
  return db;
}
