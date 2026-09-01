<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../../composables/useAuth'
import { useToast } from '../../composables/useToast'
import { useTopbarLayer } from '../../composables/useTopbarLayer'
import { useSystemBranding } from '../../composables/useSystemBranding'
import GlobalSearch from './GlobalSearch.vue'
import HelpMenu from './HelpMenu.vue'
import NotificationCenter from './NotificationCenter.vue'
import {
  BarChart3, Bell, Box, Check, ChevronDown, ClipboardList, FileText,
  LayoutDashboard, Megaphone, Menu, PackageSearch, Settings,
  TicketPercent, Truck, Users, Warehouse, X, Grid3X3, LogOut, ShieldCheck, UserRound, Globe2,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const { member, avatarPreview, hydrate, logout: signOut } = useAuth()
const { systemName, loadBranding } = useSystemBranding()
const { success } = useToast()
const mobileOpen = ref(false)
const {active:activeLayer,toggle:toggleLayer,close:closeLayer}=useTopbarLayer()
const accountOpen = computed(()=>activeLayer.value==='account')
const accountArea = ref<HTMLElement | null>(null)
const storeOpen = computed(()=>activeLayer.value==='store')
const storeArea = ref<HTMLElement | null>(null)
const stores = ['EBASE 全渠道','天猫旗舰店','京东自营店','抖音商城','品牌小程序']
const selectedStore = ref(localStorage.getItem('ebase:selected-store') || stores[0])
const navItems = [
  { to: '/', label: '控制台', icon: LayoutDashboard },
  { to: '/orders', label: '订单管理', icon: ClipboardList },
  { to: '/logistics', label: '物流履约', icon: Truck },
  { to: '/products', label: '产品管理', icon: PackageSearch },
  { to: '/inventory', label: '库存中心', icon: Warehouse },
  { to: '/users', label: '用户管理', icon: Users },
  { to: '/content', label: '内容中心', icon: FileText },
  { to: '/coupons', label: '优惠券管理', icon: TicketPercent },
  { to: '/marketing', label: '营销活动', icon: Megaphone },
  { to: '/reports', label: '数据报表', icon: BarChart3 },
  { to: '/storefront', label: '独立站', icon: Globe2 },
  { to: '/settings', label: '权限与系统设置', icon: Settings },
  { to: '/features', label: '功能地图', icon: Grid3X3 },
]
const pageTitle = computed(() => String(route.meta.title || '运营控制台'))
const avatarSource = computed(()=>avatarPreview.value||member.value?.avatar||'')
function isNavActive(path:string){return path==='/'?route.path==='/' : route.path===path||route.path.startsWith(`${path}/`)}
function openAccountPage(tab:string){closeLayer('account');router.push({path:'/member/profile',query:{tab}})}
async function logout(){closeLayer('account');await signOut();router.push('/login')}
function selectStore(store:string){selectedStore.value=store;closeLayer('store');localStorage.setItem('ebase:selected-store',store);success('经营视图已切换',`当前显示：${store}`)}
function toggleAccount(){toggleLayer('account')}
function toggleStore(){toggleLayer('store')}
function closeMenusOnOutside(event:MouseEvent){const target=event.target as Node;if(accountOpen.value&&accountArea.value&&!accountArea.value.contains(target))closeLayer('account');if(storeOpen.value&&storeArea.value&&!storeArea.value.contains(target))closeLayer('store')}
function closeMenusOnEscape(event:KeyboardEvent){if(event.key==='Escape')closeLayer()}
onMounted(async()=>{
  await Promise.all([hydrate(true), loadBranding()])
  document.title = systemName.value
  document.addEventListener('click',closeMenusOnOutside)
  document.addEventListener('keydown',closeMenusOnEscape)
})
onBeforeUnmount(()=>{document.removeEventListener('click',closeMenusOnOutside);document.removeEventListener('keydown',closeMenusOnEscape)})
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar" :class="{ open: mobileOpen }">
      <div class="brand">
        <div class="brand-mark">AC</div>
        <div><strong>AC · 清透商业</strong><span>多商户运营系统</span></div>
        <button class="mobile-close" aria-label="关闭导航" @click="mobileOpen = false"><X :size="18" /></button>
      </div>
      <nav class="nav-list">
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to" class="nav-item" :class="{'is-active':isNavActive(item.to)}" @click="mobileOpen = false">
          <component :is="item.icon" :size="18" :stroke-width="1.8" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>
      <div ref="accountArea" class="sidebar-account">
        <Transition name="account-menu">
          <div v-if="accountOpen" class="account-menu" role="menu" aria-label="成员账户菜单">
            <button :class="{active:route.path==='/member/profile'&&(!route.query.tab||route.query.tab==='profile')}" role="menuitem" @click="openAccountPage('profile')"><UserRound :size="17"/><span>个人资料</span></button>
            <button :class="{active:route.path==='/member/profile'&&route.query.tab==='security'}" role="menuitem" @click="openAccountPage('security')"><ShieldCheck :size="17"/><span>账号安全</span></button>
            <button :class="{active:route.path==='/member/profile'&&route.query.tab==='notifications'}" role="menuitem" @click="openAccountPage('notifications')"><Bell :size="17"/><span>通知偏好</span></button>
            <span class="account-menu-divider"></span>
            <button class="account-menu-logout" role="menuitem" @click="logout"><LogOut :size="17"/><span>退出当前账号</span></button>
          </div>
        </Transition>
        <button class="sidebar-profile" :class="{expanded:accountOpen}" aria-haspopup="menu" :aria-expanded="accountOpen" @click="toggleAccount">
          <div class="avatar"><img v-if="avatarSource" :src="avatarSource" alt=""/><template v-else>{{ member?.name?.[0] || '用' }}</template></div>
          <div><strong>{{ member?.name || '加载中' }}</strong><span>{{ member?.email || '成员账号' }}</span></div>
          <ChevronDown class="account-chevron" :size="16" />
        </button>
      </div>
    </aside>

    <div v-if="mobileOpen" class="sidebar-scrim" @click="mobileOpen = false"></div>

    <div class="workspace">
      <header class="topbar">
        <div class="topbar-left">
          <button class="mobile-menu" aria-label="打开导航" @click="mobileOpen = true"><Menu :size="20" /></button>
          <div ref="storeArea" class="store-switcher-wrap">
            <button class="store-switcher" :class="{expanded:storeOpen}" aria-haspopup="menu" :aria-expanded="storeOpen" @click="toggleStore"><Box :size="17" /><span>{{selectedStore}}</span><ChevronDown class="store-chevron" :size="15" /></button>
            <Transition name="store-menu">
              <div v-if="storeOpen" class="store-menu" role="menu" aria-label="切换店铺与渠道">
                <div class="store-menu-heading"><strong>切换经营视图</strong><span>当前页面数据将按所选渠道展示</span></div>
                <button v-for="store in stores" :key="store" :class="{active:selectedStore===store}" role="menuitemradio" :aria-checked="selectedStore===store" @click="selectStore(store)"><span class="store-icon"><Box :size="15"/></span><span>{{store}}</span><Check v-if="selectedStore===store" :size="15"/></button>
              </div>
            </Transition>
          </div>
          <GlobalSearch />
        </div>
        <div class="topbar-actions">
          <HelpMenu />
          <NotificationCenter />
          <span class="topbar-divider"></span>
          <button class="topbar-avatar" aria-label="打开个人资料" @click="router.push('/member/profile')"><img v-if="avatarSource" :src="avatarSource" alt=""/><template v-else>{{ member?.name?.[0] || '用' }}</template></button>
        </div>
      </header>
      <main class="page-canvas" :aria-label="pageTitle">
        <RouterView />
      </main>
    </div>
  </div>
</template>
