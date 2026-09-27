import { appRouter } from '../routers';
import { createTRPCContext } from './context';

export const getServerCaller = async () => appRouter.createCaller(await createTRPCContext());
