import { apiRequest } from './client'

export interface OrderItem { sku_id: number; sku_code: string; product_name: string; quantity: number; unit_price: string; subtotal: string }
export interface Order { id: string; order_no: string; external_order_no?: string | null; channel_type?: string | null; channel_store_id?: string | null; status: string; total_amount: string; currency: string; created_at: string; updated_at?: string; items?: OrderItem[] }
export interface OrderPayment { id: number; channel?: string; status: string; amount: string; currency: string; paid_at?: string | null }
export interface OrderFulfillment { id: number; fulfillment_no?: string; warehouse_code?: string; status: string; shipped_at?: string | null; delivered_at?: string | null }
export interface OrderStatusLog { id: number; from_status?: string | null; to_status: string; remark?: string | null; created_at: string }
export interface OrderDetail extends Order { payments?: OrderPayment[]; fulfillments?: OrderFulfillment[]; status_logs?: OrderStatusLog[] }
export interface OrderPage { items: Order[]; pagination: { page: number; page_size: number; total: number; pages: number } }

export function listOrders(params: { page?: number; page_size?: number; keyword?: string; order_no?: string; status?: string; channel_type?: string; channel_store_id?: string } = {}) {
  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => { if (value) query.set(key, String(value)) })
  return apiRequest<OrderPage>(`/orders?${query}`)
}
export function getOrder(id: string) { return apiRequest<OrderDetail>(`/orders/${id}`) }
export function cancelOrder(id: string, remark = '后台手动取消') { return apiRequest<Order>(`/orders/${id}/cancel`, { method: 'POST', body: { remark } }) }
export function transitionOrder(id: string, status: string, remark?: string) { return apiRequest<Order>(`/orders/${id}/transition`, { method: 'POST', body: { status, remark } }) }
