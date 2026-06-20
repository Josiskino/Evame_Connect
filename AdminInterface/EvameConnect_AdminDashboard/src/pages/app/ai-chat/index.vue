<script setup>
import AiChatThreadList from '@/views/apps/ai-chat/AiChatThreadList.vue'
import AiChatThreadView from '@/views/apps/ai-chat/AiChatThreadView.vue'
import { useAiChatStore } from '@/stores/ai-chat'

definePage({ meta: { layout: 'default' } })

const store = useAiChatStore()

onMounted(async () => {
  await store.fetchThreads()
  // Auto-select the most recent thread on first load for a smoother
  // "back where I left it" feel.
  if (!store.activeThreadId && store.threads.length) {
    await store.selectThread(store.threads[0].id)
  }
})
</script>

<template>
  <VCard class="ai-chat-card" elevation="1">
    <div class="ai-chat-layout">
      <div class="ai-chat-sidebar">
        <AiChatThreadList />
      </div>
      <VDivider vertical />
      <div class="ai-chat-main">
        <AiChatThreadView />
      </div>
    </div>
  </VCard>
</template>

<style scoped>
.ai-chat-card { height: calc(100vh - 160px); overflow: hidden; }
.ai-chat-layout { display: flex; height: 100%; }
.ai-chat-sidebar { width: 320px; flex-shrink: 0; }
.ai-chat-main { flex: 1; min-width: 0; }
@media (max-width: 768px) {
  .ai-chat-sidebar { width: 100%; display: none; }
  .ai-chat-sidebar.is-active { display: block; }
}
</style>
