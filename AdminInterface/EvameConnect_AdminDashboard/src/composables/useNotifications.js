import { ref } from 'vue'

// Pile de notifications partagée (singleton applicatif)
const notifications = ref([])
let seq = 0

const ICONS = {
  success: 'tabler-circle-check',
  error: 'tabler-alert-circle',
  warning: 'tabler-alert-triangle',
  info: 'tabler-info-circle',
}

export function useNotifications() {
  const dismiss = id => {
    notifications.value = notifications.value.filter(n => n.id !== id)
  }

  const notify = opts => {
    const n = {
      id: ++seq,
      color: 'success',
      title: '',
      message: '',
      timeout: 4500,
      ...(typeof opts === 'string' ? { message: opts } : opts),
    }
    n.icon = ICONS[n.color] ?? ICONS.info
    notifications.value.push(n)

    // Auto-fermeture : la barre de progression décroît sur la durée du timeout
    if (n.timeout)
      setTimeout(() => dismiss(n.id), n.timeout)

    return n.id
  }

  // Raccourcis sémantiques
  const notifySuccess = (message, title = 'Succès') => notify({ message, title, color: 'success' })
  const notifyError = (message, title = 'Erreur') => notify({ message, title, color: 'error', timeout: 6000 })
  const notifyInfo = (message, title = '') => notify({ message, title, color: 'info' })

  return { notifications, notify, notifySuccess, notifyError, notifyInfo, dismiss }
}
