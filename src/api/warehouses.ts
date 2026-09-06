import { apiRequest } from './client'

export interface Warehouse {
  id: number
  warehouse_code: string
  name: string
  owner_code: string | null
  owner_name?: string | null
  owner_status?: number | null
  city: string | null
  capacity_rate: number | string | null
  inbound_quantity: number
  outbound_quantity: number
  supplier_id?: number | null
  supplier_code?: string | null
  supplier_name?: string | null
  sku_count?: number
  available_stock?: number
  status: 'active' | 'disabled'
  created_at: string
  updated_at: string
}

export interface WarehouseMember {
  id: number
  member_code: string
  name: string
  email: string
}

interface WarehousePage {
  items: Warehouse[]
  pagination: { page: number; page_size: number; total: number; pages: number }
}

export function listWarehouses(params: Record<string, string | number | undefined> = {}) {
  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== '') query.set(key, String(value))
  })
  return apiRequest<WarehousePage>(`/warehouses?${query.toString()}`)
}

export function listWarehouseMembers() {
  return apiRequest<{ items: WarehouseMember[] }>('/warehouses/members')
}

export function createWarehouse(payload: Record<string, unknown>) {
  return apiRequest<Warehouse>('/warehouses', { method: 'POST', body: payload })
}

export function updateWarehouse(id: number, payload: Record<string, unknown>) {
  return apiRequest<Warehouse>(`/warehouses/${id}`, { method: 'PUT', body: payload })
}

export function deleteWarehouse(id: number) {
  return apiRequest<null>(`/warehouses/${id}`, { method: 'DELETE' })
}

export function handoverWarehouse(id: number, payload: { to_owner_code: string; handover_type: 'temporary' | 'permanent'; reason?: string }) {
  return apiRequest<Warehouse>(`/warehouses/${id}/handover`, { method: 'POST', body: payload })
}

export function restoreWarehouse(id: number) {
  return apiRequest<Warehouse>(`/warehouses/${id}/restore`, { method: 'POST' })
}

export function listWarehouseHandovers(id: number) {
  return apiRequest<{ items: Array<Record<string, unknown>> }>(`/warehouses/${id}/handovers`)
}
