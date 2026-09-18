<script setup lang="ts">
import { ChartLine, List, Plus, Upload } from '@lucide/vue'
import { RouterLink } from 'vue-router'
import {
  Card,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const shortcuts = [
  {
    to: '/finances',
    title: 'Listar transações',
    description: 'Veja e gerencie as receitas e despesas cadastradas.',
    icon: List,
  },
  {
    to: '/finances/create',
    title: 'Nova transação',
    description: 'Registre uma receita ou despesa manualmente.',
    icon: Plus,
  },
  {
    to: '/finances/import',
    title: 'Importar',
    description: 'Envie uma planilha para cadastrar várias transações.',
    icon: Upload,
  },
  {
    to: '/finances/dashboard',
    title: 'Dashboard',
    description: 'Acompanhe receitas, despesas e o saldo do período.',
    icon: ChartLine,
  },
]
</script>

<template>
  <div class="flex flex-col my-4">
    <h1 class="text-4xl font-bold">Painel de controle</h1>
    <p class="text-lg text-muted-foreground">
      Gerencie as transações do seu negócio
    </p>
  </div>
  <div class="flex flex-1 flex-col gap-4">
    <div class="rounded-xl bg-muted/50 p-6">
      <h2 class="text-lg font-semibold">
        Olá, {{ auth.user?.name }}
      </h2>
      <p class="text-muted-foreground mt-1 text-sm">
        Você está autenticado no painel da Amar Assist.
      </p>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
      <RouterLink
        v-for="shortcut in shortcuts"
        :key="shortcut.to"
        :to="shortcut.to"
        class="group block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
      >
        <Card class="h-full border border-transparent shadow-sm transition-all duration-200 group-hover:scale-[1.03] group-hover:border-primary group-hover:shadow-md">
          <CardHeader class="gap-3">
            <div class="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-lg">
              <component :is="shortcut.icon" class="size-5" />
            </div>
            <CardTitle>
              {{ shortcut.title }}
            </CardTitle>
            <CardDescription>
              {{ shortcut.description }}
            </CardDescription>
          </CardHeader>
        </Card>
      </RouterLink>
    </div>
  </div>
</template>
