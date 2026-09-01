import { apiRequest } from './client'

export interface Role {
  id: string
  name: string
  description?: string | null
  permission_codes?: string[] | string
  is_active: number
}

export interface AdminMember {
  id: string
  name: string
  email: string
  status: number
  is_super: number
  last_login_at?: string | null
  roles: Role[]
}

interface Paginated<T> {
  items: T[]
  pagination: { page: number; page_size: number; total: number; pages: number }
}

export function listMembers(params: { page?: number; page_size?: number; keyword?: string; status?: number } = {}) {
  const query = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== '') query.set(key, String(value))
  })
  return apiRequest<Paginated<AdminMember>>(`/admin/members?${query.toString()}`)
}

export function getMember(id: string) {
  return apiRequest<AdminMember>(`/admin/members/${id}`)
}

export function listRoles() {
  return apiRequest<Role[]>('/admin/roles')
}

export function inviteMember(payload: { name: string; email: string; role_ids: number[] }) {
  return apiRequest<{ member: AdminMember; invite_token: string; expires_in: number }>('/admin/members/invite', {
    method: 'POST',
    body: payload,
  })
}

export function updateMember(id: string, payload: { name: string; email: string; status?: number; role_ids?: number[] }) {
  return apiRequest<AdminMember>(`/admin/members/${id}`, { method: 'PUT', body: payload })
}

export function disableMember(id: string) {
  return apiRequest<null>(`/admin/members/${id}/disable`, { method: 'POST' })
}

export function resetMemberPassword(id: string) {
  return apiRequest<{ reset_token: string; expires_in: number }>(`/admin/members/${id}/reset-password`, { method: 'POST' })
}
