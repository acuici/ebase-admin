<script setup lang="ts">
import { computed, ref } from 'vue'
import { ArrowRight, CalendarDays, ChevronDown, Download, Plus, RotateCcw, Search } from 'lucide-vue-next'

const range = ref<'14d' | '30d'>('14d')
const points = [42, 48, 45, 56, 53, 64, 60, 72, 66, 78, 73, 86, 79, 91]
const previous = [38, 43, 42, 48, 49, 54, 51, 59, 57, 64, 62, 70, 68, 74]
const linePath = (data: number[]) => data.map((value, index) => `${index ? 'L' : 'M'} ${24 + index * 48} ${200 - value * 1.55}`).join(' ')
const currentPath = computed(() => linePath(points))
const previousPath = computed(() => linePath(previous))

const metrics = [
  { label: '今日成交额', value: '¥286,742.80', delta: '+12.6%', note: '较昨日' },
  { label: '支付订单', value: '1,846', delta: '+8.4%', note: '较昨日' },
  { label: '客单价', value: '¥155.33', delta: '+3.1%', note: '较昨日' },
  { label: '退款率', value: '1.82%', delta: '-0.3%', note: '较昨日', positive: true },
  { label: '待发货', value: '126', delta: '18 单超时', note: '', warning: true },
]

const todos = [
  ['审核退款申请', '12', '高', '周宁', '8 分钟前'],
  ['处理异常订单', '7', '高', '陈曦', '16 分钟前'],
  ['回复客户咨询', '24', '中', '赵倩', '32 分钟前'],
  ['审核商家入驻资料', '5', '中', '许言', '1 小时前'],
  ['发布内容草稿', '3', '低', '林知夏', '2 小时前'],
  ['配置即将到期优惠券', '6', '中', '苏然', '3 小时前'],
]

const stockRisks = [
  ['智能手表 Pro · 黑色', 'SWP-BLK-01', 4, 20, '1.2 天'],
  ['机械键盘 K8 · 红轴', 'MKB-K8-RED', 0, 50, '已售罄'],
  ['人体工学鼠标 M3 · 白色', 'ERG-M3-WHT', 12, 30, '2.8 天'],
  ['降噪耳机 Air X · 银色', 'AIR-X-SLV', 8, 25, '1.9 天'],
  ['便携咖啡机 C2 · 米白', 'CFE-C2-CRM', 15, 40, '3.4 天'],
  ['旅行箱 Voyage · 24寸', 'VYG-24-BLK', 6, 20, '2.1 天'],
]

const orders = [
  ['#EB202609010846', '张玥', '天猫旗舰店', 'AirPods Pro 2 降噪耳机', '¥1,899.00', '支付宝', '已发货', '10:42'],
  ['#EB202609010845', '李娜', '京东自营店', 'Dyson V12 Detect Slim', '¥4,199.00', '微信支付', '待发货', '10:28'],
  ['#EB202609010844', '王磊', '抖音商城', 'Aeron 人体工学办公椅', '¥12,500.00', '抖音支付', '待发货', '10:15'],
  ['#EB202609010843', '陈曦', '品牌小程序', '机械键盘 K8 红轴', '¥799.00', '微信支付', '运输中', '09:56'],
  ['#EB202609010842', '周宁', '天猫旗舰店', '智能手表 Pro 黑色', '¥2,499.00', '花呗', '已完成', '09:41'],
  ['#EB202609010841', '赵倩', '京东自营店', '旅行箱 Voyage 24寸', '¥1,299.00', '京东支付', '售后中', '09:28'],
  ['#EB202609010840', '许言', '抖音商城', '便携咖啡机 C2 米白', '¥699.00', '抖音支付', '待付款', '09:16'],
  ['#EB202609010839', '苏然', '品牌小程序', '降噪耳机 Air X 银色', '¥1,599.00', '微信支付', '已完成', '09:03'],
]
</script>

