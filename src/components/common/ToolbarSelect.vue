<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { Check, ChevronDown } from 'lucide-vue-next'

export interface ToolbarSelectOption { label:string; value:string }
const props=withDefaults(defineProps<{modelValue:string;options:ToolbarSelectOption[];label?:string;ariaLabel?:string}>(),{label:'',ariaLabel:'筛选选项'})
const emit=defineEmits<{(event:'update:modelValue',value:string):void;(event:'change',value:string):void}>()
const open=ref(false);const root=ref<HTMLElement|null>(null);const trigger=ref<HTMLElement|null>(null);const menu=ref<HTMLElement|null>(null);const position=reactive({top:'0px',left:'0px',width:'140px'})
function updatePosition(){if(!trigger.value)return;const rect=trigger.value.getBoundingClientRect();const width=Math.max(140,rect.width);position.top=`${rect.bottom+8}px`;position.left=`${Math.max(12,Math.min(window.innerWidth-width-12,rect.left))}px`;position.width=`${width}px`}
function toggle(){open.value=!open.value;if(open.value)void nextTick(updatePosition)}
function select(value:string){emit('update:modelValue',value);emit('change',value);open.value=false}
function outside(event:MouseEvent){const target=event.target as Node;if(open.value&&root.value&&!root.value.contains(target)&&menu.value&&!menu.value.contains(target))open.value=false}
function keydown(event:KeyboardEvent){if(event.key==='Escape')open.value=false}
onMounted(()=>{document.addEventListener('click',outside);document.addEventListener('keydown',keydown);window.addEventListener('resize',updatePosition);window.addEventListener('scroll',updatePosition,true)});onBeforeUnmount(()=>{document.removeEventListener('click',outside);document.removeEventListener('keydown',keydown);window.removeEventListener('resize',updatePosition);window.removeEventListener('scroll',updatePosition,true)})
</script>

<template><div ref="root" class="toolbar-select"><button ref="trigger" class="button secondary toolbar-select-trigger" :class="{expanded:open}" aria-haspopup="listbox" :aria-expanded="open" :aria-label="ariaLabel" @click="toggle"><small v-if="label">{{label}}</small><span>{{options.find(option=>option.value===modelValue)?.label||modelValue}}</span><ChevronDown :size="14"/></button><Teleport to="body"><Transition name="toolbar-select-menu"><div v-if="open" ref="menu" class="toolbar-select-options" :style="position" role="listbox" :aria-label="ariaLabel"><button v-for="option in options" :key="option.value" role="option" :aria-selected="modelValue===option.value" :class="{active:modelValue===option.value}" @click="select(option.value)"><span>{{option.label}}</span><Check v-if="modelValue===option.value" :size="14"/></button></div></Transition></Teleport></div></template>
