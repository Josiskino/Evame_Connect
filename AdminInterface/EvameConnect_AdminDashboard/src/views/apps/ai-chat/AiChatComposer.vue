<script setup>
import { useAiChatStore } from '@/stores/ai-chat'
import { useVoiceRecorder } from '@/composables/useVoiceRecorder'

const store = useAiChatStore()
const recorder = useVoiceRecorder()

const text = ref('')
const voiceMode = ref('dictation') // 'dictation' | 'note'
const transcribing = ref(false)

const send = async () => {
  const value = text.value.trim()
  if (!value || store.isSending) return
  text.value = ''
  await store.sendTextMessage(value)
}

/**
 * Keyboard contract:
 *   - ENTER alone           → send the message
 *   - Ctrl/Cmd+ENTER        → insert a newline at the cursor
 *   - Shift+ENTER           → insert a newline (browser default)
 */
const onKeyDown = e => {
  if (e.key !== 'Enter') return

  if (e.shiftKey) return // browser handles newline insertion

  if (e.ctrlKey || e.metaKey) {
    e.preventDefault()
    const el = e.target
    const start = el.selectionStart ?? text.value.length
    const end = el.selectionEnd ?? text.value.length
    text.value = text.value.slice(0, start) + '\n' + text.value.slice(end)
    nextTick(() => {
      el.selectionStart = el.selectionEnd = start + 1
      // Trigger Vuetify auto-grow recalculation
      el.dispatchEvent(new Event('input'))
    })
    return
  }

  e.preventDefault()
  send()
}

const startRecord = async () => {
  if (!recorder.isSupported.value) {
    alert("L'enregistrement vocal n'est pas supporté sur ce navigateur.")
    return
  }
  try { await recorder.start() }
  catch { /* user denied mic */ }
}

const stopRecord = async () => {
  const result = await recorder.stop()
  if (!result?.blob) return

  if (voiceMode.value === 'dictation') {
    transcribing.value = true
    try {
      const txt = await store.transcribeAudio(result.blob)
      text.value = (text.value ? text.value + ' ' : '') + txt
    }
    catch { /* silent */ }
    finally { transcribing.value = false }
  }
  else {
    await store.sendVoiceMessage(result.blob, result.duration)
  }
}

const cancelRecord = () => recorder.cancel()

const formattedElapsed = computed(() => {
  const s = Math.floor(recorder.elapsedSeconds.value)
  return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`
})

const modeHint = computed(() => voiceMode.value === 'dictation'
  ? 'La voix sera transcrite dans le champ ci-dessous.'
  : 'L\'audio sera envoyé et conservé avec la transcription.')
</script>

<template>
  <div class="composer">
    <!-- Voice mode segmented control -->
    <div class="composer-mode-row">
      <span class="composer-mode-label">Mode voix</span>
      <div class="mode-segmented">
        <button
          type="button"
          :class="['mode-btn', voiceMode === 'dictation' && 'mode-btn--active']"
          @click="voiceMode = 'dictation'"
        >
          <VIcon icon="tabler-microphone-2" size="14" />
          <span>Dictée</span>
        </button>
        <button
          type="button"
          :class="['mode-btn', voiceMode === 'note' && 'mode-btn--active']"
          @click="voiceMode = 'note'"
        >
          <VIcon icon="tabler-message-circle-2" size="14" />
          <span>Note vocale</span>
        </button>
      </div>
      <span class="composer-mode-hint">{{ modeHint }}</span>
    </div>

    <!-- Recording state -->
    <div v-if="recorder.isRecording.value" class="recording-bar">
      <div class="recording-dot" />
      <span class="text-body-2 font-weight-medium">Enregistrement…</span>
      <span class="text-body-2 elapsed">{{ formattedElapsed }}</span>
      <VSpacer />
      <VBtn variant="text" size="small" @click="cancelRecord">
        Annuler
      </VBtn>
      <VBtn color="primary" size="small" prepend-icon="tabler-player-stop-filled" @click="stopRecord">
        {{ voiceMode === 'dictation' ? 'Transcrire' : 'Envoyer' }}
      </VBtn>
    </div>

    <!-- Input row -->
    <div v-else class="composer-input-row">
      <IconBtn
        variant="text"
        :loading="transcribing"
        :disabled="store.isSending"
        @click="startRecord"
      >
        <VIcon icon="tabler-microphone" />
        <VTooltip activator="parent" location="top">
          {{ voiceMode === 'dictation' ? 'Dictée vocale' : 'Note vocale' }}
        </VTooltip>
      </IconBtn>

      <VTextarea
        v-model="text"
        rows="1"
        auto-grow
        max-rows="6"
        density="compact"
        variant="outlined"
        hide-details
        placeholder="Pose ta question…  (Entrée pour envoyer · Ctrl+Entrée pour retour à la ligne)"
        @keydown="onKeyDown"
      />

      <VBtn
        color="primary"
        :loading="store.isSending"
        :disabled="!text.trim()"
        icon="tabler-send"
        size="small"
        @click="send"
      />
    </div>
  </div>
</template>

<style scoped>
.composer {
  border-top: 1px solid rgba(0,0,0,0.08);
  background: rgb(var(--v-theme-surface));
  padding: 12px 16px;
}

.composer-mode-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}
.composer-mode-label {
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), 0.55);
  font-weight: 500;
}
.composer-mode-hint {
  font-size: 11px;
  color: rgba(var(--v-theme-on-surface), 0.45);
  margin-inline-start: auto;
}

.mode-segmented {
  display: inline-flex;
  background: rgba(0,0,0,0.04);
  border-radius: 8px;
  padding: 2px;
  gap: 2px;
}
.mode-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 0;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12.5px;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.65);
  cursor: pointer;
  background: transparent;
  transition: background 0.15s, color 0.15s, box-shadow 0.15s;
  white-space: nowrap;
}
.mode-btn:hover { background: rgba(0,0,0,0.05); }
.mode-btn--active {
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}

.recording-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(239,83,80,0.08);
  border-radius: 8px;
  padding: 8px 12px;
}
.recording-dot {
  width: 10px; height: 10px; border-radius: 50%;
  background: #ef4444;
  animation: pulse 1.2s infinite ease-in-out;
  flex-shrink: 0;
}
.elapsed { font-variant-numeric: tabular-nums; opacity: 0.7; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

.composer-input-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;
}
</style>
