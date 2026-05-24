"use client";

import { Trash2 } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { extractError } from "@/lib/api";
import { useCreateXmlSource, useDeleteXmlSource, useXmlSources } from "@/lib/hooks";
import { formatDate } from "@/lib/utils";

export default function XmlSourcesPage() {
  const { data: sources = [] } = useXmlSources();
  const create = useCreateXmlSource();
  const remove = useDeleteXmlSource();

  const [name, setName] = useState("");
  const [url, setUrl] = useState("");
  const [selector, setSelector] = useState("");
  const [template, setTemplate] = useState("USD/TRY rate is {value}");
  const [interval, setInterval] = useState(60);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold">XML Sources</h1>
        <p className="text-sm text-muted-foreground">
          Pull values from RSS/XML feeds and turn them into scheduled posts.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Add source</CardTitle>
          <CardDescription>
            Selector can be a dot path like <code>currency/usd</code> or an XPath like <code>//item[1]/title</code>.
            Use <code>{"{value}"}</code> in the template.
          </CardDescription>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label>Name</Label>
            <Input value={name} onChange={(e) => setName(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>URL</Label>
            <Input value={url} onChange={(e) => setUrl(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Selector</Label>
            <Input value={selector} onChange={(e) => setSelector(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Interval (min)</Label>
            <Input type="number" value={interval} onChange={(e) => setInterval(Number(e.target.value))} />
          </div>
          <div className="md:col-span-2 space-y-2">
            <Label>Post template</Label>
            <Textarea rows={2} value={template} onChange={(e) => setTemplate(e.target.value)} />
          </div>
          <div className="md:col-span-2">
            <Button
              disabled={!name || !url || !selector || create.isPending}
              onClick={() =>
                create.mutate(
                  {
                    name,
                    url,
                    selector,
                    template,
                    fetch_interval_minutes: interval,
                  },
                  {
                    onSuccess: () => {
                      toast.success("Saved.");
                      setName("");
                      setUrl("");
                      setSelector("");
                    },
                    onError: (err) => toast.error(extractError(err)),
                  },
                )
              }
            >
              Add source
            </Button>
          </div>
        </CardContent>
      </Card>

      <div className="grid gap-3 md:grid-cols-2">
        {sources.map((s) => (
          <Card key={s.id}>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div>
                  <CardTitle className="text-base">{s.name}</CardTitle>
                  <CardDescription className="break-all">{s.url}</CardDescription>
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => {
                    if (confirm("Delete?")) remove.mutate(s.id);
                  }}
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            </CardHeader>
            <CardContent className="text-xs text-muted-foreground space-y-1">
              <div>Selector: <code>{s.selector}</code></div>
              <div>Every {s.fetch_interval_minutes} min</div>
              <div>Last run: {s.last_fetched_at ? formatDate(s.last_fetched_at) : "never"}</div>
            </CardContent>
          </Card>
        ))}
        {sources.length === 0 && <p className="text-sm text-muted-foreground">No sources yet.</p>}
      </div>
    </div>
  );
}
