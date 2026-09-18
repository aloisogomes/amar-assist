/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_URL: string
  readonly VITE_REVERB_APP_KEY: string
  readonly VITE_REVERB_HOST: string
  readonly VITE_REVERB_PORT: string
  readonly VITE_REVERB_SCHEME: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

export {}

declare module 'vue-router' {
  interface RouteMeta {
    title?: string
    requiresAuth?: boolean
    guest?: boolean
    breadcrumb?: Array<{
      label: string
      name?: string
    }>
  }
}
