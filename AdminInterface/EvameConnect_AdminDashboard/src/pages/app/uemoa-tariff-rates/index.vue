<script setup>
import { useRoute, useRouter } from 'vue-router'

definePage({ meta: { layout: 'default' } })

const route = useRoute()
const router = useRouter()

// ── Cascading filter state ───────────────────────────────────────────────
const sectionId = ref(route.query.section ? Number(route.query.section) : null)
const chapterId = ref(route.query.chapter ? Number(route.query.chapter) : null)
const positionId = ref(route.query.position ? Number(route.query.position) : null)
const search = ref(route.query.search ?? '')
const currentOnly = ref(route.query.current === '0' ? false : true)
const page = ref(Number(route.query.page ?? 1) || 1)
const perPage = ref(Number(route.query.per_page ?? 25) || 25)

function syncQuery() {
  router.replace({
    query: {
      ...(sectionId.value ? { section: String(sectionId.value) } : {}),
      ...(chapterId.value ? { chapter: String(chapterId.value) } : {}),
      ...(positionId.value ? { position: String(positionId.value) } : {}),
      ...(search.value ? { search: search.value } : {}),
      ...(currentOnly.value ? {} : { current: '0' }),
      ...(page.value > 1 ? { page: String(page.value) } : {}),
      ...(perPage.value !== 25 ? { per_page: String(perPage.value) } : {}),
    },
  })
}

let searchTimer = null
watch(search, () => {
  page.value = 1
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(syncQuery, 300)
})
watch([page, perPage, currentOnly], syncQuery)

// React to back/forward
watch(() => route.query, q => {
  const newSection = q.section ? Number(q.section) : null
  const newChapter = q.chapter ? Number(q.chapter) : null
  const newPosition = q.position ? Number(q.position) : null
  if (newSection !== sectionId.value) sectionId.value = newSection
  if (newChapter !== chapterId.value) chapterId.value = newChapter
  if (newPosition !== positionId.value) positionId.value = newPosition
  if ((q.search ?? '') !== search.value) search.value = q.search ?? ''
})

// ── Sections (TEC) ───────────────────────────────────────────────────────
const { data: sectionsData } = await useApi('/hs-categories?roots_only=1&only_active=1&per_page=200')

const sectionOptions = computed(() => {
  const items = sectionsData.value?.data ?? []
  
  return items
    .filter(s => (s.slug ?? '').startsWith('tec-section-'))
    .slice()
    .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
    .map(s => ({ title: s.label, value: s.id }))
})

// ── Chapters: depend on selected section ─────────────────────────────────
const chapterUrl = computed(() => {
  const pid = sectionId.value ?? 0
  
  return `/hs-categories?parent_id=${pid}&only_active=1&per_page=200`
})

const { data: chaptersData } = useApi(chapterUrl)

const chapterOptions = computed(() => {
  if (!sectionId.value) return []
  const items = chaptersData.value?.data ?? []
  
  return items
    .filter(c => (c.slug ?? '').startsWith('tec-chapter-'))
    .slice()
    .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
    .map(c => ({ title: c.label, value: c.id }))
})

// ── Positions: depend on selected chapter ────────────────────────────────
const positionUrl = computed(() => {
  const pid = chapterId.value ?? 0
  
  return `/hs-categories?parent_id=${pid}&only_active=1&per_page=500`
})

const { data: positionsData } = useApi(positionUrl)

const positionOptions = computed(() => {
  if (!chapterId.value) return []
  const items = positionsData.value?.data ?? []
  
  return items
    .filter(p => (p.slug ?? '').startsWith('tec-position-'))
    .slice()
    .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
    .map(p => ({ title: p.label, value: p.id }))
})

// ── Cascading reset behaviour ────────────────────────────────────────────
function onSectionChange(val) {
  sectionId.value = val ?? null
  chapterId.value = null
  positionId.value = null
  page.value = 1
  syncQuery()
}
function onChapterChange(val) {
  chapterId.value = val ?? null
  positionId.value = null
  page.value = 1
  syncQuery()
}
function onPositionChange(val) {
  positionId.value = val ?? null
  page.value = 1
  syncQuery()
}

