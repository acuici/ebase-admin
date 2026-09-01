export interface ApiErrorBody {
  code: string
  message: string
  errors?: Record<string, string[]>
  request_id?: string
}

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly body: ApiErrorBody,
  ) {
    super(body.message)
  }
}

export interface ApiEnvelope<T> {
  code: 'OK'
  message: string
  data: T
  request_id: string
}

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8797/api/v1'

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: unknown
  retryOnUnauthorized?: boolean
}

let refreshing: Promise<boolean> | null = null

function getAccessToken(): string | null {
  return localStorage.getItem('ebase:access_token')
}

async function refreshAccessToken(): Promise<boolean> {
  const refreshToken = localStorage.getItem('ebase:refresh_token')
  if (!refreshToken) return false

  try {
    const response = await fetch(`${API_BASE_URL}/auth/refresh`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token: refreshToken }),
    })
    if (!response.ok) return false

    const result = await response.json() as ApiEnvelope<{ access_token: string; refresh_token: string }>
    localStorage.setItem('ebase:access_token', result.data.access_token)
    localStorage.setItem('ebase:refresh_token', result.data.refresh_token)
    return true
  } catch {
    return false
  }
}

async function ensureFreshToken(): Promise<boolean> {
  if (!refreshing) {
    refreshing = refreshAccessToken().finally(() => { refreshing = null })
  }
  return refreshing
}

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { body, headers, retryOnUnauthorized = true, ...init } = options
  const token = getAccessToken()
  const requestHeaders: HeadersInit = {
    Accept: 'application/json',
    ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...headers,
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    headers: requestHeaders,
    body: body === undefined ? undefined : JSON.stringify(body),
  })

  if (response.status === 401 && retryOnUnauthorized && await ensureFreshToken()) {
    return apiRequest<T>(path, { ...options, retryOnUnauthorized: false })
  }

  const payload = await response.json() as ApiEnvelope<T> | ApiErrorBody
  if (!response.ok || payload.code !== 'OK') {
    throw new ApiError(response.status, payload as ApiErrorBody)
  }

  return (payload as ApiEnvelope<T>).data
}

export { API_BASE_URL }
