import { computed, readonly, ref } from 'vue'
import { ApiError, apiRequest } from '../api/client'

export interface MemberProfile {
  id: string
  email: string
  name: string
  avatar?: string | null
  status: number
  permissions: string[]
  phone?: string | null
  job_title?: string | null
  department?: string | null
  locale?: 'zh-CN' | 'en-US'
  notification_preferences?: Record<string, boolean> | null
}

interface LoginResponse {
  access_token: string
  refresh_token: string
  expires_in: number
  member: Pick<MemberProfile, 'id' | 'email' | 'name'>
}

const member = ref<MemberProfile | null>(null)
const hydrated = ref(false)

function clearTokens(): void {
  localStorage.removeItem('ebase:access_token')
  localStorage.removeItem('ebase:refresh_token')
}

export function useAuth() {
  const isAuthenticated = computed(() => Boolean(localStorage.getItem('ebase:access_token')))

  async function login(email: string, password: string): Promise<void> {
    const data = await apiRequest<LoginResponse>('/auth/login', {
      method: 'POST',
      body: { email, password },
      retryOnUnauthorized: false,
    })
    localStorage.setItem('ebase:access_token', data.access_token)
    localStorage.setItem('ebase:refresh_token', data.refresh_token)
    member.value = {
      id: data.member.id,
      email: data.member.email,
      name: data.member.name,
      status: 1,
      permissions: [],
    }
    hydrated.value = false
  }

  async function hydrate(force = false): Promise<boolean> {
    if (!isAuthenticated.value) {
      member.value = null
      hydrated.value = true
      return false
    }

    if (!force && member.value) {
      hydrated.value = true
      return true
    }

    try {
      member.value = await apiRequest<MemberProfile>('/member/profile')
      hydrated.value = true
      return true
    } catch (exception) {
      // Do not erase an already-known member on transient network/server errors.
      // Only an explicit authentication failure invalidates the local session.
      if (exception instanceof ApiError && exception.status === 401) {
        clearTokens()
        member.value = null
      }
      hydrated.value = true
      return Boolean(member.value)
    }
  }

  async function updateProfile(profile: Pick<MemberProfile, 'name' | 'phone' | 'job_title' | 'department' | 'locale' | 'notification_preferences'>): Promise<MemberProfile> {
    const updated = await apiRequest<MemberProfile>('/member/profile', {
      method: 'PATCH',
      body: profile,
    })
    member.value = updated
    return updated
  }

  async function logout(): Promise<void> {
    try {
      await apiRequest('/auth/logout', { method: 'POST' })
    } catch {
      // Local revocation must still happen if the server is unreachable.
    } finally {
      clearTokens()
      member.value = null
      hydrated.value = true
    }
  }

  return {
    member: readonly(member),
    hydrated: readonly(hydrated),
    isAuthenticated,
    login,
    hydrate,
    updateProfile,
    logout,
  }
}
