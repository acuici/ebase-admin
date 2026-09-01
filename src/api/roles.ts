import { apiRequest } from './client'

export interface PermissionRole {
  id: string
  name: string
  description?: string | null
  permission_codes: string[] | string
  is_active: number
}

export function getRole(id: string) {
  return apiRequest<PermissionRole>(`/admin/roles/${id}`)
}

export function updateRole(id: string, payload: { name: string; description?: string; permission_codes: string[]; is_active: number }) {
  return apiRequest<PermissionRole>(`/admin/roles/${id}`, { method: 'PUT', body: payload })
}
