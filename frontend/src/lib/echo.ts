import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { getStoredToken } from '@/lib/api'

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

window.Pusher = Pusher

let echo: Echo<'reverb'> | null = null

export function getEcho(): Echo<'reverb'> | null {
  const token = getStoredToken()
  const key = import.meta.env.VITE_REVERB_APP_KEY

  if (!token || !key) {
    return null
  }

  if (echo) {
    return echo
  }

  echo = new Echo({
    broadcaster: 'reverb',
    key,
    wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/api/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  })

  return echo
}

export function disconnectEcho(): void {
  echo?.disconnect()
  echo = null
}
