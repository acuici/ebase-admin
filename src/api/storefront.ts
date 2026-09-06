import { apiRequest } from "./client";

export interface StorefrontSite {
  id: string;
  site_code: string;
  name: string;
  brand_name?: string;
  service_email?: string;
  default_locale: string;
  currency: string;
  timezone: string;
  status: string;
  default_seo_title?: string;
  default_seo_description?: string;
}
export interface StorefrontSitePage {
  items: StorefrontSite[];
  pagination: { page: number; page_size: number; total: number; pages: number };
}
export function listStorefrontSites(
  params: Record<string, string | number | undefined> = {},
) {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== "") query.set(key, String(value));
  });
  return apiRequest<StorefrontSitePage>(`/storefront/sites?${query}`);
}
export function createStorefrontSite(payload: Partial<StorefrontSite>) {
  return apiRequest<StorefrontSite>("/storefront/sites", {
    method: "POST",
    body: payload,
  });
}
export function updateStorefrontSite(
  id: string,
  payload: Partial<StorefrontSite>,
) {
  return apiRequest<StorefrontSite>(`/storefront/sites/${id}`, {
    method: "PUT",
    body: payload,
  });
}
