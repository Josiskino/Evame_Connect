import { defineStore } from 'pinia'
import { $api } from '@/utils/api'

/**
 * State for the full-page AI chat (/app/ai-chat). Holds the list of
 * threads, the currently selected thread, and helpers to send text /
 * voice messages. Stays separate from the existing useChatStore which
 * is the multi-user messenger.
 */
export const useAiChatStore = defineStore('ai-chat', () => {
  const threads = ref([])
  const activeThreadId = ref(null)
  const activeMessages = ref([])
  const isLoadingThreads = ref(false)
  const isLoadingMessages = ref(false)
  const isSending = ref(false)
  const isRecording = ref(false)

  const activeThread = computed(() =>
    threads.value.find(t => t.id === activeThreadId.value) ?? null,
  )

  const fetchThreads = async () => {
    isLoadingThreads.value = true
    try {
      const res = await $api('/ai/threads?per_page=50')
      threads.value = res?.data?.data ?? res?.data ?? []
    }
    catch {
      threads.value = []
    }
    finally {
      isLoadingThreads.value = false
    }
  }

  const createThread = async () => {
    const res = await $api('/ai/threads', { method: 'POST' })
    const thread = res?.data?.data ?? res?.data
    if (thread) {
      threads.value.unshift(thread)
      activeThreadId.value = thread.id
      activeMessages.value = []
    }
    return thread
  }

  const selectThread = async id => {
    if (id === activeThreadId.value) return
    activeThreadId.value = id
    if (!id) {
      activeMessages.value = []
      return
    }
    isLoadingMessages.value = true
    try {
      const res = await $api(`/ai/threads/${id}`)
      const thread = res?.data?.data ?? res?.data
      activeMessages.value = thread?.messages ?? []
      // Refresh the entry in the list (title may have been auto-set)
      const idx = threads.value.findIndex(t => t.id === id)
      if (idx >= 0 && thread) threads.value[idx] = { ...threads.value[idx], ...thread, messages: undefined }
    }
    catch {
      activeMessages.value = []
    }
    finally {
      isLoadingMessages.value = false
    }
  }

  const deleteThread = async id => {
    await $api(`/ai/threads/${id}`, { method: 'DELETE' })
    threads.value = threads.value.filter(t => t.id !== id)
    if (activeThreadId.value === id) {
      activeThreadId.value = null
      activeMessages.value = []
    }
  }

  const sendTextMessage = async content => {
    if (!content?.trim()) return
    let threadId = activeThreadId.value
    if (!threadId) {
      const t = await createThread()
      threadId = t?.id
      if (!threadId) return
    }

    isSending.value = true
    // Optimistic user bubble — replaced by the persisted version on success
    const tempId = `tmp-${Date.now()}`
    activeMessages.value.push({
      id: tempId, role: 'user', content, created_at: new Date().toISOString(),
    })

    try {
      const res = await $api(`/ai/threads/${threadId}/messages`, {
        method: 'POST',
        body: { content },
      })
      const data = res?.data?.data ?? res?.data
      // Swap optimistic with persisted user msg + append assistant
      activeMessages.value = activeMessages.value.filter(m => m.id !== tempId)
      if (data?.user_message) activeMessages.value.push(data.user_message)
      if (data?.assistant_message) activeMessages.value.push(data.assistant_message)
      // Bump thread to the top of the list and update last_message_at
      bumpThreadOnTop(threadId)
    }
    catch (err) {
      activeMessages.value = activeMessages.value.filter(m => m.id !== tempId)
      activeMessages.value.push({
        id: `err-${Date.now()}`, role: 'assistant',
        content: err?.data?.message ?? "Désolé, je n'arrive pas à répondre pour le moment.",
        isError: true, created_at: new Date().toISOString(),
      })
    }
    finally {
      isSending.value = false
    }
  }

  const sendVoiceMessage = async (audioBlob, durationSeconds = null) => {
    let threadId = activeThreadId.value
    if (!threadId) {
      const t = await createThread()
      threadId = t?.id
      if (!threadId) return
    }
    isSending.value = true
    const fd = new FormData()
    fd.append('audio', audioBlob, 'voice.webm')
    if (durationSeconds) fd.append('duration_seconds', String(Math.round(durationSeconds)))

    try {
      const res = await $api(`/ai/threads/${threadId}/voice-message`, {
        method: 'POST', body: fd,
      })
      const data = res?.data?.data ?? res?.data
      if (data?.user_message) activeMessages.value.push(data.user_message)
      if (data?.assistant_message) activeMessages.value.push(data.assistant_message)
      bumpThreadOnTop(threadId)
    }
    catch (err) {
      activeMessages.value.push({
        id: `err-${Date.now()}`, role: 'assistant',
        content: err?.data?.message ?? 'Impossible de traiter la note vocale.',
        isError: true, created_at: new Date().toISOString(),
      })
    }
    finally {
      isSending.value = false
    }
  }

  const transcribeAudio = async audioBlob => {
    const fd = new FormData()
    fd.append('audio', audioBlob, 'dictation.webm')
    fd.append('language', 'fr')
    const res = await $api('/ai/voice/transcribe', { method: 'POST', body: fd })
    return res?.data?.text ?? res?.text ?? ''
  }

  const bumpThreadOnTop = id => {
    const idx = threads.value.findIndex(t => t.id === id)
    if (idx < 0) return
    const updated = { ...threads.value[idx], last_message_at: new Date().toISOString() }
    threads.value.splice(idx, 1)
    threads.value.unshift(updated)
  }

  return {
    threads, activeThreadId, activeMessages, activeThread,
    isLoadingThreads, isLoadingMessages, isSending, isRecording,
    fetchThreads, createThread, selectThread, deleteThread,
    sendTextMessage, sendVoiceMessage, transcribeAudio,
  }
})
