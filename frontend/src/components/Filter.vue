<script setup lang="ts">
import { Search, SlidersHorizontal } from '@lucide/vue'
import { reactive, ref } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { centsToReaisInput, reaisToCents } from '@/lib/money'
import type { FinanceListFilters, FinanceType } from '@/types/finance'

const emit = defineEmits<{
  search: [filters: FinanceListFilters]
}>()

const q = ref('')
const advancedOpen = ref(false)
const advanced = reactive({
  description: '',
  type: 'all',
  from: '',
  to: '',
  minReais: '',
  maxReais: '',
})
const applied = reactive({
  type: '' as '' | FinanceType,
  from: '',
  to: '',
  minAmount: undefined as number | undefined,
  maxAmount: undefined as number | undefined,
})

function toFilters(): FinanceListFilters {
  const filters: FinanceListFilters = {}
  const query = q.value.trim()

  if (query) {
    filters.q = query
  }

  if (applied.type) {
    filters.type = applied.type
  }

  if (applied.from) {
    filters.from = applied.from
  }

  if (applied.to) {
    filters.to = applied.to
  }

  if (applied.minAmount !== undefined) {
    filters.min_amount = applied.minAmount
  }

  if (applied.maxAmount !== undefined) {
    filters.max_amount = applied.maxAmount
  }

  return filters
}

function onSimpleSearch() {
  emit('search', toFilters())
}

function openAdvanced() {
  advanced.description = q.value
  advanced.type = applied.type || 'all'
  advanced.from = applied.from
  advanced.to = applied.to
  advanced.minReais = applied.minAmount === undefined ? '' : centsToReaisInput(applied.minAmount)
  advanced.maxReais = applied.maxAmount === undefined ? '' : centsToReaisInput(applied.maxAmount)
  advancedOpen.value = true
}

function applyAdvanced() {
  q.value = advanced.description
  applied.type = advanced.type === 'all' ? '' : advanced.type as FinanceType
  applied.from = advanced.from
  applied.to = advanced.to
  applied.minAmount = advanced.minReais.trim() === '' ? undefined : reaisToCents(advanced.minReais)
  applied.maxAmount = advanced.maxReais.trim() === '' ? undefined : reaisToCents(advanced.maxReais)
  advancedOpen.value = false
  emit('search', toFilters())
}

function clearFilters() {
  q.value = ''
  advanced.description = ''
  advanced.type = 'all'
  advanced.from = ''
  advanced.to = ''
  advanced.minReais = ''
  advanced.maxReais = ''
  applied.type = ''
  applied.from = ''
  applied.to = ''
  applied.minAmount = undefined
  applied.maxAmount = undefined
  emit('search', {})
}
</script>

<template>
  <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
    <form class="flex min-w-0 flex-1 items-center gap-2" @submit.prevent="onSimpleSearch">
      <Input
        id="finance-search"
        v-model="q"
        type="search"
        placeholder="Buscar por descrição"
        class="max-w-xs"
        aria-label="Buscar por descrição"
      />
      <Button type="submit">
        <Search />
        Pesquisar
      </Button>
      <Button type="button" variant="outline" @click="openAdvanced">
        <SlidersHorizontal />
        Pesquisa avançada
      </Button>
    </form>

    <Dialog v-model:open="advancedOpen">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Pesquisa avançada</DialogTitle>
          <DialogDescription>
            Filtre as transações por descrição, tipo, período e valor.
          </DialogDescription>
        </DialogHeader>

        <form class="grid gap-4" @submit.prevent="applyAdvanced">
          <div class="grid gap-1.5">
            <Label for="filter-description">Descrição</Label>
            <Input id="filter-description" v-model="advanced.description" />
          </div>

          <div class="grid gap-1.5">
            <Label>Tipo</Label>
            <Select v-model="advanced.type">
              <SelectTrigger>
                <SelectValue placeholder="Todos" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">
                  Todos
                </SelectItem>
                <SelectItem value="income">
                  Receita
                </SelectItem>
                <SelectItem value="expense">
                  Despesa
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-1.5">
              <Label for="filter-from">De</Label>
              <Input id="filter-from" v-model="advanced.from" type="date" />
            </div>
            <div class="grid gap-1.5">
              <Label for="filter-to">Até</Label>
              <Input id="filter-to" v-model="advanced.to" type="date" />
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-1.5">
              <Label for="filter-min-amount">Valor mín. (R$)</Label>
              <Input
                id="filter-min-amount"
                v-model="advanced.minReais"
                inputmode="decimal"
                placeholder="0,00"
              />
            </div>
            <div class="grid gap-1.5">
              <Label for="filter-max-amount">Valor máx. (R$)</Label>
              <Input
                id="filter-max-amount"
                v-model="advanced.maxReais"
                inputmode="decimal"
                placeholder="0,00"
              />
            </div>
          </div>

          <DialogFooter class="sm:justify-between">
            <Button type="button" variant="ghost" @click="clearFilters">
              Limpar
            </Button>
            <Button type="submit">
              Pesquisar
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
