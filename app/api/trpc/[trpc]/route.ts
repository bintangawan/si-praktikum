import { fetchRequestHandler } from '@trpc/server/adapters/fetch';
import { appRouter } from '@/server/routers';
import { createTRPCContext } from '@/server/trpc/context';

export const runtime = 'nodejs';

const handler = async (request: Request) => {
  const response = await fetchRequestHandler({
    endpoint: '/api/trpc',
    req: request,
    router: appRouter,
    createContext: createTRPCContext,
    onError({ error, path }) {
      if (process.env.NODE_ENV === 'development') console.error(`tRPC ${path ?? 'unknown'}:`, error.message);
    },
  });
  response.headers.set('Cache-Control', 'private, no-store, max-age=0');
  return response;
};

export { handler as GET, handler as POST };