// ── Rates endpoint ───────────────────────────────────────────────────────
// The backend now resolves descendant categories, so any level (Section,
// Chapitre or Position) returns the codes underneath it.
const ratesUrl = computed(() => {
  const params = new URLSearchParams({
    page: String(page.value),
    per_page: String(perPage.value),
  })

  if (search.value) params.append('search', search.value)
  if (currentOnly.value) params.append('current_only', '1')

  const deepestCategoryId = positionId.value ?? chapterId.value ?? sectionId.value
  if (deepestCategoryId) params.append('category_id', String(deepestCategoryId))

  return `/hs-code-tariff-rates?${params.toString()}`
})

const { data: ratesData, execute: refresh, isFetching } = useApi(ratesUrl)

const rates = computed(() => ratesData.value?.data ?? [])
const total = computed(() => ratesData.value?.meta?.total ?? ratesData.value?.total ?? 0)
const lastPage = computed(() => ratesData.value?.meta?.last_page ?? ratesData.value?.last_page ?? 1)

// ── Headers ──────────────────────────────────────────────────────────────
const headers = [
  { title: 'Code SH', key: 'code', width: 120 },
  { title: 'Désignation', key: 'description', width: 320 },
  { title: 'DD', key: 'dd', width: 60, align: 'end' },
  { title: 'RS', key: 'rs', width: 55, align: 'end' },
  { title: 'PCS', key: 'pcs', width: 55, align: 'end' },
  { title: 'PUA', key: 'pua', width: 55, align: 'end' },
  { title: 'PNS', key: 'pns', width: 55, align: 'end' },
  { title: 'TVA', key: 'vat', width: 60, align: 'end' },
  { title: 'DA', key: 'da', width: 55, align: 'end' },
  { title: 'Total', key: 'total', width: 70, align: 'end' },
  { title: 'Actions', key: 'actions', sortable: false, width: 100, align: 'end' },
]

// ── Helpers ──────────────────────────────────────────────────────────────
const formatDate = date => date ? new Date(date).toLocaleDateString('fr-FR') : '—'
const formatPct = val => (val !== null && val !== undefined && val !== '') ? `${val} %` : '—'

// Compact rate format: "5" instead of "5.00 %", "5.5" if half-percent
const formatRate = val => {
  if (val === null || val === undefined || val === '') return '—'
  const n = Number(val)
  if (!Number.isFinite(n)) return '—'
  if (n === 0) return '0'
  const formatted = Number.isInteger(n) ? String(n) : n.toFixed(2).replace(/\.?0+$/, '')
  return formatted
}

const truncate = (str, max = 70) => {
  if (!str) return '—'
  
  return str.length > max ? `${str.slice(0, max)}…` : str
}

const ddColor = val => {
  const n = Number(val)
  if (!Number.isFinite(n)) return 'default'
  if (n === 0) return 'success'
  if (n > 20) return 'warning'
  
  return 'default'
}

// ── Tax legend ───────────────────────────────────────────────────────────
const taxLegend = {
  dd: 'Droit de Douane (TEC CEDEAO) — 0%, 5%, 10%, 20% ou 35%',
  rs: 'Redevance Statistique (TEC CEDEAO) — 1% sur valeur en douane',
  pcs: 'Prélèvement Communautaire de Solidarité (CEDEAO) — 1%',
  pua: 'Prélèvement de l\'Union Africaine — 0,2%',
  pns: 'Prélèvement National de Solidarité — 0,5%',
  vat: 'Taxe sur la Valeur Ajoutée — généralement 18%',
  da: 'Droit d\'Accise — variable selon catégorie',
}

// ── Edit dialog state ────────────────────────────────────────────────────
const editDialog = ref(false)
const editingRate = ref(null)
const editForm = ref(makeRateForm())
const editFormRef = ref()
const saving = ref(false)
const errorMsg = ref('')

function makeRateForm() {
  return {
    dd: 0,
    rs: 0,
    pcs: 0,
    pua: 0,
    pns: 0,
    vat: 0,
    da: 0,
    effective_from: new Date().toISOString().slice(0, 10),
    effective_until: '',
    notes: '',
  }
}

const computedTotal = computed(() => {
  const f = editForm.value
  
  return Number(f.dd ?? 0)
    + Number(f.rs ?? 0)
    + Number(f.pcs ?? 0)
    + Number(f.pua ?? 0)
    + Number(f.pns ?? 0)
    + Number(f.vat ?? 0)
    + Number(f.da ?? 0)
})

const editMode = ref(false) // true = PATCH existing rate, false = POST new versioned rate
const editContextHsCodeId = ref(null) // when creating, the hs_code id to POST to

