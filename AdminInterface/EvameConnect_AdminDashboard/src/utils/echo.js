import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const createEcho = () => {
  const accessToken = useCookie('accessToken').value

  // Auth endpoint: /api/broadcasting/auth (sous le groupe api = CORS + Sanctum)
  const apiBase = (import.meta.env.VITE_API_BASE_URL || 'http://localhost/api').replace(/\/v\d+\/?$/, '')

  return new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || '',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    forceTLS: true,
    authEndpoint: `${apiBase}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${accessToken}`,
        Accept: 'application/json',
      },
    },
  })
}

let echoInstance = null

export const getEcho = () => {
  if (!echoInstance) {
    echoInstance = createEcho()
  }

  return echoInstance
}

export const resetEcho = () => {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
  }
}
