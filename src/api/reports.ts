import { apiRequest } from './client'

export interface ReportData { summary: { order_count: number; revenue: string; average_order_value: string; refund_amount: string; refund_rate: number }; daily: Array<{ date: string; orders: number; revenue: string }>; channels: Array<{ channel: string; orders: number; revenue: string }>; generated_at: string }
export function getOperationsReport(start?: string, end?: string) { const query = new URLSearchParams(); if (start) query.set('start', start); if (end) query.set('end', end); return apiRequest<ReportData>(`/reports/operations?${query}`) }