function openEdit(item) {
  editMode.value = true
  editingRate.value = item
  editContextHsCodeId.value = item.hs_code?.id ?? null

  const r = item.rates ?? {}

  editForm.value = {
    dd: r.dd ?? 0,
    rs: r.rs ?? 0,
    pcs: r.pcs ?? 0,
    pua: r.pua ?? 0,
    pns: r.pns ?? 0,
    vat: r.vat ?? 0,
    da: r.da ?? 0,
    effective_from: item.effective_from ?? new Date().toISOString().slice(0, 10),
    effective_until: item.effective_until ?? '',
    notes: item.notes ?? '',
  }
  errorMsg.value = ''
  editDialog.value = true
}

function openCreateForHsCode(hsCodeId, hsCodeMeta = null) {
  editMode.value = false
  editingRate.value = hsCodeMeta // for the read-only header
  editContextHsCodeId.value = hsCodeId
  editForm.value = makeRateForm()
  errorMsg.value = ''
  editDialog.value = true
}

const saveRate = async () => {
  const { valid } = await editFormRef.value.validate()
  if (!valid) return

  saving.value = true
  errorMsg.value = ''
  try {
    const body = {
      dd: Number(editForm.value.dd ?? 0),
      rs: Number(editForm.value.rs ?? 0),
      pcs: Number(editForm.value.pcs ?? 0),
      pua: Number(editForm.value.pua ?? 0),
      pns: Number(editForm.value.pns ?? 0),
      vat: Number(editForm.value.vat ?? 0),
      da: Number(editForm.value.da ?? 0),
      effective_from: editForm.value.effective_from,
      effective_until: editForm.value.effective_until || null,
      notes: editForm.value.notes || null,
    }

    if (editMode.value) {
      await $api(`/hs-code-tariff-rates/${editingRate.value.id}`, { method: 'PATCH', body })
    }
    else {
      await $api(`/hs-codes/${editContextHsCodeId.value}/tariff-rates`, { method: 'POST', body })
    }
    editDialog.value = false
    refresh()
    if (historyDialog.value && historyHsCodeId.value)
      loadHistory(historyHsCodeId.value)
  }
  catch (err) {
    errorMsg.value = err?.response?._data?.message ?? 'Une erreur est survenue'
  }
  finally {
    saving.value = false
  }
}

// ── Delete dialog ────────────────────────────────────────────────────────
const deleteDialog = ref(false)
const deletingRate = ref(null)

function openDelete(item) {
  deletingRate.value = item
  deleteDialog.value = true
}

const deleteRate = async () => {
  try {
    await $api(`/hs-code-tariff-rates/${deletingRate.value.id}`, { method: 'DELETE' })
    deleteDialog.value = false
    refresh()
  }
  catch {
    deleteDialog.value = false
  }
}

// ── History dialog ───────────────────────────────────────────────────────
const historyDialog = ref(false)
const historyHsCodeId = ref(null)
const historyHsCodeMeta = ref(null)
const historyRates = ref([])
const historyLoading = ref(false)

async function loadHistory(hsCodeId) {
  historyLoading.value = true
  try {
    const res = await $api(`/hs-code-tariff-rates?hs_code_id=${hsCodeId}&per_page=200`)
    const items = res?.data ?? []

    items.sort((a, b) => {
      const da = a.effective_from ? new Date(a.effective_from).getTime() : 0
      const db = b.effective_from ? new Date(b.effective_from).getTime() : 0
      
      return db - da
    })
    historyRates.value = items
  }
  catch {
    historyRates.value = []
  }
  finally {
    historyLoading.value = false
  }
}

async function openHistory(item) {
  historyHsCodeId.value = item.hs_code?.id ?? null
  historyHsCodeMeta.value = item.hs_code ?? null
  historyRates.value = []
  historyDialog.value = true
  if (historyHsCodeId.value) await loadHistory(historyHsCodeId.value)
}

function createNewFromHistory() {
  if (!historyHsCodeId.value) return
  openCreateForHsCode(historyHsCodeId.value, historyHsCodeMeta.value)
}

// ── Reset filters ────────────────────────────────────────────────────────
function resetFilters() {
  sectionId.value = null
  chapterId.value = null
  positionId.value = null
  search.value = ''
  currentOnly.value = true
  page.value = 1
  syncQuery()
}
</script>

