import { defineStore } from 'pinia'
import { $api } from '@/utils/api'
import { getEcho } from '@/utils/echo'

/**
 * Centralises Laravel database notifications and live broadcasts so the
 * bell in the top nav always reflects what is stored server-side, then
 * is augmented in real-time when new ones land via Pusher.
 */
export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref([])
  const lastNewOrder = ref(null)
  let _echoStarted = false
  let _currentUserId = null

  const hasUnread = computed(() => notifications.value.some(n => !n.isSeen))
  const unreadCount = computed(() => notifications.value.filter(n => !n.isSeen).length)

  const _timeLabel = (iso = null) => {
    const d = iso ? new Date(iso) : new Date()
    const now = new Date()
    const diff = Math.floor((now - d) / 1000)
    if (diff < 60) return "à l'instant"
    if (diff < 3600) return `il y a ${Math.floor(diff / 60)} min`
    if (diff < 86400) return `il y a ${Math.floor(diff / 3600)} h`
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
  }

  const _iconForType = type => ({
    'quote.pending_reminder': { icon: 'tabler-clock-hour-4', color: 'warning' },
    'quote.created': { icon: 'tabler-file-invoice', color: 'primary' },
    'quote.status_changed': { icon: 'tabler-arrow-right-circle', color: 'info' },
    'dossier.created': { icon: 'tabler-folder-plus', color: 'primary' },
    'dossier.status_changed': { icon: 'tabler-folder', color: 'info' },
    'dossier.delivered': { icon: 'tabler-truck-delivery', color: 'success' },
  }[type] ?? { icon: 'tabler-bell', color: 'default' })

  const _toUiNotification = raw => {
    const data = raw.data ?? raw
    const type = data.type ?? raw.type ?? 'generic'
    const { icon, color } = _iconForType(type)
    return {
      id: raw.id,
      type,
      title: data.title ?? (type.includes('quote') ? 'Devis' : type.includes('dossier') ? 'Dossier' : 'Notification'),
      subtitle: data.message ?? '',
      time: _timeLabel(raw.created_at ?? raw.timestamp ?? data.occurred_at),
      isSeen: !!raw.read_at,
      icon,
      color,
      cta_url: data.cta_url ?? null,
      raw,
    }
  }

  const fetchInitial = async () => {
    try {
      const res = await $api('/me/notifications?per_page=20')
      const items = res?.data ?? []
      notifications.value = items.map(_toUiNotification)
    }
    catch { /* silently fail — bell stays empty */ }
  }

  const addFromBroadcast = payload => {
    const id = payload.id ?? `live-${Date.now()}`
    const ui = _toUiNotification({
      id,
      type: payload.type,
      data: payload,
      read_at: null,
      created_at: payload.occurred_at ?? new Date().toISOString(),
    })
    if (!notifications.value.find(n => n.id === ui.id)) {
      notifications.value.unshift(ui)
    }
  }

  const startEchoListener = userId => {
    if (_echoStarted) return
    if (!userId) return
    let echo
    try { echo = getEcho() }
    catch { return }
    if (!echo) return

    _currentUserId = userId
    _echoStarted = true

    echo.private(`App.Models.User.${userId}`)
      .notification(payload => addFromBroadcast(payload))
  }

  const stopEchoListener = () => {
    if (!_echoStarted || !_currentUserId) return
    try {
      const echo = getEcho()
      echo?.leave(`App.Models.User.${_currentUserId}`)
    }
    catch { /* noop */ }
    _echoStarted = false
    _currentUserId = null
  }

  const markRead = async ids => {
    notifications.value.forEach(n => { if (ids.includes(n.id)) n.isSeen = true })
    for (const id of ids) {
      try { await $api(`/me/notifications/${id}/read`, { method: 'POST' }) }
      catch { /* ignore individual errors */ }
    }
  }

  const markUnread = ids => {
    notifications.value.forEach(n => { if (ids.includes(n.id)) n.isSeen = false })
  }

  const remove = async id => {
    const idx = notifications.value.findIndex(n => n.id === id)
    if (idx !== -1) notifications.value.splice(idx, 1)
    try { await $api(`/me/notifications/${id}`, { method: 'DELETE' }) }
    catch { /* ignore */ }
  }

  return {
    notifications,
    lastNewOrder,
    hasUnread,
    unreadCount,
    fetchInitial,
    addFromBroadcast,
    startEchoListener,
    stopEchoListener,
    markRead,
    markUnread,
    remove,
  }
})
