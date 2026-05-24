"use client";

import { CheckCircle2, Trash2 } from "lucide-react";
import { useSearchParams } from "next/navigation";
import { useEffect } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { extractError } from "@/lib/api";
import { useConnectX, useDeleteSocialAccount, useSocialAccounts } from "@/lib/hooks";
import { formatDate } from "@/lib/utils";

export default function AccountsPage() {
  const { data: accounts = [], isLoading } = useSocialAccounts();
  const connect = useConnectX();
  const remove = useDeleteSocialAccount();
  const search = useSearchParams();

  useEffect(() => {
    if (search.get("connected") === "1") toast.success("X account connected.");
    const xErr = search.get("x_error");
    if (xErr) toast.error(`X error: ${xErr}`);
  }, [search]);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold">X Accounts</h1>
          <p className="text-sm text-muted-foreground">
            Connect your X accounts via OAuth 2.0 (PKCE).
          </p>
        </div>
        <Button
          onClick={() =>
            connect.mutate(undefined, {
              onSuccess: (url) => {
                window.location.href = url;
              },
              onError: (err) => toast.error(extractError(err)),
            })
          }
          disabled={connect.isPending}
        >
          {connect.isPending ? "Preparing..." : "Connect X account"}
        </Button>
      </div>

      {isLoading ? (
        <p className="text-sm text-muted-foreground">Loading...</p>
      ) : accounts.length === 0 ? (
        <Card>
          <CardHeader>
            <CardTitle>No accounts yet</CardTitle>
            <CardDescription>Click &quot;Connect X account&quot; to start.</CardDescription>
          </CardHeader>
        </Card>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {accounts.map((a) => (
            <Card key={a.id}>
              <CardHeader>
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <CardTitle>@{a.username ?? "unknown"}</CardTitle>
                    <CardDescription>{a.display_name}</CardDescription>
                  </div>
                  <Badge variant={a.is_active ? "success" : "secondary"}>
                    {a.is_active ? <CheckCircle2 className="h-3 w-3 mr-1" /> : null}
                    {a.is_active ? "Active" : "Inactive"}
                  </Badge>
                </div>
              </CardHeader>
              <CardContent className="text-xs text-muted-foreground space-y-1">
                <div>Connected {formatDate(a.created_at)}</div>
                <div>Token expires {formatDate(a.expires_at)}</div>
                <div className="pt-3">
                  <Button
                    variant="destructive"
                    size="sm"
                    onClick={() => {
                      if (confirm("Disconnect this account?")) remove.mutate(a.id);
                    }}
                  >
                    <Trash2 className="h-3 w-3 mr-1" />
                    Disconnect
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
