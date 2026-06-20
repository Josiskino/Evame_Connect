<script setup>
import { marked } from 'marked'

const props = defineProps({
  message: { type: Object, required: true },
})

marked.setOptions({
  breaks: true,    // newlines become <br>
  gfm: true,       // GitHub-flavoured (tables, fenced code…)
})

const formatTime = iso => {
  if (!iso) return ''
  return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

const isUser = computed(() => props.message.role === 'user')

const renderedContent = computed(() => {
  if (isUser.value) return null // user messages stay raw
  return marked.parse(props.message.content ?? '')
})

const sourceBadge = computed(() => ({
  chassis_lookup: { icon: 'tabler-id', label: 'Châssis BDD', color: 'success' },
  catalogue: { icon: 'tabler-package', label: 'Catalogue', color: 'info' },
  filtered: { icon: 'tabler-filter', label: 'Recherche filtrée', color: 'primary' },
  semantic: { icon: 'tabler-search', label: 'Recherche large', color: 'info' },
}[props.message.source]))

// PDF preview card: shown when the assistant message carries an
// attachment payload (built by the backend when the user asked for a
// PDF export of a previous estimation).
const pdfAttachment = computed(() => {
  const att = props.message.attachment
  if (att && att.kind === 'pdf' && att.thread_id && att.message_id) return att
  return null
})

const downloadingPdf = ref(false)
const isPdfPreviewOpen = ref(false)
const pdfPreviewUrl = ref('')
const pdfPreviewError = ref('')

const fetchPdfBlobUrl = async () => {
  const att = pdfAttachment.value
  if (!att) return null
  const accessToken = useCookie('accessToken').value
  const base = import.meta.env.VITE_API_BASE_URL || '/api'
  const url = `${base}/ai/threads/${att.thread_id}/messages/${att.message_id}/pdf`
  const res = await fetch(url, {
    headers: { Authorization: `Bearer ${accessToken}`, Accept: 'application/pdf' },
  })
  if (!res.ok) throw new Error('PDF generation failed')
  const blob = await res.blob()
  return URL.createObjectURL(blob)
}

const ensurePdfPreviewUrl = async () => {
  if (pdfPreviewUrl.value) return pdfPreviewUrl.value
  pdfPreviewError.value = ''
  const blobUrl = await fetchPdfBlobUrl()
  if (blobUrl) pdfPreviewUrl.value = blobUrl
  return blobUrl
}

const openPdfPreview = async () => {
  if (downloadingPdf.value) return
  isPdfPreviewOpen.value = true
  downloadingPdf.value = true
  try {
    await ensurePdfPreviewUrl()
  }
  catch {
    pdfPreviewError.value = "Impossible d'ouvrir l'aperçu PDF pour le moment."
  }
  finally { downloadingPdf.value = false }
}

const openPdfInTab = async () => {
  if (downloadingPdf.value) return
  downloadingPdf.value = true
  try {
    const blobUrl = await ensurePdfPreviewUrl()
    if (blobUrl) window.open(blobUrl, '_blank')
  }
  catch {
    pdfPreviewError.value = "Impossible d'ouvrir le PDF dans un nouvel onglet."
  }
  finally { downloadingPdf.value = false }
}

const downloadPdfFile = async () => {
  if (downloadingPdf.value) return
  downloadingPdf.value = true
  try {
    const blobUrl = await ensurePdfPreviewUrl()
    if (!blobUrl) return
    const a = document.createElement('a')
    a.href = blobUrl
    a.download = pdfAttachment.value.filename || `estimation-${Date.now()}.pdf`
    document.body.appendChild(a)
    a.click()
    a.remove()
  }
  catch {
    pdfPreviewError.value = 'Impossible de télécharger le PDF pour le moment.'
  }
  finally { downloadingPdf.value = false }
}

onBeforeUnmount(() => {
  if (pdfPreviewUrl.value) URL.revokeObjectURL(pdfPreviewUrl.value)
})
</script>

<template>
  <div :class="['bubble-row', isUser ? 'bubble-row--user' : 'bubble-row--bot']">
    <div :class="['bubble', isUser ? 'bubble--user' : 'bubble--bot', message.isError && 'bubble--error']">
      <!-- Voice note playback -->
      <div v-if="isUser && message.has_audio" class="mb-2">
        <audio :src="message.audio_url" controls class="audio-player" />
        <div v-if="message.transcription" class="audio-transcript">
          <VIcon icon="tabler-quote" size="12" class="me-1" />
          {{ message.transcription }}
        </div>
      </div>

      <!-- Source provenance badge for assistant -->
      <div v-if="!isUser && sourceBadge" class="mb-2">
        <VChip :color="sourceBadge.color" size="x-small" variant="tonal" :prepend-icon="sourceBadge.icon">
          {{ sourceBadge.label }}
        </VChip>
      </div>

      <!-- Content: markdown for assistant, plain for user -->
      <div v-if="!isUser" class="bubble-content markdown" v-html="renderedContent" />
      <div v-else class="bubble-content">{{ message.content }}</div>

      <!-- PDF attachment preview card (export prompt) -->
      <div v-if="pdfAttachment" class="pdf-card mt-3" role="button" tabindex="0" @click="openPdfPreview" @keydown.enter.prevent="openPdfPreview">
        <div class="pdf-card__preview" aria-hidden="true">
          <div class="pdf-card__paper">
            <div class="pdf-card__paper-head">
              <span />
              <span />
            </div>
            <div class="pdf-card__paper-title" />
            <div class="pdf-card__paper-line pdf-card__paper-line--wide" />
            <div class="pdf-card__paper-line" />
            <div class="pdf-card__paper-table">
              <span v-for="i in 8" :key="i" />
            </div>
            <div class="pdf-card__paper-total" />
          </div>
        </div>
        <div class="pdf-card__body">
          <div class="pdf-card__eyebrow">
            <VIcon icon="tabler-file-type-pdf" size="13" />
            PDF prêt
          </div>
          <div class="pdf-card__title">{{ pdfAttachment.title || 'Document généré' }}</div>
          <div class="pdf-card__subtitle">
            {{ pdfAttachment.filename }}
            <span v-if="pdfAttachment.subtitle"> · {{ pdfAttachment.subtitle }}</span>
          </div>
          <div class="pdf-card__actions">
            <button
              class="pdf-card__btn pdf-card__btn--primary"
              :disabled="downloadingPdf"
              @click.stop="openPdfPreview"
            >
              <VIcon :icon="downloadingPdf ? 'tabler-loader-2' : 'tabler-maximize'" size="14" />
              <span>{{ downloadingPdf ? 'Chargement…' : 'Agrandir' }}</span>
            </button>
            <button
              class="pdf-card__btn"
              :disabled="downloadingPdf"
              @click.stop="downloadPdfFile"
            >
              <VIcon icon="tabler-download" size="14" />
              <span>Télécharger</span>
            </button>
          </div>
        </div>
      </div>

      <VDialog
        v-if="pdfAttachment"
        v-model="isPdfPreviewOpen"
        max-width="1040"
        scrollable
        class="pdf-dialog"
      >
        <div class="pdf-viewer">
          <div class="pdf-viewer__header">
            <div class="pdf-viewer__title-wrap">
              <div class="pdf-viewer__eyebrow">
                <VIcon icon="tabler-file-type-pdf" size="15" />
                Aperçu du document
              </div>
              <div class="pdf-viewer__title">
                {{ pdfAttachment.title || pdfAttachment.filename || 'Document PDF' }}
              </div>
            </div>
            <div class="pdf-viewer__actions">
              <button class="pdf-viewer__icon-btn" :disabled="downloadingPdf" title="Ouvrir dans un nouvel onglet" @click="openPdfInTab">
                <VIcon icon="tabler-external-link" size="18" />
              </button>
              <button class="pdf-viewer__icon-btn" :disabled="downloadingPdf" title="Télécharger" @click="downloadPdfFile">
                <VIcon icon="tabler-download" size="18" />
              </button>
              <button class="pdf-viewer__icon-btn" title="Fermer" @click="isPdfPreviewOpen = false">
                <VIcon icon="tabler-x" size="18" />
              </button>
            </div>
          </div>

          <div class="pdf-viewer__stage">
            <div v-if="downloadingPdf && !pdfPreviewUrl" class="pdf-viewer__loading">
              <VProgressCircular indeterminate size="28" width="3" />
              <span>Préparation du document…</span>
            </div>
            <div v-else-if="pdfPreviewError" class="pdf-viewer__error">
              <VIcon icon="tabler-alert-circle" size="22" />
              <span>{{ pdfPreviewError }}</span>
            </div>
            <iframe
              v-else-if="pdfPreviewUrl"
              :src="pdfPreviewUrl"
              class="pdf-viewer__frame"
              title="Aperçu PDF généré par l'assistant"
            />
          </div>
        </div>
      </VDialog>

      <!-- Clarification options -->
      <div v-if="message.needs_clarification && message.clarification" class="clarification mt-3">
        <div class="text-body-2 font-weight-medium mb-2">
          {{ message.clarification.question }}
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button
            v-for="opt in message.clarification.options"
            :key="opt.value"
            class="clarification-chip"
            @click="$emit('pickClarification', { missing: message.clarification.missing, option: opt })"
          >
            {{ opt.label }}
          </button>
        </div>
      </div>

      <!-- Citations -->
      <div v-if="!isUser && message.citations?.length" class="citations">
        <div class="citations__label">Sources</div>
        <div
          v-for="c in message.citations"
          :key="c.index"
          class="citations__item"
        >
          <span class="citations__index">[{{ c.index }}]</span>
          <span>
            <strong>{{ c.code }}</strong>
            <template v-if="c.fuel"> · <em>{{ c.fuel }}</em></template>
            <template v-if="c.condition"> · {{ c.condition }}</template>
            <template v-if="c.description"> — {{ c.description.slice(0, 110) }}</template>
            <template v-if="c.rates?.total">
              <span class="citations__rate">({{ c.rates.total }}%)</span>
            </template>
          </span>
        </div>
      </div>

      <div class="bubble-time">{{ formatTime(message.created_at) }}</div>
    </div>
  </div>
</template>

<style scoped>
.bubble-row { display: flex; margin-bottom: 14px; }
.bubble-row--user { justify-content: flex-end; }
.bubble-row--bot { justify-content: flex-start; }

.bubble {
  max-width: 80%;
  padding: 12px 16px;
  border-radius: 14px;
  font-size: 14px;
  line-height: 1.55;
  word-break: break-word;
}
.bubble--user {
  background: rgb(var(--v-theme-primary));
  color: white;
  border-bottom-right-radius: 4px;
}
.bubble--user .bubble-time { color: rgba(255,255,255,0.7); text-align: end; }
.bubble--bot {
  background: rgb(248, 250, 252); /* much lighter than the previous theme-surface-light */
  color: rgb(31, 41, 55);
  border: 1px solid rgba(0,0,0,0.04);
  border-bottom-left-radius: 4px;
}
.bubble--error {
  background: rgba(239,83,80,0.08);
  color: rgb(180,30,30);
  border-color: rgba(239,83,80,0.2);
}
.bubble-content { white-space: normal; }

/* Markdown styling for assistant bubbles */
.markdown :deep(p) { margin: 0 0 8px; }
.markdown :deep(p:last-child) { margin-bottom: 0; }
.markdown :deep(ul),
.markdown :deep(ol) { margin: 4px 0 8px; padding-inline-start: 22px; }
.markdown :deep(li) { margin-bottom: 4px; }
.markdown :deep(li:last-child) { margin-bottom: 0; }
.markdown :deep(strong) { color: rgb(15, 23, 42); font-weight: 600; }
.markdown :deep(em) { color: rgba(31, 41, 55, 0.85); }
.markdown :deep(code) {
  background: rgba(0,0,0,0.05);
  padding: 1px 5px;
  border-radius: 4px;
  font-size: 12.5px;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}
.markdown :deep(pre) {
  background: rgba(0,0,0,0.06);
  padding: 10px;
  border-radius: 6px;
  overflow-x: auto;
  font-size: 12.5px;
  margin: 6px 0;
}
.markdown :deep(a) {
  color: rgb(var(--v-theme-primary));
  text-decoration: underline;
}
.markdown :deep(h1),
.markdown :deep(h2),
.markdown :deep(h3) {
  font-size: 15px;
  font-weight: 600;
  margin: 10px 0 4px;
}
.markdown :deep(blockquote) {
  border-inline-start: 3px solid rgba(0,0,0,0.15);
  margin: 6px 0;
  padding: 2px 10px;
  color: rgba(31, 41, 55, 0.75);
}

/* Tables (e.g. the customs breakdown): label column left, amount column right */
.markdown :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 6px 0;
  font-variant-numeric: tabular-nums;
}
.markdown :deep(th),
.markdown :deep(td) {
  padding: 5px 8px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  text-align: left;
}
.markdown :deep(th) {
  font-weight: 600;
  color: rgb(15, 23, 42);
}
.markdown :deep(th:last-child),
.markdown :deep(td:last-child) {
  text-align: right;
  white-space: nowrap;
}

