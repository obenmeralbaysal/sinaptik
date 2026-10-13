"use client";

import { Sparkles } from "lucide-react";
import { useParams } from "next/navigation";
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
  useAiSettings,
  useGenerateRoutine,
  useRoutines,
  useSocialAccounts,
  useStoreRoutine,
} from "@/lib/hooks";
import { LANGUAGES, PERSONAS, TONES, modelsFor, presetLabel } from "@/lib/presets";

export default function RoutinesPage() {
  const params = useParams<{ slug: string }>();
  const { data: routines = [] } = useRoutines();
  const { data: accounts = [] } = useSocialAccounts();
  const { data: ai } = useAiSettings(params.slug);
  const generate = useGenerateRoutine();
  const store = useStoreRoutine();
  const textModels = modelsFor(ai?.driver, "text");

  const [name, setName] = useState("");
  const [accountId, setAccountId] = useState("");
  const [topic, setTopic] = useState("");
  const [persona, setPersona] = useState("creator");
  const [tone, setTone] = useState("supportive");
  const [language, setLanguage] = useState("en");
  const [model, setModel] = useState("");
  const [delivery, setDelivery] = useState<"draft" | "publish">("draft");
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
              <Select value={persona} onValueChange={setPersona}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {PERSONAS.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Tone</Label>
              <Select value={tone} onValueChange={setTone}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {TONES.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Language</Label>
              <Select value={language} onValueChange={setLanguage}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {LANGUAGES.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Text model</Label>
              <Select value={model || "default"} onValueChange={(value) => setModel(value === "default" ? "" : value)}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="default">Workspace default</SelectItem>
                  {textModels.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Save posts as</Label>
              <Select value={delivery} onValueChange={(value) => setDelivery(value as "draft" | "publish")}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="draft">Drafts</SelectItem>
                  <SelectItem value="publish">Publish now</SelectItem>
                </SelectContent>
              </Select>
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
                  { topic, persona, tone, language, count, model: model || undefined },
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
                    ai_model: model || null,
                    delivery,
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
                {[
                  `${r.daily_count}/day`,
                  presetLabel(PERSONAS, r.persona),
                  presetLabel(TONES, r.tone),
                  presetLabel(LANGUAGES, r.language),
                ]
                  .filter(Boolean)
                  .join(" · ")}
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </div>
  );
}
