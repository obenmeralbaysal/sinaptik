export type Workspace = {
  id: number;
  slug: string;
  name: string;
  role?: string | null;
};

export type User = {
  id: number;
  name: string | null;
  email: string | null;
  external_subject_id: string;
  issuer: string;
  current_workspace: Workspace | null;
  workspaces: Workspace[];
};

export type Site = {
  id: number;
  name: string;
  domain: string;
  google_property_id: string | null;
};

export type SinappPlan = {
  code: string;
  name: string;
  interval: "monthly" | "yearly";
  price_net: string;
  price_gross: string;
  features: string[];
  highlighted: boolean;
  active: boolean;
};

export type SinappCatalog = {
  product: string;
  currency: string;
  vat_rate: number;
  updated_at: string;
  plans: SinappPlan[];
};
