import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import HomeView from '@/views/HomeView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import DashboardView from '@/views/dashboard/DashboardView.vue'
import CreateView from '@/views/finances/CreateView.vue'
import EditView from '@/views/finances/EditView.vue'
import ImportView from '@/views/finances/ImportView.vue'
import IndexView from '@/views/finances/IndexView.vue'
import ShowView from '@/views/finances/ShowView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: AppLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'home',
          component: HomeView,
          meta: { title: 'Início', breadcrumb: [{ label: 'Início' }] },
        },
        {
          path: 'finances',
          name: 'finances.index',
          component: IndexView,
          meta: {
            title: 'Financeiro',
            breadcrumb: [
              { label: 'Início', name: 'home' },
              { label: 'Financeiro' },
            ],
          },
        },
        {
          path: 'finances/create',
          name: 'finances.create',
          component: CreateView,
          meta: {
            title: 'Nova transação',
            breadcrumb: [
              { label: 'Início', name: 'home' },
              { label: 'Financeiro', name: 'finances.index' },
              { label: 'Nova transação' },
            ],
          },
        },
        {
          path: 'finances/import',
          name: 'finances.import',
          component: ImportView,
          meta: {
            title: 'Importar transações',
            breadcrumb: [
              { label: 'Início', name: 'home' },
              { label: 'Financeiro', name: 'finances.index' },
              { label: 'Importar transações' },
            ],
          },
        },
        {
          path: 'finances/dashboard',
          name: 'finances.dashboard',
          component: DashboardView,
          meta: {
            title: 'Dashboard',
            breadcrumb: [
              { label: 'Início', name: 'home' },
              { label: 'Dashboard' },
            ],
          },
        },
        {
          path: 'finances/:uuid',
          name: 'finances.show',
          component: ShowView,
          meta: {
            title: 'Detalhes da transação',
            breadcrumb: [
              { label: 'Início', name: 'home' },
              { label: 'Financeiro', name: 'finances.index' },
              { label: 'Detalhes da transação' },
            ],
          },
        },
        {
          path: 'finances/:uuid/edit',
          name: 'finances.edit',
          component: EditView,
          meta: {
            title: 'Alterar transação',
            breadcrumb: [
              { label: 'Início', name: 'home' },
              { label: 'Financeiro', name: 'finances.index' },
              { label: 'Detalhes da transação', name: 'finances.show' },
              { label: 'Alterar transação' },
            ],
          },
        },
      ],
    },
    {
      path: '/login',
      component: AuthLayout,
      meta: { guest: true },
      children: [
        {
          path: '',
          name: 'login',
          component: LoginView,
        },
      ],
    },
    {
      path: '/register',
      component: AuthLayout,
      meta: { guest: true },
      children: [
        {
          path: '',
          name: 'register',
          component: RegisterView,
        },
      ],
    },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'home' }
  }
})

export default router
