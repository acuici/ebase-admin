<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  BarChart3, Bell, Box, ChevronDown, CircleHelp, ClipboardList, FileText,
  Gift, LayoutDashboard, Megaphone, Menu, PackageSearch, Search, Settings,
  TicketPercent, Truck, Users, Warehouse, X, Grid3X3,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const mobileOpen = ref(false)
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
  { to: '/settings', label: '权限与系统设置', icon: Settings },
  { to: '/features', label: '功能地图', icon: Grid3X3 },
]
const pageTitle = computed(() => String(route.meta.title || '运营控制台'))
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
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to" class="nav-item" @click="mobileOpen = false">
          <component :is="item.icon" :size="18" :stroke-width="1.8" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>
      <button class="sidebar-profile" @click="router.push('/member/profile')">
        <div class="avatar">林</div>
        <div><strong>林知夏</strong><span>运营总监</span></div>
        <ChevronDown :size="16" />
      </button>
    </aside>

    <div v-if="mobileOpen" class="sidebar-scrim" @click="mobileOpen = false"></div>

    <div class="workspace">
      <header class="topbar">
        <div class="topbar-left">
          <button class="mobile-menu" aria-label="打开导航" @click="mobileOpen = true"><Menu :size="20" /></button>
          <button class="store-switcher"><Box :size="17" /><span>EBASE 全渠道</span><ChevronDown :size="15" /></button>
          <label class="global-search">
            <Search :size="17" />
            <input placeholder="搜索订单、商品或用户..." />
            <kbd>⌘ K</kbd>
          </label>
        </div>
        <div class="topbar-actions">
          <button aria-label="帮助"><CircleHelp :size="19" /></button>
          <button class="notification" aria-label="通知"><Bell :size="19" /><i></i></button>
          <span class="topbar-divider"></span>
          <button class="topbar-avatar" aria-label="打开个人资料" @click="router.push('/member/profile')">林</button>
        </div>
      </header>
      <main class="page-canvas" :aria-label="pageTitle">
        <RouterView />
      </main>
    </div>
  </div>
</template>
