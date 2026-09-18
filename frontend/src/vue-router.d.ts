export {}

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    guest?: boolean
    title?: string
  }
}
