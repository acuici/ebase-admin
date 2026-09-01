<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Activity, Bell, Check, Clock3, KeyRound, Laptop, LogOut, MapPin, RefreshCw, Save, ShieldCheck, Smartphone, UserRound } from 'lucide-vue-next'
import { listAuthLogs, listSessions, revokeOtherSessions, revokeSession, type AuthLog, type MemberSession } from '../api/security'
import { ApiError } from '../api/client'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'

const route = useRoute()
const router = useRouter()
const { member, hydrate, logout, updateProfile } = useAuth()
const { success, error: showError } = useToast()
const validTabs = ['profile', 'security', 'notifications']
const tab = ref(validTabs.includes(String(route.query.tab)) ? String(route.query.tab) : 'profile')
const loading = ref(true)
const saving = ref(false)
const securityLoading = ref(false)
const sessions = ref<MemberSession[]>([])
const authLogs = ref<AuthLog[]>([])
const form = reactive({
  name: '',
  phone: '',
  job_title: '',
  department: '',
  locale: 'zh-CN' as 'zh-CN' | 'en-US',
  notification_preferences: { order: true, inventory: true, security: true },
})

const displayName = computed(() => member.value?.name || form.name || '成员')
const displayEmail = computed(() => member.value?.email || '成员账号')

function syncForm(): void {
  if (!member.value) return
  form.name = member.value.name
  form.phone = member.value.phone || ''
  form.job_title = member.value.job_title || ''
  form.department = member.value.department || ''
  form.locale = member.value.locale || 'zh-CN'
  form.notification_preferences = {
    order: member.value.notification_preferences?.order ?? true,
    inventory: member.value.notification_preferences?.inventory ?? true,
    security: member.value.notification_preferences?.security ?? true,
  }
}

async function save(): Promise<void> {
  saving.value = true
  try {
    await updateProfile({ ...form })
    await hydrate(true)
    syncForm()
    success('个人设置已保存', '资料已同步到成员账户')
  } catch (exception) {
    showError('保存失败', exception instanceof ApiError ? exception.body.message : '请稍后重试')
  } finally {
    saving.value = false
  }
}

async function loadSecurity(): Promise<void> {
  securityLoading.value = true
  try {
    const [sessionData, logData] = await Promise.all([listSessions(), listAuthLogs()])
    sessions.value = sessionData
    authLogs.value = logData.items
  } catch (exception) {
    showError('安全信息加载失败', exception instanceof ApiError ? exception.body.message : '请稍后重试')
  } finally {
    securityLoading.value = false
  }
}

async function revokeDevice(sessionId: string): Promise<void> {
  try {
    await revokeSession(sessionId)
    success('设备会话已撤销')
    await loadSecurity()
  } catch (exception) {
    showError('撤销失败', exception instanceof ApiError ? exception.body.message : '请稍后重试')
  }
}

async function revokeOtherDevices(): Promise<void> {
  if (!confirm('确认撤销除当前设备外的全部会话？其他设备将需要重新登录。')) return
  try {
    const result = await revokeOtherSessions()
    success('其他设备已撤销', `共撤销 ${result.revoked_count} 个会话`)
    await loadSecurity()
  } catch (exception) {
    showError('撤销失败', exception instanceof ApiError ? exception.body.message : '请稍后重试')
  }
}

function authEventLabel(event: AuthLog['event_type']): string {
  return {
    login_success: '登录成功',
    login_failed: '登录失败',
    logout: '主动登出',
    password_reset: '密码重置',
    session_revoked: '会话已撤销',
    other_sessions_revoked: '其他设备会话已撤销',
  }[event]
}

async function signOut(): Promise<void> {
  await logout()
  await router.push('/login')
}

watch(() => route.query.tab, value => {
  const next = String(value || 'profile')
  tab.value = validTabs.includes(next) ? next : 'profile'
  if (tab.value === 'security') void loadSecurity()
})

onMounted(async () => {
  await hydrate()
  syncForm()
  if (tab.value === 'security') await loadSecurity()
  loading.value = false
})
</script>

