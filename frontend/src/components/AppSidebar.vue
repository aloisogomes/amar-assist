<script setup lang="ts">
import { ChartLine, ChevronsUpDown, Heart, House, LogOut, Wallet } from '@lucide/vue'
import { computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail,
} from '@/components/ui/sidebar'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const userInitials = computed(() => {
  const name = auth.user?.name ?? ''

  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(part => part[0]?.toUpperCase() ?? '')
    .join('')
})

async function handleLogout() {
  await auth.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <Sidebar variant="inset" collapsible="icon">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <RouterLink to="/">
              <div class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg">
                <Heart class="size-4" />
              </div>
              <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-semibold">Amar Assist</span>
                <span class="truncate text-xs">Painel</span>
              </div>
            </RouterLink>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>
    <SidebarContent>
      <SidebarGroup>
        <SidebarGroupLabel>Navegação</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                tooltip="Início"
                :is-active="route.name === 'home'"
              >
                <RouterLink to="/">
                  <House />
                  <span>Início</span>
                </RouterLink>
              </SidebarMenuButton>
            </SidebarMenuItem>
            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                tooltip="Dashboard"
                :is-active="route.name === 'finances.dashboard'"
              >
                <RouterLink to="/finances/dashboard">
                  <ChartLine />
                  <span>Dashboard</span>
                </RouterLink>
              </SidebarMenuButton>
            </SidebarMenuItem>
            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                tooltip="Financeiro"
                :is-active="String(route.name ?? '').startsWith('finances') && route.name !== 'finances.dashboard'"
              >
                <RouterLink to="/finances">
                  <Wallet />
                  <span>Financeiro</span>
                </RouterLink>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>
    <SidebarFooter>
      <SidebarMenu>
        <SidebarMenuItem>
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <SidebarMenuButton
                size="lg"
                class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
              >
                <div class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg text-xs font-medium">
                  {{ userInitials }}
                </div>
                <div class="grid flex-1 text-left text-sm leading-tight">
                  <span class="truncate font-semibold">{{ auth.user?.name }}</span>
                  <span class="truncate text-xs">{{ auth.user?.email }}</span>
                </div>
                <ChevronsUpDown class="ml-auto size-4" />
              </SidebarMenuButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent
              class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
              side="bottom"
              align="end"
              :side-offset="4"
            >
              <DropdownMenuLabel class="p-0 font-normal">
                <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                  <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-semibold">{{ auth.user?.name }}</span>
                    <span class="truncate text-xs">{{ auth.user?.email }}</span>
                  </div>
                </div>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem variant="destructive" @click="handleLogout">
                <LogOut />
                Sair
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarFooter>
    <SidebarRail />
  </Sidebar>
</template>
