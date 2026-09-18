<script setup lang="ts">
import { onUnmounted, watch } from 'vue'
import { toast } from 'vue-sonner'
import AppBreadcrumb from '@/components/AppBreadcrumb.vue'
import AppSidebar from '@/components/AppSidebar.vue'
import { Separator } from '@/components/ui/separator'
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from '@/components/ui/sidebar'
import { getEcho } from '@/lib/echo'
import { useAuthStore } from '@/stores/auth'
import { useFinanceImportStore } from '@/stores/financeImport'
import type {
  FinanceImportCompletedPayload,
  FinanceImportProgressPayload,
} from '@/types/finance'

const auth = useAuthStore()
const financeImport = useFinanceImportStore()

watch(() => auth.user?.id, (userId, previousId) => {
  const echo = getEcho()

  if (previousId) {
    echo?.leave(`App.Models.User.${previousId}`)
  }

  if (!userId || !echo) {
    return
  }

  const channel = echo.private(`App.Models.User.${userId}`)

  channel.listen(
    '.finance.import.progress',
    (payload: FinanceImportProgressPayload) => {
      financeImport.applyProgress(payload)
    },
  )

  channel.listen(
    '.finance.import.completed',
    (payload: FinanceImportCompletedPayload) => {
      financeImport.complete(payload)

      if (payload.failed > 0) {
        toast.warning(`Importação concluída: ${payload.created} criadas, ${payload.failed} com erro.`)
        return
      }

      if (payload.created === 0 && payload.errors.length > 0) {
        toast.error(payload.errors[0]?.messages[0] ?? 'A importação falhou.')
        return
      }

      const label = payload.created === 1 ? 'transação criada' : 'transações criadas'
      toast.success(`Importação concluída: ${payload.created} ${label}.`)
    },
  )
}, { immediate: true })

onUnmounted(() => {
  if (auth.user?.id) {
    getEcho()?.leave(`App.Models.User.${auth.user.id}`)
  }
})
</script>

<template>
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset>
      <header class="flex h-16 shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12">
        <div class="flex items-center gap-2 px-4">
          <SidebarTrigger class="-ml-1" />
          <Separator orientation="vertical" class="mr-2 data-[orientation=vertical]:h-4" />
          <AppBreadcrumb />
        </div>
      </header>
      <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
        <router-view />
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>
