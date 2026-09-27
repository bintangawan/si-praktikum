import { eq, or } from 'drizzle-orm';
import { getCurrentUser } from './auth/session';
import { canViewCourse, getCourseBySlug } from './auth/access';
import { getDb } from './db';
import { courses, finalTasks, meetings, submissionHistories, submissions } from './db/schema';
import { downloadSubmissionFile } from './storage';

function attachmentName(value: string | null) {
  const cleaned = (value ?? '').replace(/[^A-Za-z0-9._ -]/g, '_').trim();
  return cleaned || 'laporan-praktikum.pdf';
}

export async function serveSubmissionFile(kind: 'submission' | 'history', id: number) {
  const user = await getCurrentUser();
  if (!user) return new Response('Unauthorized', { status: 401 });

  const db = getDb();
  const row = kind === 'history'
    ? (await db.select({ filePath: submissionHistories.filePath, originalFilename: submissionHistories.originalFilename,
      studentId: submissions.studentId, courseSlug: courses.slug }).from(submissionHistories)
      .innerJoin(submissions, eq(submissionHistories.submissionId, submissions.id))
      .leftJoin(meetings, eq(submissions.meetingId, meetings.id)).leftJoin(finalTasks, eq(submissions.finalTaskId, finalTasks.id))
      .innerJoin(courses, or(eq(meetings.courseId, courses.id), eq(finalTasks.courseId, courses.id)))
      .where(eq(submissionHistories.id, id)).limit(1))[0]
    : (await db.select({ filePath: submissions.filePath, originalFilename: submissions.originalFilename,
      studentId: submissions.studentId, courseSlug: courses.slug }).from(submissions)
      .leftJoin(meetings, eq(submissions.meetingId, meetings.id)).leftJoin(finalTasks, eq(submissions.finalTaskId, finalTasks.id))
      .innerJoin(courses, or(eq(meetings.courseId, courses.id), eq(finalTasks.courseId, courses.id)))
      .where(eq(submissions.id, id)).limit(1))[0];

  if (!row) return new Response('Not found', { status: 404 });
  const course = await getCourseBySlug(row.courseSlug);
  const canView = await canViewCourse(user, course);
  const ownsSubmission = row.studentId === user.id && user.role === 'Mahasiswa' && canView;
  if (!ownsSubmission && !canView) return new Response('Forbidden', { status: 403 });
  if (!row.filePath) return new Response('Not found', { status: 404 });

  const file = await downloadSubmissionFile(row.filePath).catch(() => null);
  if (!file) return new Response('Not found', { status: 404 });
  const filename = encodeURIComponent(attachmentName(row.originalFilename));
  return new Response(await file.arrayBuffer(), { headers: {
    'Content-Type': 'application/pdf',
    'Content-Disposition': `inline; filename="laporan-praktikum.pdf"; filename*=UTF-8''${filename}`,
    'X-Content-Type-Options': 'nosniff',
    'Cache-Control': 'private, no-store',
  } });
}