<template>
  <section class="dashboard">
    <div class="page-heading">
      <div>
        <span class="eyebrow">2026年9月1日 · 数据更新于 10:32</span>
        <h1>运营控制台</h1>
        <p>聚焦今天最重要的经营变化与待处理事项。</p>
      </div>
      <div class="heading-actions">
        <button class="button secondary"><CalendarDays :size="16" />今日<ChevronDown :size="15" /></button>
        <button class="button secondary"><Download :size="16" />导出报表</button>
        <button class="button primary"><Plus :size="16" />新建活动</button>
      </div>
    </div>

    <section class="metric-strip" aria-label="今日核心指标">
      <article v-for="metric in metrics" :key="metric.label" class="metric-item">
        <span>{{ metric.label }}</span>
        <strong>{{ metric.value }}</strong>
        <small :class="{ warning: metric.warning }"><b>{{ metric.delta }}</b>{{ metric.note }}</small>
      </article>
    </section>

    <div class="analysis-grid">
      <article class="surface trend-card">
        <header class="card-heading">
          <div><h2>成交趋势</h2><p>本期成交额 <b>¥3,864,290</b> · 同比 <em>+12.6%</em></p></div>
          <div class="segmented"><button :class="{ active: range === '14d' }" @click="range = '14d'">14 天</button><button :class="{ active: range === '30d' }" @click="range = '30d'">30 天</button></div>
        </header>
        <div class="chart-wrap">
          <svg viewBox="0 0 680 220" role="img" aria-label="14 天成交趋势折线图">
            <g class="chart-grid"><line v-for="y in [40, 80, 120, 160, 200]" :key="y" x1="24" :y1="y" x2="648" :y2="y" /></g>
            <path class="previous-line" :d="previousPath" />
            <path class="current-line" :d="currentPath" />
            <circle cx="552" cy="66" r="5" class="chart-point" />
            <g class="chart-labels"><text x="24" y="216">08-19</text><text x="168" y="216">08-22</text><text x="312" y="216">08-25</text><text x="456" y="216">08-28</text><text x="618" y="216">09-01</text></g>
          </svg>
        </div>
      </article>

      <article class="surface status-card">
        <header class="card-heading"><div><h2>订单结构</h2><p>共 1,972 笔有效订单</p></div><button class="icon-button"><RotateCcw :size="16" /></button></header>
        <div class="status-list">
          <div v-for="item in [['待付款',126,16],['待发货',842,88],['运输中',651,68],['今日送达',320,34],['退货 / 异常',33,7]]" :key="String(item[0])">
            <span>{{ item[0] }}</span><strong>{{ item[1] }}</strong><i><b :style="{ width: item[2] + '%' }"></b></i>
          </div>
        </div>
        <p class="insight"><span></span>履约压力集中在待发货，较昨日增加 8.4%</p>
      </article>
    </div>

    <div class="operations-grid">
      <article class="surface compact-card">
        <header class="card-heading"><div><h2>今日待办</h2><p>按业务优先级排序</p></div><button class="text-button">查看全部 <ArrowRight :size="15" /></button></header>
        <ul class="todo-list">
          <li v-for="todo in todos" :key="todo[0]"><span class="priority" :data-level="todo[2]"></span><div><strong>{{ todo[0] }} <b>{{ todo[1] }}</b></strong><small>{{ todo[3] }} · {{ todo[4] }}</small></div><ArrowRight :size="15" /></li>
        </ul>
      </article>
      <article class="surface compact-card">
        <header class="card-heading"><div><h2>库存风险</h2><p>6 个 SKU 需要优先处理</p></div><button class="text-button">库存中心 <ArrowRight :size="15" /></button></header>
        <ul class="stock-list">
          <li v-for="(item, index) in stockRisks" :key="item[1]"><span class="product-thumb">{{ index + 1 }}</span><div><strong>{{ item[0] }}</strong><small>{{ item[1] }} · 预计 {{ item[4] }}</small></div><p><b :class="{ critical: Number(item[2]) < 5 }">{{ item[2] }}</b><span>/ {{ item[3] }}</span></p><button>补货</button></li>
        </ul>
      </article>
    </div>

    <article class="surface orders-card">
      <header class="orders-toolbar">
        <div><h2>最新订单</h2><p>今天新增 1,846 笔订单</p></div>
        <div><label class="table-search"><Search :size="15" /><input placeholder="搜索订单" /></label><button class="button secondary">全部状态<ChevronDown :size="14" /></button><button class="text-button">查看全部订单 <ArrowRight :size="15" /></button></div>
      </header>
      <div class="table-scroll"><table><thead><tr><th>订单号</th><th>客户</th><th>渠道 / 店铺</th><th>商品</th><th class="align-right">金额</th><th>支付方式</th><th>状态</th><th>下单时间</th></tr></thead><tbody><tr v-for="order in orders" :key="order[0]"><td class="mono order-id">{{ order[0] }}</td><td>{{ order[1] }}</td><td>{{ order[2] }}</td><td class="product-name">{{ order[3] }}</td><td class="mono align-right">{{ order[4] }}</td><td>{{ order[5] }}</td><td><span class="status-tag" :data-status="order[6]">{{ order[6] }}</span></td><td class="muted">{{ order[7] }}</td></tr></tbody></table></div>
    </article>
  </section>
</template>
