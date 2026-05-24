"use client";

import { CheckCircle2, KeyRound, XCircle } from "lucide-react";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { extractError } from "@/lib/api";
import { useMe } from "@/lib/auth";
import { useAiSettings, useUpdateAiSettings } from "@/lib/hooks";

export default function SettingsPage() {
  const { data: user } = useMe();
  const params = useParams<{ slug: string }>();
  const slug = params.slug;
  const org = user?.current_organization;

  const { data: ai } = useAiSettings(slug);
  const update = useUpdateAiSettings(slug);

  const [driver, setDriver] = useState<"gemini" | "openai" | "">("");
  const [geminiKey, setGeminiKey] = useState("");
  const [openaiKey, setOpenaiKey] = useState("");
  const [textModel, setTextModel] = useState("");
  const [imageModel, setImageModel] = useState("");

  useEffect(() => {
    if (!ai) return;
    setDriver(ai.driver ?? "");
    setTextModel(ai.default_text_model ?? "");
    setImageModel(ai.default_image_model ?? "");
  }, [ai]);

  const submit = () => {
    update.mutate(
      {
        driver: (driver || null) as "gemini" | "openai" | null,
        gemini_api_key: geminiKey || undefined,
        openai_api_key: openaiKey || undefined,
        default_text_model: textModel || null,
        default_image_model: imageModel || null,
      },
      {
        onSuccess: () => {
          toast.success("AI settings saved.");
          setGeminiKey("");
          setOpenaiKey("");
        },
        onError: (err) => toast.error(extractError(err)),
      },
    );
  };

  const clearKey = (provider: "gemini" | "openai") => {
    update.mutate(
      provider === "gemini" ? { clear_gemini_key: true } : { clear_openai_key: true },
      {
        onSuccess: () => toast.success("Key removed."),
        onError: (err) => toast.error(extractError(err)),
      },
    );
  };

  return (
    <div className="space-y-6 max-w-3xl">
      <h1 className="text-2xl font-semibold">Settings</h1>

      <Card>
        <CardHeader>
          <CardTitle>Workspace</CardTitle>
          <CardDescription>{org?.name}</CardDescription>
        </CardHeader>
        <CardContent className="text-sm space-y-1">
          <div>
            <span className="text-muted-foreground">Slug:</span> /t/{org?.slug}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <KeyRound className="h-4 w-4" /> AI Settings
          </CardTitle>
          <CardDescription>
            Bring your own AI API key. Keys are encrypted at rest and never returned by the API.
            Leave a field blank to keep the existing value.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-5">
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>Active provider</Label>
              <Select value={driver || "auto"} onValueChange={(v) => setDriver(v === "auto" ? "" : (v as "gemini" | "openai"))}>
                <SelectTrigger>
                  <SelectValue placeholder="Auto" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="auto">Auto (use whichever key is set)</SelectItem>
                  <SelectItem value="gemini">Gemini</SelectItem>
                  <SelectItem value="openai">OpenAI</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div />
            <div className="space-y-2">
              <Label>Default text model</Label>
              <Input
                value={textModel}
                onChange={(e) => setTextModel(e.target.value)}
                placeholder={driver === "openai" ? "gpt-4o-mini" : "gemini-1.5-flash"}
              />
            </div>
            <div className="space-y-2">
              <Label>Default image model</Label>
              <Input
                value={imageModel}
                onChange={(e) => setImageModel(e.target.value)}
                placeholder={driver === "openai" ? "dall-e-3" : "imagen-3.0-generate-001"}
              />
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label>Gemini API key</Label>
                {ai?.has_gemini_key ? (
                  <Badge variant="success">
                    <CheckCircle2 className="h-3 w-3 mr-1" /> set
                  </Badge>
                ) : (
                  <Badge variant="secondary">
                    <XCircle className="h-3 w-3 mr-1" /> empty
                  </Badge>
                )}
              </div>
              <Input
                type="password"
                placeholder={ai?.has_gemini_key ? "•••••• (leave blank to keep)" : "AIza..."}
                value={geminiKey}
                onChange={(e) => setGeminiKey(e.target.value)}
                autoComplete="off"
              />
              {ai?.has_gemini_key && (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => clearKey("gemini")}
                  disabled={update.isPending}
                >
                  Remove key
                </Button>
              )}
            </div>

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label>OpenAI API key</Label>
                {ai?.has_openai_key ? (
                  <Badge variant="success">
                    <CheckCircle2 className="h-3 w-3 mr-1" /> set
                  </Badge>
                ) : (
                  <Badge variant="secondary">
                    <XCircle className="h-3 w-3 mr-1" /> empty
                  </Badge>
                )}
              </div>
              <Input
                type="password"
                placeholder={ai?.has_openai_key ? "•••••• (leave blank to keep)" : "sk-..."}
                value={openaiKey}
                onChange={(e) => setOpenaiKey(e.target.value)}
                autoComplete="off"
              />
              {ai?.has_openai_key && (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => clearKey("openai")}
                  disabled={update.isPending}
                >
                  Remove key
                </Button>
              )}
            </div>
          </div>

          <div className="flex justify-end">
            <Button onClick={submit} disabled={update.isPending}>
              {update.isPending ? "Saving..." : "Save AI settings"}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Account</CardTitle>
        </CardHeader>
        <CardContent className="text-sm space-y-1">
          <div>{user?.name}</div>
          <div className="text-muted-foreground">{user?.email}</div>
        </CardContent>
      </Card>
    </div>
  );
}
