import { redirect } from 'next/navigation';
import { getCurrentUser } from '@/server/auth/session';
import { PendingAccountNotice } from '@/components/PendingAccountNotice';

export default async function PendingAccountPage() {
  const user = await getCurrentUser();
  if (!user) redirect('/login');
  if (user.approvedAt) redirect(user.isFirstLogin ? '/force-change-password' : '/dashboard');
  return <PendingAccountNotice />;
}
