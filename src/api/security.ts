import { apiRequest } from './client'

export interface MemberSession {
  session_id: string
  device?: string | null
  ip?: string | null
  created_at: string
  last_seen?: string | null
}

export interface AuthLog {
  id: string
  event_type: 'login_success' | 'login_failed' | 'logout' | 'password_reset' | 'session_revoked' | 'other_sessions_revoked'
  ip?: string | null
  user_agent?: string | null
  created_at: string
}

interface Paginated<T> {
  items: T[]
  pagination: { page: number; page_size: number; total: number; pages: number }
}

export function listSessions() {
  return apiRequest<MemberSession[]>('/member/sessions')
}

export function listAuthLogs() {
  return apiRequest<Paginated<AuthLog>>('/member/auth-logs?page=1&page_size=20')
}

export function revokeOtherSessions() {
  return apiRequest<{ revoked_count: number }>('/member/sessions/others', { method: 'DELETE' })
}

export function revokeSession(sessionId: string) {
  return apiRequest<null>(`/member/sessions/${sessionId}`, { method: 'DELETE' })
}
