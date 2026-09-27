import type { Metadata, Viewport } from 'next';
import localFont from 'next/font/local';
import type { ReactNode } from 'react';
import { getEnv } from '@/server/env';
import { LocaleProvider } from '@/lib/locale-context';
import './globals.css';

const poppins = localFont({
  src: [
    { path: '../node_modules/@fontsource/poppins/files/poppins-latin-400-normal.woff2', weight: '400', style: 'normal' },
    { path: '../node_modules/@fontsource/poppins/files/poppins-latin-500-normal.woff2', weight: '500', style: 'normal' },
    { path: '../node_modules/@fontsource/poppins/files/poppins-latin-600-normal.woff2', weight: '600', style: 'normal' },
    { path: '../node_modules/@fontsource/poppins/files/poppins-latin-700-normal.woff2', weight: '700', style: 'normal' },
    { path: '../node_modules/@fontsource/poppins/files/poppins-latin-800-normal.woff2', weight: '800', style: 'normal' },
  ],
  display: 'swap',
  variable: '--font-poppins',
  preload: false,
});

const env = getEnv();
const appName = env.NEXT_PUBLIC_APP_NAME;

export const metadata: Metadata = {
  title: { default: appName, template: `%s | ${appName}` },
  description: env.APP_DESCRIPTION,
};

export const viewport: Viewport = { width: 'device-width', initialScale: 1 };

export default function RootLayout({ children }: Readonly<{ children: ReactNode }>) {
  return (
    <html lang={env.NEXT_PUBLIC_DEFAULT_LOCALE} className={poppins.variable}>
      <body className={`${poppins.className} bg-[#f6f8f7] font-sans antialiased text-gray-900`}>
        <LocaleProvider>{children}</LocaleProvider>
      </body>
    </html>
  );
}
