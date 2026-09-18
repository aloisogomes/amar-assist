<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { api, ApiError } from '@/lib/api'
import { formatBrl } from '@/lib/money'
import type { Finance } from '@/types/finance'

const route = useRoute()
const finance = ref<Finance | null>(null)
const errorMessage = ref('')
const uuid = String(route.params.uuid)

onMounted(async () => {
  try {
    const response = await api<{ data: Finance }>(`/api/finances/${uuid}`)
    finance.value = response.data
  }
  catch (error) {
    errorMessage.value = error instanceof ApiError ? error.message : 'Não foi possível carregar a transação.'
  }
})
</script>

<template>
  <div class="flex flex-col my-4">
    <h1 class="text-4xl font-bold">Transação</h1>
    <p class="text-lg text-muted-foreground">
      Visualize as informações da transação
    </p>
  </div>
  <div class="flex max-w-xl flex-col gap-4">
    <p v-if="errorMessage" class="text-destructive text-sm">
      {{ errorMessage }}
    </p>
    <Card v-else-if="finance">
      <CardHeader class="flex flex-row items-start justify-between gap-4">
        <CardTitle>{{ finance.description }}</CardTitle>
        <Badge :variant="finance.type === 'income' ? 'default' : 'secondary'">
          {{ finance.type === 'income' ? 'Receita' : 'Despesa' }}
        </Badge>
      </CardHeader>
      <CardContent class="flex flex-col gap-3 text-sm">
        <div class="flex justify-between gap-4">
          <span class="text-muted-foreground">Data</span>
          <span>{{ new Date(`${finance.date}T00:00:00`).toLocaleDateString('pt-BR') }}</span>
        </div>
        <div class="flex justify-between gap-4">
          <span class="text-muted-foreground">Valor</span>
          <span class="font-medium">{{ formatBrl(finance.amount) }}</span>
        </div>
        <div class="flex gap-2 pt-2">
          <Button as-child>
            <RouterLink :to="`/finances/${finance.uuid}/edit`">
              Alterar
            </RouterLink>
          </Button>
          <Button variant="outline" as-child>
            <RouterLink to="/finances">
              Voltar
            </RouterLink>
          </Button>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
