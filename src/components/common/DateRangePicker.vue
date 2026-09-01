<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { CalendarDays, Check, ChevronDown } from 'lucide-vue-next'

export interface DateRangeValue { start: string; end: string; preset?: 3 | 7 | 30 | 'custom' }
const props=defineProps<{modelValue:DateRangeValue}>()
const emit=defineEmits<{(event:'update:modelValue',value:DateRangeValue):void;(event:'change',value:DateRangeValue):void}>()
const open=ref(false); const root=ref<HTMLElement|null>(null); const trigger=ref<HTMLElement|null>(null); const card=ref<HTMLElement|null>(null); const cardPosition=reactive({top:'0px',left:'0px'}); const draftStart=ref(props.modelValue.start); const draftEnd=ref(props.modelValue.end); const error=ref('')
const formatDate=(value:string)=>new Intl.DateTimeFormat('zh-CN',{month:'2-digit',day:'2-digit'}).format(new Date(`${value}T00:00:00`))
const label=computed(()=>props.modelValue.preset&&props.modelValue.preset!=='custom'?`近 ${props.modelValue.preset} 日`:`${formatDate(props.modelValue.start)} – ${formatDate(props.modelValue.end)}`)
function iso(date:Date){const year=date.getFullYear();const month=String(date.getMonth()+1).padStart(2,'0');const day=String(date.getDate()).padStart(2,'0');return `${year}-${month}-${day}`}
function preset(days:3|7|30){const end=new Date();const start=new Date(end);start.setDate(end.getDate()-(days-1));const value={start:iso(start),end:iso(end),preset:days} as DateRangeValue;emit('update:modelValue',value);emit('change',value);open.value=false;error.value=''}
function dayDifference(start:string,end:string){return Math.round((new Date(`${end}T00:00:00`).getTime()-new Date(`${start}T00:00:00`).getTime())/86400000)}
function apply(){if(!draftStart.value||!draftEnd.value){error.value='请选择起始日期和截止日期。';return}const days=dayDifference(draftStart.value,draftEnd.value);if(days<0){error.value='截止日期不能早于起始日期。';return}if(days>180){error.value='日期范围最长不能超过 180 天。';return}const value={start:draftStart.value,end:draftEnd.value,preset:'custom'} as DateRangeValue;emit('update:modelValue',value);emit('change',value);open.value=false;error.value=''}
function updatePosition(){if(!trigger.value)return;const rect=trigger.value.getBoundingClientRect();cardPosition.top=`${rect.bottom+8}px`;cardPosition.left=`${Math.max(12,Math.min(window.innerWidth-352,rect.right-340))}px`}
function toggle(){open.value=!open.value;if(open.value){draftStart.value=props.modelValue.start;draftEnd.value=props.modelValue.end;error.value='';void nextTick(updatePosition)}}
function outside(event:MouseEvent){const target=event.target as Node;if(open.value&&root.value&&!root.value.contains(target)&&card.value&&!card.value.contains(target))open.value=false}
function escape(event:KeyboardEvent){if(event.key==='Escape')open.value=false}
watch(()=>props.modelValue, value=>{draftStart.value=value.start;draftEnd.value=value.end},{deep:true})
onMounted(()=>{document.addEventListener('click',outside);document.addEventListener('keydown',escape);window.addEventListener('resize',updatePosition);window.addEventListener('scroll',updatePosition,true)});onBeforeUnmount(()=>{document.removeEventListener('click',outside);document.removeEventListener('keydown',escape);window.removeEventListener('resize',updatePosition);window.removeEventListener('scroll',updatePosition,true)})
</script>

<template>
  <div ref="root" class="date-range-picker">
    <button ref="trigger" class="button secondary date-range-trigger" :class="{expanded:open}" aria-haspopup="dialog" :aria-expanded="open" @click="toggle"><CalendarDays :size="16"/><span>{{label}}</span><ChevronDown :size="15"/></button>
    <Teleport to="body"><Transition name="date-range-menu"><section v-if="open" ref="card" class="date-range-card" :style="cardPosition" role="dialog" aria-label="选择日期范围">
      <header><div><strong>选择日期范围</strong><span>最长可查询 180 天</span></div></header>
      <div class="date-range-presets"><button v-for="days in ([3,7,30] as const)" :key="days" :class="{active:modelValue.preset===days}" @click="preset(days)">近 {{days}} 日</button></div>
      <div class="date-range-custom"><label><span>起始日期</span><input v-model="draftStart" type="date"/></label><i></i><label><span>截止日期</span><input v-model="draftEnd" type="date"/></label></div>
      <p v-if="error" class="date-range-error">{{error}}</p>
      <footer><button class="button secondary" @click="open=false">取消</button><button class="button primary" @click="apply"><Check :size="14"/>应用日期</button></footer>
    </section></Transition></Teleport>
  </div>
</template>
