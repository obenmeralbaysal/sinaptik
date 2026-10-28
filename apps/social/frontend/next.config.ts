import type { NextConfig } from "next";

const api = process.env.API_INTERNAL_URL || "http://127.0.0.1:8000";

const nextConfig: NextConfig = {
  output: "standalone",
  async rewrites() {
    return [
      { source: "/api/:path*", destination: `${api}/api/:path*` },
      { source: "/sanctum/:path*", destination: `${api}/sanctum/:path*` },
      { source: "/admin/:path*", destination: `${api}/admin/:path*` },
      { source: "/livewire/:path*", destination: `${api}/livewire/:path*` },
      { source: "/horizon/:path*", destination: `${api}/horizon/:path*` },
      { source: "/mcp", destination: `${api}/mcp` },
      { source: "/up", destination: `${api}/up` },
    ];
  },
};

export default nextConfig;
