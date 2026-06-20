<script setup>
import { useRoute, useRouter } from 'vue-router'

definePage({ meta: { layout: 'default' } })

const route = useRoute()
const router = useRouter()

const dossierId = computed(() => route.params.id)

const STATUS_COLORS = {
  draft: 'default',
  preparing: 'info',
  in_transit: 'warning',
  at_customs: 'warning',
  delivered: 'success',
  archived: 'secondary',
  cancelled: 'error',
}

const STATUS_LABELS = {
  draft: 'Brouillon',
  preparing: 'En préparation',
  in_transit: 'En transit',
  at_customs: 'En douane',
  delivered: 'Livré',
  archived: 'Archivé',
  cancelled: 'Annulé',
}

const STATUS_OPTIONS = Object.entries(STATUS_LABELS).map(([value, title]) => ({ title, value }))

const STAGE_STATUS_COLORS = {
  pending: 'default',
  in_progress: 'info',
  done: 'success',
  blocked: 'error',
  skipped: 'secondary',
}

const STAGE_STATUS_LABELS = {
  pending: 'En attente',
  in_progress: 'En cours',
  done: 'Terminé',
  blocked: 'Bloqué',
  skipped: 'Ignoré',
}

const STAGE_STATUS_ICONS = {
  pending: 'tabler-clock',
  in_progress: 'tabler-dots-circle-horizontal',
  done: 'tabler-check',
  blocked: 'tabler-ban',
  skipped: 'tabler-arrow-bar-to-right',
}

const DOC_STATUS_COLORS = {
  missing: 'warning',
  received: 'info',
  validated: 'success',
  rejected: 'error',
}

const DOC_STATUS_LABELS = {
  missing: 'Manquant',
  received: 'Reçu',
  validated: 'Validé',
  rejected: 'Rejeté',
}

const CURRENCY_OPTIONS = [
  { title: 'XOF (FCFA)', value: 'XOF' },
  { title: 'USD ($)', value: 'USD' },
  { title: 'EUR (€)', value: 'EUR' },
]

// ── Load dossier ────────────────────────────────────────────────────────
const { data: dossierData, execute: refresh, isFetching } = useApi(
  computed(() => `/dossiers/${dossierId.value}?with_stats=1`),
)

const dossier = computed(() => dossierData.value?.data ?? dossierData.value ?? null)
const stats = computed(() => dossier.value?.stats ?? {})

// ── Lookups for edit dialog ──────────────────────────────────────────────
const { data: modesData } = await useApi('/transport-modes?only_active=1&per_page=200')
const { data: carriersData } = await useApi('/carriers?only_active=1&per_page=200')
const { data: portsData } = await useApi('/ports?only_active=1&per_page=200')
const { data: containerTypesData } = await useApi('/container-types?only_active=1&per_page=200')

const modeOptions = computed(() => modesData.value?.data?.map(m => ({ title: m.label, value: m.id })) ?? [])
const carrierOptions = computed(() => carriersData.value?.data?.map(c => ({ title: c.name, value: c.id })) ?? [])
const portOptions = computed(() => portsData.value?.data?.map(p => ({ title: p.name, value: p.id })) ?? [])
const containerTypeOptions = computed(() => containerTypesData.value?.data?.map(t => ({ title: t.label, value: t.id })) ?? [])

// ── Auto-extracted fields (RAG.3.5) ──────────────────────────────────────
const FIELD_LABELS = {
  cif_value: 'CIF',
  bl_number: 'BL n°',
  invoice_number: 'Facture n°',
  vehicle_brand: 'Marque',
  vehicle_model: 'Modèle',
  vehicle_year: 'Année',
  port_origin: 'Port départ',
  port_destination: 'Port arrivée',
  departure_date: 'Départ',
  arrival_date: 'Arrivée',
  weight_gross_kg: 'Poids brut (kg)',
}
const hasExtractedFields = data => {
  if (!data) return false
  return Object.values(data).some(v => v !== null && v !== '' && (!Array.isArray(v) || v.length))
}
const extractedChips = data => {
  if (!data) return []
  const chips = []
  for (const [key, label] of Object.entries(FIELD_LABELS)) {
    const v = data[key]
    if (v === null || v === undefined || v === '') continue
    chips.push({ key, label, value: typeof v === 'number' ? v.toLocaleString('fr-FR') : v })
  }
  if (data.chassis_numbers?.length) {
    chips.push({ key: 'chassis', label: 'Châssis', value: data.chassis_numbers.slice(0, 2).join(', ') + (data.chassis_numbers.length > 2 ? '…' : '') })
  }
  return chips.slice(0, 8) // cap to avoid layout explosion
}
const applyingExtractedFor = ref(null)
const applyExtracted = async doc => {
  applyingExtractedFor.value = doc.id
  try {
    const res = await $api(`/dossiers/${dossierId.value}/documents/${doc.id}/apply-extracted`, {
      method: 'POST',
    })
    const applied = res?.data?.applied_fields ?? res?.applied_fields ?? []
    if (applied.length) {
      // Refresh dossier so the form reflects the new values
      refresh()
    }
  }
  catch { /* silent — user can retry */ }
  finally { applyingExtractedFor.value = null }
}

// ── AI Documents Assistant (RAG.3) ───────────────────────────────────────
const aiQuestion = ref('')
const aiMessages = ref([])
const aiLoading = ref(false)
const aiScrollContainer = ref(null)

