<script setup lang="ts">
import { Download, Upload } from '@lucide/vue'
import { storeToRefs } from 'pinia'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Progress } from '@/components/ui/progress'
import { api, ApiError, downloadFile } from '@/lib/api'
import { useFinanceImportStore } from '@/stores/financeImport'

const financeImport = useFinanceImportStore()
const { status, percent, processed, total, created, failed, isProcessing } = storeToRefs(financeImport)

const file = ref<File | null>(null)
const submitting = ref(false)
const downloading = ref(false)
const message = ref('')
const errorMessage = ref('')

function onFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  file.value = input.files?.[0] ?? null
}

async function downloadTemplate() {
  downloading.value = true
  errorMessage.value = ''

  try {
    await downloadFile('/api/finances/template', 'financial_transactions.xlsx')
  }
  catch (error) {
    errorMessage.value = error instanceof ApiError ? error.message : 'Não foi possível baixar o modelo.'
  }
  finally {
    downloading.value = false
  }
}

async function onSubmit() {
  if (!file.value) {
    errorMessage.value = 'Selecione um arquivo .xlsx ou .csv.'
    return
  }

  errorMessage.value = ''
  message.value = ''
  submitting.value = true

  const body = new FormData()
  body.append('file', file.value)

  try {
    const response = await api<{ message: string, import_id: string }>('/api/finances/import', {
      method: 'POST',
      body,
    })
    financeImport.start(response.import_id)
    message.value = 'Processando a planilha. O progresso aparece abaixo.'
  }
  catch (error) {
    if (error instanceof ApiError) {
      errorMessage.value = error.errors.file?.[0] ?? error.message
    }
    else {
      errorMessage.value = 'Não foi possível enviar a planilha.'
    }
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="flex flex-col my-4">
    <h1 class="text-4xl font-bold">Importar transações</h1>
    <p class="text-lg text-muted-foreground">
      Envie um arquivo .xlsx ou .csv com as colunas date, description, amount e type.
      Arquivos grandes são processados em lotes.
    </p>
  </div>
  <Card class="max-w-xl">
    <CardContent class="flex flex-col gap-4">
      <Button variant="outline" class="w-fit" :disabled="downloading" @click="downloadTemplate">
        <Download />
        {{ downloading ? 'Baixando...' : 'Baixar modelo .xlsx' }}
      </Button>

      <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
        <div class="flex flex-col gap-2">
          <Label for="file">Arquivo</Label>
          <Input
            id="file"
            type="file"
            accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
            :disabled="isProcessing"
            @change="onFileChange"
          />
        </div>
        <p v-if="errorMessage" class="text-destructive text-sm">
          {{ errorMessage }}
        </p>
        <p v-if="message" class="text-sm text-muted-foreground">
          {{ message }}
        </p>
        <div v-if="status !== 'idle'" class="flex flex-col gap-2">
          <div class="flex items-center justify-between text-sm">
            <span>{{ percent }}%</span>
            <span class="text-muted-foreground">
              {{ processed }} / {{ total || '—' }}
            </span>
          </div>
          <Progress :model-value="percent" />
          <p class="text-muted-foreground text-sm">
            {{ created }} criadas · {{ failed }} com erro
          </p>
        </div>
        <Button type="submit" :disabled="submitting || isProcessing">
          <Upload />
          {{ isProcessing ? 'Processando...' : submitting ? 'Enviando...' : 'Enviar para processamento' }}
        </Button>
      </form>
    </CardContent>
  </Card>
</template>
