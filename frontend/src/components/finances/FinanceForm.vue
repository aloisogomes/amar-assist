<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { ValidationErrors } from '@/types/auth'
import type { FinancePayload, FinanceType } from '@/types/finance'
import { parseReaisToCents } from '@/lib/money'

const props = defineProps<{
  submitLabel: string
  submitting?: boolean
  errors?: ValidationErrors
  initial?: {
    description: string
    amountReais: string
    date: string
    type: FinanceType
  }
}>()

const emit = defineEmits<{
  submit: [payload: FinancePayload]
}>()

const form = reactive({
  description: props.initial?.description ?? '',
  amountReais: props.initial?.amountReais ?? '',
  date: props.initial?.date ?? '',
  type: props.initial?.type as FinanceType | undefined,
})

const fieldErrors = ref<ValidationErrors>({})

watch(() => props.errors, (next) => {
  fieldErrors.value = { ...(next ?? {}) }
}, { deep: true })

function clearError(field: string) {
  if (!fieldErrors.value[field]) {
    return
  }

  const next = { ...fieldErrors.value }
  delete next[field]
  fieldErrors.value = next
}

watch(() => form.description, () => clearError('description'))
watch(() => form.amountReais, () => clearError('amount'))
watch(() => form.date, () => clearError('date'))
watch(() => form.type, () => clearError('type'))

function validate(): FinancePayload | null {
  const errors: ValidationErrors = {}
  const description = form.description.trim()

  if (description === '') {
    errors.description = ['Informe a descrição.']
  }
  else if (description.length > 255) {
    errors.description = ['A descrição deve ter no máximo 255 caracteres.']
  }

  const amountRaw = form.amountReais.trim()

  if (amountRaw === '') {
    errors.amount = ['Informe o valor.']
  }
  else {
    const cents = parseReaisToCents(form.amountReais)

    if (cents === null) {
      errors.amount = ['Informe um valor válido.']
    }
    else if (cents < 0) {
      errors.amount = ['O valor deve ser maior ou igual a zero.']
    }
  }

  if (form.date.trim() === '') {
    errors.date = ['Informe a data.']
  }
  else if (!/^\d{4}-\d{2}-\d{2}$/.test(form.date) || Number.isNaN(Date.parse(`${form.date}T00:00:00`))) {
    errors.date = ['Informe uma data válida.']
  }

  if (form.type !== 'income' && form.type !== 'expense') {
    errors.type = ['Selecione o tipo.']
  }

  fieldErrors.value = errors

  if (Object.keys(errors).length > 0) {
    return null
  }

  return {
    description,
    amount: parseReaisToCents(form.amountReais) ?? 0,
    date: form.date,
    type: form.type as FinanceType,
  }
}

function onSubmit() {
  const payload = validate()

  if (!payload) {
    return
  }

  emit('submit', payload)
}
</script>

<template>
  <form class="flex max-w-xl flex-col gap-4" novalidate @submit.prevent="onSubmit">
    <div class="flex flex-col gap-2">
      <Label for="description">Descrição</Label>
      <Input
        id="description"
        v-model="form.description"
        :aria-invalid="Boolean(fieldErrors.description)"
      />
      <p v-if="fieldErrors.description" class="text-destructive text-sm">
        {{ fieldErrors.description[0] }}
      </p>
    </div>
    <div class="flex flex-col gap-2">
      <Label for="amount">Valor (R$)</Label>
      <Input
        id="amount"
        v-model="form.amountReais"
        inputmode="decimal"
        placeholder="0,00"
        :aria-invalid="Boolean(fieldErrors.amount)"
      />
      <p v-if="fieldErrors.amount" class="text-destructive text-sm">
        {{ fieldErrors.amount[0] }}
      </p>
    </div>
    <div class="flex flex-col gap-2">
      <Label for="date">Data</Label>
      <Input
        id="date"
        v-model="form.date"
        type="date"
        :aria-invalid="Boolean(fieldErrors.date)"
      />
      <p v-if="fieldErrors.date" class="text-destructive text-sm">
        {{ fieldErrors.date[0] }}
      </p>
    </div>
    <div class="flex flex-col gap-2">
      <Label>Tipo</Label>
      <Select v-model="form.type">
        <SelectTrigger :aria-invalid="Boolean(fieldErrors.type)">
          <SelectValue placeholder="Selecione o tipo" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="income">
            Receita
          </SelectItem>
          <SelectItem value="expense">
            Despesa
          </SelectItem>
        </SelectContent>
      </Select>
      <p v-if="fieldErrors.type" class="text-destructive text-sm">
        {{ fieldErrors.type[0] }}
      </p>
    </div>
    <div>
      <Button type="submit" :disabled="submitting">
        {{ submitting ? 'Salvando...' : submitLabel }}
      </Button>
    </div>
  </form>
</template>
