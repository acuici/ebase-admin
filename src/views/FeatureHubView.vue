<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRight, Boxes, ChartNoAxesCombined, CircleDollarSign, Clock3, FileClock, FolderKanban, PackageOpen, Search, ShieldCheck, Star, Tags, UsersRound, Warehouse, X } from 'lucide-vue-next'

const router=useRouter(); const query=ref(''); const activeGroup=ref('全部功能')
const features=[
{id:'refunds',name:'售后退款中心',group:'交易与履约',description:'退款、退货、换货与平台争议',icon:CircleDollarSign,status:'运行正常',usage:'今日处理 86 单'},
{id:'warehouses',name:'仓库管理',group:'供应链',description:'仓库容量、作业效率与库存分布',icon:Warehouse,status:'运行正常',usage:'6 个启用仓库'},
{id:'categories',name:'类目与品牌',group:'商品中心',description:'类目树、品牌授权与渠道映射',icon:Tags,status:'待处理 42',usage:'2,911 个商品'},
{id:'suppliers',name:'供应商管理',group:'供应链',description:'供应商准入、交期和质量绩效',icon:PackageOpen,status:'预警 9',usage:'128 家供应商'},
{id:'segments',name:'用户分群',group:'用户运营',description:'动态人群、标签与营销应用',icon:UsersRound,status:'运行正常',usage:'86 个人群'},
{id:'assets',name:'素材库',group:'内容运营',description:'图片、视频与品牌数字资产',icon:FolderKanban,status:'待审核 32',usage:'18,642 个素材'},
{id:'coupon-delivery',name:'批量发券',group:'优惠与营销',description:'人群选择、投放和触达任务',icon:Boxes,status:'运行正常',usage:'今日 12 个任务'},
{id:'approvals',name:'营销审批中心',group:'优惠与营销',description:'预算、优惠机制和投放审批',icon:ShieldCheck,status:'待审批 7',usage:'平均 2.6 小时'},
{id:'report-builder',name:'自定义报表',group:'数据与系统',description:'指标、维度与定时订阅',icon:ChartNoAxesCombined,status:'运行正常',usage:'24 个报表'},
{id:'audit-logs',name:'操作日志',group:'数据与系统',description:'关键操作与安全审计',icon:FileClock,status:'高风险 4',usage:'今日 1,286 条'},
]
const groups=['全部功能',...new Set(features.map(f=>f.group))]
const favorites=ref<string[]>(JSON.parse(localStorage.getItem('ebase:feature-favorites')||'["refunds","report-builder"]'))
const recent=ref<string[]>(JSON.parse(localStorage.getItem('ebase:feature-recent')||'["suppliers","refunds","audit-logs"]'))
const filtered=computed(()=>features.filter(f=>(activeGroup.value==='全部功能'||f.group===activeGroup.value)&&(`${f.name}${f.description}${f.group}`.toLowerCase().includes(query.value.trim().toLowerCase()))))
const favoriteFeatures=computed(()=>favorites.value.map(id=>features.find(f=>f.id===id)).filter(Boolean))
const recentFeatures=computed(()=>recent.value.map(id=>features.find(f=>f.id===id)).filter(Boolean).slice(0,4))
function toggleFavorite(id:string){favorites.value=favorites.value.includes(id)?favorites.value.filter(v=>v!==id):[...favorites.value,id];localStorage.setItem('ebase:feature-favorites',JSON.stringify(favorites.value))}
function openFeature(id:string){recent.value=[id,...recent.value.filter(v=>v!==id)].slice(0,6);localStorage.setItem('ebase:feature-recent',JSON.stringify(recent.value));router.push(`/features/${id}`)}
</script>

<template><section class="feature-hub enhanced-hub">
<div class="page-heading"><div><span class="eyebrow">SECONDARY OPERATIONS</span><h1>功能地图</h1><p>查找、收藏和访问主业务之外的运营工具。</p></div><label class="hub-search"><Search :size="17"/><input v-model="query" placeholder="搜索功能、业务或关键词"/><button v-if="query" @click="query=''" aria-label="清空"><X :size="15"/></button><kbd>⌘ K</kbd></label></div>
<section class="hub-overview"><article><span>可用功能</span><strong>10</strong><small>覆盖 7 个业务域</small></article><article><span>我的收藏</span><strong>{{favorites.length}}</strong><small>常用功能快捷入口</small></article><article><span>待处理事项</span><strong>94</strong><small>审批、预警与审核</small></article><article><span>系统状态</span><strong class="healthy">正常</strong><small>所有服务可用</small></article></section>

<section v-if="!query&&recentFeatures.length" class="hub-section"><header><div><Clock3 :size="17"/><h2>最近访问</h2></div><span>基于本机访问记录</span></header><div class="recent-grid"><button v-for="f in recentFeatures" :key="f!.id" @click="openFeature(f!.id)"><div class="mini-icon"><component :is="f!.icon" :size="17"/></div><div><strong>{{f!.name}}</strong><small>{{f!.group}} · {{f!.usage}}</small></div><ArrowRight :size="15"/></button></div></section>

<section v-if="!query&&favoriteFeatures.length" class="hub-section"><header><div><Star :size="17"/><h2>我的收藏</h2></div><span>{{favoriteFeatures.length}} 个常用功能</span></header><div class="favorite-strip"><button v-for="f in favoriteFeatures" :key="f!.id" @click="openFeature(f!.id)"><component :is="f!.icon" :size="17"/><span>{{f!.name}}</span><ArrowRight :size="14"/></button></div></section>

<div class="hub-filter"><div class="group-tabs"><button v-for="group in groups" :key="group" :class="{active:activeGroup===group}" @click="activeGroup=group">{{group}}<span>{{group==='全部功能'?features.length:features.filter(f=>f.group===group).length}}</span></button></div><span>显示 {{filtered.length}} 个功能</span></div>
<div v-if="filtered.length" class="feature-grid"><article v-for="f in filtered" :key="f.id" class="feature-card surface" @click="openFeature(f.id)"><button class="favorite-button" :class="{active:favorites.includes(f.id)}" aria-label="收藏" @click.stop="toggleFavorite(f.id)"><Star :size="16" :fill="favorites.includes(f.id)?'currentColor':'none'"/></button><div class="feature-icon"><component :is="f.icon" :size="20"/></div><span>{{f.group}}</span><h2>{{f.name}}</h2><p>{{f.description}}</p><div class="feature-meta"><small>{{f.usage}}</small><b :class="{warning:!f.status.includes('正常')}">{{f.status}}</b></div><div class="feature-enter">进入功能<ArrowRight :size="15"/></div></article></div>
<div v-else class="hub-empty"><Search :size="24"/><h2>没有找到相关功能</h2><p>尝试更换关键词或选择其他业务分组。</p><button @click="query='';activeGroup='全部功能'">清除筛选</button></div>
</section></template>
