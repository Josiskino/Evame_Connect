<script setup>
import { useRoute, useRouter } from 'vue-router'

definePage({ meta: { layout: 'default' } })

const route = useRoute()
const router = useRouter()

// ── State derived from URL query ─────────────────────────────────────────
const level = computed(() => {
  const v = Number(route.query.level ?? 0)
  return Number.isFinite(v) && v >= 0 && v <= 3 ? v : 0
})
const parentId = computed(() => route.query.parent ? Number(route.query.parent) : null)
const search = ref(route.query.search ?? '')
const includeInactive = ref(route.query.inactive === '1')
const showLegacy = ref(route.query.legacy === '1')
const viewMode = ref(route.query.view === 'table' ? 'table' : 'drill')

// Table-mode dedicated state (paginated server-side)
const tablePage = ref(Number(route.query.tpage ?? 1) || 1)
const tablePerPage = ref(Number(route.query.tper ?? 25) || 25)
const tableLevelFilter = ref(route.query.tlevel ?? 'all') // all / section / chapter / position / legacy

watch(viewMode, val => {
  router.replace({
    query: { ...route.query, view: val === 'table' ? 'table' : undefined },
  })
})

watch([tablePage, tablePerPage, tableLevelFilter], () => {
  router.replace({
    query: {
      ...route.query,
      tpage: tablePage.value > 1 ? String(tablePage.value) : undefined,
      tper: tablePerPage.value !== 25 ? String(tablePerPage.value) : undefined,
      tlevel: tableLevelFilter.value !== 'all' ? tableLevelFilter.value : undefined,
    },
  })
})

watch(tableLevelFilter, () => { tablePage.value = 1 })

// Sync search/toggles back into URL (debounced via watch)
let searchTimeout = null
watch(search, val => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.replace({
      query: { ...route.query, search: val || undefined },
    })
  }, 300)
})
watch(includeInactive, val => {
  router.replace({ query: { ...route.query, inactive: val ? '1' : undefined } })
})
watch(showLegacy, val => {
  router.replace({ query: { ...route.query, legacy: val ? '1' : undefined } })
})

// Reset local search when URL search query changes externally (back/forward)
watch(() => route.query.search, q => {
  if ((q ?? '') !== search.value) search.value = q ?? ''
})
watch(() => route.query.inactive, q => {
  includeInactive.value = q === '1'
})
watch(() => route.query.legacy, q => {
  showLegacy.value = q === '1'
})

// ── Breadcrumb stack ─────────────────────────────────────────────────────
// Each entry: { id, label, level }
const breadcrumbStack = ref([])

// When level=0, clear stack. When parentId points to a category we don't have
// in the stack (e.g., direct URL load), fetch it via /hs-categories/{id}.
async function ensureBreadcrumbForCurrent() {
  if (level.value === 0) {
    breadcrumbStack.value = []
    return
  }
  if (!parentId.value) return

  // If top of stack already matches the current parent, nothing to do.
  const top = breadcrumbStack.value[breadcrumbStack.value.length - 1]
  if (top && top.id === parentId.value) return

  // Try to walk back: if the current parent is in the stack at some position,
  // truncate to that.
  const idx = breadcrumbStack.value.findIndex(c => c.id === parentId.value)
  if (idx !== -1) {
    breadcrumbStack.value = breadcrumbStack.value.slice(0, idx + 1)
    return
  }

  // Otherwise, deep-fetch the parent chain via /hs-categories/{id}.
  try {
    const chain = []
    let currentId = parentId.value
    let safety = 5
    while (currentId && safety-- > 0) {
      const res = await $api(`/hs-categories/${currentId}`)
      const cat = res?.data ?? res
      if (!cat) break
      chain.unshift({ id: cat.id, label: cat.label, level: chain.length + 1 })
      currentId = cat.parent_id ?? cat.parent?.id ?? null
    }
    // re-level the chain (root is level 1, then 2, then 3)
    chain.forEach((c, i) => { c.level = i + 1 })
    breadcrumbStack.value = chain
  }
  catch {
    breadcrumbStack.value = []
  }
}

