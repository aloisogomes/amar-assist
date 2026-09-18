import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type {
  FinanceImportCompletedPayload,
  FinanceImportProgressPayload,
} from '@/types/finance'

export type FinanceImportStatus = 'idle' | 'processing' | 'completed' | 'failed'

export const useFinanceImportStore = defineStore('financeImport', () => {
  const importId = ref<string | null>(null)
  const status = ref<FinanceImportStatus>('idle')
  const processed = ref(0)
  const total = ref(0)
  const created = ref(0)
  const failed = ref(0)
  const percent = ref(0)

  const isProcessing = computed(() => status.value === 'processing')

  function start(nextImportId: string): void {
    importId.value = nextImportId
    status.value = 'processing'
    processed.value = 0
    total.value = 0
    created.value = 0
    failed.value = 0
    percent.value = 0
  }

  function applyProgress(payload: FinanceImportProgressPayload): void {
    if (importId.value && payload.import_id !== importId.value) {
      return
    }

    importId.value = payload.import_id
    status.value = 'processing'
    processed.value = payload.processed
    total.value = payload.total
    created.value = payload.created
    failed.value = payload.failed
    percent.value = payload.percent
  }

  function complete(payload: FinanceImportCompletedPayload): void {
    if (importId.value && payload.import_id !== importId.value) {
      return
    }

    importId.value = payload.import_id
    created.value = payload.created
    failed.value = payload.failed
    percent.value = payload.percent ?? 100
    processed.value = Math.max(processed.value, payload.created + payload.failed)
    status.value = payload.created === 0 && payload.errors.length > 0 && payload.failed === 0
      ? 'failed'
      : 'completed'
  }

  return {
    importId,
    status,
    processed,
    total,
    created,
    failed,
    percent,
    isProcessing,
    start,
    applyProgress,
    complete,
  }
})
