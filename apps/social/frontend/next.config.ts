import type { NextConfig } from "next";

const apiProxyTarget =
  process.env.API_PROXY_TARGET?.trim() || "http://127.0.0.1:8011";

const nextConfig: NextConfig = {
  output: "standalone",
  async rewrites() {
    return [
      {
        source: "/api/:path*",
        destination: `${apiProxyTarget}/api/:path*`,
      },
      {
        source: "/sanctum/:path*",
        destination: `${apiProxyTarget}/sanctum/:path*`,
      },
    ];
  },
};

export default nextConfig;