// ── Endpoint computed from current state ─────────────────────────────────
const queryUrl = computed(() => {
  const params = new URLSearchParams()
  if (!includeInactive.value) params.set('only_active', '1')
  params.set('per_page', '500')
  if (search.value) params.set('search', search.value)

  if (level.value === 3) {
    if (parentId.value) params.set('category_id', String(parentId.value))
    return `/hs-codes?${params.toString()}`
  }

  if (level.value === 0) {
    if (showLegacy.value) {
      // Show only legacy non-TEC roots
      params.set('roots_only', '1')
    }
    else {
      params.set('roots_only', '1')
    }
  }
  else if (parentId.value) {
    params.set('parent_id', String(parentId.value))
  }
  return `/hs-categories?${params.toString()}`
})

const { data, isFetching, execute: refresh } = useApi(queryUrl)
const rawItems = computed(() => data.value?.data ?? [])

// At level 0, partition TEC sections vs legacy categories based on slug prefix.
const tecSections = computed(() => rawItems.value.filter(i => (i.slug ?? '').startsWith('tec-')))
const legacyCategories = computed(() => rawItems.value.filter(i => !(i.slug ?? '').startsWith('tec-')))

const displayedItems = computed(() => {
  if (level.value === 0) {
    return showLegacy.value ? legacyCategories.value : tecSections.value
  }
  return rawItems.value
})

// ── Drill navigation ─────────────────────────────────────────────────────
function drillInto(category) {
  // Push to breadcrumb stack
  breadcrumbStack.value = [
    ...breadcrumbStack.value,
    { id: category.id, label: category.label, level: level.value + 1 },
  ]
  router.push({
    query: {
      level: level.value + 1,
      parent: category.id,
      ...(includeInactive.value ? { inactive: '1' } : {}),
    },
  })
}

function goToRoot() {
  breadcrumbStack.value = []
  router.push({
    query: {
      level: 0,
      ...(includeInactive.value ? { inactive: '1' } : {}),
    },
  })
}

function goToBreadcrumbAt(targetIndex) {
  // targetIndex is the index in breadcrumbStack (0-based)
  const target = breadcrumbStack.value[targetIndex]
  if (!target) return goToRoot()
  breadcrumbStack.value = breadcrumbStack.value.slice(0, targetIndex + 1)
  router.push({
    query: {
      level: target.level,
      parent: target.id,
      ...(includeInactive.value ? { inactive: '1' } : {}),
    },
  })
}

function showLegacyView() {
  showLegacy.value = true
  router.push({
    query: {
      level: 0,
      legacy: '1',
      ...(includeInactive.value ? { inactive: '1' } : {}),
    },
  })
}

// ── Init: ensure breadcrumb is correct on mount and on route change ──────
watch(
  () => [route.query.level, route.query.parent],
  () => { ensureBreadcrumbForCurrent() },
  { immediate: true },
)

// ── Header counts helpers ────────────────────────────────────────────────
const headerLabels = {
  0: 'Sections',
  1: 'Chapitres',
  2: 'Positions',
  3: 'Codes SH',
}

const cardIcons = {
  0: 'tabler-folder',
  1: 'tabler-folders',
  2: 'tabler-folder-open',
}

const cardCaption = item => {
  if (level.value === 0) {
    return `${item.children_count ?? 0} chapitre(s) · ${item.hs_codes_count ?? 0} code(s) SH`
  }
  if (level.value === 1) {
    return `${item.children_count ?? 0} position(s) · ${item.hs_codes_count ?? 0} code(s) SH`
  }
  if (level.value === 2) {
    return `${item.hs_codes_count ?? 0} code(s) SH`
  }
  return ''
}

// ── Categories list (for create/edit dialog parent picker) ───────────────
const { data: allCategoriesData, execute: refreshAllCategories } = await useApi('/hs-categories?per_page=500')
const categoryOptions = computed(() => allCategoriesData.value?.data?.map(c => ({ title: c.label, value: c.id })) ?? [])

