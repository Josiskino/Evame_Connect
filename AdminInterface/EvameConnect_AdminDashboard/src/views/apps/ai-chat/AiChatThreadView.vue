<script setup>
import { useAiChatStore } from '@/stores/ai-chat'
import AiChatComposer from './AiChatComposer.vue'
import AiChatMessageBubble from './AiChatMessageBubble.vue'

const store = useAiChatStore()

const scrollContainer = ref(null)
const scrollToBottom = () => {
  if (scrollContainer.value) {
    scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight
  }
}
watch(() => store.activeMessages.length, async () => {
  await nextTick()
  scrollToBottom()
})
watch(() => store.activeThreadId, async () => {
  await nextTick()
  scrollToBottom()
})

const downloadPdf = () => {
  if (!store.activeThreadId) return
  const base = import.meta.env.VITE_API_BASE_URL || '/api'
  const token = useCookie('accessToken').value
  // We can't set headers on a tab open, so fetch as blob and trigger
  // download client-side.
  fetch(`${base}/ai/threads/${store.activeThreadId}/pdf`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/pdf' },
  })
    .then(r => r.blob())
    .then(blob => {
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `ai-thread-${store.activeThreadId}.pdf`
      document.body.appendChild(a)
      a.click()
      a.remove()
      URL.revokeObjectURL(url)
    })
}

const onClarification = async ({ missing, option }) => {
  // Re-fire the question with the override embedded as a normal user
  // turn so the persistence layer stores something explicit.
  const labelled = `${option.label}`
  await store.sendTextMessage(labelled + ' (précisé)')
}
</script>

<template>
  <div class="ai-chat-view h-100 d-flex flex-column">
    <!-- Header -->
    <div v-if="store.activeThread" class="thread-header">
      <div class="thread-header__title">
        <VIcon icon="tabler-sparkles" size="20" class="thread-header__icon" />
        <div class="min-width-0">
          <div class="text-body-1 font-weight-medium text-truncate">
            {{ store.activeThread.title ?? 'Nouvelle conversation' }}
          </div>
          <div class="text-caption text-medium-emphasis">
            Assistant douanier · TEC CEDEAO + LdF 2026
          </div>
        </div>
      </div>
      <VBtn
        variant="outlined"
        size="small"
        prepend-icon="tabler-file-download"
        :disabled="!store.activeMessages.length"
        @click="downloadPdf"
      >
        Télécharger PDF
      </VBtn>
    </div>

    <!-- Messages -->
    <div
      v-if="store.activeThread"
      ref="scrollContainer"
      class="flex-grow-1 overflow-y-auto pa-4"
    >
      <div v-if="store.isLoadingMessages" class="d-flex justify-center py-6">
        <VProgressCircular indeterminate size="24" width="2" />
      </div>
      <template v-else-if="!store.activeMessages.length">
        <div class="text-center text-medium-emphasis py-6">
          <VIcon icon="tabler-message-circle-question" size="48" class="mb-2" />
          <p class="text-body-2 mb-3">
            Pose une question : code SH, taxes par châssis, recherche dans le catalogue, etc.
          </p>
        </div>
      </template>
      <template v-else>
        <AiChatMessageBubble
          v-for="m in store.activeMessages"
          :key="m.id"
          :message="m"
          @pick-clarification="onClarification"
        />
        <div v-if="store.isSending" class="d-flex align-center gap-2 text-medium-emphasis">
          <VProgressCircular indeterminate size="16" width="2" />
          <span class="text-body-2">L'assistant réfléchit…</span>
        </div>
      </template>
    </div>

    <!-- Empty placeholder -->
    <div v-else class="flex-grow-1 d-flex flex-column align-center justify-center text-medium-emphasis">
      <VIcon icon="tabler-message-2" size="64" class="mb-3" />
      <p class="text-h6">Sélectionne une conversation</p>
      <p class="text-body-2 mb-4">ou crée-en une nouvelle pour démarrer.</p>
      <VBtn color="primary" prepend-icon="tabler-plus" @click="store.createThread()">
        Nouvelle conversation
      </VBtn>
    </div>

    <!-- Composer -->
    <AiChatComposer v-if="store.activeThread || store.activeThreadId" />
  </div>
</template>

<style scoped>
.thread-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 18px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}
.thread-header__title { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1; }
.thread-header__icon { color: rgb(var(--v-theme-primary)); flex-shrink: 0; }
.min-width-0 { min-width: 0; }
</style>
