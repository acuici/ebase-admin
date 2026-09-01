<script setup lang="ts">
import { AlertCircle, AlertTriangle, CheckCircle2, Info, X } from 'lucide-vue-next'
import { useToast, type ToastTone } from '../../composables/useToast'
const {items,dismiss}=useToast()
const icons:Record<ToastTone,unknown>={success:CheckCircle2,error:AlertCircle,warning:AlertTriangle,info:Info}
</script>

<template><Teleport to="body"><section class="toast-viewport" aria-label="操作通知" aria-live="polite"><TransitionGroup name="toast-item"><article v-for="item in items" :key="item.id" class="global-toast" :data-tone="item.tone" role="status"><span class="toast-symbol"><component :is="icons[item.tone]" :size="18"/></span><div><strong>{{item.title}}</strong><p v-if="item.description">{{item.description}}</p></div><button aria-label="关闭通知" @click="dismiss(item.id)"><X :size="15"/></button><i :style="{'--toast-duration':`${item.duration}ms`}"></i></article></TransitionGroup></section></Teleport></template>
