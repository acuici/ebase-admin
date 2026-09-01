<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { AlertTriangle, Bell, Check, PackageSearch, ShoppingBag } from 'lucide-vue-next'
import { apiRequest } from '../../api/client'
import { useToast } from '../../composables/useToast'
import { useTopbarLayer } from '../../composables/useTopbarLayer'

interface NotificationItem { id: string; notification_type: string; title: string; content: string; target_path?: string | null; read_at?: string | null; created_at: string }
interface NotificationResponse { items: NotificationItem[]; unread: number }

const router = useRouter()
const { success, error: showError } = useToast()
const { active, open: openLayer, close } = useTopbarLayer()
const open = computed({ get: () => active.value === 'notifications', set: value => value ? openLayer('notifications') : close('notifications') })
const root = ref<HTMLElement | null>(null)
const notices = ref<NotificationItem[]>([])
const unread = ref(0)
const loading = ref(false)

async function load(): Promise<void> {
  loading.value = true
  try {
    const data = await apiRequest<NotificationResponse>('/notifications')
    notices.value = data.items
    unread.value = data.unread
  } catch {
    showError('通知加载失败', '请稍后重试')
  } finally {
    loading.value = false
  }
}

async function markAll(): Promise<void> {
  await apiRequest('/notifications/read-all', { method: 'POST' })
  notices.value.forEach(item => { item.read_at = new Date().toISOString() })
  unread.value = 0
  success('通知已全部标记为已读')
}

async function go(item: NotificationItem): Promise<void> {
  if (!item.read_at) {
    await apiRequest(`/notifications/${item.id}/read`, { method: 'POST' })
    item.read_at = new Date().toISOString()
    unread.value = Math.max(0, unread.value - 1)
  }
  open.value = false
  if (item.target_path) await router.push(item.target_path)
}

function eventIcon(type: string) {
  if (type === 'restock') return PackageSearch
  if (type === 'logistics_exception') return AlertTriangle
  return ShoppingBag
}
function relativeTime(date: string): string { return new Date(date).toLocaleString('zh-CN', { hour: '2-digit', minute: '2-digit' }) }
function outside(event: MouseEvent) { if (root.value && !root.value.contains(event.target as Node)) open.value = false }
function key(event: KeyboardEvent) { if (event.key === 'Escape') open.value = false }

onMounted(() => { void load(); document.addEventListener('click', outside); document.addEventListener('keydown', key) })
onBeforeUnmount(() => { document.removeEventListener('click', outside); document.removeEventListener('keydown', key) })
</script>

<template>
  <div ref="root" class="topbar-popover-wrap">
    <button class="notification" :class="{ active: open }" aria-label="通知" aria-haspopup="dialog" :aria-expanded="open" @click.stop="open = !open"><Bell :size="19" /><i v-if="unread" /></button>
    <Transition name="topbar-popover">
      <section v-if="open" class="topbar-popover notification-popover">
        <header><div><strong>通知中心</strong><span>{{ unread ? `${unread} 条未读消息` : '全部已读' }}</span></div><button v-if="unread" @click="markAll"><Check :size="14" />全部已读</button></header>
        <div v-if="loading" class="notice-empty">正在加载通知...</div>
        <div v-else-if="notices.length" class="notice-list"><button v-for="item in notices" :key="item.id" :class="{ unread: !item.read_at }" @click="go(item)"><span :data-type="item.notification_type"><component :is="eventIcon(item.notification_type)" :size="16" /></span><div><b>{{ item.title }}</b><p>{{ item.content }}</p><small>{{ relativeTime(item.created_at) }}</small></div><i v-if="!item.read_at" /></button></div>
        <div v-else class="notice-empty"><Bell :size="22" /><strong>暂无通知</strong><p>新的补货、物流和审批提醒会出现在这里。</p></div>
        <footer><button @click="open = false; router.push('/member/profile?tab=notifications')">管理通知偏好</button></footer>
      </section>
    </Transition>
  </div>
</template>