<template>
  <section class="data-page profile-page">
    <div class="page-heading">
      <div>
        <span class="eyebrow">MY ACCOUNT · 成员中心</span>
        <h1>个人资料与安全</h1>
        <p>管理个人信息、通知偏好和当前登录设备。</p>
      </div>
      <div class="heading-actions">
        <button class="button primary" :disabled="saving || loading" @click="save">
          <Save :size="15" />{{ saving ? '保存中...' : '保存更改' }}
        </button>
      </div>
    </div>

    <div class="profile-layout">
      <aside class="surface profile-nav">
        <div class="profile-person">
          <span>{{ displayName[0] }}</span>
          <h3>{{ displayName }}</h3>
          <p>{{ form.job_title || '成员' }} · {{ form.department || '未设置部门' }}</p>
          <i>{{ member?.status === 1 ? '账号正常' : '账号已停用' }}</i>
        </div>
        <nav>
          <button :class="{ active: tab === 'profile' }" @click="tab = 'profile'"><UserRound :size="16" />个人资料</button>
          <button :class="{ active: tab === 'security' }" @click="tab = 'security'"><ShieldCheck :size="16" />账号安全</button>
          <button :class="{ active: tab === 'notifications' }" @click="tab = 'notifications'"><Bell :size="16" />通知偏好</button>
        </nav>
        <button class="profile-logout" @click="signOut"><LogOut :size="16" />退出当前账号</button>
      </aside>

      <main class="profile-content">
        <article v-if="loading" class="surface editor-section"><p>正在加载成员资料...</p></article>
        <template v-else-if="tab === 'profile'">
          <article class="surface editor-section">
            <header><span><UserRound :size="18" /></span><div><h2>个人信息</h2><p>姓名会展示在成员目录与操作记录中。</p></div></header>
            <div class="editor-fields">
              <label><span>姓名</span><input v-model="form.name" /></label>
              <label><span>工作邮箱</span><input :value="displayEmail" disabled /></label>
              <label><span>手机号码</span><input v-model="form.phone" placeholder="选填" /></label>
              <label><span>职位</span><input v-model="form.job_title" placeholder="选填" /></label>
              <label><span>所属部门</span><input v-model="form.department" placeholder="选填" /></label>
              <label><span>界面语言</span><select v-model="form.locale"><option value="zh-CN">简体中文</option><option value="en-US">English</option></select></label>
            </div>
          </article>
        </template>

        <template v-else-if="tab === 'security'">
          <section class="security-overview" aria-label="账号安全摘要">
            <div><span><ShieldCheck :size="17" /></span><p>账号状态<b>保护正常</b></p></div>
            <div><span><Laptop :size="17" /></span><p>活动设备<b>{{ sessions.length }} 台</b></p></div>
            <div><span><Activity :size="17" /></span><p>近期安全事件<b>{{ authLogs.length }} 条</b></p></div>
          </section>
          <article class="surface editor-section security-panel">
            <header><span><KeyRound :size="18" /></span><div><h2>登录设备</h2><p>查看已建立的设备会话，并撤销不再使用的设备。</p></div><div class="security-header-actions"><button class="security-refresh" :disabled="securityLoading" aria-label="重新加载安全信息" @click="loadSecurity"><RefreshCw :size="15" :class="{ spinning: securityLoading }" />重新检查</button><button class="security-refresh danger-action" :disabled="securityLoading || sessions.length < 2" @click="revokeOtherDevices">撤销其他设备</button></div></header>
            <div v-if="securityLoading" class="security-skeleton" aria-label="正在加载登录设备"><i></i><i></i></div>
            <div v-else-if="sessions.length" class="device-list">
              <div v-for="session in sessions" :key="session.session_id">
                <span class="device-icon"><Smartphone v-if="/mobile|iphone|android/i.test(session.device || '')" :size="18" /><Laptop v-else :size="18" /></span>
                <div><b>{{ session.device || '未知设备' }}</b><small><MapPin :size="12" />{{ session.ip || '未知 IP' }}<Clock3 :size="12" />最近活动 {{ session.last_seen || session.created_at }}</small></div>
                <button @click="revokeDevice(session.session_id)">撤销会话</button>
              </div>
            </div>
            <div v-else class="security-empty compact-empty">
              <span><Laptop :size="22" /></span><div><h3>没有其他活动设备</h3><p>新设备登录后会显示在这里，你可以随时撤销对应会话。</p></div><button class="button secondary" @click="loadSecurity"><RefreshCw :size="14" />重新检查</button>
            </div>
          </article>
          <article class="surface editor-section security-panel">
            <header><span><ShieldCheck :size="18" /></span><div><h2>登录与安全日志</h2><p>追踪登录、登出、密码重置和会话撤销。</p></div></header>
            <div v-if="securityLoading" class="security-skeleton log-skeleton" aria-label="正在加载安全日志"><i></i><i></i><i></i></div>
            <div v-else-if="authLogs.length" class="auth-log-list">
              <div v-for="log in authLogs" :key="log.id"><span :data-event="log.event_type"><KeyRound :size="15" /></span><div><b>{{ authEventLabel(log.event_type) }}</b><small>{{ log.ip || '未知 IP' }}</small></div><time>{{ log.created_at }}</time></div>
            </div>
            <div v-else class="security-empty compact-empty log-empty">
              <span><ShieldCheck :size="22" /></span><div><h3>暂无安全事件</h3><p>登录、密码修改或设备撤销后，相关记录会自动出现在这里。</p></div>
            </div>
          </article>
        </template>

        <template v-else>
          <article class="surface editor-section">
            <header><span><Bell :size="18" /></span><div><h2>通知偏好</h2><p>通知设置保存在你的成员账户中。</p></div></header>
            <div class="security-options">
              <label><div><b>订单与履约通知</b><small>订单状态、发货和异常提醒。</small></div><input v-model="form.notification_preferences.order" type="checkbox" /></label>
              <label><div><b>库存风险通知</b><small>库存预警、补货和盘点提醒。</small></div><input v-model="form.notification_preferences.inventory" type="checkbox" /></label>
              <label><div><b>安全通知</b><small>登录、会话和权限变化提醒。</small></div><input v-model="form.notification_preferences.security" type="checkbox" /></label>
            </div>
          </article>
        </template>
      </main>
    </div>
  </section>
</template>
