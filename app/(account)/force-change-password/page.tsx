import { redirect } from 'next/navigation';
import { FirstLoginPasswordForm } from '@/components/AuthForms';
import { getCurrentUser } from '@/server/auth/session';
import { Providers } from '../../providers';

export default async function ForceChangePasswordPage() {
  const user = await getCurrentUser();
  if (!user) redirect('/login');
  if (!user.approvedAt) redirect('/account/pending');
  if (!user.isFirstLogin) redirect('/dashboard');
  return <Providers><FirstLoginPasswordForm /></Providers>;
}
