import { router } from '../trpc/init';
import { authRouter } from './auth';
import { coursesRouter } from './courses';
import { dashboardRouter } from './dashboard';
import { attendanceRouter } from './attendance';
import { meetingsRouter } from './meetings';
import { submissionsRouter } from './submissions';
import { semestersRouter } from './semesters';
import { tutorialsRouter } from './tutorials';
import { usersRouter } from './users';

export const appRouter = router({
  auth: authRouter,
  attendance: attendanceRouter,
  courses: coursesRouter,
  dashboard: dashboardRouter,
  meetings: meetingsRouter,
  semesters: semestersRouter,
  submissions: submissionsRouter,
  tutorials: tutorialsRouter,
  users: usersRouter,
});

export type AppRouter = typeof appRouter;
