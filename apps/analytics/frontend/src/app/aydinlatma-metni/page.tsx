import Link from "next/link";

export default function AydinlatmaMetniPage() {
  return (
    <main className="mx-auto max-w-2xl px-6 py-16">
      <h1 className="text-2xl font-semibold">Aydınlatma Metni</h1>
      <p className="mt-4 text-sm text-slate">
        Bu adres bilgilendirme bağlantısının yer tutucusudur. Metin bu sürümde yayımlanmamıştır.
      </p>
      <p className="mt-6">
        <Link href="/" className="text-sm underline">
          Ana sayfa
        </Link>
      </p>
    </main>
  );
}
