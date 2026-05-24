export type OrganizationMembership = {
  id: number;
  slug: string;
  name: string;
  role: "owner" | "admin" | "member";
};

export type CurrentOrganization = {
  id: number;
  slug: string;
  name: string;
};

export type User = {
  id: number;
  name: string;
  email: string;
  is_super_admin: boolean;
  email_verified_at: string | null;
  current_organization: CurrentOrganization | null;
  organizations: OrganizationMembership[];
};

export type Plan = {
  id: number;
  slug: string;
  name: string;
  price_cents: number;
  currency: string;
  x_account_limit: number;
  daily_post_limit: number;
  daily_reply_limit: number;
  features: Record<string, unknown> | null;
  is_active: boolean;
};

export type SocialAccount = {
  id: number;
  provider: "x" | "instagram" | "facebook";
  username: string | null;
  display_name: string | null;
  avatar_url: string | null;
  is_active: boolean;
  expires_at: string | null;
  reply_frequency_minutes: number;
  daily_reply_limit: number;
  ai_model: string | null;
  created_at: string;
};

export type Target = {
  id: number;
  social_account_id: number | null;
  platform: string;
  target_username: string;
  ai_tone: string;
  custom_prompt: string | null;
  ai_model: string | null;
  daily_limit: number;
  reply_frequency_minutes: number;
  is_active: boolean;
  social_account?: { id: number; username: string | null; provider: string } | null;
};

export type Post = {
  id: number;
  social_account_id: number;
  content: string;
  media_paths: string[] | null;
  status: "draft" | "pending" | "posted" | "failed";
  scheduled_at: string | null;
  posted_at: string | null;
  provider_post_id: string | null;
  error: string | null;
  created_at: string;
  social_account?: { id: number; username: string | null; provider: string };
};

export type Routine = {
  id: number;
  social_account_id: number;
  name: string;
  persona: string | null;
  tone: string;
  language: string;
  topic: string;
  ai_model: string | null;
  image_model: string | null;
  generate_images: boolean;
  schedule_cron: string | null;
  daily_count: number;
  is_active: boolean;
};

export type XmlSource = {
  id: number;
  social_account_id: number | null;
  name: string;
  url: string;
  selector: string;
  template: string;
  fetch_interval_minutes: number;
  last_fetched_at: string | null;
  is_active: boolean;
};
