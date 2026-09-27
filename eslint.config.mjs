import { defineConfig, globalIgnores } from 'eslint/config';
import nextVitals from 'eslint-config-next/core-web-vitals';
import nextTs from 'eslint-config-next/typescript';

export default defineConfig([
  ...nextVitals,
  ...nextTs,
  globalIgnores([
    '.next/**',
    'out/**',
    'build/**',
    'next-env.d.ts',
    'legacy-laravel/**',
    'bootstrap/**',
    'config/**',
    'database/**',
    'lang/**',
    'resources/**',
    'routes/**',
    'storage/**',
    'tests/**',
    'drizzle/meta/**',
  ]),
]);
