<script setup>
import { useAiChatStore } from '@/stores/ai-chat'

const store = useAiChatStore()

const formatRelative = iso => {
  if (!iso) return ''
  const d = new Date(iso)
  const now = new Date()
  const diff = Math.floor((now - d) / 1000)
  if (diff < 60) return "à l'instant"
  if (diff < 3600) return `il y a ${Math.floor(diff / 60)} min`
  if (diff < 86400) return `il y a ${Math.floor(diff / 3600)} h`
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

const confirmDeleteId = ref(null)
const askDelete = id => { confirmDeleteId.value = id }
const doDelete = async () => {
  if (!confirmDeleteId.value) return
  await store.deleteThread(confirmDeleteId.value)
  confirmDeleteId.value = null
}
</script>

<template>
  <div class="thread-list h-100 d-flex flex-column">
    <div class="thread-list__header">
      <span class="text-h6">Conversations</span>
      <VBtn
        color="primary"
        size="small"
        prepend-icon="tabler-plus"
        @click="store.createThread()"
      >
        Nouveau
      </VBtn>
    </div>

    <div class="thread-list__body">
      <div v-if="store.isLoadingThreads" class="d-flex justify-center py-6">
        <VProgressCircular indeterminate size="24" width="2" />
      </div>
      <div v-else-if="!store.threads.length" class="thread-list__empty">
        <VIcon icon="tabler-message-2-off" size="36" class="mb-2 text-disabled" />
        <div class="text-body-2 text-medium-emphasis">
          Aucune conversation. Clique « Nouveau » pour démarrer.
        </div>
      </div>
      <ul v-else class="thread-list__items">
        <li
          v-for="t in store.threads"
          :key="t.id"
          :class="['thread-item', store.activeThreadId === t.id && 'thread-item--active']"
          @click="store.selectThread(t.id)"
        >
          <div class="thread-item__icon">
            <VIcon icon="tabler-message-2" size="18" />
          </div>
          <div class="thread-item__body">
            <div class="thread-item__title">
              {{ t.title ?? 'Nouvelle conversation' }}
            </div>
            <div class="thread-item__meta">
              {{ formatRelative(t.last_message_at ?? t.created_at) }}
              <span v-if="t.messages_count != null"> · {{ t.messages_count }} msg</span>
            </div>
          </div>
          <button
            class="thread-item__delete"
            title="Supprimer"
            @click.stop="askDelete(t.id)"
          >
            <VIcon icon="tabler-trash" size="16" />
          </button>
        </li>
      </ul>
    </div>

    <VDialog :model-value="!!confirmDeleteId" max-width="400" @update:model-value="confirmDeleteId = null">
      <VCard title="Supprimer la conversation">
        <VCardText>
          Cette action supprime tous les messages associés. Continuer ?
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="text" @click="confirmDeleteId = null">Annuler</VBtn>
          <VBtn color="error" variant="flat" @click="doDelete">Supprimer</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.thread-list__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 12px 14px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}
.thread-list__body { flex: 1; overflow-y: auto; padding: 8px; }
.thread-list__empty {
  text-align: center;
  padding: 32px 16px;
}
.thread-list__items {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.thread-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 10px 10px 12px;
  border-radius: 8px;
  cursor: pointer;
  position: relative;
  border-left: 3px solid transparent;
  transition: background 0.15s, border-color 0.15s;
}
.thread-item:hover { background: rgba(0,0,0,0.03); }
.thread-item:hover .thread-item__delete { opacity: 1; }
.thread-item--active {
  background: rgba(var(--v-theme-primary), 0.06);
  border-left-color: rgb(var(--v-theme-primary));
}

.thread-item__icon {
  width: 32px; height: 32px;
  border-radius: 8px;
  background: rgba(0,0,0,0.05);
  display: flex; align-items: center; justify-content: center;
  color: rgba(var(--v-theme-on-surface), 0.55);
  flex-shrink: 0;
}
.thread-item--active .thread-item__icon {
  background: rgba(var(--v-theme-primary), 0.12);
  color: rgb(var(--v-theme-primary));
}

.thread-item__body { flex: 1; min-width: 0; }
.thread-item__title {
  font-size: 13.5px;
  font-weight: 500;
  color: rgb(var(--v-theme-on-surface));
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.thread-item__meta {
  font-size: 11.5px;
  color: rgba(var(--v-theme-on-surface), 0.55);
  margin-top: 2px;
}

.thread-item__delete {
  border: 0;
  background: transparent;
  padding: 4px;
  border-radius: 4px;
  cursor: pointer;
  color: rgba(var(--v-theme-on-surface), 0.45);
  opacity: 0;
  transition: opacity 0.15s, background 0.15s, color 0.15s;
}
.thread-item__delete:hover {
  background: rgba(239,83,80,0.1);
  color: rgb(239,83,80);
}
.thread-item--active .thread-item__delete { opacity: 0.6; }
</style>
