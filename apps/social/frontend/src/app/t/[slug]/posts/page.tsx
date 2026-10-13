"use client";

import { Send, Trash2 } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { extractError } from "@/lib/api";
import { useCreatePost, useDeletePost, usePosts, useSendPostNow, useSocialAccounts } from "@/lib/hooks";
import { formatDate } from "@/lib/utils";

const STATUS_VARIANT = {
  draft: "secondary",
  pending: "warning",
  posted: "success",
  failed: "destructive",
} as const;

export default function PostsPage() {
  const { data: accounts = [] } = useSocialAccounts();
  const { data: posts = [] } = usePosts();
  const create = useCreatePost();
  const sendNow = useSendPostNow();
  const remove = useDeletePost();

  const [content, setContent] = useState("");
  const [accountId, setAccountId] = useState<string>("");
  const [delivery, setDelivery] = useState<"draft" | "publish" | "schedule">("draft");
  const [scheduledAt, setScheduledAt] = useState<string>("");

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Posts</h1>

      <Card>
        <CardHeader>
          <CardTitle>Compose</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label>X Account</Label>
              <Select value={accountId} onValueChange={setAccountId}>
                <SelectTrigger>
                  <SelectValue placeholder="Select account" />
                </SelectTrigger>
                <SelectContent>
                  {accounts.map((a) => (
                    <SelectItem key={a.id} value={String(a.id)}>
                      @{a.username}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Status</Label>
              <Select value={delivery} onValueChange={(value) => setDelivery(value as "draft" | "publish" | "schedule")}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="draft">Draft</SelectItem>
                  <SelectItem value="publish">Publish now</SelectItem>
                  <SelectItem value="schedule">Schedule</SelectItem>
                </SelectContent>
              </Select>
            </div>
            {delivery === "schedule" && (
              <div className="space-y-2 md:col-span-2">
                <Label>Publish at</Label>
                <Input
                  type="datetime-local"
                  value={scheduledAt}
                  onChange={(e) => setScheduledAt(e.target.value)}
                />
              </div>
            )}
          </div>
          <div className="space-y-2">
            <Label>Content</Label>
            <Textarea
              maxLength={280}
              rows={4}
              value={content}
              onChange={(e) => setContent(e.target.value)}
              placeholder="What's happening?"
            />
            <p className="text-xs text-muted-foreground">{content.length}/280</p>
          </div>
          <Button
            disabled={!accountId || !content || create.isPending || (delivery === "schedule" && !scheduledAt)}
            onClick={() =>
              create.mutate(
                {
                  social_account_id: Number(accountId),
                  content,
                  delivery,
                  scheduled_at: delivery === "schedule" ? scheduledAt : null,
                },
                {
                  onSuccess: () => {
                    setContent("");
                    setScheduledAt("");
                    toast.success(delivery === "publish" ? "Published." : delivery === "schedule" ? "Scheduled." : "Saved as draft.");
                  },
                  onError: (err) => toast.error(extractError(err)),
                },
              )
            }
          >
            {create.isPending
              ? "Saving..."
              : delivery === "publish"
                ? "Publish now"
                : delivery === "schedule"
                  ? "Schedule"
                  : "Save as draft"}
          </Button>
        </CardContent>
      </Card>

      <div className="space-y-3">
        <h2 className="text-lg font-medium">Queue</h2>
        {posts.length === 0 && <p className="text-sm text-muted-foreground">No posts yet.</p>}
        {posts.map((p) => (
          <Card key={p.id}>
            <CardContent className="p-4 flex items-start gap-4">
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                  <Badge variant={STATUS_VARIANT[p.status]}>{p.status}</Badge>
                  {p.social_account?.username && (
                    <span className="text-xs text-muted-foreground">@{p.social_account.username}</span>
                  )}
                  {p.scheduled_at && (
                    <span className="text-xs text-muted-foreground">scheduled {formatDate(p.scheduled_at)}</span>
                  )}
                  {p.posted_at && (
                    <span className="text-xs text-muted-foreground">posted {formatDate(p.posted_at)}</span>
                  )}
                </div>
                <p className="text-sm whitespace-pre-wrap">{p.content}</p>
                {p.error && <p className="text-xs text-destructive mt-2">{p.error}</p>}
              </div>
              <div className="flex flex-col gap-2 shrink-0">
                {p.status !== "posted" && (
                  <Button
                    size="sm"
                    onClick={() =>
                      sendNow.mutate(p.id, {
                        onSuccess: () => toast.success("Posted."),
                        onError: (err) => toast.error(extractError(err)),
                      })
                    }
                  >
                    <Send className="h-3 w-3 mr-1" />
                    Send now
                  </Button>
                )}
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={() => {
                    if (confirm("Delete this post?")) remove.mutate(p.id);
                  }}
                >
                  <Trash2 className="h-3 w-3" />
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
