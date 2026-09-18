<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import FinanceForm from '@/components/finances/FinanceForm.vue'
import { api, ApiError } from '@/lib/api'
import type { ValidationErrors } from '@/types/auth'
import type { Finance, FinancePayload } from '@/types/finance'

const router = useRouter()
const submitting = ref(false)
const errors = ref<ValidationErrors>({})
const generalError = ref('')

async function onSubmit(payload: FinancePayload) {
  errors.value = {}
  generalError.value = ''
  submitting.value = true

  try {
    const response = await api<{ data: Finance }>('/api/finances', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    await router.push(`/finances/${response.data.uuid}`)
  }
  catch (error) {
    if (error instanceof ApiError) {
      errors.value = error.errors
      if (!Object.keys(error.errors).length) {
        generalError.value = error.message
      }
    }
    else {
      generalError.value = 'Não foi possível salvar a transação.'
    }
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="flex flex-col my-4">
    <h1 class="text-4xl font-bold">Nova transação</h1>
    <p class="text-lg text-muted-foreground">
      Cadastre uma nova transação
    </p>
  </div>
  <div class="flex flex-col gap-4">
    <p v-if="generalError" class="text-destructive text-sm">
      {{ generalError }}
    </p>
    <FinanceForm
      submit-label="Cadastrar"
      :submitting="submitting"
      :errors="errors"
      @submit="onSubmit"
    />
  </div>
</template>