const askDocsAi = async () => {
  const q = aiQuestion.value.trim()
  if (!q || aiLoading.value) return
  aiMessages.value.push({ role: 'user', content: q })
  aiQuestion.value = ''
  aiLoading.value = true
  await nextTick()
  if (aiScrollContainer.value) aiScrollContainer.value.scrollTop = aiScrollContainer.value.scrollHeight

  try {
    const res = await $api(`/dossiers/${dossierId.value}/documents/ask`, {
      method: 'POST',
      body: {
        question: q,
        conversation: aiMessages.value.slice(-8).map(m => ({ role: m.role, content: m.content })),
      },
    })
    const data = res?.data ?? res
    aiMessages.value.push({
      role: 'assistant',
      content: data?.answer ?? '(réponse vide)',
      citations: data?.citations ?? [],
    })
  }
  catch (err) {
    aiMessages.value.push({
      role: 'assistant',
      content: err?.data?.message ?? "Désolé, je n'arrive pas à interroger les documents pour le moment.",
      citations: [],
    })
  }
  finally {
    aiLoading.value = false
    await nextTick()
    if (aiScrollContainer.value) aiScrollContainer.value.scrollTop = aiScrollContainer.value.scrollHeight
  }
}

// ── Helpers ──────────────────────────────────────────────────────────────
const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '–'
const formatDateTime = d => d ? new Date(d).toLocaleString('fr-FR') : '–'
const formatPrice = (val, currency) => {
  if (val === null || val === undefined || val === '') return '–'
  return `${Number(val).toLocaleString('fr-FR')} ${currency ?? ''}`.trim()
}
const formatBytes = bytes => {
  if (!bytes) return '–'
  if (bytes < 1024) return `${bytes} o`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`
  return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`
}
const clientName = c => {
  if (!c) return '–'
  return `${c.first_name ?? ''} ${c.last_name ?? ''}`.trim() || c.name || c.email || '–'
}

// ── Tabs ─────────────────────────────────────────────────────────────────
const currentTab = ref('overview')

// ── Edit dialog ──────────────────────────────────────────────────────────
const editDialog = ref(false)
const editFormRef = ref()
const editForm = ref({})
const editErrorMsg = ref('')
const editSaving = ref(false)

const openEdit = () => {
  const d = dossier.value
  if (!d) return
  editForm.value = {
    title: d.title ?? '',
    transport_mode_id: d.transport_mode?.id ?? d.transport_mode_id ?? null,
    carrier_id: d.carrier?.id ?? d.carrier_id ?? null,
    origin_port_id: d.origin_port?.id ?? d.origin_port_id ?? null,
    destination_port_id: d.destination_port?.id ?? d.destination_port_id ?? null,
    estimated_departure: d.estimated_departure ? d.estimated_departure.substring(0, 10) : '',
    estimated_arrival: d.estimated_arrival ? d.estimated_arrival.substring(0, 10) : '',
    actual_departure: d.actual_departure ? d.actual_departure.substring(0, 10) : '',
    actual_arrival: d.actual_arrival ? d.actual_arrival.substring(0, 10) : '',
    total_estimated_cost: d.total_estimated_cost ?? null,
    currency: d.currency ?? 'XOF',
    notes: d.notes ?? '',
  }
  editErrorMsg.value = ''
  editDialog.value = true
}

const saveEdit = async () => {
  const { valid } = await editFormRef.value.validate()
  if (!valid) return

  editSaving.value = true
  editErrorMsg.value = ''
  try {
    const body = { ...editForm.value }
    Object.keys(body).forEach(k => { if (body[k] === '') body[k] = null })
    await $api(`/dossiers/${dossierId.value}`, { method: 'PATCH', body })
    editDialog.value = false
    refresh()
  }
  catch (err) {
    editErrorMsg.value = err?.response?._data?.message ?? 'Une erreur est survenue'
  }
  finally {
    editSaving.value = false
  }
}

// ── Status quick change ──────────────────────────────────────────────────
const statusChanging = ref(false)
const changeStatus = async newStatus => {
  if (!newStatus || newStatus === dossier.value?.status) return
  statusChanging.value = true
  try {
    await $api(`/dossiers/${dossierId.value}`, { method: 'PATCH', body: { status: newStatus } })
    refresh()
  }
  catch {
    // silent
  }
  finally {
    statusChanging.value = false
  }
}

// ── Archive / Delete ─────────────────────────────────────────────────────
const archive = async () => {
  await $api(`/dossiers/${dossierId.value}`, {
    method: 'PATCH',
    body: { archived_at: new Date().toISOString() },
  })
  refresh()
}

const dialogDelete = ref(false)
const deleteDossier = async () => {
  try {
    await $api(`/dossiers/${dossierId.value}`, { method: 'DELETE' })
    dialogDelete.value = false
    router.push('/dossiers')
  }
  catch {
    dialogDelete.value = false
  }
}

// ── Audit trail expansion ───────────────────────────────────────────────
const showAllEvents = ref(false)
const visibleEvents = computed(() => {
  const events = dossier.value?.events ?? []
  return showAllEvents.value ? events : events.slice(0, 10)
})

// ── Stage actions ────────────────────────────────────────────────────────
const updateStage = async (stage, status, notes) => {
  try {
    await $api(`/dossiers/${dossierId.value}/stages/${stage.id}`, {
      method: 'PATCH',
      body: { status, ...(notes !== undefined ? { notes } : {}) },
    })
    refresh()
  }
  catch {
    // silent
  }
}

const stageNotes = ref({})
watch(dossier, d => {
  if (!d?.stages) return
  d.stages.forEach(s => {
    if (stageNotes.value[s.id] === undefined) stageNotes.value[s.id] = s.notes ?? ''
  })
}, { immediate: true })

const onStageNoteBlur = stage => {
  const newNotes = stageNotes.value[stage.id] ?? ''
  if (newNotes === (stage.notes ?? '')) return
  updateStage(stage, stage.status, newNotes)
}

// ── Documents ────────────────────────────────────────────────────────────
const uploadDialog = ref(false)
const uploadDoc = ref(null)
const uploadFile = ref(null)
const uploading = ref(false)
const uploadErrorMsg = ref('')

const openUpload = doc => {
  uploadDoc.value = doc
  uploadFile.value = null
  uploadErrorMsg.value = ''
  uploadDialog.value = true
}

