export type FinanceType = 'income' | 'expense'

export interface Finance {
  uuid: string
  description: string
  amount: number
  date: string
  type: FinanceType
  created_at: string
  updated_at: string
}

export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface FinancePayload {
  description: string
  amount: number
  date: string
  type: FinanceType
}

export interface FinanceListFilters {
  q?: string
  type?: FinanceType
  from?: string
  to?: string
  min_amount?: number
  max_amount?: number
}

export interface FinanceImportProgressPayload {
  import_id: string
  processed: number
  total: number
  created: number
  failed: number
  percent: number
}

export interface FinanceImportCompletedPayload {
  import_id: string
  created: number
  failed: number
  errors: Array<{ row: number, messages: string[] }>
  percent: number
}

export interface FinanceDashboardPoint {
  date: string
  income: number
  expense: number
}

export interface FinanceDashboardHighlight {
  description: string
  amount: number
  date: string
}

export interface FinanceDashboardPrevious {
  from: string
  to: string
  income_total: number
  expense_total: number
  balance: number
  income_change: number
  expense_change: number
  balance_change: number
}

export interface FinanceDashboardKpis {
  income_total: number
  expense_total: number
  balance: number
  transactions_count: number
  avg_daily_expense: number
  largest_income: FinanceDashboardHighlight | null
  largest_expense: FinanceDashboardHighlight | null
  previous: FinanceDashboardPrevious
}

export interface FinanceDashboard {
  from: string
  to: string
  series: FinanceDashboardPoint[]
  kpis: FinanceDashboardKpis
}
