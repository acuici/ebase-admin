import { apiRequest } from './client'

export interface OperationPage { items: Array<Record<string, unknown>>; pagination: { page: number; page_size: number; total: number; pages: number } }
export interface DashboardStats { products: number; skus: number; customers: number; orders: number; today_revenue: string | number; paid_orders: number; average_order_value: string | number; refund_rate: string | number; pending_shipment_orders: number; pending_orders: number; low_stock_skus: number; open_logistics_exceptions: number; unread_notifications: number }
export interface ModuleStats { metrics: Array<{ label: string; value: string | number; note: string }>; panel: { eyebrow: string; title: string; description: string; score_label: string; score: string; score_width: number; items: Array<{ title: string; meta: string; tone: string }> } }

export function getDashboardStats() { return apiRequest<DashboardStats>('/operations/dashboard') }
export type OperationModuleParams = Record<string, string | number | undefined>

export function listOperationModule(module: string, params: OperationModuleParams = {}) {
  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => { if (value !== undefined && value !== '') query.set(key, String(value)) })
  return apiRequest<OperationPage>(`/operations/${module}?${query}`)
}
export function getOperationModuleStats(module: string) { return apiRequest<ModuleStats>(`/operations/${module}/stats`) }