const submitUpload = async () => {
  if (!uploadFile.value) {
    uploadErrorMsg.value = 'Veuillez sélectionner un fichier'
    return
  }
  uploading.value = true
  uploadErrorMsg.value = ''
  try {
    const file = Array.isArray(uploadFile.value) ? uploadFile.value[0] : uploadFile.value
    const fd = new FormData()
    fd.append('file', file)
    await $api(`/dossiers/${dossierId.value}/documents/${uploadDoc.value.id}/upload`, {
      method: 'POST',
      body: fd,
    })
    uploadDialog.value = false
    refresh()
  }
  catch (err) {
    uploadErrorMsg.value = err?.response?._data?.message ?? 'Erreur d\'envoi'
  }
  finally {
    uploading.value = false
  }
}

const validateDialog = ref(false)
const validateDoc = ref(null)
const validateAction = ref('validate')
const validateReason = ref('')
const validateLoading = ref(false)
const validateErrorMsg = ref('')

const openValidate = (doc, action) => {
  validateDoc.value = doc
  validateAction.value = action
  validateReason.value = ''
  validateErrorMsg.value = ''
  validateDialog.value = true
}

const submitValidate = async () => {
  validateLoading.value = true
  validateErrorMsg.value = ''
  try {
    const body = { action: validateAction.value }
    if (validateAction.value === 'reject') body.rejection_reason = validateReason.value
    await $api(`/dossiers/${dossierId.value}/documents/${validateDoc.value.id}/validate`, {
      method: 'POST',
      body,
    })
    validateDialog.value = false
    refresh()
  }
  catch (err) {
    validateErrorMsg.value = err?.response?._data?.message ?? 'Erreur'
  }
  finally {
    validateLoading.value = false
  }
}

const downloadDoc = async doc => {
  try {
    const url = `${import.meta.env.VITE_API_BASE_URL || '/api'}/dossiers/${dossierId.value}/documents/${doc.id}/download`
    const accessToken = useCookie('accessToken').value
    const res = await fetch(url, {
      headers: {
        Authorization: `Bearer ${accessToken}`,
        Accept: 'application/json',
        'ngrok-skip-browser-warning': 'true',
      },
    })
    if (!res.ok) return
    const blob = await res.blob()
    const blobUrl = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = blobUrl
    a.download = doc.original_filename || `document-${doc.id}`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(blobUrl)
  }
  catch {
    // silent
  }
}

const deleteDoc = async doc => {
  if (!confirm(`Supprimer le document "${doc.document_type?.label}" ?`)) return
  try {
    await $api(`/dossiers/${dossierId.value}/documents/${doc.id}`, { method: 'DELETE' })
    refresh()
  }
  catch {
    // silent
  }
}

// ── Containers ───────────────────────────────────────────────────────────
const containerForms = ref({})
watch(dossier, d => {
  if (!d?.containers) return
  d.containers.forEach(c => {
    if (!containerForms.value[c.id]) {
      containerForms.value[c.id] = {
        container_type_id: c.container_type?.id ?? c.container_type_id ?? null,
        container_number: c.container_number ?? '',
        seal_number: c.seal_number ?? '',
        loaded_at: c.loaded_at ? c.loaded_at.substring(0, 10) : '',
        departed_at: c.departed_at ? c.departed_at.substring(0, 10) : '',
        arrived_at: c.arrived_at ? c.arrived_at.substring(0, 10) : '',
        notes: c.notes ?? '',
      }
    }
  })
}, { immediate: true })

const containerSaving = ref({})
const saveContainer = async container => {
  containerSaving.value[container.id] = true
  try {
    const body = { ...containerForms.value[container.id] }
    Object.keys(body).forEach(k => { if (body[k] === '') body[k] = null })
    await $api(`/dossiers/${dossierId.value}/containers/${container.id}`, {
      method: 'PATCH',
      body,
    })
    refresh()
  }
  catch {
    // silent
  }
  finally {
    containerSaving.value[container.id] = false
  }
}

const deleteContainer = async container => {
  if (!confirm(`Supprimer le conteneur ${container.container_number || container.id} ?`)) return
  try {
    await $api(`/dossiers/${dossierId.value}/containers/${container.id}`, { method: 'DELETE' })
    refresh()
  }
  catch {
    // silent
  }
}

const newContainerDialog = ref(false)
const newContainerForm = ref({})
const newContainerSaving = ref(false)
const newContainerErrorMsg = ref('')

const openNewContainer = () => {
  newContainerForm.value = {
    container_type_id: null,
    container_number: '',
    seal_number: '',
    loaded_at: '',
    departed_at: '',
    arrived_at: '',
    notes: '',
  }
  newContainerErrorMsg.value = ''
  newContainerDialog.value = true
}

const submitNewContainer = async () => {
  newContainerSaving.value = true
  newContainerErrorMsg.value = ''
  try {
    const body = { ...newContainerForm.value }
    Object.keys(body).forEach(k => { if (body[k] === '') delete body[k] })
    await $api(`/dossiers/${dossierId.value}/containers`, { method: 'POST', body })
    newContainerDialog.value = false
    refresh()
  }
  catch (err) {
    newContainerErrorMsg.value = err?.response?._data?.message ?? 'Erreur'
  }
  finally {
    newContainerSaving.value = false
  }
}
</script>

