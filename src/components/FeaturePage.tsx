import { notFound } from 'next/navigation';
import { FeatureContent } from './FeatureContent';
import { getCurrentUser } from '@/server/auth/session';
import { getServerCaller } from '@/server/trpc/server';

export async function FeaturePage({ segments, view, archive, studentId }: { segments: string[]; view?: string; archive?: string; studentId?: string }) {
  const user = await getCurrentUser();
  if (!user) notFound();
  const caller = await getServerCaller();
  const head = segments[0] ?? 'dashboard';
  let page = 'not-found';
  let data: unknown = null;

  if (head === 'dashboard' || segments.length === 0) { page = 'dashboard'; data = await caller.dashboard.summary(); }
  else if (head === 'profile') { page = 'profile'; data = { user }; }
  else if (head === 'courses' && segments[1] === 'create') { page = 'course-create'; data = await caller.courses.createOptions(); }
  else if (head === 'courses' && segments.length === 1) { page = 'courses'; data = await caller.courses.list({ view: view === 'all' ? 'all' : 'my_classes' }); }
  else if (head === 'courses' && segments[1] && segments[2] === 'print-card') { page = 'print-card'; data = await caller.courses.printCardData({ slug: segments[1] }); }
  else if (head === 'courses' && segments[1] && segments[2] === 'grades') { page = 'grades'; data = { slug: segments[1], course: await caller.courses.get({ slug: segments[1] }), rows: await caller.courses.grades({ slug: segments[1] }) }; }
  else if (head === 'courses' && segments[1] && segments[2] === 'students') { page = 'students'; data = { slug: segments[1], ...(await caller.courses.students({ slug: segments[1] })) }; }
  else if (head === 'courses' && segments[1] && segments[2] === 'attendance-report') {
    page = 'laprak-report';
    const [course, rows] = await Promise.all([
      caller.courses.get({ slug: segments[1] }),
      caller.courses.grades({ slug: segments[1] }),
    ]);
    data = { course, rows };
  }
  else if (head === 'courses' && segments[1] && segments[2] === 'modules' && segments[3] === 'edit') { page = 'module-editor'; data = await caller.courses.get({ slug: segments[1] }); }
  else if (head === 'courses' && segments[1] && segments[2] === 'edit') { page = 'course-edit'; data = { course: await caller.courses.get({ slug: segments[1] }), options: await caller.courses.createOptions() }; }
  else if (head === 'courses' && segments[1] && segments[2] === 'staff') { page = 'course-staff'; data = { course: await caller.courses.get({ slug: segments[1] }), options: await caller.courses.createOptions() }; }
  else if (head === 'courses' && segments[1] && segments.length === 2) { page = 'course-detail'; data = await caller.courses.get({ slug: segments[1] }); }
  else if (head === 'courses' && segments[1] && segments[2] === 'students' && segments[3]) { page = 'students'; data = { slug: segments[1], ...(await caller.courses.students({ slug: segments[1] })) }; }
  else if (head === 'arsip') { page = 'archives'; data = await caller.courses.archives(); }
  else if (head === 'tutorials') { page = 'tutorials'; data = await caller.tutorials.list({ search: '' }); }
  else if (head === 'semesters') { page = 'semesters'; data = await caller.semesters.list(); }
  else if (head === 'account-approvals') { page = 'account-approvals'; data = await caller.users.pendingApprovals(); }
  else if (head === 'users-management') { page = 'users'; data = await caller.users.list({ search: '', roles: [], limit: 25, offset: 0 }); }
  else if (head === 'import-users') { page = 'import-users'; }
  else if (head === 'my-submissions') { page = 'my-submissions'; data = await caller.submissions.mine({ archive: archive === '1' || archive === 'true' }); }
  else if (head === 'submissions' && segments[1] === 'pending') { page = 'pending-submissions'; data = await caller.submissions.pending(); }
  else if (head === 'submissions' && segments[1] && segments[2] && /^\d+$/.test(segments[2]) && Number(segments[2]) > 0 && segments[3] === 'handler' && studentId) { page = 'review'; data = await caller.submissions.reviewDetail({ courseSlug: segments[1], meetingNumber: Number(segments[2]), studentId }); }
  else if (head === 'final-submissions' && segments[1] && segments[2] === 'handler') { page = 'review'; data = await caller.submissions.reviewDetail({ id: Number(segments[1]) }); }
  else if (head === 'meetings' && segments[1] && segments[2] === 'attendance') { page = 'attendance'; data = { meetingId: Number(segments[1]), rows: await caller.attendance.byMeeting({ meetingId: Number(segments[1]) }) }; }
  else if (head === 'meetings' && segments[1] && segments[2] === 'submissions') { page = 'meeting-submissions'; data = await caller.submissions.listForMeeting({ meetingId: Number(segments[1]) }); }
  else if (head === 'mahasiswa' && segments[1] === 'meetings' && segments[2] && segments[3] === 'submission') { page = 'student-submission'; data = await caller.submissions.studentContext({ meetingId: Number(segments[2]) }); }
  else if (head === 'mahasiswa' && segments[1] === 'final-tasks' && segments[2]) { page = 'student-final-submission'; data = await caller.submissions.studentContext({ finalTaskId: Number(segments[2]) }); }
  else if (head === 'final-tasks' && segments[1]) { page = 'final-task'; data = await caller.submissions.finalTask({ finalTaskId: Number(segments[1]) }); }
  else if (head === 'force-change-password' || head === 'account') notFound();

  if (page === 'not-found') notFound();
  return <FeatureContent page={page} user={user} data={data} />;
}
