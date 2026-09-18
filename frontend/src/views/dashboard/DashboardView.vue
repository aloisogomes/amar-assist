<script setup lang="ts">
import type { ChartConfig } from '@/components/ui/chart'
import { CurveType } from '@unovis/ts'
import { VisAxis, VisLine, VisXYContainer } from '@unovis/vue'
import { computed, onMounted, ref } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  ChartContainer,
  ChartCrosshair,
  ChartLegendContent,
  ChartTooltip,
  componentToString,
} from '@/components/ui/chart'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { api, ApiError } from '@/lib/api'
import { formatBrl } from '@/lib/money'
import type { FinanceDashboard, FinanceDashboardPoint } from '@/types/finance'
import DashboardChartTooltip from './DashboardChartTooltip.vue'

type ChartPoint = {
  date: Date
  income: number
  expense: number
}

const from = ref(startOfMonth())
const to = ref(today())
const loading = ref(false)
const errorMessage = ref('')
const dashboard = ref<FinanceDashboard | null>(null)

const chartConfig = {
  income: {
    label: 'Receita',
    color: 'var(--chart-1)',
  },
  expense: {
    label: 'Despesa',
    color: 'var(--chart-5)',
  },
} satisfies ChartConfig

const chartData = computed<ChartPoint[]>(() => {
  return (dashboard.value?.series ?? []).map((point: FinanceDashboardPoint) => ({
    date: new Date(`${point.date}T00:00:00`),
    income: point.income,
    expense: point.expense,
  }))
})

const periodLabel = computed(() => {
  if (!dashboard.value) {
    return ''
  }

  return `${formatDateBr(dashboard.value.from)} – ${formatDateBr(dashboard.value.to)}`
})

const kpis = computed(() => dashboard.value?.kpis)

function startOfMonth(date = new Date()): string {
  return toIsoDate(new Date(date.getFullYear(), date.getMonth(), 1))
}

function today(date = new Date()): string {
  return toIsoDate(date)
}

function toIsoDate(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function formatDateBr(iso: string): string {
  return new Date(`${iso}T00:00:00`).toLocaleDateString('pt-BR')
}

function formatChange(value: number): string {
  const formatted = Math.abs(value).toLocaleString('pt-BR', {
    maximumFractionDigits: 2,
  })

  if (value > 0) {
    return `+${formatted}%`
  }

  if (value < 0) {
    return `-${formatted}%`
  }

  return '0%'
}

function changeClass(value: number, invert = false): string {
  const positive = invert ? value < 0 : value > 0
  const negative = invert ? value > 0 : value < 0

  if (positive) {
    return 'text-emerald-600 dark:text-emerald-400'
  }

  if (negative) {
    return 'text-destructive'
  }

  return 'text-muted-foreground'
}

function formatAxisCurrency(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    maximumFractionDigits: 0,
  })
}

async function load() {
  loading.value = true
  errorMessage.value = ''

  try {
    const params = new URLSearchParams({
      from: from.value,
      to: to.value,
    })
    const response = await api<{ data: FinanceDashboard }>(`/api/finances/dashboard?${params}`)
    dashboard.value = response.data
    from.value = response.data.from
    to.value = response.data.to
  }
  catch (error) {
    errorMessage.value = error instanceof ApiError
      ? Object.values(error.errors).flat()[0] ?? error.message
      : 'Não foi possível carregar o dashboard.'
  }
  finally {
    loading.value = false
  }
}

