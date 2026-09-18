<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import type { ChartConfig } from '@/components/ui/chart'
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import { formatBrl } from '@/lib/money'

const props = withDefaults(defineProps<{
  hideLabel?: boolean
  payload?: Record<string, unknown>
  config?: ChartConfig
  class?: HTMLAttributes['class']
  x?: number | Date
}>(), {
  payload: () => ({}),
  config: () => ({}),
})

const tooltipLabel = computed(() => {
  if (props.hideLabel || props.x === undefined) {
    return null
  }

  const date = props.x instanceof Date ? props.x : new Date(props.x)

  return date.toLocaleDateString('pt-BR')
})

const items = computed(() => {
  return Object.entries(props.payload)
    .filter(([key]) => props.config[key])
    .map(([key, value]) => ({
      key,
      value: Number(value) || 0,
      itemConfig: props.config[key],
    }))
})
</script>

<template>
  <div
    :class="cn(
      'border-border/50 bg-background grid min-w-32 items-start gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs shadow-xl',
      props.class,
    )"
  >
    <div v-if="tooltipLabel" class="font-medium">
      {{ tooltipLabel }}
    </div>
    <div class="grid gap-1.5">
      <div
        v-for="{ value, itemConfig, key } in items"
        :key="key"
        class="flex w-full flex-wrap items-center gap-2"
      >
        <div
          class="size-2.5 shrink-0 rounded-xs"
          :style="{ backgroundColor: itemConfig?.color }"
        />
        <div class="flex flex-1 items-center justify-between leading-none">
          <span class="text-muted-foreground">
            {{ itemConfig?.label }}
          </span>
          <span class="text-foreground font-mono font-medium tabular-nums">
            {{ formatBrl(value) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
