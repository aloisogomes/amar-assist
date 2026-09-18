<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'

const route = useRoute()

const items = computed(() => {
  if (route.meta.breadcrumb?.length) {
    return route.meta.breadcrumb
  }

  return [{ label: String(route.meta.title ?? 'Início') }]
})

function crumbTo(name: string) {
  if (name === 'finances.show') {
    return { name, params: { uuid: route.params.uuid } }
  }

  return { name }
}
</script>

<template>
  <Breadcrumb>
    <BreadcrumbList>
      <template v-for="(item, index) in items" :key="`${item.label}-${index}`">
        <BreadcrumbSeparator v-if="index > 0" />
        <BreadcrumbItem>
          <BreadcrumbPage v-if="index === items.length - 1 || !item.name">
            {{ item.label }}
          </BreadcrumbPage>
          <BreadcrumbLink v-else as-child>
            <RouterLink :to="crumbTo(item.name)">
              {{ item.label }}
            </RouterLink>
          </BreadcrumbLink>
        </BreadcrumbItem>
      </template>
    </BreadcrumbList>
  </Breadcrumb>
</template>
