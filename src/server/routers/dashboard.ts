import { and, count, desc, eq, or, sql } from 'drizzle-orm';
import { getDb } from '../db';
import { courses, finalTasks, meetings, semesters, submissions, users } from '../db/schema';
import { protectedProcedure, router } from '../trpc/init';
import { canViewEnrollmentCode } from '@/lib/course-permissions';

export const dashboardRouter = router({
  summary: protectedProcedure.query(async ({ ctx }) => {
    const db = getDb();
    const user = ctx.user;
    const [activeSemester] = await db.select({ id: semesters.id, name: semesters.name })
      .from(semesters).where(eq(semesters.isActive, true)).limit(1);

    let courseFilter = eq(courses.isArchived, false);
    if (user.role === 'Dosen') courseFilter = and(courseFilter, eq(courses.dosenId, user.id))!;
    if (user.role === 'Aslab') courseFilter = and(courseFilter, eq(courses.aslabId, user.id))!;
    if (user.role === 'Laboran') courseFilter = and(courseFilter, eq(courses.laboranId, user.id))!;
    if (activeSemester) courseFilter = and(courseFilter, eq(courses.semesterId, activeSemester.id))!;
    else courseFilter = and(courseFilter, sql`false`)!;
    if (user.role === 'Mahasiswa') courseFilter = and(courseFilter,
      sql`EXISTS (SELECT 1 FROM course_user cu WHERE cu.course_id = ${courses.id} AND cu.user_id = ${user.id})`) as typeof courseFilter;

    const submissionsQuery = db.select({ value: count() }).from(submissions)
      .leftJoin(meetings, eq(submissions.meetingId, meetings.id))
      .leftJoin(finalTasks, eq(submissions.finalTaskId, finalTasks.id))
      .leftJoin(courses, or(eq(meetings.courseId, courses.id), eq(finalTasks.courseId, courses.id)));
    const pendingCountPromise = (async () => {
      if (user.role === 'Mahasiswa') {
        const [row] = await submissionsQuery.where(eq(submissions.studentId, user.id));
        return row?.value ?? 0;
      }
      const roleStatus = user.role === 'Dosen' ? eq(submissions.dosenStatus, 'Pending') :
        user.role === 'Aslab' ? eq(submissions.aslabStatus, 'Pending') : eq(submissions.laboranStatus, 'Pending');
      let assignment = user.role === 'Dosen' ? eq(courses.dosenId, user.id) : user.role === 'Aslab' ? eq(courses.aslabId, user.id) : eq(courses.laboranId, user.id);
      if (activeSemester) assignment = and(assignment, eq(courses.semesterId, activeSemester.id))!;
      const [row] = await submissionsQuery.where(and(eq(submissions.isCompleted, false), roleStatus, assignment));
      return row?.value ?? 0;
    })();

    const pendingAccountsPromise = user.role === 'Aslab' || user.role === 'Laboran'
      ? db.select({ value: count() }).from(users).where(and(eq(users.role, 'Mahasiswa'), sql`${users.approvedAt} IS NULL`))
          .then(([row]) => row?.value ?? 0)
      : Promise.resolve(0);

    if (user.role === 'Dosen') {
      const [pendingCount, pendingAccounts, lecturerCourses, activeLecturerCount, lecturerModuleCount] = await Promise.all([
        pendingCountPromise,
        pendingAccountsPromise,
        db.select({
          id: courses.id,
          slug: courses.slug,
          name: courses.courseName,
          group: courses.classGroup,
          semesterName: semesters.name,
          isArchived: sql<boolean>`${courses.isArchived} OR NOT ${semesters.isActive}`,
          studentCount: sql<number>`(SELECT count(*)::int FROM course_user cu WHERE cu.course_id = ${courses.id})`,
          moduleCount: sql<number>`(SELECT count(*)::int FROM meetings m WHERE m.course_id = ${courses.id})`,
          dosenName: sql<string | null>`(SELECT u.name FROM users u WHERE u.id = ${courses.dosenId} LIMIT 1)`,
          aslabName: sql<string | null>`(SELECT u.name FROM users u WHERE u.id = ${courses.aslabId} LIMIT 1)`,
          laboranName: sql<string | null>`(SELECT u.name FROM users u WHERE u.id = ${courses.laboranId} LIMIT 1)`,
        })
          .from(courses)
          .innerJoin(semesters, eq(courses.semesterId, semesters.id))
          .where(eq(courses.dosenId, user.id))
          .orderBy(desc(courses.createdAt)),
        db.select({ value: count() }).from(courses)
          .innerJoin(semesters, eq(courses.semesterId, semesters.id))
          .where(and(eq(courses.dosenId, user.id), eq(courses.isArchived, false), eq(semesters.isActive, true))),
        db.select({ value: count() }).from(meetings)
          .innerJoin(courses, eq(meetings.courseId, courses.id))
          .where(eq(courses.dosenId, user.id)),
      ]);

      return {
        activeSemester: activeSemester ?? null,
        courseCount: lecturerCourses.length,
        activeCourseCount: activeLecturerCount[0]?.value ?? 0,
        moduleCount: lecturerModuleCount[0]?.value ?? 0,
        pendingCount,
        pendingAccounts,
        courses: lecturerCourses,
      };
    }

    const [courseCounts, moduleCounts, pendingCount, dashboardCourses, pendingAccounts] = await Promise.all([
      db.select({ value: count() }).from(courses).where(courseFilter),
      db.select({ value: count() }).from(meetings).innerJoin(courses, eq(meetings.courseId, courses.id)).where(courseFilter),
      pendingCountPromise,
      db.select({ id: courses.id, slug: courses.slug, name: courses.courseName, group: courses.classGroup,
      semesterName: semesters.name, isArchived: sql<boolean>`${courses.isArchived} OR NOT ${semesters.isActive}`,
      studentCount: sql<number>`(SELECT count(*)::int FROM course_user cu WHERE cu.course_id = ${courses.id})`,
      moduleCount: sql<number>`(SELECT count(*)::int FROM meetings m WHERE m.course_id = ${courses.id})`,
      enrollmentCode: canViewEnrollmentCode(user.role) ? courses.enrollmentCode : sql<string | null>`NULL`,
      dosenName: sql<string | null>`(SELECT u.name FROM users u WHERE u.id = ${courses.dosenId} LIMIT 1)`,
      aslabName: sql<string | null>`(SELECT u.name FROM users u WHERE u.id = ${courses.aslabId} LIMIT 1)`,
      laboranName: sql<string | null>`(SELECT u.name FROM users u WHERE u.id = ${courses.laboranId} LIMIT 1)` })
        .from(courses).innerJoin(semesters, eq(courses.semesterId, semesters.id)).where(courseFilter).orderBy(desc(courses.createdAt)).limit(6),
      pendingAccountsPromise,
    ]);
    const courseCount = courseCounts[0]?.value ?? 0;
    const moduleCount = moduleCounts[0]?.value ?? 0;

    return { activeSemester: activeSemester ?? null, courseCount, moduleCount, pendingCount, pendingAccounts, courses: dashboardCourses };
  }),
});
