import type { Metadata } from 'next'
import { Inter } from 'next/font/google'
import './globals.css'

const inter = Inter({ subsets: ['latin'] })

export const metadata: Metadata = {
  title: 'Control Center CMS - Professional Content Management System',
  description: 'Powerful, developer-friendly CMS with integrated codespaces, API management, and modern development workflows.',
  keywords: 'CMS, Content Management, API Management, Development Tools, Codespaces',
  authors: [{ name: 'Control Center Team' }],
  openGraph: {
    title: 'Control Center CMS',
    description: 'Professional Content Management System for modern developers',
    type: 'website',
    url: 'https://control-center.eu',
  },
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en">
      <body className={inter.className}>{children}</body>
    </html>
  )
}
