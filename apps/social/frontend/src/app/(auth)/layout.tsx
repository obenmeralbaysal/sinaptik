import Link from "next/link";

export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen grid grid-cols-1 lg:grid-cols-2">
      <div className="hidden lg:flex flex-col justify-between p-10 bg-secondary text-secondary-foreground">
        <Link href="/" className="text-lg font-semibold">
          Sinapp Social
        </Link>
        <blockquote className="text-2xl font-medium leading-snug">
          “Schedule, automate, and engage on X with AI co-pilot. One calm dashboard for your team.”
        </blockquote>
      </div>
      <div className="flex items-center justify-center p-6">
        <div className="w-full max-w-md">{children}</div>
      </div>
    </div>
  );
}
