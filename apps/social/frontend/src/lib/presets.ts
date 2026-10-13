export type PresetOption = { value: string; label: string };

export const LANGUAGES: PresetOption[] = [
  { value: "en", label: "English" },
  { value: "tr", label: "Türkçe" },
];

export const TONES: PresetOption[] = [
  { value: "supportive", label: "Supportive" },
  { value: "ironic", label: "Ironic" },
  { value: "critical", label: "Critical" },
  { value: "neutral", label: "Neutral" },
  { value: "witty", label: "Witty" },
  { value: "professional", label: "Professional" },
  { value: "friendly", label: "Friendly" },
  { value: "bold", label: "Bold" },
  { value: "educational", label: "Educational" },
  { value: "provocative", label: "Provocative" },
  { value: "empathetic", label: "Empathetic" },
  { value: "analytical", label: "Analytical" },
  { value: "playful", label: "Playful" },
  { value: "urgent", label: "Urgent" },
  { value: "calm", label: "Calm" },
  { value: "skeptical", label: "Skeptical" },
  { value: "inspirational", label: "Inspirational" },
  { value: "conversational", label: "Conversational" },
  { value: "authoritative", label: "Authoritative" },
  { value: "humble", label: "Humble" },
];

export const PERSONAS: PresetOption[] = [
  { value: "indie-hacker", label: "Indie hacker" },
  { value: "founder", label: "Startup founder" },
  { value: "engineer", label: "Senior engineer" },
  { value: "marketer", label: "Growth marketer" },
  { value: "journalist", label: "Reporter" },
  { value: "analyst", label: "Analyst" },
  { value: "teacher", label: "Teacher" },
  { value: "creator", label: "Creator" },
  { value: "operator", label: "Operator" },
  { value: "researcher", label: "Researcher" },
  { value: "designer", label: "Product designer" },
  { value: "investor", label: "Investor" },
  { value: "community", label: "Community builder" },
  { value: "satirist", label: "Satirist" },
  { value: "mentor", label: "Mentor" },
  { value: "curator", label: "Curator" },
  { value: "contrarian", label: "Contrarian" },
  { value: "storyteller", label: "Storyteller" },
  { value: "local-guide", label: "Local observer" },
  { value: "executive", label: "Executive" },
];

export const TEXT_MODELS: Record<"gemini" | "openai", PresetOption[]> = {
  gemini: [
    { value: "gemini-2.5-flash", label: "Gemini 2.5 Flash" },
    { value: "gemini-2.5-pro", label: "Gemini 2.5 Pro" },
    { value: "gemini-2.0-flash", label: "Gemini 2.0 Flash" },
    { value: "gemini-1.5-flash", label: "Gemini 1.5 Flash" },
    { value: "gemini-1.5-pro", label: "Gemini 1.5 Pro" },
  ],
  openai: [
    { value: "gpt-4.1", label: "GPT-4.1" },
    { value: "gpt-4.1-mini", label: "GPT-4.1 mini" },
    { value: "gpt-4.1-nano", label: "GPT-4.1 nano" },
    { value: "gpt-4o", label: "GPT-4o" },
    { value: "gpt-4o-mini", label: "GPT-4o mini" },
    { value: "gpt-4-turbo", label: "GPT-4 Turbo" },
  ],
};

export const IMAGE_MODELS: Record<"gemini" | "openai", PresetOption[]> = {
  gemini: [
    { value: "gemini-2.5-flash-image", label: "Gemini 2.5 Flash Image" },
    { value: "imagen-3.0-generate-001", label: "Imagen 3" },
  ],
  openai: [
    { value: "gpt-image-1", label: "GPT Image 1" },
    { value: "dall-e-3", label: "DALL·E 3" },
    { value: "dall-e-2", label: "DALL·E 2" },
  ],
};

export function presetLabel(options: PresetOption[], value: string | null | undefined): string {
  if (!value) return "";
  return options.find((option) => option.value === value)?.label ?? value;
}

export function modelsFor(
  provider: "" | "gemini" | "openai" | null | undefined,
  kind: "text" | "image",
): PresetOption[] {
  const catalog = kind === "text" ? TEXT_MODELS : IMAGE_MODELS;
  if (provider === "gemini" || provider === "openai") return catalog[provider];
  return [
    ...catalog.gemini.map((model) => ({ ...model, label: `Gemini · ${model.label}` })),
    ...catalog.openai.map((model) => ({ ...model, label: `OpenAI · ${model.label}` })),
  ];
}

export function withCurrentModel(options: PresetOption[], current: string): PresetOption[] {
  if (current && !options.some((option) => option.value === current)) {
    return [{ value: current, label: current }, ...options];
  }
  return options;
}
