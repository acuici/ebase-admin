<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, KeyRound, Mail, Save, Send, ShieldCheck, Trash2, UserRound } from 'lucide-vue-next'
import { ApiError } from '../api/client'
import { disableMember, getMember, inviteMember, listRoles, resetMemberPassword, updateMember, type Role } from '../api/members'
import { useToast } from '../composables/useToast'

const props = defineProps<{ mode: 'invite' | 'edit' }>()
const route = useRoute()
const router = useRouter()
const { success, error: showError, info } = useToast()
const loading = ref(props.mode === 'edit')
const saving = ref(false)
const roles = ref<Role[]>([])
const form = reactive({ name: '', email: '', roleIds: [] as number[], status: 1 })
const memberId = computed(() => String(route.params.id || ''))
const title = computed(() => props.mode === 'invite' ? '邀请新成员' : '成员详情')

async function load(): Promise<void> {
  try {
    roles.value = await listRoles()
    if (props.mode === 'edit') {
      const member = await getMember(memberId.value)
      form.name = member.name
      form.email = member.email
      form.status = member.status
      form.roleIds = member.roles.map(role => Number(role.id))
    }
  } catch (exception) {
    showError('加载失败', exception instanceof ApiError ? exception.body.message : '请稍后重试')
  } finally {
    loading.value = false
  }
}

async function save(): Promise<void> {
  saving.value = true
  try {
    if (props.mode === 'invite') {
      const result = await inviteMember({ name: form.name, email: form.email, role_ids: form.roleIds })
      success('成员邀请已创建', `${result.member.email} 的安全邀请令牌有效期为 24 小时`)
      await router.push('/settings/members')
    } else {
      await updateMember(memberId.value, { name: form.name, email: form.email, status: form.status, role_ids: form.roleIds })
      success('成员资料已保存', form.name)
    }
  } catch (exception) {
    showError('保存失败', exception instanceof ApiError ? exception.body.message : '请检查输入后重试')
  } finally {
    saving.value = false
  }
}

async function disable(): Promise<void> {
  if (!confirm('确认停用该成员？该成员所有活动会话将被撤销。')) return
  try {
    await disableMember(memberId.value)
    success('成员已停用', '所有活动会话已撤销')
    await router.push('/settings/members')
  } catch (exception) {
    showError('停用失败', exception instanceof ApiError ? exception.body.message : '请稍后重试')
  }
}

async function resetPassword(): Promise<void> {
  try {
    const result = await resetMemberPassword(memberId.value)
    info('密码重置令牌已创建', `令牌有效期 ${Math.round(result.expires_in / 60)} 分钟；邮件通知接入后将自动发送。`)
  } catch (exception) {
    showError('操作失败', exception instanceof ApiError ? exception.body.message : '请稍后重试')
  }
}

onMounted(() => { void load() })
</script>

<template>
  <section class="data-page member-editor">
    <button class="editor-back" @click="router.push('/settings/members')"><ArrowLeft :size="15" />返回成员目录</button>
    <div class="page-heading">
      <div><span class="eyebrow">{{ mode === 'invite' ? 'NEW MEMBER · 成员入职' : 'MEMBER RECORD · ' + memberId }}</span><h1>{{ title }}</h1><p>{{ mode === 'invite' ? '发送企业邀请并配置成员角色。' : '维护成员资料、角色与账号状态。' }}</p></div>
      <div class="heading-actions"><button class="button secondary" @click="router.push('/settings/members')">取消</button><button class="button primary" :disabled="saving || loading" @click="save"><component :is="mode === 'invite' ? Send : Save" :size="15" />{{ saving ? '保存中...' : mode === 'invite' ? '发送邀请' : '保存更改' }}</button></div>
    </div>

    <div v-if="loading" class="empty-state">正在加载成员资料...</div>
    <div v-else class="editor-layout">
      <main>
        <article class="surface editor-section"><header><span><UserRound :size="18" /></span><div><h2>基本资料</h2><p>用于成员目录、协作通知和操作记录。</p></div></header><div class="editor-fields"><label><span>姓名</span><input v-model="form.name" placeholder="输入成员姓名" /></label><label><span>工作邮箱</span><input v-model="form.email" type="email" placeholder="name@company.com" /></label></div></article>
        <article class="surface editor-section"><header><span><ShieldCheck :size="18" /></span><div><h2>组织与权限</h2><p>角色决定后台功能权限，最终授权由后端执行。</p></div></header><div class="editor-fields"><label class="field-wide"><span>成员角色</span><select v-model="form.roleIds" multiple :size="Math.max(3, roles.length)"><option v-for="role in roles" :key="role.id" :value="Number(role.id)">{{ role.name }}</option></select><small>可按住 Command / Ctrl 选择多个角色。</small></label><label v-if="mode === 'edit'"><span>账号状态</span><select v-model="form.status"><option :value="1">正常</option><option :value="0">已停用</option></select></label></div></article>
      </main>
      <aside>
        <article class="surface editor-summary"><span class="member-avatar-large">{{ form.name?.[0] || '新' }}</span><h3>{{ form.name || '新成员' }}</h3><p>{{ form.email || '尚未填写邮箱' }}</p><dl><div><dt>角色</dt><dd>{{ form.roleIds.length }} 个已选</dd></div><div><dt>账号状态</dt><dd>{{ mode === 'invite' ? '等待邀请' : form.status === 1 ? '正常' : '已停用' }}</dd></div></dl></article>
        <article v-if="mode === 'edit'" class="surface danger-zone"><h3>账号管理</h3><button @click="resetPassword"><Mail :size="15" />生成密码重置令牌</button><button class="danger" @click="disable"><Trash2 :size="15" />停用成员账号</button></article>
      </aside>
    </div>
  </section>
</template>
