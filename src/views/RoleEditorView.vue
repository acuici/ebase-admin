<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Check, Save, ShieldCheck } from 'lucide-vue-next'
import { ApiError } from '../api/client'
import { getRole, updateRole, type PermissionRole } from '../api/roles'
import { useToast } from '../composables/useToast'

const route = useRoute()
const router = useRouter()
const { success, error: showError } = useToast()
const loading = ref(true)
const saving = ref(false)
const role = reactive({ id: String(route.params.id), name: '', description: '', is_active: 1, permission_codes: [] as string[] })

const permissionGroups = [
  { title: '订单与售后', items: ['order.order.read', 'order.order.update', 'order.order.export', 'order.refund.approve'] },
  { title: '商品与库存', items: ['catalog.product.read', 'catalog.product.update', 'catalog.product.export', 'inventory.stock.adjust'] },
  { title: '用户与营销', items: ['customer.customer.read', 'customer.customer.update', 'marketing.coupon.manage', 'marketing.campaign.approve'] },
  { title: '独立站与内容', items: ['storefront.site.read', 'storefront.site.update', 'content.content.publish', 'content.content.review'] },
  { title: '系统管理', items: ['admin.member.read', 'admin.member.update', 'admin.member.invite', 'admin.role.update'] },
]

function rolePermissions(value: PermissionRole): string[] {
  return Array.isArray(value.permission_codes) ? value.permission_codes : value.permission_codes.split(',').filter(Boolean)
}

async function load(): Promise<void> {
  try {
    const value = await getRole(role.id)
    role.name = value.name
    role.description = value.description || ''
    role.is_active = value.is_active
    role.permission_codes = rolePermissions(value)
  } catch (exception) {
    showError('角色加载失败', exception instanceof ApiError ? exception.body.message : '请稍后重试')
  } finally {
    loading.value = false
  }
}

function togglePermission(code: string): void {
  role.permission_codes = role.permission_codes.includes(code)
    ? role.permission_codes.filter(item => item !== code)
    : [...role.permission_codes, code]
}

async function save(): Promise<void> {
  saving.value = true
  try {
    await updateRole(role.id, role)
    success('角色权限已保存', `${role.permission_codes.length} 项权限已生效`)
  } catch (exception) {
    showError('保存失败', exception instanceof ApiError ? exception.body.message : '请稍后重试')
  } finally {
    saving.value = false
  }
}

onMounted(() => { void load() })
</script>

<template>
  <section class="data-page workflow-page">
    <div class="workflow-top"><button class="back-button" @click="router.push('/settings')"><ArrowLeft :size="17" />返回设置</button><div class="workflow-actions"><button class="button primary" :disabled="loading || saving" @click="save"><Save :size="15" />{{ saving ? '保存中...' : '保存角色权限' }}</button></div></div>
    <div class="workflow-heading"><span class="eyebrow">ROLE PERMISSIONS · RBAC</span><h1>{{ role.name || '角色权限' }}</h1><p>{{ role.description || '配置功能权限和最终数据访问边界。' }}</p></div>
    <article v-if="loading" class="surface form-card"><p>正在加载权限配置...</p></article>
    <template v-else>
      <article class="surface form-card role-editor-card"><header><h2>角色信息</h2><p>角色权限由后端中间件最终校验，前端只负责配置和展示。</p></header><div class="form-grid"><label><span>角色名称</span><input v-model="role.name" /></label><label><span>状态</span><select v-model="role.is_active"><option :value="1">启用</option><option :value="0">停用</option></select></label><label class="wide"><span>角色描述</span><textarea v-model="role.description" class="textarea-control" /></label></div></article>
      <article v-for="group in permissionGroups" :key="group.title" class="surface permission-group"><header><div><h2>{{ group.title }}</h2><p>选择该角色允许执行的操作。</p></div><span>{{ group.items.filter(code => role.permission_codes.includes(code)).length }} / {{ group.items.length }}</span></header><div class="permission-grid"><label v-for="code in group.items" :key="code" :class="{ checked: role.permission_codes.includes(code) }"><input type="checkbox" :checked="role.permission_codes.includes(code)" @change="togglePermission(code)" /><ShieldCheck :size="16" /><span>{{ code }}</span></label></div></article>
    </template>
  </section>
</template>
