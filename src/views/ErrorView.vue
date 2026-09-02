<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, FileQuestion, LayoutDashboard, RefreshCw, ServerCrash, ShieldX } from 'lucide-vue-next'

type ErrorStatus = 403 | 404 | 500

const props = withDefaults(defineProps<{ status?: ErrorStatus }>(), { status: 404 })
const route = useRoute()
const router = useRouter()

const content = computed(() => ({
  403: {
    eyebrow: 'ACCESS RESTRICTED',
    title: '没有权限访问此页面',
    description: '当前账号缺少所需权限。你的登录状态和已保存数据不会受到影响。',
    hint: '如需访问，请联系管理员为当前角色分配对应的功能与数据权限。',
    icon: ShieldX,
  },
  404: {
    eyebrow: 'PAGE NOT FOUND',
    title: '页面不存在或已被移动',
    description: '当前地址无法匹配后台中的任何功能页面。',
    hint: '请检查链接是否完整，或从控制台和左侧导航重新进入目标功能。',
    icon: FileQuestion,
  },
  500: {
    eyebrow: 'SYSTEM ERROR',
    title: '页面暂时无法加载',
    description: '系统处理请求时遇到异常，你刚才的操作可能尚未完成。',
    hint: '可以先重新加载；如果问题持续出现，请将下方请求信息提供给管理员。',
    icon: ServerCrash,
  },
}[props.status]))

const requestCode = computed(() => props.status === 404
  ? route.fullPath
  : `EBASE-${props.status}-${new Date().toISOString().slice(0, 10).replaceAll('-', '')}`)

function goBack() {
  if (window.history.length > 1) router.back()
  else void router.push('/')
}

function retry() {
  window.location.reload()
}
</script>

<template>
  <section class="error-page" :data-status="status">
    <article class="surface error-card">
      <div class="error-visual" aria-hidden="true">
        <span class="error-icon"><component :is="content.icon" :size="28" /></span>
        <strong>{{ status }}</strong>
      </div>
      <div class="error-copy">
        <span class="eyebrow">{{ content.eyebrow }}</span>
        <h1>{{ content.title }}</h1>
        <p>{{ content.description }}</p>
        <div class="error-hint"><span></span>{{ content.hint }}</div>
        <div class="error-actions">
          <button class="button secondary" @click="goBack"><ArrowLeft :size="16" />返回上一页</button>
          <button v-if="status === 500" class="button primary" @click="retry"><RefreshCw :size="16" />重新加载</button>
          <button v-else class="button primary" @click="router.push('/')"><LayoutDashboard :size="16" />返回控制台</button>
        </div>
      </div>
      <footer><span>{{ status === 404 ? '访问地址' : '请求标识' }}</span><code>{{ requestCode }}</code></footer>
    </article>
  </section>
</template>
