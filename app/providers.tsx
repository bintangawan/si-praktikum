'use client';

import { useState, type ReactNode } from 'react';
import { MutationCache, QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { httpBatchLink, loggerLink } from '@trpc/client';
import superjson from 'superjson';
import { trpc } from '@/trpc/react';
import { localeCookieName } from '@/lib/app-config';
import { getCookieLocale } from '@/lib/locale';
import { getCrudAlertCopy, getMutationProcedure } from '@/lib/crud-alerts';
import { showAppAlert } from '@/lib/alerts';

export function Providers({ children }: { children: ReactNode }) {
  const [queryClient] = useState(() => new QueryClient({
    mutationCache: new MutationCache({
      onSuccess: (_data, _variables, _context, mutation) => {
        const locale = getCookieLocale(localeCookieName);
        const copy = getCrudAlertCopy(getMutationProcedure(mutation.options.mutationKey), locale);
        if (copy) void showAppAlert('success', copy.title, copy.text, locale);
      },
      onError: (error, _variables, _context, mutation) => {
        const locale = getCookieLocale(localeCookieName);
        const copy = getCrudAlertCopy(getMutationProcedure(mutation.options.mutationKey), locale);
        if (copy) void showAppAlert('error', locale === 'en' ? 'Action failed' : 'Aksi gagal', error instanceof Error ? error.message : copy.text, locale);
      },
    }),
    defaultOptions: {
      queries: { staleTime: 30_000, refetchOnWindowFocus: false, retry: 1 },
      mutations: { retry: 0 },
    },
  }));
  const [trpcClient] = useState(() => trpc.createClient({
    links: [
      loggerLink({
        enabled: (operation) => {
          if (process.env.NODE_ENV !== 'development' || operation.direction !== 'down' || !(operation.result instanceof Error)) return false;
          // Auth mutations can contain passwords and identifiers in their input.
          const path = 'path' in operation && typeof operation.path === 'string' ? operation.path : '';
          return !path.startsWith('auth.');
        },
      }),
      httpBatchLink({ url: '/api/trpc', transformer: superjson }),
    ],
  }));

  return (
    <trpc.Provider client={trpcClient} queryClient={queryClient}>
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    </trpc.Provider>
  );
}