<template>
  <div>
    <VCard rounded="lg">
      <!-- ── Header ──────────────────────────────────────────── -->
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <div>
          <span class="text-h6">{{ $t('Taux UEMOA — TEC CEDEAO') }}</span>
          <div class="text-caption text-medium-emphasis">
            Taux DD / RS / PCS / PUA / PNS / TVA / DA pour les 6070 codes SH du Tarif Extérieur Commun
          </div>
        </div>
      </VCardTitle>

      <VDivider />

      <!-- ── Cascading filter row ─────────────────────────── -->
      <VCardText>
        <VRow
          class="mb-1"
          align="center"
        >
          <VCol
            cols="12"
            sm="6"
            md="3"
          >
            <AppSelect
              :model-value="sectionId"
              :items="[{ title: '— Section —', value: null }, ...sectionOptions]"
              density="compact"
              label="Section"
              clearable
              @update:model-value="onSectionChange"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="3"
          >
            <AppSelect
              :model-value="chapterId"
              :items="[{ title: '— Chapitre —', value: null }, ...chapterOptions]"
              density="compact"
              label="Chapitre"
              clearable
              :disabled="!sectionId"
              @update:model-value="onChapterChange"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="3"
          >
            <AppSelect
              :model-value="positionId"
              :items="[{ title: '— Position —', value: null }, ...positionOptions]"
              density="compact"
              label="Position"
              clearable
              :disabled="!chapterId"
              @update:model-value="onPositionChange"
            />
          </VCol>
          <VCol
            cols="12"
            sm="4"
            md="2"
          >
            <AppTextField
              v-model="search"
              placeholder="Rechercher…"
              prepend-inner-icon="tabler-search"
              density="compact"
              clearable
            />
          </VCol>
          <VCol
            cols="12"
            sm="2"
            md="1"
            class="d-flex justify-end"
          >
            <VBtn
              variant="tonal"
              icon="tabler-x"
              size="small"
              title="Réinitialiser"
              @click="resetFilters"
            />
          </VCol>
        </VRow>
        <VRow
          align="center"
          class="mt-0"
        >
          <VCol cols="12">
            <VSwitch
              v-model="currentOnly"
              :label="$t('Taux en vigueur uniquement')"
              color="primary"
              density="compact"
              hide-details
            />
          </VCol>
        </VRow>
      </VCardText>

      <!-- ── Caption above table ─────────────────────────────── -->
      <VCardText class="py-2 d-flex justify-end">
        <div class="text-caption text-medium-emphasis">
          <VIcon
            icon="tabler-info-circle"
            size="14"
            class="me-1"
          />
          Filtrez par Section, Chapitre ou Position — le résultat inclut tous les codes descendants.
        </div>
      </VCardText>

      <!-- ── Table ───────────────────────────────────────────── -->
      <VDataTable
        :headers="headers"
        :items="rates"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
        <template #item.code="{ item }">
          <span class="hs-code-mono">{{ item.hs_code?.code ?? '—' }}</span>
        </template>

        <template #item.description="{ item }">
          <div class="py-1" style="white-space: normal; min-width: 0;">
            <div
              class="text-body-2 description-cell"
              :title="item.hs_code?.description"
            >
              {{ truncate(item.hs_code?.description, 60) }}
            </div>
            <VChip
              v-if="item.hs_code?.category"
              size="x-small"
              variant="tonal"
              color="default"
              class="mt-1 category-chip"
              :title="item.hs_code.category.label"
            >
              <VIcon icon="tabler-folder" size="11" start />
              {{ truncate(item.hs_code.category.label, 40) }}
            </VChip>
          </div>
        </template>

        <template #item.dd="{ item }">
          <span class="text-body-2 font-weight-medium">{{ formatRate(item.rates?.dd) }}</span>
        </template>

        <template #item.rs="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ formatRate(item.rates?.rs) }}</span>
        </template>

        <template #item.pcs="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ formatRate(item.rates?.pcs) }}</span>
        </template>

        <template #item.pua="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ formatRate(item.rates?.pua) }}</span>
        </template>

        <template #item.pns="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ formatRate(item.rates?.pns) }}</span>
        </template>

        <template #item.vat="{ item }">
          <span class="text-body-2 font-weight-medium">{{ formatRate(item.rates?.vat) }}</span>
        </template>

        <template #item.da="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ formatRate(item.rates?.da) }}</span>
        </template>

        <template #item.total="{ item }">
          <strong class="text-primary">{{ formatRate(item.rates?.total) }}</strong>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-end">
            <VBtn
              icon
              size="small"
              variant="text"
              title="Modifier"
              @click="openEdit(item)"
            >
              <VIcon
                size="18"
                icon="tabler-edit"
              />
            </VBtn>
            <VBtn
              icon
              size="small"
              variant="text"
              title="Historique"
              @click="openHistory(item)"
            >
              <VIcon
                size="18"
                icon="tabler-clock"
              />
            </VBtn>
            <VBtn
              icon
              size="small"
              variant="text"
              color="error"
              title="Supprimer"
              @click="openDelete(item)"
            >
              <VIcon
                size="18"
                icon="tabler-trash"
              />
            </VBtn>
          </div>
        </template>
      </VDataTable>

      <!-- ── Pagination block ────────────────────────────────── -->
      <VCardText class="d-flex align-center justify-space-between flex-wrap gap-2 py-3">
        <div class="d-flex align-center gap-3 flex-wrap">
          <span class="text-body-2 text-medium-emphasis">{{ total }} taux au total</span>
          <div class="d-flex align-center gap-2">
            <span class="text-body-2 text-medium-emphasis">Par page :</span>
            <AppSelect
              v-model="perPage"
              :items="[10, 25, 50, 100]"
              density="compact"
              hide-details
              style="inline-size: 90px"
            />
          </div>
        </div>
        <VPagination
          v-model="page"
          :length="lastPage"
          :total-visible="5"
          rounded
        />
      </VCardText>
    </VCard>

    <!-- ── Edit / Create dialog ────────────────────────────── -->
    <VDialog
      v-model="editDialog"
      max-width="900"
      scrollable
    >
      <VCard
        :title="editMode
          ? 'Modifier le taux'
          : $t('Créer un nouveau taux')"
      >
        <VCardText style="max-block-size: 70vh;">
          <!-- Read-only HS code header -->
          <div
            v-if="editingRate"
            class="d-flex align-center gap-3 mb-4 pa-3 rounded bg-grey-lighten-5"
          >
            <VAvatar
              color="primary"
              variant="tonal"
              size="40"
            >
              <VIcon
                icon="tabler-barcode"
                size="22"
              />
            </VAvatar>
            <div class="flex-grow-1 min-w-0">
              <div class="hs-code-mono text-subtitle-1 font-weight-bold">
                {{ editingRate.hs_code?.code ?? editingRate.code ?? '—' }}
              </div>
              <div class="text-caption text-medium-emphasis text-truncate">
                {{ editingRate.hs_code?.description ?? editingRate.description ?? '—' }}
              </div>
            </div>
          </div>

          <VForm ref="editFormRef">
            <div class="text-subtitle-2 mb-2">
              Taux applicables (%)
            </div>

            <VRow>
              <VCol
                v-for="key in ['dd', 'rs', 'pcs', 'pua', 'pns', 'vat', 'da']"
                :key="key"
                cols="12"
                sm="6"
                md="4"
              >
                <AppTextField
                  v-model.number="editForm[key]"
                  type="number"
                  step="0.01"
                  :label="key.toUpperCase()"
                  suffix="%"
                >
                  <template #append-inner>
                    <VIcon
                      icon="tabler-info-circle"
                      size="16"
                      class="text-medium-emphasis"
                    >
                      <VTooltip
                        activator="parent"
                        location="top"
                        max-width="280"
                      >
                        {{ taxLegend[key] }}
                      </VTooltip>
                    </VIcon>
                  </template>
                </AppTextField>
              </VCol>

              <VCol
                cols="12"
                sm="6"
                md="4"
              >
                <AppTextField
                  :model-value="computedTotal.toFixed(2)"
                  label="Total"
                  suffix="%"
                  readonly
                  variant="filled"
                />
              </VCol>
            </VRow>

            <VDivider class="my-3" />
            <div class="text-subtitle-2 mb-2">
              Période d'application
            </div>

            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="editForm.effective_from"
                  type="date"
                  label="Date de début"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="editForm.effective_until"
                  type="date"
                  label="Date de fin (optionnel)"
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="editForm.notes"
                  label="Notes (Article LdF, références…)"
                  rows="2"
                />
              </VCol>
            </VRow>

            <VAlert
              v-if="errorMsg"
              type="error"
              variant="tonal"
              class="mt-3"
            >
              {{ errorMsg }}
            </VAlert>
          </VForm>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn
            variant="tonal"
            @click="editDialog = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="primary"
            :loading="saving"
            @click="saveRate"
          >
            {{ editMode ? 'Enregistrer' : 'Créer' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── History dialog ──────────────────────────────────── -->
    <VDialog
      v-model="historyDialog"
      max-width="780"
      scrollable
    >
      <VCard :title="$t('Historique des taux')">
        <VCardText style="max-block-size: 70vh;">
          <div
            v-if="historyHsCodeMeta"
            class="d-flex align-center gap-3 mb-4 pa-3 rounded bg-grey-lighten-5"
          >
            <VAvatar
              color="primary"
              variant="tonal"
              size="40"
            >
              <VIcon
                icon="tabler-barcode"
                size="22"
              />
            </VAvatar>
            <div class="flex-grow-1 min-w-0">
              <div class="hs-code-mono text-subtitle-1 font-weight-bold">
                {{ historyHsCodeMeta.code }}
              </div>
              <div class="text-caption text-medium-emphasis text-truncate">
                {{ historyHsCodeMeta.description ?? '—' }}
              </div>
            </div>
          </div>

          <div
            v-if="historyLoading"
            class="d-flex justify-center pa-6"
          >
            <VProgressCircular
              indeterminate
              color="primary"
            />
          </div>

          <VAlert
            v-else-if="!historyRates.length"
            type="info"
            variant="tonal"
            density="compact"
          >
            Aucun taux historisé pour ce code SH.
          </VAlert>

          <VTimeline
            v-else
            density="compact"
            side="end"
            align="start"
          >
            <VTimelineItem
              v-for="(rate, idx) in historyRates"
              :key="rate.id"
              :dot-color="idx === 0 ? 'primary' : 'grey-lighten-1'"
              size="x-small"
            >
              <div class="mb-3">
                <div class="d-flex align-center flex-wrap gap-2 mb-1">
                  <strong>
                    {{ formatDate(rate.effective_from) }}
                    <span class="text-medium-emphasis"> → </span>
                    {{ rate.effective_until ? formatDate(rate.effective_until) : 'En cours' }}
                  </strong>
                  <VChip
                    v-if="idx === 0"
                    size="x-small"
                    color="primary"
                  >
                    Actuel
                  </VChip>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-1">
                  <VChip
                    size="x-small"
                    variant="tonal"
                  >
                    DD {{ formatPct(rate.rates?.dd) }}
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                  >
                    RS {{ formatPct(rate.rates?.rs) }}
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                  >
                    PCS {{ formatPct(rate.rates?.pcs) }}
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                  >
                    PUA {{ formatPct(rate.rates?.pua) }}
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                  >
                    PNS {{ formatPct(rate.rates?.pns) }}
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                  >
                    TVA {{ formatPct(rate.rates?.vat) }}
                  </VChip>
                  <VChip
                    size="x-small"
                    variant="tonal"
                  >
                    DA {{ formatPct(rate.rates?.da) }}
                  </VChip>
                  <VChip
                    size="x-small"
                    color="primary"
                  >
                    Total {{ formatPct(rate.rates?.total) }}
                  </VChip>
                </div>
                <div
                  v-if="rate.notes"
                  class="text-caption text-medium-emphasis"
                >
                  {{ rate.notes }}
                </div>
              </div>
            </VTimelineItem>
          </VTimeline>
        </VCardText>
        <VCardActions class="justify-space-between">
          <VBtn
            color="primary"
            variant="tonal"
            prepend-icon="tabler-plus"
            @click="createNewFromHistory"
          >
            {{ $t('Créer un nouveau taux') }}
          </VBtn>
          <VBtn
            variant="tonal"
            @click="historyDialog = false"
          >
            Fermer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Delete dialog ───────────────────────────────────── -->
    <VDialog
      v-model="deleteDialog"
      max-width="400"
    >
      <VCard title="Supprimer le taux">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer ce taux pour
          <strong class="hs-code-mono">{{ deletingRate?.hs_code?.code ?? '—' }}</strong>
          (à compter du {{ formatDate(deletingRate?.effective_from) }}) ?
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn
            variant="tonal"
            @click="deleteDialog = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="error"
            @click="deleteRate"
          >
            Supprimer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.hs-code-mono {
  font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace);
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.02em;
}
.min-w-0 {
  min-inline-size: 0;
}
.description-cell {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-inline-size: 320px;
}
.category-chip {
  background-color: rgba(var(--v-theme-on-surface), 0.06) !important;
  color: rgba(var(--v-theme-on-surface), 0.72) !important;
  max-inline-size: 320px;
}
.category-chip :deep(.v-chip__content) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
