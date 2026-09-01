import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../views/DashboardView.vue'
import ModuleView from '../views/ModuleView.vue'
import WorkflowView from '../views/WorkflowView.vue'
import FeatureHubView from '../views/FeatureHubView.vue'
import SecondaryView from '../views/SecondaryView.vue'
import SettingsView from '../views/SettingsView.vue'
import LoginView from '../views/LoginView.vue'
import ForgotPasswordView from '../views/ForgotPasswordView.vue'
import MemberDirectoryView from '../views/MemberDirectoryView.vue'
import MemberEditorView from '../views/MemberEditorView.vue'
import MemberProfileView from '../views/MemberProfileView.vue'

const modules = [
  ['orders', '订单管理'],
  ['logistics', '物流履约'],
  ['products', '产品管理'],
  ['inventory', '库存中心'],
  ['users', '用户管理'],
  ['content', '内容中心'],
  ['coupons', '优惠券管理'],
  ['marketing', '营销活动'],
  ['reports', '数据报表'],
] as const

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: LoginView, meta: { title: '成员登录', layout: 'auth' } },
    { path: '/forgot-password', name: 'forgot-password', component: ForgotPasswordView, meta: { title: '找回密码', layout: 'auth' } },
    { path: '/', name: 'dashboard', component: DashboardView, meta: { title: '运营控制台' } },
    { path: '/features', name: 'features', component: FeatureHubView, meta: { title: '功能地图' } },
    { path: '/features/:type', name: 'secondary-feature', component: SecondaryView, props: true, meta: { title: '二级功能' } },
    { path: '/settings', name: 'settings', component: SettingsView, meta: { title: '权限与系统设置' } },
    { path: '/settings/members', name: 'member-directory', component: MemberDirectoryView, meta: { title: '成员目录' } },
    { path: '/settings/members/invite', name: 'member-invite', component: MemberEditorView, props: { mode: 'invite' }, meta: { title: '邀请成员' } },
    { path: '/settings/members/:id', name: 'member-detail', component: MemberEditorView, props: { mode: 'edit' }, meta: { title: '成员详情' } },
    { path: '/member/profile', name: 'member-profile', component: MemberProfileView, meta: { title: '个人资料' } },
    ...modules.map(([path, title]) => ({
      path: `/${path}`,
      name: path,
      component: ModuleView,
      props: { title },
      meta: { title },
    })),
    { path: '/orders/:id', name: 'order-detail', component: WorkflowView, props: { type: 'order' }, meta: { title: '订单详情' } },
    { path: '/logistics/:id', name: 'logistics-detail', component: WorkflowView, props: { type: 'logistics' }, meta: { title: '异常运单处理' } },
    { path: '/products/new', name: 'product-create', component: WorkflowView, props: { type: 'product' }, meta: { title: '新建商品' } },
    { path: '/inventory/restock', name: 'inventory-restock', component: WorkflowView, props: { type: 'inventory' }, meta: { title: '补货计划' } },
    { path: '/users/:id', name: 'user-profile', component: WorkflowView, props: { type: 'user' }, meta: { title: '用户画像' } },
    { path: '/content/new', name: 'content-create', component: WorkflowView, props: { type: 'content' }, meta: { title: '新建内容' } },
    { path: '/coupons/new', name: 'coupon-create', component: WorkflowView, props: { type: 'coupon' }, meta: { title: '新建优惠券' } },
    { path: '/marketing/new', name: 'marketing-create', component: WorkflowView, props: { type: 'marketing' }, meta: { title: '新建营销活动' } },
    { path: '/reports/analysis', name: 'report-analysis', component: WorkflowView, props: { type: 'report' }, meta: { title: '经营分析详情' } },
    { path: '/settings/roles/:id', name: 'role-edit', component: WorkflowView, props: { type: 'settings' }, meta: { title: '角色权限编辑' } },
  ],
})
