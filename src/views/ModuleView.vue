<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRight, ChevronDown, Download, MoreHorizontal, Plus, Search, SlidersHorizontal } from 'lucide-vue-next'
import { listOrders, type Order } from '../api/orders'
import { ApiError } from '../api/client'
import { useToast } from '../composables/useToast'
import { moduleConfigs } from '../data/moduleConfigs'
const props=defineProps<{title:string}>()
const router=useRouter()
const { success, error: showError, info }=useToast()
const query=ref(''); const activeTab=ref(0); const selectedRow=ref(0); const selected=ref<number[]>([])
const config=computed(()=>moduleConfigs[props.title])
const paths:Record<string,{primary:string;detail:string}>={'订单管理':{primary:'/orders/new',detail:'/orders/EB202609010846'},'物流履约':{primary:'/logistics/SF128604780',detail:'/logistics/SF128604780'},'产品管理':{primary:'/products/new',detail:'/products/new'},'库存中心':{primary:'/inventory/restock',detail:'/inventory/restock'},'用户管理':{primary:'/users/U-286420',detail:'/users/U-286420'},'内容中心':{primary:'/content/new',detail:'/content/new'},'优惠券管理':{primary:'/coupons/new',detail:'/coupons/new'},'营销活动':{primary:'/marketing/new',detail:'/marketing/new'},'数据报表':{primary:'/reports/analysis',detail:'/reports/analysis'},'权限与系统设置':{primary:'/settings/roles/operator',detail:'/settings/roles/operator'}}
const workflow=computed(()=>paths[props.title])
const remoteOrders=ref<Order[]>([]); const orderTotal=ref(0); const loading=ref(false); const loadError=ref('')
const isOrders=computed(()=>props.title==='订单管理')
const statusMap:Record<string,string|undefined>={'全部订单':undefined,'待付款':'pending_payment','待发货':'paid','运输中':'shipped','已完成':'completed','售后中':undefined}
function displayOrderStatus(status:string){return ({pending_payment:'待付款',paid:'待发货',processing:'处理中',shipped:'运输中',completed:'已完成',cancelled:'已取消'} as Record<string,string>)[status]||status}
function displayChannel(channel:string){return ({storefront:'独立站',tmall:'天猫',jd:'京东',douyin:'抖音',wechat_miniapp:'品牌小程序'} as Record<string,string>)[channel]||channel}
const rows=computed(()=>{if(!isOrders.value)return filteredStaticRows();return remoteOrders.value.map(order=>[order.order_no,order.external_order_no||'后台订单',`${order.items?.[0]?.product_name||'订单商品'} · ${order.items?.[0]?.quantity||0} 件`,displayChannel(order.channel_type||'storefront'),`${order.currency} ${order.total_amount}`, '—',displayOrderStatus(order.status),order.created_at])})
function filteredStaticRows(){const k=query.value.trim().toLowerCase();const tab=config.value.tabs[activeTab.value];let result=config.value.rows;if(activeTab.value>0)result=result.filter(r=>r.some(v=>String(v).includes(tab)||tab.includes(String(v))));return k?result.filter(r=>r.some(v=>String(v).toLowerCase().includes(k))):result}
const allSelected=computed(()=>rows.value.length>0&&selected.value.length===rows.value.length)
async function loadOrders(){loading.value=true;loadError.value='';try{const data=await listOrders({page:1,page_size:100,keyword:query.value,status:statusMap[config.value.tabs[activeTab.value]]});remoteOrders.value=data.items;orderTotal.value=data.pagination.total}catch(e){loadError.value=e instanceof ApiError?e.body.message:'订单加载失败'}finally{loading.value=false}}
function toggleAll(){selected.value=allSelected.value?[]:rows.value.map((_,i)=>i)}
function openOrder(order:Order){router.push(`/orders/${order.id}`)}
function exportData(){info('导出任务未创建','当前后端尚未提供订单导出接口。')}
watch([query,activeTab],()=>{if(isOrders.value)void loadOrders()},{immediate:true})
</script>
<template><section v-if="config" class="data-page">
<div class="page-heading"><div><span class="eyebrow">{{config.eyebrow}}</span><h1>{{title}}</h1><p>{{config.description}}</p></div><div class="heading-actions"><button class="button secondary"><Download :size="16"/>导出</button><button class="button primary" @click="router.push(workflow.primary)"><Plus :size="16"/>{{config.primaryAction}}</button></div></div>
<section class="metric-strip module-metrics"><article v-for="m in config.metrics" :key="m.label" class="metric-item"><span>{{m.label}}</span><strong>{{m.value}}</strong><small><b :class="{negative:m.negative}">{{m.delta}}</b>{{m.note}}</small></article></section>
<div class="module-tabs"><button v-for="(tab,i) in config.tabs" :key="tab" :class="{active:activeTab===i}" @click="activeTab=i">{{tab}}<span v-if="config.tabCounts[i]">{{config.tabCounts[i]}}</span></button></div>
<div class="module-layout"><article class="surface module-table-card"><div class="module-toolbar"><label class="module-search"><Search :size="16"/><input v-model="query" :placeholder="config.searchPlaceholder"/></label><div class="filter-group"><button v-for="f in config.filters" :key="f" class="button secondary">{{f}}<ChevronDown :size="14"/></button><button class="button secondary icon-only"><SlidersHorizontal :size="16"/></button></div></div>
<div v-if="selected.length" class="batch-bar"><strong>已选择 {{selected.length}} 项</strong><button>批量更新状态</button><button>批量导出</button><button @click="selected=[]">取消选择</button></div>
<div v-if="loading" class="empty-state">正在加载订单...</div><div v-else-if="loadError" class="empty-state">{{loadError}}<button class="button secondary" @click="loadOrders">重试</button></div><div v-else-if="!rows.length" class="empty-state">当前筛选条件下暂无订单</div>
<div v-else class="table-scroll"><table class="module-table"><thead><tr><th class="check-cell"><input type="checkbox" :checked="allSelected" @change="toggleAll"/></th><th v-for="c in config.columns" :key="c">{{c}}</th><th class="action-cell">操作</th></tr></thead><tbody><tr v-for="(row,ri) in rows" :key="ri" :class="{selected:selectedRow===ri}" @click="selectedRow=ri"><td class="check-cell"><input v-model="selected" type="checkbox" :value="ri" @click.stop/></td><td v-for="(cell,i) in row" :key="i" :class="{mono:i===0,'strong-cell':i===1}"><span v-if="i===config.statusColumn" class="status-tag" :data-status="cell">{{cell}}</span><template v-else>{{cell}}</template></td><td class="action-cell"><button class="row-action" @click.stop="isOrders ? openOrder(remoteOrders[ri]) : router.push(workflow.detail)"><MoreHorizontal :size="16"/></button></td></tr></tbody></table></div>
<footer class="table-footer"><span>共 {{isOrders ? orderTotal : config.total}} 条记录</span><div><button>上一页</button><button class="active">1</button><button>2</button><button>3</button><button>下一页</button></div></footer></article>
<aside class="surface context-panel"><div class="context-heading"><span>{{config.panelEyebrow}}</span><h2>{{config.panelTitle}}</h2><p>{{config.panelDescription}}</p></div><div class="context-score"><span>{{config.scoreLabel}}</span><strong>{{config.score}}</strong><i><b :style="{width:config.scoreWidth+'%'}"></b></i></div><div class="context-list"><button v-for="it in config.panelItems" :key="it.title"><span :data-tone="it.tone"></span><div><strong>{{it.title}}</strong><small>{{it.meta}}</small></div><ArrowRight :size="15"/></button></div><button class="context-action" @click="router.push(workflow.detail)">{{config.panelAction}}<ArrowRight :size="15"/></button></aside></div>
</section></template>
