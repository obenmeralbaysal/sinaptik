import Link from "next/link";
import { Button } from "@/components/ui/button";

export default function HomePage() {
  return (
    <main className="min-h-screen flex flex-col">
      <header className="flex items-center justify-between px-8 py-5 border-b">
        <Link href="/" className="text-lg font-semibold">
          Sinaptik Analytics
        </Link>
        <div className="flex gap-2">
          <Button variant="ghost" asChild>
            <Link href="/pricing">Fiyatlandırma</Link>
          </Button>
          <Button variant="ghost" asChild>
            <Link href="/login">Giriş</Link>
          </Button>
          <Button asChild>
            <Link href="/register">Hesap aç</Link>
          </Button>
        </div>
      </header>
      <section className="flex-1 flex flex-col items-center justify-center px-6 text-center">
        <h1 className="text-5xl font-bold tracking-tight max-w-3xl">
          Sitelerinizin arama ve yapay zeka görünürlüğünü tek panelde izleyin.
        </h1>
        <p className="mt-6 text-lg text-muted-foreground max-w-2xl">
          Sinaptik Analytics, Search Console ve GA4 verisini çalışma alanınıza bağlamak için hazırlanmış bir iskelettir.
        </p>
        <div className="mt-10 flex gap-3">
          <Button size="lg" asChild>
            <Link href="/register">Başla</Link>
          </Button>
          <Button size="lg" variant="outline" asChild>
            <Link href="/pricing">Planları gör</Link>
          </Button>
        </div>
      </section>
    </main>
  );
}
