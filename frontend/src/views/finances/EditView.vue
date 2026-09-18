<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FinanceForm from '@/components/finances/FinanceForm.vue'
import { api, ApiError } from '@/lib/api'
import { centsToReaisInput } from '@/lib/money'
import type { ValidationErrors } from '@/types/auth'
import type { Finance, FinancePayload } from '@/types/finance'

const route = useRoute()
const router = useRouter()
const submitting = ref(false)
const loading = ref(true)
const errors = ref<ValidationErrors>({})
const generalError = ref('')
const initial = ref<{
  description: string
  amountReais: string
  date: string
  type: Finance['type']
} | null>(null)

const uuid = String(route.params.uuid)

async function load() {
  try {
    const response = await api<{ data: Finance }>(`/api/finances/${uuid}`)
    initial.value = {
      description: response.data.description,
      amountReais: centsToReaisInput(response.data.amount),
      date: response.data.date,
      type: response.data.type,
    }
  }
  catch (error) {
    generalError.value = error instanceof ApiError ? error.message : 'Não foi possível carregar a transação.'
  }
  finally {
    loading.value = false
  }
}

async function onSubmit(payload: FinancePayload) {
  errors.value = {}
  generalError.value = ''
  submitting.value = true

  try {
    await api(`/api/finances/${uuid}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    })
    await router.push(`/finances/${uuid}`)
  }
  catch (error) {
    if (error instanceof ApiError) {
      errors.value = error.errors
      if (!Object.keys(error.errors).length) {
        generalError.value = error.message
      }
    }
    else {
      generalError.value = 'Não foi possível atualizar a transação.'
    }
  }
  finally {
    submitting.value = false
  }
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div class="flex flex-col my-4">
    <h1 class="text-4xl font-bold">Editar transação</h1>
    <p class="text-lg text-muted-foreground">
      Altere as informações da transação
    </p>
  </div>
  <div class="flex flex-col gap-4">
    <p v-if="generalError" class="text-destructive text-sm">
      {{ generalError }}
    </p>
    <p v-else-if="loading" class="text-muted-foreground text-sm">
      Carregando...
    </p>
    <FinanceForm
      v-else-if="initial"
      submit-label="Salvar alterações"
      :submitting="submitting"
      :errors="errors"
      :initial="initial"
      @submit="onSubmit"
    />
  </div>
</template>
