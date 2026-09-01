<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Download, Filter, MoreHorizontal, Plus, Search, ShieldCheck, UserCheck, UserRoundX, Users } from 'lucide-vue-next'
import { ApiError } from '../api/client'
import { listMembers, type AdminMember } from '../api/members'
import { useToast } from '../composables/useToast'
import TableState from '../components/common/TableState.vue'

const router = useRouter()
const { error: showError, info } = useToast()
const query = ref('')
const tab = ref('全部成员')
const tabs = ['全部成员', '正常', '已停用']
const members = ref<AdminMember[]>([])
const total = ref(0)
const loading = ref(true)
const loadError = ref('')

const activeMembers = computed(() => members.value.filter(member => member.status === 1).length)
const disabledMembers = computed(() => members.value.filter(member => member.status === 0).length)

function statusFilter(): number | undefined {
  if (tab.value === '正常') return 1
  if (tab.value === '已停用') return 0
  return undefined
}

async function loadMembers(): Promise<void> {
  loading.value = true
  loadError.value = ''
  try {
    const data = await listMembers({ page: 1, page_size: 100, keyword: query.value, status: statusFilter() })
    members.value = data.items
    total.value = data.pagination.total
  } catch (exception) {
    loadError.value = exception instanceof ApiError ? exception.body.message : '成员目录加载失败'
  } finally {
    loading.value = false
  }
}

function memberRole(member: AdminMember): string {
  return member.is_super === 1 ? '超级管理员' : member.roles.map(role => role.name).join('、') || '未分配角色'
}

function displayStatus(member: AdminMember): string {
  return member.status === 1 ? '正常' : '已停用'
}

function exportMembers(): void {
  info('导出任务未创建', '当前后端尚未提供成员 CSV 导出接口。')
}

watch([query, tab], () => { void loadMembers() })
onMounted(() => { void loadMembers() })
</script>

<template>
  <section class="data-page member-page">
    <div class="page-heading">
      <div><span class="eyebrow">ORGANIZATION · 组织权限</span><h1>成员目录</h1><p>管理内部员工账号、角色与账号状态。</p></div>
      <div class="heading-actions">
        <button class="button secondary" @click="exportMembers"><Download :size="15" />导出成员</button>
        <button class="button primary" @click="router.push('/settings/members/invite')"><Plus :size="16" />邀请成员</button>
      </div>
    </div>

    <div class="metric-strip member-metrics">
      <div class="metric-item"><span>全部成员</span><strong>{{ total }}</strong><small>当前已加载目录</small></div>
      <div class="metric-item"><span>正常成员</span><strong>{{ activeMembers }}</strong><small>可正常访问后台</small></div>
      <div class="metric-item"><span>已停用</span><strong>{{ disabledMembers }}</strong><small>会话已撤销</small></div>
      <div class="metric-item"><span>超级管理员</span><strong>{{ members.filter(m => m.is_super === 1).length }}</strong><small>拥有全部权限</small></div>
    </div>

    <div class="member-tabs"><button v-for="item in tabs" :key="item" :class="{ active: tab === item }" @click="tab = item">{{ item }}</button></div>

    <article class="surface member-table-card">
      <header class="member-toolbar">
        <label class="module-search"><Search :size="16" /><input v-model="query" placeholder="搜索姓名或邮箱" /></label>
        <button class="button secondary" @click="loadMembers"><Filter :size="15" />刷新</button>
      </header>

      <TableState v-if="loading" state="loading" title="正在加载成员目录" description="正在同步成员、角色和账号状态。" />
      <TableState v-else-if="loadError" state="error" title="成员目录加载失败" :description="loadError"><template #action><button class="button secondary" @click="loadMembers">重新加载</button></template></TableState>
      <TableState v-else-if="!members.length" state="empty" :filtered="Boolean(query || tab !== '全部成员')" title="没有找到成员" description="当前条件下没有成员，调整状态或搜索关键词后再试。"><template #action><button v-if="query || tab !== '全部成员'" class="button secondary" @click="query='';tab='全部成员'">清除筛选</button></template></TableState>
      <div v-else class="table-scroll">
        <table class="member-table">
          <thead><tr><th>成员</th><th>角色</th><th>账号状态</th><th>最近登录</th><th></th></tr></thead>
          <tbody>
            <tr v-for="member in members" :key="member.id" @click="router.push('/settings/members/' + member.id)">
              <td><div class="member-identity"><span>{{ member.name[0] }}</span><div><strong>{{ member.name }}</strong><small>{{ member.email }}</small></div></div></td>
              <td>{{ memberRole(member) }}</td>
              <td><span class="member-status" :data-status="displayStatus(member)">{{ displayStatus(member) }}</span></td>
              <td class="muted">{{ member.last_login_at || '从未登录' }}</td>
              <td><button class="row-action" @click.stop="router.push('/settings/members/' + member.id)"><MoreHorizontal :size="16" /></button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <footer class="table-footer"><span>共 {{ total }} 位成员</span></footer>
    </article>

    <div class="member-insights">
      <div><Users :size="18" /><span><b>{{ total }} 位成员</b>来自真实后端目录</span></div>
      <div><ShieldCheck :size="18" /><span><b>{{ members.filter(m => m.is_super === 1).length }} 位管理员</b>拥有全部系统权限</span></div>
      <div><UserCheck :size="18" /><span><b>{{ activeMembers }} 个账号</b>当前可正常访问</span></div>
      <div><UserRoundX :size="18" /><span><b>{{ disabledMembers }} 个账号</b>已停用</span></div>
    </div>
  </section>
</template>
