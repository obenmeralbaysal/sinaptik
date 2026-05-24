import Link from "next/link";
import { Button } from "@/components/ui/button";

export default function Home() {
  return (
    <main className="min-h-screen flex flex-col">
      <header className="flex items-center justify-between px-8 py-5 border-b">
        <Link href="/" className="text-lg font-semibold">
          Sinapp Social
        </Link>
        <div className="flex gap-2">
          <Button variant="ghost" asChild>
            <Link href="/login">Login</Link>
          </Button>
          <Button asChild>
            <Link href="/register">Get started</Link>
          </Button>
        </div>
      </header>

      <section className="flex-1 flex flex-col items-center justify-center px-6 text-center">
        <h1 className="text-5xl font-bold tracking-tight max-w-3xl">
          Automate your X presence with AI-powered posts and replies.
        </h1>
        <p className="mt-6 text-lg text-muted-foreground max-w-2xl">
          Schedule, generate, and engage. Sinapp Social brings posting, target replies,
          and dynamic content together in one calm dashboard.
        </p>
        <div className="mt-10 flex gap-3">
          <Button size="lg" asChild>
            <Link href="/register">Start free</Link>
          </Button>
          <Button size="lg" variant="outline" asChild>
            <Link href="/login">I have an account</Link>
          </Button>
        </div>
      </section>

      <footer className="border-t px-8 py-5 text-sm text-muted-foreground flex justify-between">
        <span>© {new Date().getFullYear()} Sinapp Social</span>
        <div className="flex gap-4">
          <Link href="/privacy">Privacy</Link>
          <Link href="/terms">Terms</Link>
        </div>
      </footer>
    </main>
  );
}
