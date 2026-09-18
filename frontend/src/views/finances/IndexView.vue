<script setup lang="ts">
import { Ellipsis, Eye, Pencil, Plus, Trash, Upload } from '@lucide/vue'
import { onMounted, ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import Filter from '@/components/Filter.vue'
import Paginator from '@/components/Paginator.vue'
import Prompt from '@/components/Prompt.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { api, ApiError } from '@/lib/api'
import { formatBrl } from '@/lib/money'
import type { Finance, FinanceListFilters, Paginated } from '@/types/finance'

const page = ref(1)
const filters = ref<FinanceListFilters>({})
const loading = ref(false)
const errorMessage = ref('')
const finances = ref<Finance[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const deleting = ref<Finance | null>(null)
const deletingUuid = ref<string | null>(null)
const hasFilters = computed(() => Object.keys(filters.value).length > 0)
const deleteOpen = computed({
  get: () => deleting.value !== null,
  set: (value: boolean) => {
    if (!value) {
      deleting.value = null
    }
  },
})

function requestDelete(finance: Finance) {
  deleting.value = finance
  deletingUuid.value = finance.uuid
}

async function load() {
  loading.value = true
  errorMessage.value = ''

  try {
    const params = new URLSearchParams({
      page: String(page.value),
      per_page: '15',
    })

    Object.entries(filters.value).forEach(([key, value]) => {
      if (value !== undefined && value !== '') {
        params.set(key, String(value))
      }
    })

    const response = await api<Paginated<Finance>>(`/api/finances?${params}`)
    finances.value = response.data
    meta.value = response.meta
  }
  catch (error) {
    errorMessage.value = error instanceof ApiError ? error.message : 'Não foi possível carregar o financeiro.'
  }
  finally {
    loading.value = false
  }
}

async function confirmDelete() {
  const uuid = deletingUuid.value

  if (!uuid) {
    return
  }

  await api(`/api/finances/${uuid}`, { method: 'DELETE' })
  deleting.value = null
  deletingUuid.value = null
  await load()
}

function onSearch(next: FinanceListFilters) {
  filters.value = next
  page.value = 1
  void load()
}

function onPage(next: number) {
  page.value = next
  void load()
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div class="flex flex-col my-4">
    <h1 class="text-4xl font-bold">Transações</h1>
    <p class="text-lg text-muted-foreground">
      Gerencie as transações do seu negócio
    </p>
  </div>
  <div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <Filter @search="onSearch" />
      <div class="flex flex-wrap items-center justify-end gap-2">
        <Button variant="outline" as-child>
          <RouterLink to="/finances/import">
            <Upload />
            Importar
          </RouterLink>
        </Button>
        <Button as-child>
          <RouterLink to="/finances/create">
            <Plus />
            Nova transação
          </RouterLink>
        </Button>
      </div>
    </div>

    <p v-if="errorMessage" class="text-destructive text-sm">
      {{ errorMessage }}
    </p>
    <p v-else-if="loading" class="text-muted-foreground text-sm">
      Carregando...
    </p>

    <div class="overflow-hidden rounded-xl border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="w-12" />
            <TableHead>Data</TableHead>
            <TableHead>Descrição</TableHead>
            <TableHead>Tipo</TableHead>
            <TableHead class="text-right">
              Valor
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="!loading && finances.length === 0">
            <TableCell colspan="5" class="text-muted-foreground text-center">
              {{ hasFilters ? 'Nenhuma transação encontrada.' : 'Nenhuma transação cadastrada.' }}
            </TableCell>
          </TableRow>
          <TableRow v-for="finance in finances" :key="finance.uuid">
            <TableCell>
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon">
                    <Ellipsis />
                    <span class="sr-only">Ações</span>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start">
                  <DropdownMenuItem as-child>
                    <RouterLink :to="`/finances/${finance.uuid}`">
                      <Eye />
                      Ver detalhes
                    </RouterLink>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <RouterLink :to="`/finances/${finance.uuid}/edit`">
                      <Pencil />
                      Alterar
                    </RouterLink>
                  </DropdownMenuItem>
                  <DropdownMenuItem variant="destructive" @click="requestDelete(finance)">
                    <Trash />
                    Excluir
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>
            <TableCell>{{ new Date(`${finance.date}T00:00:00`).toLocaleDateString('pt-BR') }}</TableCell>
            <TableCell>{{ finance.description }}</TableCell>
            <TableCell>
              <Badge :variant="finance.type === 'income' ? 'default' : 'secondary'">
                {{ finance.type === 'income' ? 'Receita' : 'Despesa' }}
              </Badge>
            </TableCell>
            <TableCell class="text-right font-medium">
              {{ formatBrl(finance.amount) }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <Paginator
      :current-page="meta.current_page"
      :last-page="meta.last_page"
      :total="meta.total"
      @update:page="onPage"
    />

    <Prompt
      v-model:open="deleteOpen"
      title="Excluir transação"
      description="Esta ação não pode ser desfeita. A transação será removida da listagem."
      confirm-label="Excluir"
      @confirm="confirmDelete"
    />
  </div>
</template>