.audio-player {
  width: 100%;
  max-width: 320px;
  height: 36px;
}
.audio-transcript {
  font-size: 12px;
  color: rgba(255,255,255,0.85);
  font-style: italic;
  margin-top: 4px;
}

.clarification { padding-top: 10px; border-top: 1px dashed rgba(0,0,0,0.1); }
.clarification-chip {
  display: inline-flex;
  align-items: center;
  border: 1px solid rgba(var(--v-theme-primary), 0.4);
  background: rgba(var(--v-theme-primary), 0.06);
  color: rgb(var(--v-theme-primary));
  padding: 5px 12px;
  border-radius: 16px;
  font-size: 12.5px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s;
}
.clarification-chip:hover { background: rgba(var(--v-theme-primary), 0.14); }

.citations {
  margin-top: 12px;
  padding-top: 8px;
  border-top: 1px dashed rgba(0,0,0,0.1);
}
.citations__label {
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 10.5px;
  color: rgba(31, 41, 55, 0.55);
  margin-bottom: 5px;
}
.citations__item {
  display: flex;
  align-items: flex-start;
  gap: 4px;
  font-size: 11.5px;
  color: rgba(31, 41, 55, 0.78);
  margin-bottom: 3px;
}
.citations__index { font-weight: 600; color: rgb(var(--v-theme-primary)); flex-shrink: 0; }
.citations__rate { color: rgba(31, 41, 55, 0.55); margin-inline-start: 4px; }