<template>
  <div>
    <div v-if="isFetching && !dossier" class="d-flex justify-center pa-8">
      <VProgressCircular indeterminate color="primary" />
    </div>

    <template v-else-if="dossier">
      <!-- Header bar -->
      <div class="d-flex align-center flex-wrap gap-3 mb-4">
        <VBtn
          variant="text"
          prepend-icon="tabler-arrow-left"
          to="/dossiers"
        >
          Dossiers
        </VBtn>

        <div class="d-flex align-center flex-wrap gap-2 flex-grow-1">
          <span class="text-h5 font-weight-bold">{{ dossier.reference }}</span>
          <span class="text-h6 text-medium-emphasis">— {{ dossier.title }}</span>
          <VChip
            :color="STATUS_COLORS[dossier.status] ?? 'default'"
            size="small"
          >
            {{ STATUS_LABELS[dossier.status] ?? dossier.status }}
          </VChip>
        </div>

        <div class="d-flex align-center gap-2 flex-wrap">
          <AppSelect
            :model-value="dossier.status"
            :items="STATUS_OPTIONS"
            density="compact"
            hide-details
            style="inline-size: 180px"
            :disabled="statusChanging"
            @update:model-value="changeStatus"
          />
          <VBtn
            variant="tonal"
            prepend-icon="tabler-edit"
            @click="openEdit"
          >
            Modifier
          </VBtn>
          <VBtn
            v-if="!dossier.archived_at"
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-archive"
            @click="archive"
          >
            Archiver
          </VBtn>
          <VBtn
            variant="tonal"
            color="error"
            prepend-icon="tabler-trash"
            @click="dialogDelete = true"
          >
            Supprimer
          </VBtn>
        </div>
      </div>

      <!-- KPI row -->
      <VRow class="mb-4">
        <VCol cols="12" sm="6" md="3">
          <VCard rounded="lg">
            <VCardText>
              <div class="text-body-2 text-medium-emphasis mb-1">
                Étapes
              </div>
              <div class="text-h5 font-weight-bold mb-2">
                {{ stats.stages_done ?? 0 }}/{{ stats.stages_total ?? 0 }}
              </div>
              <VProgressLinear
                :model-value="stats.stages_total ? (stats.stages_done / stats.stages_total) * 100 : 0"
                color="primary"
                height="6"
                rounded
              />
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" md="3">
          <VCard rounded="lg">
            <VCardText>
              <div class="text-body-2 text-medium-emphasis mb-1">
                Documents
              </div>
              <div class="text-h5 font-weight-bold">
                {{ stats.documents_validated ?? 0 }} validés / {{ stats.documents_total ?? 0 }} requis
              </div>
              <div
                v-if="(stats.documents_missing ?? 0) > 0"
                class="text-error text-caption mt-1"
              >
                {{ stats.documents_missing }} manquant(s)
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" md="3">
          <VCard rounded="lg">
            <VCardText>
              <div class="text-body-2 text-medium-emphasis mb-1">
                Items
              </div>
              <div class="text-h5 font-weight-bold">
                {{ stats.items_count ?? dossier.items?.length ?? 0 }} produit(s)
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" md="3">
          <VCard rounded="lg">
            <VCardText>
              <div class="text-body-2 text-medium-emphasis mb-1">
                Total estimé
              </div>
              <div class="text-h5 font-weight-bold">
                {{ formatPrice(dossier.total_estimated_cost, dossier.currency) }}
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Tabs -->
      <VCard rounded="lg">
        <VTabs v-model="currentTab">
          <VTab value="overview">
            <VIcon start icon="tabler-info-circle" size="18" />
            Aperçu
          </VTab>
          <VTab value="timeline">
            <VIcon start icon="tabler-timeline" size="18" />
            Chronologie
          </VTab>
          <VTab value="documents">
            <VIcon start icon="tabler-files" size="18" />
            Documents
          </VTab>
          <VTab value="containers">
            <VIcon start icon="tabler-box" size="18" />
            Conteneurs
          </VTab>
          <VTab value="ai">
            <VIcon start icon="tabler-sparkles" size="18" />
            Assistant docs
          </VTab>
        </VTabs>

        <VDivider />

        <VWindow v-model="currentTab">
          <!-- ── Onglet 1: Aperçu ─────────────────────────── -->
          <VWindowItem value="overview">
            <VCardText>
              <VRow>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Référence</div>
                  <div class="text-body-1 font-weight-medium mb-3">{{ dossier.reference }}</div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Titre</div>
                  <div class="text-body-1 font-weight-medium mb-3">{{ dossier.title }}</div>
                </VCol>

                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Statut</div>
                  <div class="mb-3">
                    <VChip
                      :color="STATUS_COLORS[dossier.status] ?? 'default'"
                      size="small"
                    >
                      {{ STATUS_LABELS[dossier.status] ?? dossier.status }}
                    </VChip>
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Type</div>
                  <div class="mb-3">
                    <VChip
                      size="small"
                      variant="tonal"
                      :color="dossier.type === 'vehicle' ? 'info' : 'primary'"
                    >
                      <VIcon
                        start
                        :icon="dossier.type === 'vehicle' ? 'tabler-car' : 'tabler-package'"
                        size="14"
                      />
                      {{ dossier.type === 'vehicle' ? 'Véhicule' : 'Marchandises' }}
                    </VChip>
                  </div>
                </VCol>

                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Client</div>
                  <div class="text-body-1 font-weight-medium">{{ clientName(dossier.client) }}</div>
                  <div v-if="dossier.client?.email" class="text-caption text-medium-emphasis mb-3">
                    {{ dossier.client.email }}
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Créé par</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ clientName(dossier.created_by) }}
                  </div>
                </VCol>

                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Mode de transport</div>
                  <div class="mb-3">
                    <VChip
                      v-if="dossier.transport_mode"
                      size="small"
                      variant="tonal"
                      color="primary"
                    >
                      <VIcon
                        v-if="dossier.transport_mode.icon"
                        start
                        :icon="dossier.transport_mode.icon"
                        size="14"
                      />
                      {{ dossier.transport_mode.label }}
                    </VChip>
                    <span v-else class="text-medium-emphasis">–</span>
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Transporteur</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ dossier.carrier?.name ?? '–' }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Devise</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ dossier.currency ?? '–' }}
                  </div>
                </VCol>

                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Port d'origine</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ dossier.origin_port?.name ?? '–' }}
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Port de destination</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ dossier.destination_port?.name ?? '–' }}
                  </div>
                </VCol>

                <VCol cols="12" md="3">
                  <div class="text-body-2 text-medium-emphasis">Départ estimé</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatDate(dossier.estimated_departure) }}
                  </div>
                </VCol>
                <VCol cols="12" md="3">
                  <div class="text-body-2 text-medium-emphasis">Arrivée estimée</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatDate(dossier.estimated_arrival) }}
                  </div>
                </VCol>
                <VCol cols="12" md="3">
                  <div class="text-body-2 text-medium-emphasis">Départ réel</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatDate(dossier.actual_departure) }}
                  </div>
                </VCol>
                <VCol cols="12" md="3">
                  <div class="text-body-2 text-medium-emphasis">Arrivée réelle</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatDate(dossier.actual_arrival) }}
                  </div>
                </VCol>

                <VCol cols="12">
                  <div class="text-body-2 text-medium-emphasis">Coût total estimé</div>
                  <div class="text-h6 font-weight-bold mb-3">
                    {{ formatPrice(dossier.total_estimated_cost, dossier.currency) }}
                  </div>
                </VCol>

                <VCol cols="12">
                  <div class="text-body-2 text-medium-emphasis">Notes</div>
                  <div class="text-body-1 mb-3">
                    {{ dossier.notes || '–' }}
                  </div>
                </VCol>
              </VRow>

              <!-- Items list -->
              <VDivider class="my-4" />
              <div class="text-h6 mb-3">Produits</div>
              <VAlert
                v-if="!dossier.items?.length"
                type="info"
                variant="tonal"
                density="compact"
              >
                Aucun produit dans ce dossier.
              </VAlert>
              <VTable v-else density="compact">
                <thead>
                  <tr>
                    <th>Produit</th>
                    <th class="text-right">Qté</th>
                    <th class="text-right">Coût unitaire</th>
                    <th>Notes</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="it in dossier.items" :key="it.id">
                    <td>{{ it.product?.name ?? '–' }}</td>
                    <td class="text-right">{{ it.quantity ?? '–' }}</td>
                    <td class="text-right">
                      {{ formatPrice(it.unit_estimated_cost, dossier.currency) }}
                    </td>
                    <td>{{ it.notes || '–' }}</td>
                  </tr>
                </tbody>
              </VTable>

              <!-- Audit trail -->
              <VDivider class="my-4" />
              <div class="d-flex align-center justify-space-between mb-3">
                <span class="text-h6">Historique</span>
                <VBtn
                  v-if="(dossier.events?.length ?? 0) > 10"
                  size="small"
                  variant="text"
                  @click="showAllEvents = !showAllEvents"
                >
                  {{ showAllEvents ? 'Réduire' : `Voir tout (${dossier.events.length})` }}
                </VBtn>
              </div>
              <VAlert
                v-if="!visibleEvents.length"
                type="info"
                variant="tonal"
                density="compact"
              >
                Aucun événement enregistré.
              </VAlert>
              <div v-else class="d-flex flex-column gap-2">
                <div
                  v-for="ev in visibleEvents"
                  :key="ev.id"
                  class="d-flex align-start gap-3 pa-3 rounded"
                  style="background: rgba(0,0,0,0.03);"
                >
                  <VIcon icon="tabler-circle-dot" size="14" color="primary" class="mt-1" />
                  <div class="flex-grow-1">
                    <div class="text-body-2">
                      <strong>{{ ev.event_type }}</strong> — {{ ev.description }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      {{ clientName(ev.user) }} · {{ formatDateTime(ev.created_at) }}
                    </div>
                  </div>
                </div>
              </div>
            </VCardText>
          </VWindowItem>

          <!-- ── Onglet 2: Timeline ───────────────────────── -->
          <VWindowItem value="timeline">
            <VCardText>
              <VAlert
                v-if="!dossier.stages?.length"
                type="info"
                variant="tonal"
              >
                Aucune étape configurée pour ce dossier.
              </VAlert>
              <VTimeline
                v-else
                side="end"
                align="start"
                density="compact"
              >
                <VTimelineItem
                  v-for="stage in dossier.stages"
                  :key="stage.id"
                  :dot-color="STAGE_STATUS_COLORS[stage.status] ?? 'default'"
                  :icon="STAGE_STATUS_ICONS[stage.status] ?? 'tabler-circle'"
                  size="small"
                >
                  <div class="d-flex align-center flex-wrap gap-2 mb-1">
                    <span class="text-body-1 font-weight-bold">
                      {{ stage.name ?? stage.template?.name ?? `Étape ${stage.position}` }}
                    </span>
                    <VChip
                      :color="STAGE_STATUS_COLORS[stage.status] ?? 'default'"
                      size="small"
                    >
                      {{ STAGE_STATUS_LABELS[stage.status] ?? stage.status }}
                    </VChip>
                    <VChip
                      v-if="stage.visible_to_client"
                      size="x-small"
                      color="info"
                      variant="tonal"
                    >
                      Visible client
                    </VChip>
                  </div>

                  <div
                    v-if="stage.description"
                    class="text-body-2 text-medium-emphasis mb-2"
                  >
                    {{ stage.description }}
                  </div>

                  <div class="text-caption text-medium-emphasis mb-2">
                    <span v-if="stage.expected_at">Prévu : {{ formatDate(stage.expected_at) }}</span>
                    <span v-if="stage.started_at"> · Démarré : {{ formatDate(stage.started_at) }}</span>
                    <span v-if="stage.completed_at"> · Terminé : {{ formatDate(stage.completed_at) }}</span>
                  </div>

                  <div class="d-flex flex-wrap gap-1 mb-2">
                    <VBtn
                      v-if="stage.status !== 'in_progress'"
                      size="x-small"
                      variant="tonal"
                      color="info"
                      prepend-icon="tabler-player-play"
                      @click="updateStage(stage, 'in_progress')"
                    >
                      Démarrer
                    </VBtn>
                    <VBtn
                      v-if="stage.status !== 'done'"
                      size="x-small"
                      variant="tonal"
                      color="success"
                      prepend-icon="tabler-check"
                      @click="updateStage(stage, 'done')"
                    >
                      Terminer
                    </VBtn>
                    <VBtn
                      v-if="stage.status !== 'blocked'"
                      size="x-small"
                      variant="tonal"
                      color="error"
                      prepend-icon="tabler-ban"
                      @click="updateStage(stage, 'blocked')"
                    >
                      Bloquer
                    </VBtn>
                    <VBtn
                      v-if="stage.status !== 'skipped'"
                      size="x-small"
                      variant="tonal"
                      color="secondary"
                      prepend-icon="tabler-arrow-bar-to-right"
                      @click="updateStage(stage, 'skipped')"
                    >
                      Ignorer
                    </VBtn>
                    <VBtn
                      v-if="stage.status !== 'pending'"
                      size="x-small"
                      variant="text"
                      prepend-icon="tabler-rotate"
                      @click="updateStage(stage, 'pending')"
                    >
                      Réinitialiser
                    </VBtn>
                  </div>

                  <AppTextarea
                    v-model="stageNotes[stage.id]"
                    label="Notes"
                    rows="2"
                    auto-grow
                    density="compact"
                    @blur="onStageNoteBlur(stage)"
                  />
                </VTimelineItem>
              </VTimeline>
            </VCardText>
          </VWindowItem>

          <!-- ── Onglet 3: Documents ──────────────────────── -->
          <VWindowItem value="documents">
            <VCardText>
              <VAlert
                v-if="!dossier.documents?.length"
                type="info"
                variant="tonal"
              >
                Aucun document associé à ce dossier.
              </VAlert>
              <VTable v-else>
                <thead>
                  <tr>
                    <th>Type de document</th>
                    <th>Statut</th>
                    <th>Fichier</th>
                    <th>Uploadé par</th>
                    <th>Validé par</th>
                    <th class="text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="doc in dossier.documents" :key="doc.id">
                    <td>
                      {{ doc.document_type?.label ?? '–' }}
                      <VChip
                        v-if="doc.document_type?.is_required"
                        size="x-small"
                        color="error"
                        variant="tonal"
                        class="ms-1"
                      >
                        Requis
                      </VChip>
                    </td>
                    <td>
                      <VChip
                        :color="DOC_STATUS_COLORS[doc.status] ?? 'default'"
                        size="small"
                      >
                        {{ DOC_STATUS_LABELS[doc.status] ?? doc.status }}
                      </VChip>
                    </td>
                    <td>
                      <div v-if="doc.original_filename">
                        <div class="text-body-2">{{ doc.original_filename }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ formatBytes(doc.file_size) }}
                        </div>
                        <!-- Auto-extracted fields chip -->
                        <div
                          v-if="doc.extracted_data && hasExtractedFields(doc.extracted_data)"
                          class="d-flex flex-wrap gap-1 mt-2"
                        >
                          <VChip
                            v-for="f in extractedChips(doc.extracted_data)"
                            :key="f.key"
                            size="x-small"
                            variant="tonal"
                            color="success"
                            prepend-icon="tabler-sparkles"
                          >
                            {{ f.label }} : {{ f.value }}
                          </VChip>
                          <VBtn
                            size="x-small"
                            variant="tonal"
                            color="primary"
                            prepend-icon="tabler-wand"
                            @click="applyExtracted(doc)"
                          >
                            Appliquer au dossier
                          </VBtn>
                        </div>
                      </div>
                      <span v-else class="text-medium-emphasis">–</span>
                    </td>
                    <td>
                      <div v-if="doc.uploaded_by">
                        <div class="text-body-2">{{ clientName(doc.uploaded_by) }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ formatDate(doc.uploaded_at) }}
                        </div>
                      </div>
                      <span v-else class="text-medium-emphasis">–</span>
                    </td>
                    <td>
                      <div v-if="doc.validated_by">
                        <div class="text-body-2">{{ clientName(doc.validated_by) }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ formatDate(doc.validated_at) }}
                        </div>
                      </div>
                      <span v-else class="text-medium-emphasis">–</span>
                    </td>
                    <td class="text-right">
                      <div class="d-flex gap-1 justify-end">
                        <VBtn
                          v-if="doc.status === 'missing' || doc.status === 'rejected'"
                          size="x-small"
                          variant="tonal"
                          color="primary"
                          prepend-icon="tabler-upload"
                          @click="openUpload(doc)"
                        >
                          Téléverser
                        </VBtn>
                        <VBtn
                          v-if="doc.status === 'received'"
                          size="x-small"
                          variant="tonal"
                          color="success"
                          prepend-icon="tabler-check"
                          @click="openValidate(doc, 'validate')"
                        >
                          Valider
                        </VBtn>
                        <VBtn
                          v-if="doc.status === 'received'"
                          size="x-small"
                          variant="tonal"
                          color="error"
                          prepend-icon="tabler-x"
                          @click="openValidate(doc, 'reject')"
                        >
                          Rejeter
                        </VBtn>
                        <VBtn
                          v-if="doc.has_file"
                          icon
                          size="x-small"
                          variant="text"
                          title="Télécharger"
                          @click="downloadDoc(doc)"
                        >
                          <VIcon size="16" icon="tabler-download" />
                        </VBtn>
                        <VBtn
                          icon
                          size="x-small"
                          variant="text"
                          color="error"
                          title="Supprimer"
                          @click="deleteDoc(doc)"
                        >
                          <VIcon size="16" icon="tabler-trash" />
                        </VBtn>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </VCardText>
          </VWindowItem>

          <!-- ── Onglet 4: Conteneurs ─────────────────────── -->
          <VWindowItem value="containers">
            <VCardText>
              <div class="d-flex justify-end mb-3">
                <VBtn
                  color="primary"
                  prepend-icon="tabler-plus"
                  @click="openNewContainer"
                >
                  Ajouter un conteneur
                </VBtn>
              </div>

              <VAlert
                v-if="!dossier.containers?.length"
                type="info"
                variant="tonal"
              >
                Aucun conteneur enregistré pour ce dossier.
              </VAlert>

              <VExpansionPanels v-else multiple>
                <VExpansionPanel
                  v-for="container in dossier.containers"
                  :key="container.id"
                >
                  <VExpansionPanelTitle>
                    <div class="d-flex align-center gap-2 flex-wrap">
                      <VIcon icon="tabler-box" size="18" />
                      <span class="font-weight-medium">
                        {{ container.container_number || `Conteneur #${container.id}` }}
                      </span>
                      <VChip
                        v-if="container.container_type"
                        size="x-small"
                        variant="tonal"
                        color="primary"
                      >
                        {{ container.container_type.label }}
                      </VChip>
                      <span v-if="container.seal_number" class="text-caption text-medium-emphasis">
                        Plomb : {{ container.seal_number }}
                      </span>
                    </div>
                  </VExpansionPanelTitle>
                  <VExpansionPanelText>
                    <VRow>
                      <VCol cols="12" md="6">
                        <AppSelect
                          v-model="containerForms[container.id].container_type_id"
                          :items="[{ title: '–', value: null }, ...containerTypeOptions]"
                          label="Type de conteneur"
                        />
                      </VCol>
                      <VCol cols="12" md="6">
                        <AppTextField
                          v-model="containerForms[container.id].container_number"
                          label="Numéro de conteneur"
                        />
                      </VCol>
                      <VCol cols="12" md="6">
                        <AppTextField
                          v-model="containerForms[container.id].seal_number"
                          label="Numéro de plomb"
                        />
                      </VCol>
                      <VCol cols="12" md="2">
                        <AppTextField
                          v-model="containerForms[container.id].loaded_at"
                          type="date"
                          label="Chargé le"
                        />
                      </VCol>
                      <VCol cols="12" md="2">
                        <AppTextField
                          v-model="containerForms[container.id].departed_at"
                          type="date"
                          label="Départ"
                        />
                      </VCol>
                      <VCol cols="12" md="2">
                        <AppTextField
                          v-model="containerForms[container.id].arrived_at"
                          type="date"
                          label="Arrivée"
                        />
                      </VCol>
                      <VCol cols="12">
                        <AppTextarea
                          v-model="containerForms[container.id].notes"
                          label="Notes"
                          rows="2"
                        />
                      </VCol>
                    </VRow>
                    <div class="d-flex justify-end gap-2 mt-3">
                      <VBtn
                        color="error"
                        variant="tonal"
                        prepend-icon="tabler-trash"
                        @click="deleteContainer(container)"
                      >
                        Supprimer
                      </VBtn>
                      <VBtn
                        color="primary"
                        :loading="containerSaving[container.id]"
                        prepend-icon="tabler-device-floppy"
                        @click="saveContainer(container)"
                      >
                        Enregistrer
                      </VBtn>
                    </div>
                  </VExpansionPanelText>
                </VExpansionPanel>
              </VExpansionPanels>
            </VCardText>
          </VWindowItem>

          <!-- ── Onglet 5: Assistant documents (RAG.3) ──────── -->
          <VWindowItem value="ai">
            <VCardText>
              <div class="d-flex align-center gap-2 mb-4">
                <VIcon icon="tabler-sparkles" color="primary" />
                <div>
                  <div class="text-h6">Assistant documents</div>
                  <div class="text-caption text-medium-emphasis">
                    Pose une question en langage naturel sur les documents uploadés dans ce dossier.
                  </div>
                </div>
              </div>

              <div
                ref="aiScrollContainer"
                class="overflow-y-auto pa-2 mb-3"
                style="max-height: 460px; min-height: 280px; background: rgba(0,0,0,0.02); border-radius: 8px;"
              >
                <div v-if="!aiMessages.length" class="text-center text-medium-emphasis py-6">
                  <VIcon icon="tabler-message-circle-question" size="40" class="mb-2" />
                  <div class="text-body-2">
                    Exemples : "Quel est le numéro de châssis sur le BL ?" ·
                    "Quelle est la valeur CIF déclarée ?" · "Quel est le port de départ ?"
                  </div>
                </div>
                <div
                  v-for="(m, i) in aiMessages"
                  :key="i"
                  :class="['mb-3 d-flex', m.role === 'user' ? 'justify-end' : 'justify-start']"
                >
                  <div
                    :class="['pa-3 rounded-lg', m.role === 'user' ? 'bg-primary text-white' : 'bg-grey-lighten-3']"
                    style="max-width: 80%; white-space: pre-line;"
                  >
                    {{ m.content }}
                    <div v-if="m.citations?.length" class="mt-2 pt-2" style="border-top: 1px solid rgba(0,0,0,0.1);">
                      <div class="text-caption text-medium-emphasis mb-1">Sources :</div>
                      <div v-for="c in m.citations" :key="c.index" class="text-caption mb-1">
                        <strong>[{{ c.index }}]</strong> {{ c.filename }}
                        <span class="text-medium-emphasis"> · {{ c.excerpt?.slice(0, 100) }}…</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-if="aiLoading" class="d-flex align-center gap-2 text-medium-emphasis">
                  <VProgressCircular indeterminate size="16" width="2" />
                  Recherche dans les documents…
                </div>
              </div>

              <div class="d-flex gap-2">
                <AppTextField
                  v-model="aiQuestion"
                  placeholder="Pose ta question…"
                  density="compact"
                  hide-details
                  @keydown.enter.prevent="askDocsAi"
                />
                <VBtn
                  color="primary"
                  :loading="aiLoading"
                  :disabled="!aiQuestion.trim()"
                  icon="tabler-send"
                  @click="askDocsAi"
                />
              </div>
            </VCardText>
          </VWindowItem>
        </VWindow>
      </VCard>
    </template>

    <!-- ── Edit dialog ────────────────────────────────────── -->
    <VDialog v-model="editDialog" max-width="800" scrollable>
      <VCard title="Modifier le dossier">
        <VCardText style="max-block-size: 70vh;">
          <VForm ref="editFormRef">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="editForm.title"
                  label="Titre"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="editForm.transport_mode_id"
                  :items="modeOptions"
                  label="Mode de transport"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="editForm.carrier_id"
                  :items="[{ title: '–', value: null }, ...carrierOptions]"
                  label="Transporteur"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="editForm.origin_port_id"
                  :items="[{ title: '–', value: null }, ...portOptions]"
                  label="Port d'origine"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="editForm.destination_port_id"
                  :items="[{ title: '–', value: null }, ...portOptions]"
                  label="Port de destination"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="editForm.estimated_departure"
                  type="date"
                  label="Départ estimé"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="editForm.estimated_arrival"
                  type="date"
                  label="Arrivée estimée"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="editForm.actual_departure"
                  type="date"
                  label="Départ réel"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="editForm.actual_arrival"
                  type="date"
                  label="Arrivée réelle"
                />
              </VCol>

              <VCol cols="12" md="8">
                <AppTextField
                  v-model.number="editForm.total_estimated_cost"
                  type="number"
                  label="Coût total estimé"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppSelect
                  v-model="editForm.currency"
                  :items="CURRENCY_OPTIONS"
                  label="Devise"
                />
              </VCol>

              <VCol cols="12">
                <AppTextarea
                  v-model="editForm.notes"
                  label="Notes"
                  rows="3"
                />
              </VCol>
            </VRow>

            <VAlert
              v-if="editErrorMsg"
              type="error"
              variant="tonal"
              class="mt-3"
            >
              {{ editErrorMsg }}
            </VAlert>
          </VForm>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="editDialog = false">
            Annuler
          </VBtn>
          <VBtn color="primary" :loading="editSaving" @click="saveEdit">
            Enregistrer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Upload dialog ──────────────────────────────────── -->
    <VDialog v-model="uploadDialog" max-width="500">
      <VCard :title="`Téléverser : ${uploadDoc?.document_type?.label ?? ''}`">
        <VCardText>
          <VFileInput
            v-model="uploadFile"
            label="Fichier"
            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
            prepend-icon=""
            prepend-inner-icon="tabler-paperclip"
            show-size
          />
          <div class="text-caption text-medium-emphasis mt-1">
            Formats : PDF, JPG, PNG, WEBP, DOC, DOCX, XLS, XLSX. Taille max : 20 Mo.
          </div>
          <VAlert
            v-if="uploadErrorMsg"
            type="error"
            variant="tonal"
            class="mt-3"
          >
            {{ uploadErrorMsg }}
          </VAlert>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="uploadDialog = false">Annuler</VBtn>
          <VBtn
            color="primary"
            :loading="uploading"
            @click="submitUpload"
          >
            Téléverser
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Validate / Reject dialog ────────────────────────── -->
    <VDialog v-model="validateDialog" max-width="500">
      <VCard
        :title="validateAction === 'validate' ? 'Valider le document' : 'Rejeter le document'"
      >
        <VCardText>
          <p class="text-body-2 mb-3">
            <span v-if="validateAction === 'validate'">
              Confirmer la validation du document
              <strong>{{ validateDoc?.document_type?.label }}</strong> ?
            </span>
            <span v-else>
              Indiquer la raison du rejet du document
              <strong>{{ validateDoc?.document_type?.label }}</strong>.
            </span>
          </p>
          <AppTextarea
            v-if="validateAction === 'reject'"
            v-model="validateReason"
            label="Raison du rejet"
            rows="3"
            :rules="[requiredValidator]"
          />
          <VAlert
            v-if="validateErrorMsg"
            type="error"
            variant="tonal"
            class="mt-3"
          >
            {{ validateErrorMsg }}
          </VAlert>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="validateDialog = false">Annuler</VBtn>
          <VBtn
            :color="validateAction === 'validate' ? 'success' : 'error'"
            :loading="validateLoading"
            @click="submitValidate"
          >
            {{ validateAction === 'validate' ? 'Valider' : 'Rejeter' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── New container dialog ────────────────────────────── -->
    <VDialog v-model="newContainerDialog" max-width="600">
      <VCard title="Nouveau conteneur">
        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="newContainerForm.container_type_id"
                :items="[{ title: '–', value: null }, ...containerTypeOptions]"
                label="Type de conteneur"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="newContainerForm.container_number"
                label="Numéro de conteneur"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="newContainerForm.seal_number"
                label="Numéro de plomb"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="newContainerForm.loaded_at"
                type="date"
                label="Chargé le"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="newContainerForm.departed_at"
                type="date"
                label="Départ"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="newContainerForm.arrived_at"
                type="date"
                label="Arrivée"
              />
            </VCol>
            <VCol cols="12">
              <AppTextarea
                v-model="newContainerForm.notes"
                label="Notes"
                rows="2"
              />
            </VCol>
          </VRow>
          <VAlert
            v-if="newContainerErrorMsg"
            type="error"
            variant="tonal"
            class="mt-3"
          >
            {{ newContainerErrorMsg }}
          </VAlert>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="newContainerDialog = false">Annuler</VBtn>
          <VBtn
            color="primary"
            :loading="newContainerSaving"
            @click="submitNewContainer"
          >
            Créer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Delete dossier dialog ──────────────────────────── -->
    <VDialog v-model="dialogDelete" max-width="400">
      <VCard title="Supprimer le dossier">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer
          <strong>{{ dossier?.reference }} – {{ dossier?.title }}</strong> ?
          Cette action est irréversible.
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="dialogDelete = false">
            Annuler
          </VBtn>
          <VBtn color="error" @click="deleteDossier">
            Supprimer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
