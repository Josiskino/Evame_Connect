import { defineStore } from 'pinia'
import { $api } from '@/utils/api'
import { getEcho, resetEcho } from '@/utils/echo'

export const useSidebarCountsStore = defineStore('sidebar-counts', () => {
  const quotes = ref(0)
  const dossiers = ref(0)
  const loaded = ref(false)
  let echoBound = false

  const fetch = async () => {
    try {
      const res = await $api('/stats/sidebar-counts')
      const data = res?.data ?? res
      quotes.value = data?.quotes ?? 0
      dossiers.value = data?.dossiers ?? 0
      loaded.value = true
    }
    catch {
      loaded.value = false
    }
  }

  const subscribe = () => {
    if (echoBound) return
    let echo
    try { echo = getEcho() }
    catch { return }
    if (!echo) return

    const channel = echo.private('admin-feed')

    channel.listen('.quote.created', () => { quotes.value++ })
    channel.listen('.quote.deleted', () => { quotes.value = Math.max(0, quotes.value - 1) })
    channel.listen('.dossier.created', () => { dossiers.value++ })
    channel.listen('.dossier.deleted', () => { dossiers.value = Math.max(0, dossiers.value - 1) })

    // Status changes don't move the total raw count but we re-fetch to
    // stay consistent in case a soft-delete or restore happened.
    channel.listen('.quote.status_changed', () => { /* count unchanged */ })
    channel.listen('.dossier.status_changed', () => { /* count unchanged */ })

    echoBound = true
  }

  const unsubscribe = () => {
    if (!echoBound) return
    try {
      const echo = getEcho()
      echo?.leave('admin-feed')
    }
    catch { /* noop */ }
    echoBound = false
  }

  const reset = () => {
    unsubscribe()
    resetEcho()
    quotes.value = 0
    dossiers.value = 0
    loaded.value = false
  }

  return { quotes, dossiers, loaded, fetch, subscribe, unsubscribe, reset }
})