.bubble-time {
  font-size: 10.5px;
  color: rgba(31, 41, 55, 0.45);
  margin-top: 6px;
}

/* PDF attachment preview card */
.pdf-card {
  display: grid;
  grid-template-columns: 112px minmax(0, 1fr);
  gap: 14px;
  align-items: stretch;
  background: rgb(255, 255, 255);
  border: 1px solid rgba(15, 23, 42, 0.1);
  border-radius: 12px;
  padding: 12px;
  cursor: pointer;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
  transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.12s ease;
}
.pdf-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.34);
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.12);
  transform: translateY(-1px);
}
.pdf-card:active { transform: scale(0.99); }
.pdf-card:focus-visible {
  outline: 2px solid rgba(var(--v-theme-primary), 0.45);
  outline-offset: 3px;
}
.pdf-card__preview {
  min-height: 148px;
  border-radius: 10px;
  background:
    linear-gradient(145deg, rgba(15, 23, 42, 0.08), rgba(15, 23, 42, 0.02)),
    rgb(246, 248, 251);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.pdf-card__paper {
  width: 78px;
  min-height: 116px;
  padding: 9px;
  border-radius: 4px;
  background: rgb(255, 253, 249);
  border: 1px solid rgba(15, 23, 42, 0.12);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.16);
  transform: rotate(-1.5deg);
}
.pdf-card__paper-head {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 9px;
}
.pdf-card__paper-head span:first-child {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: rgba(var(--v-theme-primary), 0.16);
}
.pdf-card__paper-head span:last-child {
  width: 24px;
  height: 4px;
  margin-top: 3px;
  border-radius: 999px;
  background: rgba(220, 38, 38, 0.7);
}
.pdf-card__paper-title,
.pdf-card__paper-line,
.pdf-card__paper-total {
  height: 4px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.2);
}
.pdf-card__paper-title {
  width: 68%;
  height: 6px;
  margin-bottom: 8px;
  background: rgba(15, 23, 42, 0.72);
}
.pdf-card__paper-line {
  width: 72%;
  margin-bottom: 5px;
}
.pdf-card__paper-line--wide { width: 100%; }
.pdf-card__paper-table {
  display: grid;
  grid-template-columns: 1fr 0.65fr;
  gap: 4px;
  margin-top: 10px;
}
.pdf-card__paper-table span {
  height: 5px;
  border-radius: 2px;
  background: rgba(15, 23, 42, 0.1);
}
.pdf-card__paper-total {
  width: 48%;
  margin-block-start: 10px;
  margin-inline-start: auto;
  background: rgba(var(--v-theme-primary), 0.45);
}
.pdf-card__body { flex: 1; min-width: 0; }
.pdf-card__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: rgb(185, 28, 28);
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 6px;
}
.pdf-card__title {
  font-size: 14px;
  font-weight: 700;
  color: rgb(17, 24, 39);
  line-height: 1.25;
}
.pdf-card__subtitle {
  font-size: 11.5px;
  color: rgba(31, 41, 55, 0.6);
  margin-top: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
}
.pdf-card__actions {
  display: flex;
  gap: 6px;
  margin-top: 8px;
  flex-wrap: wrap;
}
.pdf-card__btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border: 1px solid rgba(0,0,0,0.12);
  background: white;
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 500;
  color: rgb(55, 65, 81);
  cursor: pointer;
  transition: background 0.12s, border-color 0.12s;
}
.pdf-card__btn:hover {
  background: rgba(0,0,0,0.04);
  border-color: rgba(0,0,0,0.2);
}
.pdf-card__btn--primary {
  background: rgb(17, 24, 39);
  color: white;
  border-color: rgb(17, 24, 39);
}
.pdf-card__btn--primary:hover {
  background: rgb(31, 41, 55);
  border-color: rgb(31, 41, 55);
}
.pdf-card__btn:disabled {
  opacity: 0.6;
  cursor: wait;
}

