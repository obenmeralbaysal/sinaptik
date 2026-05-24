"use client";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { usePosts, useSocialAccounts, useTargets } from "@/lib/hooks";

export default function Dashboard() {
  const { data: accounts = [] } = useSocialAccounts();
  const { data: posts = [] } = usePosts();
  const { data: targets = [] } = useTargets();

  const pending = posts.filter((p) => p.status === "pending").length;
  const posted = posts.filter((p) => p.status === "posted").length;
  const failed = posts.filter((p) => p.status === "failed").length;

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Dashboard</h1>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader>
            <CardDescription>X Accounts</CardDescription>
            <CardTitle className="text-3xl">{accounts.length}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Active Targets</CardDescription>
            <CardTitle className="text-3xl">{targets.filter((t) => t.is_active).length}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Pending Posts</CardDescription>
            <CardTitle className="text-3xl">{pending}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Posted</CardDescription>
            <CardTitle className="text-3xl">{posted}</CardTitle>
          </CardHeader>
        </Card>
      </div>

      {failed > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Attention needed</CardTitle>
            <CardDescription>{failed} posts failed to send. Check the Posts page.</CardDescription>
          </CardHeader>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Get started</CardTitle>
          <CardDescription>Quick steps to be operational.</CardDescription>
        </CardHeader>
        <CardContent>
          <ol className="list-decimal pl-6 space-y-2 text-sm">
            <li>Connect an X account on the Accounts page.</li>
            <li>Add Targets you want to monitor.</li>
            <li>Create Posts manually, or use Routines to batch-generate with AI.</li>
            <li>Optional: pull values from XML/RSS feeds via XML Sources.</li>
          </ol>
        </CardContent>
      </Card>
    </div>
  );
}
