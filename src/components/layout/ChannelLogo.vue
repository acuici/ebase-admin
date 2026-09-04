<script setup lang="ts">
import { Box } from 'lucide-vue-next'

defineProps<{ channel: 'all' | 'tmall' | 'jd' | 'douyin' | 'miniapp' }>()
</script>

<template>
  <span class="channel-logo" :data-channel="channel" aria-hidden="true">
    <Box v-if="channel === 'all'" :size="17" :stroke-width="1.9" />
    <svg v-else-if="channel === 'tmall'" viewBox="0 0 24 24">
      <path d="M4.5 9.2 6.8 4l4 3.1h2.4l4-3.1 2.3 5.2v7.4c0 2-1.6 3.6-3.6 3.6H8.1a3.6 3.6 0 0 1-3.6-3.6V9.2Z" />
      <path d="M8.3 13.2h.1m7.2 0h.1M9.1 16.2c1.8 1.1 4 1.1 5.8 0" />
    </svg>
    <span v-else-if="channel === 'jd'" class="channel-lettermark">JD</span>
    <svg v-else-if="channel === 'douyin'" viewBox="0 0 24 24">
      <path class="douyin-cyan" d="M13.8 4.2v10.1a4.7 4.7 0 1 1-3.2-4.5v3.1a1.8 1.8 0 1 0 .3 1V2.8h3.2c.4 2.4 2 4 4.4 4.5v3.1a8.2 8.2 0 0 1-4.7-2.1" />
      <path class="douyin-red" d="M15.1 3.4v10.1a4.7 4.7 0 1 1-3.2-4.5v3.1a1.8 1.8 0 1 0 .3 1V2h3.2c.4 2.4 2 4 4.4 4.5v3.1a8.2 8.2 0 0 1-4.7-2.1" />
      <path class="douyin-core" d="M14.45 2.7v10.1a4.7 4.7 0 1 1-3.2-4.5v3.1a1.8 1.8 0 1 0 .3 1V2.35h3.2c.4 2.4 2 4 4.4 4.5v3.1a8.2 8.2 0 0 1-4.7-2.1" />
    </svg>
    <svg v-else viewBox="0 0 24 24">
      <path d="M9.2 5.3H7.1a4.1 4.1 0 0 0 0 8.2h3.4a2.5 2.5 0 0 1 0 5H8.8" />
      <path d="M14.8 18.7h2.1a4.1 4.1 0 0 0 0-8.2h-3.4a2.5 2.5 0 0 1 0-5h1.7" />
      <circle cx="8.1" cy="18.5" r="1.1" />
      <circle cx="15.9" cy="5.5" r="1.1" />
    </svg>
  </span>
</template>

<style scoped>
.channel-logo{width:20px;height:20px;display:grid;place-items:center;flex:none;color:#667085}.channel-logo svg{width:18px;height:18px;display:block;overflow:visible}.channel-logo[data-channel='tmall']{color:#ff0036}.channel-logo[data-channel='tmall'] path:first-child{fill:currentColor;stroke:none}.channel-logo[data-channel='tmall'] path:last-child{fill:none;stroke:#fff;stroke-width:1.6;stroke-linecap:round}.channel-lettermark{width:18px;height:18px;display:grid;place-items:center;border-radius:4px;background:#e1251b;color:#fff;font:800 8px/1 Arial,sans-serif;letter-spacing:-.06em}.channel-logo[data-channel='douyin'] path{fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}.douyin-cyan{stroke:#25f4ee;transform:translate(-.45px,.45px)}.douyin-red{stroke:#fe2c55;transform:translate(.45px,-.45px)}.douyin-core{stroke:#17202f}.channel-logo[data-channel='miniapp']{color:#07c160}.channel-logo[data-channel='miniapp'] path{fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}.channel-logo[data-channel='miniapp'] circle{fill:currentColor;stroke:none}
</style>