function onFilter() {
  void load()
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div class="flex flex-col my-4">
    <h1 class="text-4xl font-bold">Dashboard</h1>
    <p class="text-lg text-muted-foreground">
      Visualize as principais informações do seu negócio
    </p>
  </div>
  <div class="flex flex-col gap-4">
    <form class="flex flex-wrap items-end gap-3" @submit.prevent="onFilter">
      
      <div class="grid gap-1.5">
        <Label for="dashboard-from">Período de</Label>
        <Input id="dashboard-from" v-model="from" type="date" class="w-auto" />
      </div>
      <div class="grid gap-1.5">
        <Label for="dashboard-to">Até</Label>
        <Input id="dashboard-to" v-model="to" type="date" class="w-auto" />
      </div>
      <Button type="submit" :disabled="loading">
        Filtrar
      </Button>
    </form>

    <p v-if="errorMessage" class="text-destructive text-sm">
      {{ errorMessage }}
    </p>
    <p v-else-if="loading && !dashboard" class="text-muted-foreground text-sm">
      Carregando...
    </p>

    <template v-if="dashboard && kpis">
      <Card>
        <CardHeader>
          <CardTitle>Receitas vs despesas</CardTitle>
          <CardDescription>
            Série diária de {{ periodLabel }}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <ChartContainer :config="chartConfig" class="aspect-auto h-[320px] w-full" cursor>
            <VisXYContainer
              :data="chartData"
              :margin="{ left: 8, right: 8, top: 8, bottom: 0 }"
              :y-domain="[0, undefined]"
            >
              <VisLine
                :x="(d: ChartPoint) => d.date"
                :y="[(d: ChartPoint) => d.income, (d: ChartPoint) => d.expense]"
                :color="[chartConfig.income.color, chartConfig.expense.color]"
                :curve-type="CurveType.MonotoneX"
              />
              <VisAxis
                type="x"
                :x="(d: ChartPoint) => d.date"
                :tick-line="false"
                :domain-line="false"
                :grid-line="false"
                :num-ticks="6"
                :tick-format="(d: number) => new Date(d).toLocaleDateString('pt-BR', {
                  day: '2-digit',
                  month: 'short',
                })"
              />
              <VisAxis
                type="y"
                :num-ticks="4"
                :tick-line="false"
                :domain-line="false"
                :tick-format="(d: number) => formatAxisCurrency(d)"
              />
              <ChartTooltip />
              <ChartCrosshair
                :template="componentToString(chartConfig, DashboardChartTooltip)"
                :color="[chartConfig.income.color, chartConfig.expense.color]"
              />
            </VisXYContainer>
            <ChartLegendContent />
          </ChartContainer>
        </CardContent>
      </Card>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardHeader>
            <CardDescription>Saldo</CardDescription>
            <CardTitle class="text-2xl">
              {{ formatBrl(kpis.balance) }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p :class="changeClass(kpis.previous.balance_change)" class="text-sm">
              {{ formatChange(kpis.previous.balance_change) }} vs período anterior
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardDescription>Receitas</CardDescription>
            <CardTitle class="text-2xl">
              {{ formatBrl(kpis.income_total) }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p :class="changeClass(kpis.previous.income_change)" class="text-sm">
              {{ formatChange(kpis.previous.income_change) }} vs período anterior
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardDescription>Despesas</CardDescription>
            <CardTitle class="text-2xl">
              {{ formatBrl(kpis.expense_total) }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p :class="changeClass(kpis.previous.expense_change, true)" class="text-sm">
              {{ formatChange(kpis.previous.expense_change) }} vs período anterior
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardDescription>Ticket médio diário de despesa</CardDescription>
            <CardTitle class="text-2xl">
              {{ formatBrl(kpis.avg_daily_expense) }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-muted-foreground text-sm">
              Despesas divididas pelos dias do período
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardDescription>Maior receita</CardDescription>
            <CardTitle class="text-2xl">
              {{ kpis.largest_income ? formatBrl(kpis.largest_income.amount) : '—' }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-muted-foreground text-sm">
              {{ kpis.largest_income
                ? `${kpis.largest_income.description} · ${formatDateBr(kpis.largest_income.date)}`
                : 'Nenhuma receita no período' }}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardDescription>Maior despesa</CardDescription>
            <CardTitle class="text-2xl">
              {{ kpis.largest_expense ? formatBrl(kpis.largest_expense.amount) : '—' }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-muted-foreground text-sm">
              {{ kpis.largest_expense
                ? `${kpis.largest_expense.description} · ${formatDateBr(kpis.largest_expense.date)}`
                : 'Nenhuma despesa no período' }}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardDescription>Transações</CardDescription>
            <CardTitle class="text-2xl">
              {{ kpis.transactions_count }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-muted-foreground text-sm">
              Movimentações no período selecionado
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardDescription>Variação vs período anterior</CardDescription>
            <CardTitle :class="changeClass(kpis.previous.balance_change)" class="text-2xl">
              {{ formatChange(kpis.previous.balance_change) }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-muted-foreground text-sm">
              {{ formatDateBr(kpis.previous.from) }} – {{ formatDateBr(kpis.previous.to) }}
            </p>
          </CardContent>
        </Card>
      </div>
    </template>
  </div>
</template>
