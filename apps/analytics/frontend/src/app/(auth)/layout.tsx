import Link from "next/link";
import { Logo } from "@/components/chrome";

export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen grid grid-cols-1 lg:grid-cols-2">
      <div className="hidden lg:flex flex-col justify-between bg-navy p-10 text-canvas">
        <Link href="/" aria-label="Sinaptik Analytics">
          <Logo tone="on-navy" />
        </Link>
        <blockquote className="text-2xl font-medium leading-snug">
          Arama performansı, site envanteri ve abonelik tek çatı altında.
        </blockquote>
      </div>
      <div className="flex items-center justify-center p-6">
        <div className="w-full max-w-md">{children}</div>
      </div>
    </div>
  );
}
