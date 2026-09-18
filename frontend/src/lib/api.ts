import type { ValidationErrors } from '@/types/auth'

const TOKEN_KEY = 'auth_token'

export function getStoredToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setStoredToken(token: string | null): void {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token)
    return
  }

  localStorage.removeItem(TOKEN_KEY)
}

export class ApiError extends Error {
  status: number
  errors: ValidationErrors

  constructor(
    status: number,
    errors: ValidationErrors = {},
    message = 'Request failed',
  ) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

function apiUrl(path: string): string {
  const base = import.meta.env.VITE_API_URL ?? ''

  return `${base.replace(/\/$/, '')}${path}`
}

function defaultHeaders(init?: HeadersInit): Headers {
  const headers = new Headers(init)
  headers.set('Accept', 'application/json')

  const token = getStoredToken()

  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  return headers
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = defaultHeaders(options.headers)
  const isFormData = options.body instanceof FormData

  if (options.body && !isFormData && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  const response = await fetch(apiUrl(path), {
    ...options,
    headers,
  })

  if (response.status === 204) {
    return undefined as T
  }

  const payload = await response.json().catch(() => ({})) as {
    message?: string
    errors?: ValidationErrors
  }

  if (!response.ok) {
    throw new ApiError(
      response.status,
      payload.errors ?? {},
      payload.message ?? 'Request failed',
    )
  }

  return payload as T
}

export async function downloadFile(path: string, filename: string): Promise<void> {
  const headers = defaultHeaders()
  headers.set('Accept', 'application/octet-stream')

  const response = await fetch(apiUrl(path), { headers })

  if (!response.ok) {
    throw new ApiError(response.status, {}, 'Download failed')
  }

  const blob = await response.blob()
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.click()
  URL.revokeObjectURL(url)
}