// ── Create / Edit / Delete dialogs ───────────────────────────────────────
const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const defaultForm = () => ({
  parent_id: parentId.value ?? null,
  slug: '',
  label: '',
  description: '',
  sort_order: 0,
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
    parent_id: item.parent_id ?? item.parent?.id ?? null,
    slug: item.slug,
    label: item.label,
    description: item.description ?? '',
    sort_order: item.sort_order ?? 0,
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
      await $api(`/hs-categories/${selectedItem.value.id}`, { method: 'PUT', body: form.value })
    }
    else {
      await $api('/hs-categories', { method: 'POST', body: form.value })
    }
    dialog.value = false
    refresh()
    refreshAllCategories()
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
    await $api(`/hs-categories/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
    refreshAllCategories()
  }
  catch {
    dialogDelete.value = false
  }
}

// ── Code detail dialog (Level 3) ─────────────────────────────────────────
const codeDialog = ref(false)
const selectedCode = ref(null)
const openCodeDetail = code => {
  selectedCode.value = code
  codeDialog.value = true
}

const truncate = (str, max = 80) => {
  if (!str) return '–'
  return str.length > max ? `${str.slice(0, max)}…` : str
}

const codeHeaders = [
  { title: 'Code SH', key: 'code', width: 180 },
  { title: 'Désignation', key: 'description' },
  { title: 'U.S.', key: 'statistical_unit', width: 80 },
  { title: 'Statut', key: 'is_active', width: 100 },
  { title: 'Détail', key: 'actions', sortable: false, width: 90, align: 'end' },
]

// ── Table mode (flat paginated view) ─────────────────────────────────────
const tableQueryUrl = computed(() => {
  const params = new URLSearchParams({
    page: String(tablePage.value),
    per_page: String(tablePerPage.value),
  })
  if (search.value) params.set('search', search.value)
  if (!includeInactive.value) params.set('only_active', '1')
  return `/hs-categories?${params.toString()}`
})

const { data: tableData, isFetching: isFetchingTable } = useApi(tableQueryUrl)

const tableRawItems = computed(() => tableData.value?.data ?? [])
const tableTotal = computed(() => tableData.value?.meta?.total ?? 0)
const tableLastPage = computed(() => tableData.value?.meta?.last_page ?? 1)

function detectLevel(slug) {
  const s = slug ?? ''
  if (s.startsWith('tec-section-')) return 'section'
  if (s.startsWith('tec-chapter-')) return 'chapter'
  if (s.startsWith('tec-position-')) return 'position'
  return 'legacy'
}

const tableItems = computed(() => {
  if (tableLevelFilter.value === 'all') return tableRawItems.value
  return tableRawItems.value.filter(i => detectLevel(i.slug) === tableLevelFilter.value)
})

const levelMeta = {
  section: { label: 'Section', color: 'primary', icon: 'tabler-folder' },
  chapter: { label: 'Chapitre', color: 'info', icon: 'tabler-folders' },
  position: { label: 'Position', color: 'success', icon: 'tabler-folder-open' },
  legacy: { label: 'Legacy', color: 'secondary', icon: 'tabler-archive' },
}

const tableHeaders = [
  { title: 'Label', key: 'label' },
  { title: 'Slug', key: 'slug', width: 200 },
  { title: 'Niveau', key: 'level', width: 130, sortable: false },
  { title: 'Parent', key: 'parent', width: 220, sortable: false },
  { title: 'Sous-cat.', key: 'children_count', width: 100 },
  { title: 'Codes SH', key: 'hs_codes_count', width: 110 },
  { title: 'Statut', key: 'is_active', width: 100 },
  { title: 'Actions', key: 'actions', sortable: false, width: 130, align: 'end' },
]
</script>

<template>
  <div>
    <!-- ── Header ──────────────────────────────────────────── -->
    <div class="d-flex align-center flex-wrap gap-6 mb-4">
      <div class="flex-grow-1">
        <div class="text-h5 font-weight-bold">
          Catégories tarifaires (TEC CEDEAO)
        </div>
        <div class="text-body-2 text-medium-emphasis">
          {{ viewMode === 'table' ? 'Vue tableau plate' : 'Parcours hiérarchique des sections, chapitres, positions et codes SH' }}
        </div>
      </div>
      <VBtnToggle
        v-model="viewMode"
        mandatory
        divided
        density="comfortable"
        variant="outlined"
        color="primary"
      >
        <VBtn value="drill" min-width="120">
          <VIcon icon="tabler-stack-2" size="18" class="me-2" />
          Cartes
        </VBtn>
        <VBtn value="table" min-width="120">
          <VIcon icon="tabler-table" size="18" class="me-2" />
          Tableau
        </VBtn>
      </VBtnToggle>
      <VBtn
        prepend-icon="tabler-plus"
        color="primary"
        @click="openCreate"
      >
        Ajouter
      </VBtn>
    </div>

    <!-- ── Breadcrumb (drill mode only) ────────────────────── -->
    <VCard v-if="viewMode === 'drill'" class="mb-4" rounded="lg">
      <VCardText class="py-2">
        <VBreadcrumbs
          :items="[
            { title: 'Toutes les sections', disabled: level === 0, onClick: goToRoot },
            ...breadcrumbStack.map((c, i) => ({
              title: c.label,
              disabled: i === breadcrumbStack.length - 1 && level === c.level,
              onClick: () => goToBreadcrumbAt(i),
            })),
          ]"
          density="compact"
          class="pa-0"
        >
          <template #divider>
            <VIcon icon="tabler-chevron-right" size="14" />
          </template>
          <template #item="{ item }">
            <a
              v-if="!item.disabled"
              href="#"
              class="text-primary text-decoration-none"
              @click.prevent="item.onClick && item.onClick()"
            >
              {{ item.title }}
            </a>
            <span v-else class="text-medium-emphasis font-weight-medium">
              {{ item.title }}
            </span>
          </template>
        </VBreadcrumbs>
      </VCardText>
    </VCard>

    <!-- ── Filter bar ──────────────────────────────────────── -->
    <VCard class="mb-4" rounded="lg">
      <VCardText>
        <VRow align="center">
          <VCol cols="12" sm="6" md="5">
            <AppTextField
              v-model="search"
              :placeholder="level === 3 ? 'Rechercher un code ou une désignation…' : 'Rechercher…'"
              prepend-inner-icon="tabler-search"
              density="compact"
              clearable
            />
          </VCol>
          <VCol cols="12" sm="6" md="4">
            <VSwitch
              v-model="includeInactive"
              label="Inclure inactifs"
              color="primary"
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="12" sm="12" md="3" class="d-flex justify-md-end">
            <VChip
              v-if="!isFetching"
              size="small"
              variant="tonal"
              color="info"
            >
              {{ displayedItems.length }} {{ headerLabels[level]?.toLowerCase() ?? 'élément(s)' }}
            </VChip>
            <VProgressCircular
              v-else
              indeterminate
              size="22"
              width="2"
              color="primary"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- ── Browse area ─────────────────────────────────────── -->
    <VCard v-if="viewMode === 'drill'" rounded="lg">
      <VCardText>
        <!-- Loading skeleton -->
        <div v-if="isFetching && !displayedItems.length" class="d-flex justify-center pa-8">
          <VProgressCircular indeterminate color="primary" />
        </div>

        <!-- Empty state -->
        <VAlert
          v-else-if="!displayedItems.length"
          type="info"
          variant="tonal"
          density="compact"
        >
          <template v-if="level === 0 && showLegacy">
            Aucune catégorie métier (legacy) à afficher.
          </template>
          <template v-else-if="level === 0">
            Aucune section TEC CEDEAO trouvée. Importez le référentiel ou créez une nouvelle catégorie.
          </template>
          <template v-else-if="level === 3">
            Aucun code SH dans cette position{{ search ? ' pour cette recherche' : '' }}.
          </template>
          <template v-else>
            Aucune sous-catégorie{{ search ? ' pour cette recherche' : '' }}.
          </template>
        </VAlert>

        <!-- ── Level 0/1/2: Card grid ───────────────────────── -->
        <template v-else-if="level < 3">
          <VRow>
            <!-- Legacy banner card at level 0 (TEC view, when not already in legacy mode) -->
            <VCol
              v-if="level === 0 && !showLegacy && legacyCategories.length"
              cols="12"
              sm="6"
              lg="4"
            >
              <VCard
                rounded="lg"
                class="h-100 cursor-pointer legacy-card"
                variant="tonal"
                color="secondary"
                hover
                @click="showLegacyView"
              >
                <VCardText>
                  <div class="d-flex align-center gap-3 mb-2">
                    <VAvatar
                      color="secondary"
                      variant="flat"
                      size="40"
                    >
                      <VIcon icon="tabler-archive" size="22" />
                    </VAvatar>
                    <div class="flex-grow-1">
                      <div class="text-body-1 font-weight-bold">
                        Catégories métier (legacy)
                      </div>
                      <div class="text-caption text-medium-emphasis">
                        Ancien référentiel — non préfixé par tec-
                      </div>
                    </div>
                  </div>
                  <VChip
                    size="x-small"
                    variant="elevated"
                    color="secondary"
                  >
                    {{ legacyCategories.length }} catégorie(s)
                  </VChip>
                </VCardText>
              </VCard>
            </VCol>

            <!-- Normal section/chapter/position cards -->
            <VCol
              v-for="item in displayedItems"
              :key="item.id"
              cols="12"
              sm="6"
              lg="4"
            >
              <VCard
                rounded="lg"
                class="h-100 cursor-pointer browse-card"
                hover
                @click="drillInto(item)"
              >
                <VCardText>
                  <div class="d-flex align-start gap-3 mb-2">
                    <VAvatar
                      color="primary"
                      variant="tonal"
                      size="40"
                    >
                      <VIcon
                        :icon="cardIcons[level]"
                        size="22"
                      />
                    </VAvatar>
                    <div class="flex-grow-1 min-w-0">
                      <div
                        class="text-body-1 font-weight-bold text-truncate"
                        :title="item.label"
                      >
                        {{ item.label }}
                      </div>
                      <div class="text-caption text-medium-emphasis text-truncate">
                        {{ item.slug }}
                      </div>
                    </div>
                    <VBtn
                      icon
                      size="x-small"
                      variant="text"
                      title="Modifier"
                      @click.stop="openEdit(item)"
                    >
                      <VIcon size="16" icon="tabler-edit" />
                    </VBtn>
                    <VBtn
                      icon
                      size="x-small"
                      variant="text"
                      color="error"
                      title="Supprimer"
                      @click.stop="openDelete(item)"
                    >
                      <VIcon size="16" icon="tabler-trash" />
                    </VBtn>
                  </div>

                  <div class="d-flex align-center justify-space-between flex-wrap gap-2 mt-3">
                    <span class="text-caption text-medium-emphasis">
                      {{ cardCaption(item) }}
                    </span>
                    <VChip
                      v-if="!item.is_active"
                      size="x-small"
                      color="default"
                      variant="tonal"
                    >
                      Inactif
                    </VChip>
                  </div>
                </VCardText>
              </VCard>
            </VCol>
          </VRow>
        </template>

        <!-- ── Level 3: Code table ──────────────────────────── -->
        <template v-else>
          <VDataTable
            :headers="codeHeaders"
            :items="displayedItems"
            :loading="isFetching"
            hide-default-footer
            :items-per-page="-1"
            class="text-no-wrap"
          >
            <template #item.code="{ item }">
              <span class="hs-code-mono">{{ item.code }}</span>
            </template>

            <template #item.description="{ item }">
              <span :title="item.description">{{ truncate(item.description, 100) }}</span>
            </template>

            <template #item.statistical_unit="{ item }">
              <span class="text-caption text-medium-emphasis">
                {{ item.statistical_unit ?? '–' }}
              </span>
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
              <VBtn
                icon
                size="small"
                variant="text"
                title="Détail du code SH"
                @click="openCodeDetail(item)"
              >
                <VIcon size="18" icon="tabler-eye" />
              </VBtn>
            </template>
          </VDataTable>
        </template>
      </VCardText>
    </VCard>

    <!-- ── Table mode (flat paginated view) ────────────────── -->
    <VCard v-else rounded="lg">
      <VCardText class="pb-2">
        <VRow align="center" class="mb-2">
          <VCol cols="12" sm="6" md="4">
            <AppSelect
              v-model="tableLevelFilter"
              :items="[
                { title: 'Tous les niveaux', value: 'all' },
                { title: 'Sections', value: 'section' },
                { title: 'Chapitres', value: 'chapter' },
                { title: 'Positions', value: 'position' },
                { title: 'Legacy', value: 'legacy' },
              ]"
              density="compact"
              hide-details
              prepend-inner-icon="tabler-filter"
            />
          </VCol>
          <VCol cols="12" sm="6" md="4" class="d-flex justify-md-end">
            <VChip
              v-if="!isFetchingTable"
              size="small"
              variant="tonal"
              color="info"
            >
              {{ tableTotal }} catégorie(s) au total
            </VChip>
          </VCol>
        </VRow>
      </VCardText>

      <VDataTable
        :headers="tableHeaders"
        :items="tableItems"
        :loading="isFetchingTable"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
        <template #item.label="{ item }">
          <div class="d-flex align-center gap-2">
            <VIcon
              :icon="levelMeta[detectLevel(item.slug)]?.icon ?? 'tabler-folder'"
              :color="levelMeta[detectLevel(item.slug)]?.color ?? 'default'"
              size="18"
            />
            <span class="font-weight-medium">{{ item.label }}</span>
          </div>
        </template>

        <template #item.slug="{ item }">
          <code class="text-caption">{{ item.slug }}</code>
        </template>

        <template #item.level="{ item }">
          <VChip
            size="x-small"
            variant="tonal"
            :color="levelMeta[detectLevel(item.slug)]?.color ?? 'default'"
          >
            {{ levelMeta[detectLevel(item.slug)]?.label ?? '–' }}
          </VChip>
        </template>

        <template #item.parent="{ item }">
          <span v-if="item.parent" class="text-body-2">{{ item.parent.label }}</span>
          <span v-else class="text-medium-emphasis">–</span>
        </template>

        <template #item.children_count="{ item }">
          <span class="text-body-2">{{ item.children_count ?? 0 }}</span>
        </template>

        <template #item.hs_codes_count="{ item }">
          <span class="text-body-2">{{ item.hs_codes_count ?? 0 }}</span>
        </template>

        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'default'"
            size="small"
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
              title="Modifier"
              @click="openEdit(item)"
            >
              <VIcon size="18" icon="tabler-edit" />
            </VBtn>
            <VBtn
              icon
              size="small"
              variant="text"
              color="error"
              title="Supprimer"
              @click="openDelete(item)"
            >
              <VIcon size="18" icon="tabler-trash" />
            </VBtn>
          </div>
        </template>
      </VDataTable>

      <VCardText class="d-flex align-center justify-space-between flex-wrap gap-3 py-3">
        <div class="d-flex align-center gap-3 flex-wrap">
          <span class="text-body-2 text-medium-emphasis">
            {{ tableTotal }} au total
          </span>
          <div class="d-flex align-center gap-2">
            <span class="text-body-2 text-medium-emphasis">Par page :</span>
            <AppSelect
              v-model="tablePerPage"
              :items="[10, 25, 50, 100]"
              density="compact"
              hide-details
              style="inline-size: 90px"
            />
          </div>
        </div>
        <VPagination
          v-model="tablePage"
          :length="tableLastPage"
          :total-visible="5"
          rounded
        />
      </VCardText>
    </VCard>

    <!-- ── Create / Edit dialog ────────────────────────────── -->
    <VDialog
      v-model="dialog"
      max-width="600"
    >
      <VCard :title="editMode ? 'Modifier la catégorie' : 'Nouvelle catégorie tarifaire'">
        <VCardText>
          <VForm ref="formRef">
            <VRow>
              <VCol cols="12">
                <AppSelect
                  v-model="form.parent_id"
                  :items="[{ title: '— Racine —', value: null }, ...categoryOptions]"
                  label="Catégorie parente (optionnel)"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.slug"
                  label="Slug"
                  :rules="[requiredValidator, alphaDashValidator]"
                  placeholder="tec-section-i"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.label"
                  label="Libellé"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="form.description"
                  label="Description"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.sort_order"
                  type="number"
                  label="Ordre d'affichage"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VSwitch
                  v-model="form.is_active"
                  label="Actif"
                  color="primary"
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
          <VBtn variant="tonal" @click="dialog = false">
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
      <VCard title="Supprimer la catégorie">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer <strong>{{ selectedItem?.label }}</strong> ?
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="dialogDelete = false">
            Annuler
          </VBtn>
          <VBtn color="error" @click="deleteItem">
            Supprimer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Code detail dialog ─────────────────────────────── -->
    <VDialog
      v-model="codeDialog"
      max-width="700"
      scrollable
    >
      <VCard v-if="selectedCode" title="Détail du code SH">
        <VCardText style="max-block-size: 70vh;">
          <div class="d-flex align-center gap-3 mb-4">
            <VAvatar color="primary" variant="tonal" size="48">
              <VIcon icon="tabler-barcode" size="26" />
            </VAvatar>
            <div>
              <div class="hs-code-mono text-h5">
                {{ selectedCode.code }}
              </div>
              <div class="text-caption text-medium-emphasis">
                Code Système Harmonisé
              </div>
            </div>
          </div>

          <VDivider class="mb-4" />

          <div class="text-body-2 text-medium-emphasis mb-1">Désignation</div>
          <div class="text-body-1 mb-3">
            {{ selectedCode.description || '–' }}
          </div>

          <template v-if="selectedCode.long_description">
            <div class="text-body-2 text-medium-emphasis mb-1">Description longue</div>
            <div class="text-body-2 mb-3" style="white-space: pre-line;">
              {{ selectedCode.long_description }}
            </div>
          </template>

          <VRow>
            <VCol cols="12" md="6">
              <div class="text-body-2 text-medium-emphasis mb-1">Unité statistique</div>
              <div class="text-body-1">
                {{ selectedCode.statistical_unit || '–' }}
              </div>
            </VCol>
            <VCol cols="12" md="6">
              <div class="text-body-2 text-medium-emphasis mb-1">Statut</div>
              <VChip
                :color="selectedCode.is_active ? 'success' : 'default'"
                size="small"
              >
                {{ selectedCode.is_active ? 'Actif' : 'Inactif' }}
              </VChip>
            </VCol>
          </VRow>

          <template v-if="selectedCode.notes">
            <VDivider class="my-3" />
            <div class="text-body-2 text-medium-emphasis mb-1">Notes</div>
            <div class="text-body-2">{{ selectedCode.notes }}</div>
          </template>

          <VDivider class="my-3" />
          <div class="text-body-2 text-medium-emphasis mb-2">Hiérarchie</div>
          <VBreadcrumbs
            :items="[
              { title: 'Toutes les sections' },
              ...breadcrumbStack.map(c => ({ title: c.label })),
              ...(selectedCode.category ? [{ title: selectedCode.category.label }] : []),
            ]"
            density="compact"
            class="pa-0"
          >
            <template #divider>
              <VIcon icon="tabler-chevron-right" size="14" />
            </template>
          </VBreadcrumbs>

          <VDivider class="my-3" />
          <div class="text-body-2 text-medium-emphasis mb-2">Taux applicables</div>
          <VAlert
            v-if="!selectedCode.tariff_rates?.length"
            type="info"
            variant="tonal"
            density="compact"
          >
            Les taux par code SH sont en cours d'intégration.
          </VAlert>
          <VTable v-else density="compact">
            <thead>
              <tr>
                <th>Type</th>
                <th class="text-right">Taux</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="rate in selectedCode.tariff_rates" :key="rate.id">
                <td>{{ rate.label ?? rate.type ?? '–' }}</td>
                <td class="text-right">{{ rate.rate ?? '–' }}%</td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="codeDialog = false">
            Fermer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.browse-card:hover,
.legacy-card:hover {
  transform: translateY(-2px);
}
.hs-code-mono {
  font-family: var(--mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace);
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.02em;
}
.min-w-0 {
  min-inline-size: 0;
}
</style>
