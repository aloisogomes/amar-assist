<script setup lang="ts">
import { Button } from '@/components/ui/button'

const props = defineProps<{
  currentPage: number
  lastPage: number
  total: number
}>()

const emit = defineEmits<{
  'update:page': [page: number]
}>()

function pages(): number[] {
  const items: number[] = []
  const start = Math.max(1, props.currentPage - 2)
  const end = Math.min(props.lastPage, props.currentPage + 2)

  for (let page = start; page <= end; page += 1) {
    items.push(page)
  }

  return items
}
</script>

<template>
  <div v-if="lastPage > 1" class="flex flex-wrap items-center justify-between gap-3">
    <p class="text-muted-foreground text-sm">
      Página {{ currentPage }} de {{ lastPage }} · {{ total }} registros
    </p>
    <div class="flex items-center gap-1">
      <Button
        variant="outline"
        size="sm"
        :disabled="currentPage <= 1"
        @click="emit('update:page', currentPage - 1)"
      >
        Anterior
      </Button>
      <Button
        v-for="page in pages()"
        :key="page"
        size="sm"
        :variant="page === currentPage ? 'default' : 'outline'"
        @click="emit('update:page', page)"
      >
        {{ page }}
      </Button>
      <Button
        variant="outline"
        size="sm"
        :disabled="currentPage >= lastPage"
        @click="emit('update:page', currentPage + 1)"
      >
        Próxima
      </Button>
    </div>
  </div>
</template>
