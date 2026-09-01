<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowRight, CheckCircle2, Eye, EyeOff, LockKeyhole, Mail } from 'lucide-vue-next'
import { ApiError } from '../api/client'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const route = useRoute()
const { login: signIn } = useAuth()

const email = ref('admin@ebase.local')
const password = ref('ChangeMe123!')
const showPassword = ref(false)
const submitting = ref(false)
const error = ref('')

async function login(): Promise<void> {
  error.value = ''
  if (!email.value || !password.value) {
    error.value = '请输入成员邮箱和密码'
    return
  }

  submitting.value = true
  try {
    await signIn(email.value, password.value)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/'
    await router.push(redirect)
  } catch (exception) {
    error.value = exception instanceof ApiError ? exception.body.message : '登录失败，请检查网络后重试'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-story">
      <div class="auth-brand"><span>AC</span><strong>AC · 清透商业</strong></div>
      <div class="auth-copy">
        <span class="auth-kicker">EBASE COMMERCE OS</span>
        <h1>把复杂经营，<br>收束成清晰行动。</h1>
        <p>订单、库存、内容与增长协同在同一个工作空间，帮助团队始终看见下一步。</p>
        <ul>
          <li><CheckCircle2 :size="17" />实时汇总全渠道经营数据</li>
          <li><CheckCircle2 :size="17" />按角色控制数据与操作权限</li>
          <li><CheckCircle2 :size="17" />关键风险主动提醒并留痕</li>
        </ul>
      </div>
      <small>© 2026 EBASE · 企业级安全保护</small>
    </section>

    <section class="auth-form-panel">
      <form class="auth-form" @submit.prevent="login">
        <div class="auth-mobile-brand"><span>AC</span><strong>清透商业</strong></div>
        <span class="eyebrow">MEMBER ACCESS</span>
        <h2>欢迎回来</h2>
        <p>使用企业成员账号进入运营控制台。</p>

        <label>
          <span>成员邮箱</span>
          <div><Mail :size="17" /><input v-model="email" type="email" autocomplete="email" placeholder="name@company.com" /></div>
        </label>
        <label>
          <span>登录密码</span>
          <div>
            <LockKeyhole :size="17" />
            <input v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" />
            <button type="button" aria-label="显示密码" @click="showPassword = !showPassword">
              <EyeOff v-if="showPassword" :size="17" /><Eye v-else :size="17" />
            </button>
          </div>
        </label>
        <div class="auth-options">
          <label><input type="checkbox" checked />保持登录</label>
          <RouterLink to="/forgot-password">忘记密码？</RouterLink>
        </div>
        <p v-if="error" class="auth-error">{{ error }}</p>
        <button class="auth-submit" type="submit" :disabled="submitting">
          {{ submitting ? '登录中...' : '进入工作台' }}<ArrowRight :size="17" />
        </button>
        <div class="auth-divider"><span>或</span></div>
        <button type="button" class="auth-sso" disabled>使用企业单点登录（SSO）</button>
        <p class="auth-help">还没有成员账号？请联系企业管理员发送邀请。</p>
      </form>
    </section>
  </main>
</template>
