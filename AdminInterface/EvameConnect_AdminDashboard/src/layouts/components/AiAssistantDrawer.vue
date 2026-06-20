<script setup>
import { $api } from '@/utils/api'

const open = ref(false)
const question = ref('')
const messages = ref([]) // [{role, content, citations?, clarification?, extracted?}]
const loading = ref(false)
const inputRef = ref(null)
const lastExtracted = ref({})  // remembered across turns to grow context

const buildConversationPayload = () => {
  // Send last ~10 messages (5 user/assistant pairs) for grounding without
  // blowing the token budget. Skip clarification placeholders.
  return messages.value
    .filter(m => m.content)
    .slice(-10)
    .map(m => ({ role: m.role, content: m.content }))
}

const ask = async (overrideQuestion = null, extractedOverrides = {}) => {
  const q = (overrideQuestion ?? question.value).trim()
  if (!q || loading.value) return

  messages.value.push({ role: 'user', content: q })
  question.value = ''
  loading.value = true
  await nextTick()
  scrollToBottom()

  try {
    const res = await $api('/ai/ask', {
      method: 'POST',
      body: {
        question: q,
        conversation: buildConversationPayload(),
        extracted_overrides: { ...lastExtracted.value, ...extractedOverrides },
      },
    })
    const data = res?.data ?? res

    if (data?.needs_clarification && data?.clarification) {
      messages.value.push({
        role: 'assistant',
        clarification: data.clarification,
        extracted: data.extracted ?? {},
        content: data.clarification.question,
      })
      lastExtracted.value = data.extracted ?? lastExtracted.value
    }
    else {
      messages.value.push({
        role: 'assistant',
        content: data?.answer ?? '(réponse vide)',
        citations: data?.citations ?? [],
        extracted: data?.extracted ?? {},
        sourceHint: data?.source === 'chassis_lookup' ? 'chassis' : (data?.filter_used ? 'filtered' : 'semantic'),
      })
      lastExtracted.value = data?.extracted ?? lastExtracted.value
    }
  }
  catch (err) {
    const msg = err?.data?.message ?? "Désolé, je n'arrive pas à répondre pour le moment."
    messages.value.push({ role: 'assistant', content: msg, citations: [], isError: true })
  }
  finally {
    loading.value = false
    await nextTick()
    scrollToBottom()
  }
}

const onClarificationPick = (option, message) => {
  const missing = message.clarification?.missing
  if (!missing) return
  // Build a follow-up question with the answer baked in for transparency
  const reformulated = `${option.label} (précisé pour clarification)`
  ask(reformulated, { [missing]: option.value })
}

const scrollContainer = ref(null)
const scrollToBottom = () => {
  if (scrollContainer.value) {
    scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight
  }
}

const reset = () => {
  messages.value = []
  question.value = ''
  lastExtracted.value = {}
}

watch(open, val => {
  if (val) nextTick(() => inputRef.value?.focus?.())
})

const exampleQuestions = [
  "Combien va me coûter SB1KZ28E10E042954 ?",
  "Taxes pour une voiture essence cylindrée 4500cc d'occasion ?",
  "Code SH pour un tracteur agricole diesel ?",
  "Quel total de taxes pour un Land Cruiser 4500cc neuf ?",
]

const sourceBadge = sourceHint => ({
  chassis: { icon: 'tabler-id', label: 'Lookup châssis', color: 'success' },
  filtered: { icon: 'tabler-filter', label: 'Recherche filtrée', color: 'primary' },
  semantic: { icon: 'tabler-search', label: 'Recherche sémantique', color: 'info' },
}[sourceHint])
</script>

