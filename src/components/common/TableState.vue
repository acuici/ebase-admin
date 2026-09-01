<script setup lang="ts">
import { CircleAlert, Inbox, LoaderCircle, SearchX } from 'lucide-vue-next'

withDefaults(defineProps<{
  state: 'loading' | 'error' | 'empty'
  title: string
  description?: string
  filtered?: boolean
}>(), { description: '', filtered: false })
</script>

<template>
  <div class="table-state" :data-state="state" role="status" aria-live="polite">
    <span class="table-state-icon">
      <LoaderCircle v-if="state === 'loading'" :size="22" class="is-spinning" />
      <CircleAlert v-else-if="state === 'error'" :size="22" />
      <SearchX v-else-if="filtered" :size="22" />
      <Inbox v-else :size="22" />
    </span>
    <div>
      <h3>{{ title }}</h3>
      <p v-if="description">{{ description }}</p>
    </div>
    <div v-if="$slots.action" class="table-state-action"><slot name="action" /></div>
  </div>
</template>