.pdf-viewer {
  overflow: hidden;
  border-radius: 14px;
  background: rgb(245, 247, 250);
  color: rgb(17, 24, 39);
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.32);
}
.pdf-viewer__header {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: center;
  padding: 14px 16px;
  background: rgb(255, 255, 255);
  border-bottom: 1px solid rgba(15, 23, 42, 0.1);
}
.pdf-viewer__title-wrap { min-width: 0; }
.pdf-viewer__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: rgb(185, 28, 28);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}
.pdf-viewer__title {
  margin-top: 2px;
  font-size: 15px;
  font-weight: 700;
  color: rgb(17, 24, 39);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.pdf-viewer__actions {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}
.pdf-viewer__icon-btn {
  width: 34px;
  height: 34px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  border-radius: 8px;
  background: rgb(255, 255, 255);
  color: rgb(31, 41, 55);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.14s ease, border-color 0.14s ease;
}
.pdf-viewer__icon-btn:hover {
  background: rgb(241, 245, 249);
  border-color: rgba(15, 23, 42, 0.22);
}
.pdf-viewer__icon-btn:disabled {
  opacity: 0.55;
  cursor: wait;
}
.pdf-viewer__stage {
  height: min(78vh, 820px);
  min-height: 520px;
  padding: 14px;
}
.pdf-viewer__frame {
  width: 100%;
  height: 100%;
  border: 0;
  border-radius: 10px;
  background: rgb(255, 255, 255);
}
.pdf-viewer__loading,
.pdf-viewer__error {
  height: 100%;
  min-height: 360px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: rgba(31, 41, 55, 0.72);
  font-size: 14px;
}
.pdf-viewer__error {
  color: rgb(185, 28, 28);
}

@media (max-width: 640px) {
  .bubble { max-width: 92%; }
  .pdf-card {
    grid-template-columns: 84px minmax(0, 1fr);
    gap: 10px;
  }
  .pdf-card__preview { min-height: 124px; }
  .pdf-card__paper {
    width: 62px;
    min-height: 94px;
    padding: 7px;
  }
  .pdf-card__title { font-size: 13px; }
  .pdf-viewer__header {
    align-items: flex-start;
    flex-direction: column;
  }
  .pdf-viewer__stage {
    height: 72vh;
    min-height: 420px;
    padding: 8px;
  }
}
</style>