<template>
  <IconBtn id="ai-assistant-btn" @click="open = true">
    <VIcon icon="tabler-sparkles" />
    <VTooltip activator="parent" location="bottom">
      Assistant IA douanier
    </VTooltip>
  </IconBtn>

  <VNavigationDrawer
    v-model="open"
    location="right"
    width="480"
    temporary
    class="ai-drawer"
  >
    <div class="d-flex flex-column h-100">
      <!-- Header -->
      <div class="pa-4 d-flex align-center justify-space-between border-b">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="36">
            <VIcon icon="tabler-sparkles" />
          </VAvatar>
          <div>
            <div class="text-body-1 font-weight-medium">Assistant douanier</div>
            <div class="text-caption text-medium-emphasis">TEC CEDEAO + LdF 2026 Togo</div>
          </div>
        </div>
        <div class="d-flex gap-1">
          <IconBtn v-if="messages.length" size="small" @click="reset">
            <VIcon icon="tabler-refresh" />
            <VTooltip activator="parent" location="bottom">Nouvelle conversation</VTooltip>
          </IconBtn>
          <IconBtn size="small" @click="open = false">
            <VIcon icon="tabler-x" />
          </IconBtn>
        </div>
      </div>

      <!-- Messages -->
      <div ref="scrollContainer" class="flex-grow-1 overflow-y-auto pa-4 d-flex flex-column gap-3">
        <template v-if="!messages.length">
          <div class="text-center text-medium-emphasis my-4">
            <VIcon icon="tabler-message-circle-question" size="48" class="mb-2" />
            <p class="text-body-2 mb-3">
              Pose une question sur les taux douaniers, codes SH ou un véhicule par son châssis.
            </p>
          </div>
          <div class="d-flex flex-column gap-2">
            <div class="text-caption text-medium-emphasis text-uppercase mb-1">
              Exemples
            </div>
            <VChip
              v-for="ex in exampleQuestions"
              :key="ex"
              variant="tonal"
              size="small"
              class="text-wrap text-left"
              style="height: auto; padding: 8px 12px; white-space: normal;"
              @click="ask(ex)"
            >
              {{ ex }}
            </VChip>
          </div>
        </template>

        <template v-else>
          <div
            v-for="(m, i) in messages"
            :key="i"
            :class="['msg-row', m.role === 'user' ? 'msg-user' : 'msg-bot']"
          >
            <div v-if="m.role === 'user'" class="msg-bubble bg-primary text-white">
              {{ m.content }}
            </div>

            <div v-else :class="['msg-bubble', m.isError ? 'bg-error-tonal' : 'bg-grey-lighten-4']">
              <!-- Source provenance badge -->
              <div v-if="m.sourceHint" class="mb-2">
                <VChip
                  :color="sourceBadge(m.sourceHint).color"
                  size="x-small"
                  variant="tonal"
                  :prepend-icon="sourceBadge(m.sourceHint).icon"
                >
                  {{ sourceBadge(m.sourceHint).label }}
                </VChip>
              </div>

              <!-- Clarification prompt with clickable options -->
              <template v-if="m.clarification">
                <div class="mb-2 font-weight-medium">{{ m.content }}</div>
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <VBtn
                    v-for="opt in m.clarification.options"
                    :key="opt.value"
                    size="small"
                    color="primary"
                    variant="tonal"
                    @click="onClarificationPick(opt, m)"
                  >
                    {{ opt.label }}
                  </VBtn>
                </div>
              </template>

              <template v-else>
                <div style="white-space: pre-line;">{{ m.content }}</div>
                <div v-if="m.citations?.length" class="mt-3 pt-2 border-t">
                  <div class="text-caption text-medium-emphasis text-uppercase mb-1">
                    Sources
                  </div>
                  <div class="d-flex flex-column gap-1">
                    <div
                      v-for="c in m.citations"
                      :key="c.index"
                      class="d-flex align-start gap-1 text-caption"
                    >
                      <span class="font-weight-bold flex-shrink-0">[{{ c.index }}]</span>
                      <span>
                        <strong>{{ c.code }}</strong>
                        <template v-if="c.fuel"> · <em>{{ c.fuel }}</em></template>
                        <template v-if="c.condition"> · {{ c.condition }}</template>
                        — {{ c.description?.slice(0, 90) }}<span v-if="(c.description?.length ?? 0) > 90">…</span>
                        <span class="text-medium-emphasis ms-1">
                          ({{ c.rates?.total ?? c.rates?.total_import_cost ?? '?' }})
                        </span>
                      </span>
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>
          <div v-if="loading" class="msg-row msg-bot">
            <div class="msg-bubble bg-grey-lighten-4 d-flex align-center gap-2">
              <VProgressCircular indeterminate size="16" width="2" />
              <span class="text-body-2 text-medium-emphasis">Recherche dans la base TEC…</span>
            </div>
          </div>
        </template>
      </div>

      <div class="pa-3 border-t">
        <div class="d-flex gap-2">
          <AppTextField
            ref="inputRef"
            v-model="question"
            placeholder="Pose ta question…"
            density="compact"
            hide-details
            @keydown.enter.prevent="ask()"
          />
          <VBtn
            color="primary"
            :loading="loading"
            :disabled="!question.trim()"
            icon="tabler-send"
            size="small"
            @click="ask()"
          />
        </div>
        <div class="text-caption text-medium-emphasis mt-2 text-center">
          GPT-4o-mini · TEC CEDEAO 2026 · lookup châssis & filtrage carburant
        </div>
      </div>
    </div>
  </VNavigationDrawer>
</template>

<style scoped>
.ai-drawer .border-b { border-bottom: 1px solid rgba(0,0,0,0.08); }
.ai-drawer .border-t { border-top: 1px solid rgba(0,0,0,0.08); }
.msg-row { display: flex; }
.msg-row.msg-user { justify-content: flex-end; }
.msg-row.msg-bot { justify-content: flex-start; }
.msg-bubble {
  max-width: 88%;
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 13.5px;
  line-height: 1.45;
  word-break: break-word;
}
.bg-error-tonal { background: rgba(239,83,80,0.12); color: rgb(180,30,30); }
</style>
