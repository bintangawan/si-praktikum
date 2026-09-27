import { notFound } from 'next/navigation';
import { FeaturePage } from '@/components/FeaturePage';

export default async function DashboardPage({ params, searchParams }: {
  params: Promise<{ segments?: string[] }>;
  searchParams: Promise<{ view?: string; archive?: string; studentId?: string }>;
}) {
  const { segments = [] } = await params;
  const { view, archive, studentId } = await searchParams;
  if (!segments.length || segments[0] === 'dashboard') return <FeaturePage segments={segments} view={view} archive={archive} studentId={studentId} />;
  const known = new Set(['profile', 'courses', 'arsip', 'tutorials', 'my-submissions', 'submissions', 'submission-histories', 'final-submissions', 'final-tasks', 'meetings', 'mahasiswa', 'account-approvals', 'users-management', 'users', 'import-users', 'semesters', 'courses-staff', 'force-change-password', 'account']);
  if (!known.has(segments[0])) notFound();
  return <FeaturePage segments={segments} view={view} archive={archive} studentId={studentId} />;
}
