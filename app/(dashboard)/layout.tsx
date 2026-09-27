import { redirect } from 'next/navigation';
import type { ReactNode } from 'react';
import { AppShell } from '@/components/AppShell';
import { getCurrentUser } from '@/server/auth/session';
import { Providers } from '../providers';

export default async function DashboardLayout({ children }: { children: ReactNode }) {
  const user = await getCurrentUser();
  if (!user) redirect('/login');
  if (!user.approvedAt) redirect('/account/pending');
  if (user.isFirstLogin) redirect('/force-change-password');
  return <Providers><AppShell user={user}>{children}</AppShell></Providers>;
}
