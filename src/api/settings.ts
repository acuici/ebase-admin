import { apiRequest } from './client'

export interface SystemSettingsResponse { group: string; settings: Record<string, unknown> }
export interface OperationLog { id: string; module: string; action: string; risk_level: string; result: string; created_at: string; detail?: Record<string, unknown> }

export function getSystemSettings(group: string) { return apiRequest<SystemSettingsResponse>(`/system-settings/${group}`) }
export function saveSystemSettings(group: string, settings: Record<string, unknown>) { return apiRequest<SystemSettingsResponse>(`/system-settings/${group}`, { method: 'PUT', body: { settings } }) }
export function listOperationLogs() { return apiRequest<{ items: OperationLog[]; pagination: Record<string, number> }>('/operation-logs?page=1&page_size=20') }
