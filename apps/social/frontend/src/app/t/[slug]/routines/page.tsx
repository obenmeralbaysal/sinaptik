"use client";

import { Sparkles } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { extractError } from "@/lib/api";
import {
  useGenerateRoutine,
  useRoutines,
  useSocialAccounts,
  useStoreRoutine,
} from "@/lib/hooks";

export default function RoutinesPage() {
  const { data: routines = [] } = useRoutines();
  const { data: accounts = [] } = useSocialAccounts();
  const generate = useGenerateRoutine();
  const store = useStoreRoutine();

  const [name, setName] = useState("");
  const [accountId, setAccountId] = useState("");
  const [topic, setTopic] = useState("");
  const [persona, setPersona] = useState("");
  const [tone, setTone] = useState("supportive");
  const [language, setLanguage] = useState("en");
  const [count, setCount] = useState(5);
  const [previews, setPreviews] = useState<string[]>([]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold">Routines</h1>
        <p className="text-sm text-muted-foreground">
          Batch-generate posts with AI, preview, and save them as a recurring routine.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>New routine</CardTitle>
          <CardDescription>Configure inputs, generate previews, then save.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label>Name</Label>
              <Input value={name} onChange={(e) => setName(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>X Account</Label>
              <Select value={accountId} onValueChange={setAccountId}>
                <SelectTrigger>
                  <SelectValue placeholder="Select" />
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
              <Label>Persona</Label>
              <Input value={persona} onChange={(e) => setPersona(e.target.value)} placeholder="A friendly indie hacker" />
            </div>
            <div className="space-y-2">
              <Label>Tone</Label>
              <Input value={tone} onChange={(e) => setTone(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Language</Label>
              <Input value={language} onChange={(e) => setLanguage(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Count</Label>
              <Input type="number" min={1} max={20} value={count} onChange={(e) => setCount(Number(e.target.value))} />
            </div>
          </div>
          <div className="space-y-2">
            <Label>Topic</Label>
            <Textarea rows={3} value={topic} onChange={(e) => setTopic(e.target.value)} />
          </div>
          <div className="flex gap-2">
            <Button
              variant="outline"
              disabled={!topic || generate.isPending}
              onClick={() =>
                generate.mutate(
                  { topic, persona, tone, language, count },
                  {
                    onSuccess: (items) => setPreviews(items),
                    onError: (err) => toast.error(extractError(err)),
                  },
                )
              }
            >
              <Sparkles className="h-4 w-4 mr-1" />
              {generate.isPending ? "Generating..." : "Generate"}
            </Button>
            <Button
              disabled={!name || !accountId || previews.length === 0 || store.isPending}
              onClick={() =>
                store.mutate(
                  {
                    name,
                    social_account_id: Number(accountId),
                    persona,
                    tone,
                    language,
                    topic,
                    daily_count: count,
                    posts: previews.map((content) => ({ content })),
                  },
                  {
                    onSuccess: () => {
                      toast.success("Routine saved.");
                      setName("");
                      setTopic("");
                      setPreviews([]);
                    },
                    onError: (err) => toast.error(extractError(err)),
                  },
                )
              }
            >
              Save routine
            </Button>
          </div>

          {previews.length > 0 && (
            <div className="space-y-2 pt-2">
              <Label>Preview</Label>
              {previews.map((p, i) => (
                <div key={i} className="rounded-md border p-3 text-sm whitespace-pre-wrap">
                  {p}
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <div className="space-y-3">
        <h2 className="text-lg font-medium">Saved routines</h2>
        {routines.length === 0 && <p className="text-sm text-muted-foreground">No routines yet.</p>}
        <div className="grid gap-3 md:grid-cols-2">
          {routines.map((r) => (
            <Card key={r.id}>
              <CardHeader>
                <CardTitle className="text-base">{r.name}</CardTitle>
                <CardDescription>{r.topic}</CardDescription>
              </CardHeader>
              <CardContent className="text-xs text-muted-foreground">
                {r.daily_count}/day · tone {r.tone} · lang {r.language}
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </div>
  );
}
