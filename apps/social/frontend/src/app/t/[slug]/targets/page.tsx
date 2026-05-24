"use client";

import { Trash2 } from "lucide-react";
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
import { useCreateTarget, useDeleteTarget, useSocialAccounts, useTargets } from "@/lib/hooks";

const TONES = ["supportive", "ironic", "critical", "neutral", "custom"];

export default function TargetsPage() {
  const { data: targets = [] } = useTargets();
  const { data: accounts = [] } = useSocialAccounts();
  const create = useCreateTarget();
  const remove = useDeleteTarget();

  const [username, setUsername] = useState("");
  const [accountId, setAccountId] = useState<string>("");
  const [tone, setTone] = useState("supportive");
  const [customPrompt, setCustomPrompt] = useState("");
  const [dailyLimit, setDailyLimit] = useState(10);
  const [freq, setFreq] = useState(60);

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Targets</h1>

      <Card>
        <CardHeader>
          <CardTitle>Add target</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label>Username (without @)</Label>
            <Input value={username} onChange={(e) => setUsername(e.target.value)} placeholder="elonmusk" />
          </div>
          <div className="space-y-2">
            <Label>Reply from account</Label>
            <Select value={accountId} onValueChange={setAccountId}>
              <SelectTrigger>
                <SelectValue placeholder="Any active account" />
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
            <Label>AI tone</Label>
            <Select value={tone} onValueChange={setTone}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {TONES.map((t) => (
                  <SelectItem key={t} value={t}>
                    {t}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="grid grid-cols-2 gap-2">
            <div className="space-y-2">
              <Label>Daily limit</Label>
              <Input type="number" value={dailyLimit} onChange={(e) => setDailyLimit(Number(e.target.value))} />
            </div>
            <div className="space-y-2">
              <Label>Frequency (min)</Label>
              <Input type="number" value={freq} onChange={(e) => setFreq(Number(e.target.value))} />
            </div>
          </div>
          {tone === "custom" && (
            <div className="md:col-span-2 space-y-2">
              <Label>Custom prompt</Label>
              <Textarea value={customPrompt} onChange={(e) => setCustomPrompt(e.target.value)} rows={3} />
            </div>
          )}
          <div className="md:col-span-2">
            <Button
              disabled={!username || create.isPending}
              onClick={() =>
                create.mutate(
                  {
                    target_username: username,
                    social_account_id: accountId ? Number(accountId) : null,
                    ai_tone: tone,
                    custom_prompt: tone === "custom" ? customPrompt : null,
                    daily_limit: dailyLimit,
                    reply_frequency_minutes: freq,
                  },
                  {
                    onSuccess: () => {
                      setUsername("");
                      setCustomPrompt("");
                      toast.success("Target added.");
                    },
                    onError: (err) => toast.error(extractError(err)),
                  },
                )
              }
            >
              Add target
            </Button>
          </div>
        </CardContent>
      </Card>

      <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
        {targets.map((t) => (
          <Card key={t.id}>
            <CardContent className="p-4">
              <div className="flex items-start justify-between mb-2">
                <div>
                  <div className="font-semibold">@{t.target_username}</div>
                  <div className="text-xs text-muted-foreground">
                    {t.social_account?.username ? `via @${t.social_account.username}` : "any account"}
                  </div>
                </div>
                <Badge variant={t.is_active ? "success" : "secondary"}>
                  {t.is_active ? "active" : "off"}
                </Badge>
              </div>
              <div className="text-xs text-muted-foreground space-y-1">
                <div>Tone: {t.ai_tone}</div>
                <div>{t.daily_limit}/day · every {t.reply_frequency_minutes} min</div>
              </div>
              <Button
                variant="ghost"
                size="sm"
                className="mt-3 h-7"
                onClick={() => {
                  if (confirm("Remove target?")) remove.mutate(t.id);
                }}
              >
                <Trash2 className="h-3 w-3 mr-1" /> Remove
              </Button>
            </CardContent>
          </Card>
        ))}
        {targets.length === 0 && (
          <p className="text-sm text-muted-foreground">No targets yet.</p>
        )}
      </div>
    </div>
  );
}
