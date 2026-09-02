import { apiRequest } from './client'

export interface OperationPage { items: Array<Record<string, unknown>>; pagination: { page: number; page_size: number; total: number; pages: number } }
export interface DashboardStats { products: number; skus: number; customers: number; orders: number; pending_orders: number; low_stock_skus: number; open_logistics_exceptions: number; unread_notifications: number }
export interface ModuleStats { metrics: Array<{ label: string; value: string | number; note: string }>; panel: { eyebrow: string; title: string; description: string; score_label: string; score: string; score_width: number; items: Array<{ title: string; meta: string; tone: string }> } }

export function getDashboardStats() { return apiRequest<DashboardStats>('/operations/dashboard') }
export function listOperationModule(module: string, params: { page?: number; page_size?: number; keyword?: string; status?: string; category?: string; source_channel?: string; carrier_code?: string } = {}) {
  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => { if (value !== undefined && value !== '') query.set(key, String(value)) })
  return apiRequest<OperationPage>(`/operations/${module}?${query}`)
}
export function getOperationModuleStats(module: string) { return apiRequest<ModuleStats>(`/operations/${module}/stats`) }
