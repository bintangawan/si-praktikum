import { getCurrentUser } from '@/server/auth/session';
import { LandingPage } from '@/components/LandingPage';

export default async function WelcomePage() {
  const user = await getCurrentUser();
  const destination = user ? (!user.approvedAt ? '/account/pending' : user.isFirstLogin ? '/force-change-password' : '/dashboard') : '/login';
  return <LandingPage signedIn={Boolean(user)} destination={destination} />;
}
