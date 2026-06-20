<script setup>
import { useRoute, useRouter } from 'vue-router'

definePage({ meta: { layout: 'default' } })

const route = useRoute()
const router = useRouter()

// ── State (mirrored to URL for back/forward navigation) ──────────────────
const sectionId = ref(route.query.section ? Number(route.query.section) : null)
const chapterId = ref(route.query.chapter ? Number(route.query.chapter) : null)
const positionId = ref(route.query.position ? Number(route.query.position) : null)
const search = ref(route.query.search ?? '')
const page = ref(Number(route.query.page ?? 1) || 1)
const perPage = ref(Number(route.query.per_page ?? 25) || 25)

// Sync state → URL whenever a filter changes
function syncQuery() {
  router.replace({
    query: {
      ...(sectionId.value ? { section: String(sectionId.value) } : {}),
      ...(chapterId.value ? { chapter: String(chapterId.value) } : {}),
      ...(positionId.value ? { position: String(positionId.value) } : {}),
      ...(search.value ? { search: search.value } : {}),
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
watch([page, perPage], syncQuery)

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

// ── Sections (always loaded) ─────────────────────────────────────────────
const { data: sectionsData } = await useApi('/hs-categories?roots_only=1&only_active=1&per_page=200')

const sectionOptions = computed(() => {
  const items = sectionsData.value?.data ?? []

  // Prefer TEC sections — keep them, then non-TEC as fallback
  return items
    .slice()
    .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
    .map(s => ({ title: s.label, value: s.id }))
})

// ── Chapters: depend on selected section ─────────────────────────────────
// We use a sentinel parent_id=0 when no section is picked — the API returns
// an empty list, and the dropdown stays empty / disabled.
const chapterUrl = computed(() => {
  const pid = sectionId.value ?? 0
  
  return `/hs-categories?parent_id=${pid}&only_active=1&per_page=200`
})

const { data: chaptersData } = useApi(chapterUrl)

const chapterOptions = computed(() => {
  if (!sectionId.value) return []
  const items = chaptersData.value?.data ?? []
  
  return items
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

// ── Codes endpoint ───────────────────────────────────────────────────────
// Option B: only filter the codes list when a Position is selected. The
// HsCodeController accepts a single category_id only.
const codesUrl = computed(() => {
  const params = new URLSearchParams({
    page: String(page.value),
    per_page: String(perPage.value),
    only_active: '1',
  })

  if (search.value) params.append('search', search.value)
  if (positionId.value) params.append('category_id', String(positionId.value))
  
  return `/hs-codes?${params.toString()}`
})

// Don't fetch codes until a Position is picked OR the user is searching globally.
const enableCodesFetch = computed(() => Boolean(positionId.value) || Boolean(search.value))

// Always pass a valid URL to avoid /api/null, but use a guaranteed-empty
// query when not enabled (category_id=0 returns nothing).
const safeCodesUrl = computed(() => {
  if (enableCodesFetch.value) return codesUrl.value
  
  return '/hs-codes?category_id=0&per_page=1'
})

const { data: codesData, execute: refresh, isFetching } = useApi(safeCodesUrl)

const codes = computed(() => codesData.value?.data ?? [])
const total = computed(() => codesData.value?.meta?.total ?? codesData.value?.total ?? 0)
const lastPage = computed(() => codesData.value?.meta?.last_page ?? codesData.value?.last_page ?? 1)

// ── For category selector inside dialogs ─────────────────────────────────
const { data: allCategoriesData } = await useApi('/hs-categories?per_page=500')
const categoryOptions = computed(() => allCategoriesData.value?.data?.map(c => ({ title: c.label, value: c.id })) ?? [])

// ── Headers ──────────────────────────────────────────────────────────────
const headers = [
  { title: 'Code SH', key: 'code', width: 180 },
  { title: 'Description', key: 'description' },
  { title: 'Catégorie', key: 'category' },
  { title: 'Statut', key: 'is_active', width: 100 },
  { title: 'Actions', key: 'actions', sortable: false, width: 130, align: 'end' },
]

// ── Create/Edit/Delete state ────────────────────────────────────────────
const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)

const defaultForm = () => ({
  hs_category_id: positionId.value ?? null,
  code: '',
  description: '',
  notes: '',
  is_active: true,
})

const form = ref(defaultForm())
const formRef = ref()
const saving = ref(false)
const errorMsg = ref('')

const openCreate = () => {
  editMode.value = false
  form.value = defaultForm()
  errorMsg.value = ''
  dialog.value = true
}

const openEdit = item => {
  editMode.value = true
  selectedItem.value = item
  form.value = {
    hs_category_id: item.hs_category_id ?? item.category?.id ?? null,
    code: item.code,
    description: item.description ?? '',
    notes: item.notes ?? '',
    is_active: !!item.is_active,
  }
  errorMsg.value = ''
  dialog.value = true
}

const openDelete = item => {
  selectedItem.value = item
  dialogDelete.value = true
}

const save = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  errorMsg.value = ''
  try {
    if (editMode.value) {
      await $api(`/hs-codes/${selectedItem.value.id}`, { method: 'PUT', body: form.value })
    }
    else {
      await $api('/hs-codes', { method: 'POST', body: form.value })
    }
    dialog.value = false
    refresh()
  }
  catch (err) {
    errorMsg.value = err?.response?._data?.message ?? 'Une erreur est survenue'
  }
  finally {
    saving.value = false
  }
}

const deleteItem = async () => {
  try {
    await $api(`/hs-codes/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

const resetFilters = () => {
  sectionId.value = null
  chapterId.value = null
  positionId.value = null
  search.value = ''
  page.value = 1
  syncQuery()
}

const truncate = (str, max = 70) => {
  if (!str) return '–'
  
  return str.length > max ? `${str.slice(0, max)}…` : str
}

// ── Detail dialog (eye icon) ─────────────────────────────────────────────
const detailDialog = ref(false)
const detailCode = ref(null)
const detailHierarchy = ref([])
const detailLoading = ref(false)
const detailRates = ref([])
const detailCurrentTariff = ref(null)
const showHistoryInline = ref(false)

async function buildHierarchy(category) {
  // Walk parent chain via /hs-categories/{id}, oldest-first.
  if (!category?.id) return []
  const chain = []
  let currentId = category.id
  let safety = 5
  while (currentId && safety-- > 0) {
    try {
      const res = await $api(`/hs-categories/${currentId}`)
      const cat = res?.data ?? res
      if (!cat) break
      chain.unshift({ id: cat.id, label: cat.label })
      currentId = cat.parent_id ?? cat.parent?.id ?? null
    }
    catch {
      break
    }
  }
  
  return chain
}

async function loadCodeDetails(codeId) {
  try {
    const res = await $api(`/hs-codes/${codeId}`)
    const payload = res?.data ?? res
    if (!payload) return

    // Merge fresh details with the table snapshot
    detailCode.value = { ...detailCode.value, ...payload }
    detailCurrentTariff.value = payload.current_tariff ?? null

    const list = Array.isArray(payload.tariff_rates) ? [...payload.tariff_rates] : []

    list.sort((a, b) => {
      const da = a.effective_from ? new Date(a.effective_from).getTime() : 0
      const db = b.effective_from ? new Date(b.effective_from).getTime() : 0
      
      return db - da
    })
    detailRates.value = list
  }
  catch {
    detailCurrentTariff.value = null
    detailRates.value = []
  }
}

const openDetail = async code => {
  detailCode.value = code
  detailHierarchy.value = []
  detailRates.value = []
  detailCurrentTariff.value = null
  showHistoryInline.value = false
  detailDialog.value = true
  detailLoading.value = true
  try {
    await Promise.all([
      buildHierarchy(code.category).then(h => { detailHierarchy.value = h }),
      loadCodeDetails(code.id),
    ])
  }
  finally {
    detailLoading.value = false
  }
}

// ── Rate edit / create dialog (inside HS code detail) ────────────────────
const rateDialog = ref(false)
const rateEditMode = ref(false) // true=PATCH existing, false=POST new for hs_code
const rateEditingId = ref(null)
const rateForm = ref(makeRateForm())
const rateFormRef = ref()
const rateSaving = ref(false)
const rateErrorMsg = ref('')

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

const computedRateTotal = computed(() => {
  const f = rateForm.value
  
  return Number(f.dd ?? 0)
    + Number(f.rs ?? 0)
    + Number(f.pcs ?? 0)
    + Number(f.pua ?? 0)
    + Number(f.pns ?? 0)
    + Number(f.vat ?? 0)
    + Number(f.da ?? 0)
})

const taxLegend = {
  dd: 'Droit de Douane (TEC CEDEAO) — 0%, 5%, 10%, 20% ou 35%',
  rs: 'Redevance Statistique (TEC CEDEAO) — 1% sur valeur en douane',
  pcs: 'Prélèvement Communautaire de Solidarité (CEDEAO) — 1%',
  pua: 'Prélèvement de l\'Union Africaine — 0,2%',
  pns: 'Prélèvement National de Solidarité — 0,5%',
  vat: 'Taxe sur la Valeur Ajoutée — généralement 18%',
  da: 'Droit d\'Accise — variable selon catégorie',
}

function openEditRate() {
  const r = detailCurrentTariff.value
  if (!r) return
  rateEditMode.value = true
  rateEditingId.value = r.id

  const v = r.rates ?? {}

  rateForm.value = {
    dd: v.dd ?? 0,
    rs: v.rs ?? 0,
    pcs: v.pcs ?? 0,
    pua: v.pua ?? 0,
    pns: v.pns ?? 0,
    vat: v.vat ?? 0,
    da: v.da ?? 0,
    effective_from: r.effective_from ?? new Date().toISOString().slice(0, 10),
    effective_until: r.effective_until ?? '',
    notes: r.notes ?? '',
  }
  rateErrorMsg.value = ''
  rateDialog.value = true
}

function openCreateRate() {
  rateEditMode.value = false
  rateEditingId.value = null
  rateForm.value = makeRateForm()
  rateErrorMsg.value = ''
  rateDialog.value = true
}

const saveDetailRate = async () => {
  const { valid } = await rateFormRef.value.validate()
  if (!valid) return

  rateSaving.value = true
  rateErrorMsg.value = ''
  try {
    const body = {
      dd: Number(rateForm.value.dd ?? 0),
      rs: Number(rateForm.value.rs ?? 0),
      pcs: Number(rateForm.value.pcs ?? 0),
      pua: Number(rateForm.value.pua ?? 0),
      pns: Number(rateForm.value.pns ?? 0),
      vat: Number(rateForm.value.vat ?? 0),
      da: Number(rateForm.value.da ?? 0),
      effective_from: rateForm.value.effective_from,
      effective_until: rateForm.value.effective_until || null,
      notes: rateForm.value.notes || null,
    }

    if (rateEditMode.value) {
      await $api(`/hs-code-tariff-rates/${rateEditingId.value}`, { method: 'PATCH', body })
    }
    else {
      await $api(`/hs-codes/${detailCode.value.id}/tariff-rates`, { method: 'POST', body })
    }
    rateDialog.value = false
    if (detailCode.value?.id) await loadCodeDetails(detailCode.value.id)
  }
  catch (err) {
    rateErrorMsg.value = err?.response?._data?.message ?? 'Une erreur est survenue'
  }
  finally {
    rateSaving.value = false
  }
}

const formatDate = date => date ? new Date(date).toLocaleDateString('fr-FR') : '—'
const formatPct = val => (val !== null && val !== undefined && val !== '') ? `${val} %` : '—'
</script>

<template>
  <div>
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <div>
          <span class="text-h6">Codes SH</span>
          <div class="text-caption text-medium-emphasis">
            Tarif Extérieur Commun CEDEAO — Système Harmonisé 10 chiffres
          </div>
        </div>
        <VBtn
          prepend-icon="tabler-plus"
          color="primary"
          @click="openCreate"
        >
          Ajouter
        </VBtn>
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
        <div
          v-if="!enableCodesFetch"
          class="text-caption text-medium-emphasis mt-1"
        >
          <VIcon
            icon="tabler-info-circle"
            size="14"
            class="me-1"
          />
          Sélectionnez une <strong>Position</strong> ou utilisez la recherche pour voir les codes SH.
        </div>
      </VCardText>

      <VDataTable
        v-if="enableCodesFetch"
        :headers="headers"
        :items="codes"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
        <template #item.code="{ item }">
          <span class="hs-code-mono">{{ item.code }}</span>
        </template>

        <template #item.description="{ item }">
          <span :title="item.description">{{ truncate(item.description) }}</span>
        </template>

        <template #item.category="{ item }">
          <VChip
            v-if="item.category"
            size="small"
            variant="tonal"
            color="primary"
          >
            {{ item.category.label }}
          </VChip>
          <span
            v-else
            class="text-medium-emphasis"
          >–</span>
        </template>

        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'default'"
            size="x-small"
          >
            {{ item.is_active ? 'Actif' : 'Inactif' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-end">
            <VBtn
              icon
              size="small"
              variant="text"
              title="Détail du code SH"
              @click="openDetail(item)"
            >
              <VIcon
                size="18"
                icon="tabler-eye"
              />
            </VBtn>
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

      <VCardText
        v-if="enableCodesFetch"
        class="d-flex align-center justify-space-between flex-wrap gap-2 py-3"
      >
        <div class="d-flex align-center gap-3 flex-wrap">
          <span class="text-body-2 text-medium-emphasis">{{ total }} code(s) au total</span>
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

    <!-- ── Create / Edit dialog ────────────────────────────── -->
    <VDialog
      v-model="dialog"
      max-width="650"
    >
      <VCard :title="editMode ? 'Modifier le code SH' : 'Nouveau code SH'">
        <VCardText>
          <VForm ref="formRef">
            <VRow>
              <VCol cols="12">
                <AppSelect
                  v-model="form.hs_category_id"
                  :items="categoryOptions"
                  label="Catégorie"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol
                cols="12"
                md="5"
              >
                <AppTextField
                  v-model="form.code"
                  label="Code SH"
                  :rules="[requiredValidator]"
                  hint="10 chiffres TEC CEDEAO"
                  persistent-hint
                />
              </VCol>
              <VCol
                cols="12"
                md="7"
              >
                <VSwitch
                  v-model="form.is_active"
                  label="Actif"
                  color="primary"
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Description"
                  rows="3"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="form.notes"
                  label="Notes"
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
            @click="dialog = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="primary"
            :loading="saving"
            @click="save"
          >
            {{ editMode ? 'Enregistrer' : 'Créer' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Delete dialog ──────────────────────────────────── -->
    <VDialog
      v-model="dialogDelete"
      max-width="400"
    >
      <VCard title="Supprimer le code SH">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer <strong>{{ selectedItem?.code }}</strong> ?
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn
            variant="tonal"
            @click="dialogDelete = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="error"
            @click="deleteItem"
          >
            Supprimer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Detail dialog ──────────────────────────────────── -->
    <VDialog
      v-model="detailDialog"
      max-width="700"
      scrollable
    >
      <VCard
        v-if="detailCode"
        title="Détail du code SH"
      >
        <VCardText style="max-block-size: 70vh;">
          <div class="d-flex align-center gap-3 mb-4">
            <VAvatar
              color="primary"
              variant="tonal"
              size="48"
            >
              <VIcon
                icon="tabler-barcode"
                size="26"
              />
            </VAvatar>
            <div>
              <div class="hs-code-mono text-h5">
                {{ detailCode.code }}
              </div>
              <div class="text-caption text-medium-emphasis">
                Code Système Harmonisé
              </div>
            </div>
          </div>

          <VDivider class="mb-4" />

          <div class="text-body-2 text-medium-emphasis mb-1">
            Désignation
          </div>
          <div class="text-body-1 mb-3">
            {{ detailCode.description || '–' }}
          </div>

          <template v-if="detailCode.long_description">
            <div class="text-body-2 text-medium-emphasis mb-1">
              Description longue
            </div>
            <div
              class="text-body-2 mb-3"
              style="white-space: pre-line;"
            >
              {{ detailCode.long_description }}
            </div>
          </template>

          <VRow>
            <VCol
              cols="12"
              md="6"
            >
              <div class="text-body-2 text-medium-emphasis mb-1">
                Unité statistique
              </div>
              <div class="text-body-1">
                {{ detailCode.statistical_unit || '–' }}
              </div>
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <div class="text-body-2 text-medium-emphasis mb-1">
                Statut
              </div>
              <VChip
                :color="detailCode.is_active ? 'success' : 'default'"
                size="small"
              >
                {{ detailCode.is_active ? 'Actif' : 'Inactif' }}
              </VChip>
            </VCol>
          </VRow>

          <template v-if="detailCode.notes">
            <VDivider class="my-3" />
            <div class="text-body-2 text-medium-emphasis mb-1">
              Notes
            </div>
            <div class="text-body-2">
              {{ detailCode.notes }}
            </div>
          </template>

          <VDivider class="my-3" />
          <div class="text-body-2 text-medium-emphasis mb-2">
            Hiérarchie
          </div>
          <div
            v-if="detailLoading"
            class="d-flex align-center gap-2"
          >
            <VProgressCircular
              indeterminate
              size="16"
              width="2"
              color="primary"
            />
            <span class="text-caption text-medium-emphasis">Chargement…</span>
          </div>
          <VBreadcrumbs
            v-else-if="detailHierarchy.length"
            :items="[
              { title: 'Toutes les sections' },
              ...detailHierarchy.map(c => ({ title: c.label })),
            ]"
            density="compact"
            class="pa-0"
          >
            <template #divider>
              <VIcon
                icon="tabler-chevron-right"
                size="14"
              />
            </template>
          </VBreadcrumbs>
          <span
            v-else
            class="text-medium-emphasis text-body-2"
          >–</span>

          <VDivider class="my-3" />
          <div class="d-flex align-center justify-space-between mb-2 flex-wrap gap-2">
            <div class="text-body-2 text-medium-emphasis">
              {{ $t('Taux applicables') }}
            </div>
            <div
              v-if="detailLoading"
              class="d-flex align-center gap-2"
            >
              <VProgressCircular
                indeterminate
                size="14"
                width="2"
                color="primary"
              />
              <span class="text-caption text-medium-emphasis">Chargement…</span>
            </div>
          </div>

          <!-- Empty state -->
          <VAlert
            v-if="!detailLoading && !detailCurrentTariff"
            type="info"
            variant="tonal"
            density="compact"
          >
            {{ $t('Aucun taux applicable à ce jour') }}. Cliquez sur
            "{{ $t('Créer un nouveau taux') }}" pour en saisir un.
            <template #append>
              <VBtn
                color="primary"
                size="small"
                prepend-icon="tabler-plus"
                variant="tonal"
                @click="openCreateRate"
              >
                {{ $t('Créer un nouveau taux') }}
              </VBtn>
            </template>
          </VAlert>

          <!-- Current tariff card -->
          <VCard
            v-else-if="detailCurrentTariff"
            variant="tonal"
            color="primary"
            class="mb-3"
          >
            <VCardText>
              <div class="d-flex flex-wrap gap-1 mb-3">
                <VChip
                  size="small"
                  variant="elevated"
                >
                  DD {{ formatPct(detailCurrentTariff.rates?.dd) }}
                </VChip>
                <VChip
                  size="small"
                  variant="elevated"
                >
                  RS {{ formatPct(detailCurrentTariff.rates?.rs) }}
                </VChip>
                <VChip
                  size="small"
                  variant="elevated"
                >
                  PCS {{ formatPct(detailCurrentTariff.rates?.pcs) }}
                </VChip>
                <VChip
                  size="small"
                  variant="elevated"
                >
                  PUA {{ formatPct(detailCurrentTariff.rates?.pua) }}
                </VChip>
                <VChip
                  size="small"
                  variant="elevated"
                >
                  PNS {{ formatPct(detailCurrentTariff.rates?.pns) }}
                </VChip>
                <VChip
                  size="small"
                  variant="elevated"
                >
                  TVA {{ formatPct(detailCurrentTariff.rates?.vat) }}
                </VChip>
                <VChip
                  size="small"
                  variant="elevated"
                >
                  DA {{ formatPct(detailCurrentTariff.rates?.da) }}
                </VChip>
              </div>
              <div class="d-flex align-center gap-2 mb-1">
                <span class="text-body-2 text-medium-emphasis">Total :</span>
                <strong class="text-h6">
                  {{ formatPct(detailCurrentTariff.rates?.total) }}
                </strong>
              </div>
              <div class="text-caption text-medium-emphasis">
                Applicable depuis le {{ formatDate(detailCurrentTariff.effective_from) }}
                <template v-if="detailCurrentTariff.effective_until">
                  jusqu'au {{ formatDate(detailCurrentTariff.effective_until) }}
                </template>
              </div>
              <div
                v-if="detailCurrentTariff.notes"
                class="text-caption mt-1"
              >
                <VIcon
                  icon="tabler-note"
                  size="12"
                  class="me-1"
                />
                {{ detailCurrentTariff.notes }}
              </div>
            </VCardText>
            <VCardActions class="d-flex flex-wrap gap-1">
              <VBtn
                size="small"
                variant="text"
                prepend-icon="tabler-history"
                @click="showHistoryInline = !showHistoryInline"
              >
                {{ $t("Voir l'historique") }}
                <span
                  v-if="detailRates.length > 1"
                  class="text-caption ms-1"
                >
                  ({{ detailRates.length }})
                </span>
              </VBtn>
              <VBtn
                size="small"
                variant="text"
                prepend-icon="tabler-edit"
                @click="openEditRate"
              >
                {{ $t('Modifier ce taux') }}
              </VBtn>
              <VBtn
                size="small"
                variant="text"
                prepend-icon="tabler-plus"
                @click="openCreateRate"
              >
                {{ $t('Créer un nouveau taux') }}
              </VBtn>
            </VCardActions>
          </VCard>

          <!-- Inline history -->
          <VExpandTransition>
            <div v-if="showHistoryInline && detailRates.length">
              <div class="text-body-2 text-medium-emphasis mb-2">
                {{ $t('Historique des taux') }}
              </div>
              <VTimeline
                density="compact"
                side="end"
                align="start"
              >
                <VTimelineItem
                  v-for="rate in detailRates"
                  :key="rate.id"
                  :dot-color="rate.id === detailCurrentTariff?.id ? 'primary' : 'grey-lighten-1'"
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
                        v-if="rate.id === detailCurrentTariff?.id"
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
                        TVA {{ formatPct(rate.rates?.vat) }}
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
            </div>
          </VExpandTransition>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn
            variant="tonal"
            @click="detailDialog = false"
          >
            Fermer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Rate edit / create dialog ───────────────────────── -->
    <VDialog
      v-model="rateDialog"
      max-width="900"
      scrollable
    >
      <VCard
        :title="rateEditMode
          ? $t('Modifier ce taux')
          : $t('Créer un nouveau taux')"
      >
        <VCardText style="max-block-size: 70vh;">
          <div
            v-if="detailCode"
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
                {{ detailCode.code }}
              </div>
              <div class="text-caption text-medium-emphasis text-truncate">
                {{ detailCode.description ?? '—' }}
              </div>
            </div>
          </div>

          <VForm ref="rateFormRef">
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
                  v-model.number="rateForm[key]"
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
                  :model-value="computedRateTotal.toFixed(2)"
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
                  v-model="rateForm.effective_from"
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
                  v-model="rateForm.effective_until"
                  type="date"
                  label="Date de fin (optionnel)"
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="rateForm.notes"
                  label="Notes (Article LdF, références…)"
                  rows="2"
                />
              </VCol>
            </VRow>

            <VAlert
              v-if="rateErrorMsg"
              type="error"
              variant="tonal"
              class="mt-3"
            >
              {{ rateErrorMsg }}
            </VAlert>
          </VForm>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn
            variant="tonal"
            @click="rateDialog = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="primary"
            :loading="rateSaving"
            @click="saveDetailRate"
          >
            {{ rateEditMode ? 'Enregistrer' : 'Créer' }}
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
</style>
