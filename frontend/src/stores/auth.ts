import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { api, ApiError, setStoredToken } from '@/lib/api'
import { disconnectEcho } from '@/lib/echo'
import type { AuthResponse, User } from '@/types/auth'

const USER_KEY = 'auth_user'

function readStoredUser(): User | null {
  const raw = localStorage.getItem(USER_KEY)

  if (!raw) {
    return null
  }

  try {
    return JSON.parse(raw) as User
  }
  catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const user = ref<User | null>(readStoredUser())

  const isAuthenticated = computed(() => Boolean(token.value))

  function persist(nextToken: string, nextUser: User): void {
    token.value = nextToken
    user.value = nextUser
    setStoredToken(nextToken)
    localStorage.setItem(USER_KEY, JSON.stringify(nextUser))
  }

  function clear(): void {
    disconnectEcho()
    token.value = null
    user.value = null
    setStoredToken(null)
    localStorage.removeItem(USER_KEY)
  }

  async function register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<void> {
    const response = await api<AuthResponse>('/api/register', {
      method: 'POST',
      body: JSON.stringify(payload),
    })

    persist(response.token, response.user)
  }

  async function login(payload: { email: string, password: string }): Promise<void> {
    const response = await api<AuthResponse>('/api/login', {
      method: 'POST',
      body: JSON.stringify(payload),
    })

    persist(response.token, response.user)
  }

  async function fetchUser(): Promise<void> {
    if (!token.value) {
      clear()
      return
    }

    try {
      const nextUser = await api<User>('/api/user')
      user.value = nextUser
      localStorage.setItem(USER_KEY, JSON.stringify(nextUser))
    }
    catch (error) {
      if (error instanceof ApiError && error.status === 401) {
        clear()
      }

      throw error
    }
  }

  async function logout(): Promise<void> {
    try {
      await api('/api/logout', { method: 'POST' })
    }
    catch (error) {
      if (!(error instanceof ApiError && error.status === 401)) {
        throw error
      }
    }
    finally {
      clear()
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    register,
    login,
    fetchUser,
    logout,
    clear,
  }
})
