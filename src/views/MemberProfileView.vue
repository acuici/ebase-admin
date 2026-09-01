<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Bell, Check, KeyRound, LogOut, Save, ShieldCheck, UserRound } from 'lucide-vue-next'
import { listAuthLogs, listSessions, revokeSession, type AuthLog, type MemberSession } from '../api/security'
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

function authEventLabel(event: AuthLog['event_type']): string {
  return {
    login_success: '登录成功',
    login_failed: '登录失败',
    logout: '主动登出',
    password_reset: '密码重置',
    session_revoked: '会话已撤销',
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
          <article class="surface editor-section">
            <header><span><KeyRound :size="18" /></span><div><h2>登录设备</h2><p>设备会话来自真实后台记录，可撤销非当前设备。</p></div></header>
            <p v-if="securityLoading">正在加载登录设备与安全日志...</p>
            <div v-else class="security-cards">
              <div v-for="session in sessions" :key="session.session_id">
                <span><ShieldCheck :size="19" /></span>
                <div><b>{{ session.device || '未知设备' }}</b><small>{{ session.ip || '未知 IP' }} · 最近活动 {{ session.last_seen || session.created_at }}</small></div>
                <button @click="revokeDevice(session.session_id)">撤销</button>
              </div>
              <p v-if="!sessions.length">没有活动登录设备。</p>
            </div>
          </article>
          <article class="surface editor-section">
            <header><span><ShieldCheck :size="18" /></span><div><h2>登录与安全日志</h2><p>记录登录成功、失败、登出、密码重置与会话撤销。</p></div></header>
            <div class="security-cards">
              <div v-for="log in authLogs" :key="log.id">
                <span><KeyRound :size="19" /></span>
                <div><b>{{ authEventLabel(log.event_type) }}</b><small>{{ log.ip || '未知 IP' }} · {{ log.created_at }}</small></div>
              </div>
              <p v-if="!securityLoading && !authLogs.length">暂无安全日志；下一次登录后会自动生成。</p>
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
