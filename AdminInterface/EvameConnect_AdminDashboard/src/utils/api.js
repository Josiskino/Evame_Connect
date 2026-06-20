import { ofetch } from 'ofetch'

/**
 * Appends ngrok bypass query param so <img> tags can load media in dev.
 * In production the URL is returned unchanged.
 */
export const toMediaUrl = url => {
  if (!url) return url
  if (!import.meta.env.DEV) return url

  try {
    const apiOrigin = new URL(import.meta.env.VITE_API_BASE_URL).origin
    if (url.startsWith(apiOrigin)) {
      return `${url}?ngrok-skip-browser-warning=true`
    }
  } catch {}

  return url
}

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  async onRequest({ options }) {
    const accessToken = useCookie('accessToken').value

    options.headers = {
      ...options.headers,
      'ngrok-skip-browser-warning': 'true',
      'Accept': 'application/json',
    }

    if (accessToken) {
      options.headers = {
        ...options.headers,
        Authorization: `Bearer ${accessToken}`,
      }
    }
  },
})
