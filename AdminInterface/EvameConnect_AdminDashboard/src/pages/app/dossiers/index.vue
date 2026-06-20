<script setup>
import { useRouter } from 'vue-router'
import { getEcho } from '@/utils/echo'

definePage({ meta: { layout: 'default' } })

const router = useRouter()

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

const TYPE_OPTIONS = [
  { title: 'Tous types', value: '' },
  { title: 'Véhicule', value: 'vehicle' },
  { title: 'Marchandises', value: 'goods' },
]

const STATUS_OPTIONS = [
  { title: 'Tous statuts', value: '' },
  { title: 'Brouillon', value: 'draft' },
  { title: 'En préparation', value: 'preparing' },
  { title: 'En transit', value: 'in_transit' },
  { title: 'En douane', value: 'at_customs' },
  { title: 'Livré', value: 'delivered' },
  { title: 'Archivé', value: 'archived' },
  { title: 'Annulé', value: 'cancelled' },
]

const CURRENCY_OPTIONS = [
  { title: 'XOF (FCFA)', value: 'XOF' },
  { title: 'USD ($)', value: 'USD' },
  { title: 'EUR (€)', value: 'EUR' },
]

const search = ref('')
const filterStatus = ref('')
const filterType = ref('')
const filterMode = ref('')
const filterOnlyActive = ref(true)
const page = ref(1)
const perPage = ref(25)

const { data: modesData } = await useApi('/transport-modes?only_active=1&per_page=200')
const { data: carriersData } = await useApi('/carriers?only_active=1&per_page=200')
const { data: portsData } = await useApi('/ports?only_active=1&per_page=200')
const { data: usersData } = await useApi('/users?per_page=200')

const modeOptions = computed(() => modesData.value?.data?.map(m => ({ title: m.label, value: m.id, icon: m.icon })) ?? [])
const carrierOptions = computed(() => carriersData.value?.data ?? [])
const portOptions = computed(() => portsData.value?.data?.map(p => ({ title: p.name, value: p.id })) ?? [])
const userOptions = computed(() => usersData.value?.data?.map(u => ({
  title: `${u.first_name ?? ''} ${u.last_name ?? ''}`.trim() || u.name || u.email,
  value: u.id,
})) ?? [])

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value, with_stats: 1 })
  if (search.value) params.append('search', search.value)
  if (filterStatus.value) params.append('status', filterStatus.value)
  if (filterType.value) params.append('type', filterType.value)
  if (filterMode.value) params.append('mode_id', filterMode.value)
  if (filterOnlyActive.value) params.append('only_active', 1)
  return params.toString()
})

const { data: dossiersData, execute: refresh, isFetching } = useApi(
  computed(() => `/dossiers?${queryParams.value}`),
)

// Live refresh on dossier events from admin-feed
let _refreshTimer = null
const queueRefresh = () => {
  if (_refreshTimer) return
  _refreshTimer = setTimeout(() => { _refreshTimer = null; refresh() }, 600)
}

onMounted(() => {
  try {
    getEcho().private('admin-feed')
      .listen('.dossier.created', queueRefresh)
      .listen('.dossier.status_changed', queueRefresh)
      .listen('.dossier.updated', queueRefresh)
      .listen('.dossier.deleted', queueRefresh)
  }
  catch { /* Pusher not configured */ }
})

onUnmounted(() => {
  if (_refreshTimer) clearTimeout(_refreshTimer)
  try { getEcho()?.leave('admin-feed') }
  catch { /* noop */ }
})

const dossiers = computed(() => dossiersData.value?.data ?? [])
const total = computed(() => dossiersData.value?.meta?.total ?? dossiersData.value?.total ?? 0)
const lastPage = computed(() => dossiersData.value?.meta?.last_page ?? dossiersData.value?.last_page ?? 1)

const headers = [
  { title: 'Référence', key: 'reference' },
  { title: 'Titre', key: 'title' },
  { title: 'Client', key: 'client' },
  { title: 'Type', key: 'type' },
  { title: 'Mode', key: 'mode' },
  { title: 'Statut', key: 'status' },
  { title: 'ETA', key: 'estimated_arrival' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '–'
const clientName = c => {
  if (!c) return '–'
  return `${c.first_name ?? ''} ${c.last_name ?? ''}`.trim() || c.name || c.email || '–'
}

// ── Create dialog ─────────────────────────────────────────────────────────
const dialog = ref(false)
const dialogDelete = ref(false)
const selectedItem = ref(null)
const formRef = ref()
const saving = ref(false)
const errorMsg = ref('')

const defaultForm = () => ({
  title: '',
  client_id: null,
  type: 'goods',
  transport_mode_id: null,
  carrier_id: null,
  origin_port_id: null,
  destination_port_id: null,
  estimated_departure: '',
  estimated_arrival: '',
  total_estimated_cost: null,
  currency: 'XOF',
  notes: '',
  items: [],
})
const form = ref(defaultForm())

const openCreate = () => {
  form.value = defaultForm()
  errorMsg.value = ''
  dialog.value = true
}

const openDelete = item => {
  selectedItem.value = item
  dialogDelete.value = true
}

// Carriers filtered by selected transport mode
const filteredCarrierOptions = computed(() => {
  const list = carrierOptions.value
  if (!form.value.transport_mode_id) {
    return list.map(c => ({ title: c.name, value: c.id }))
  }
  return list
    .filter(c => (c.transport_mode?.id ?? c.transport_mode_id) === form.value.transport_mode_id)
    .map(c => ({ title: c.name, value: c.id }))
})

// Reset carrier when mode changes if not compatible
watch(() => form.value.transport_mode_id, () => {
  if (!form.value.carrier_id) return
  const ok = filteredCarrierOptions.value.some(c => c.value === form.value.carrier_id)
  if (!ok) form.value.carrier_id = null
})

// ── Products autocomplete (debounced search) ─────────────────────────────
const productSearch = ref('')
const productResults = ref([])
const isSearchingProducts = ref(false)
let productSearchTimer = null

watch(productSearch, val => {
  clearTimeout(productSearchTimer)
  if (!val || val.length < 2) {
    productResults.value = []
    return
  }
  isSearchingProducts.value = true
  productSearchTimer = setTimeout(async () => {
    try {
      const res = await $api(`/products?search=${encodeURIComponent(val)}&per_page=20`)
      const list = res?.data ?? res ?? []
      productResults.value = list.map(p => ({
        title: [p.brand?.name, p.model?.name, p.name].filter(Boolean).join(' ') || p.name,
        value: p.id,
      }))
    }
    catch {
      productResults.value = []
    }
    finally {
      isSearchingProducts.value = false
    }
  }, 300)
})

const addItem = () => {
  form.value.items.push({ product_id: null, quantity: 1, unit_estimated_cost: null, notes: '' })
}
const removeItem = idx => {
  form.value.items.splice(idx, 1)
}

const save = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  errorMsg.value = ''
  try {
    const body = {
      title: form.value.title,
      client_id: form.value.client_id,
      type: form.value.type,
      transport_mode_id: form.value.transport_mode_id,
      items: form.value.items.filter(i => i.product_id),
    }
    if (form.value.carrier_id) body.carrier_id = form.value.carrier_id
    if (form.value.origin_port_id) body.origin_port_id = form.value.origin_port_id
    if (form.value.destination_port_id) body.destination_port_id = form.value.destination_port_id
    if (form.value.estimated_departure) body.estimated_departure = form.value.estimated_departure
    if (form.value.estimated_arrival) body.estimated_arrival = form.value.estimated_arrival
    if (form.value.total_estimated_cost) body.total_estimated_cost = form.value.total_estimated_cost
    if (form.value.currency) body.currency = form.value.currency
    if (form.value.notes) body.notes = form.value.notes

    await $api('/dossiers', { method: 'POST', body })
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
    await $api(`/dossiers/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

const resetFilters = () => {
  search.value = ''
  filterStatus.value = ''
  filterType.value = ''
  filterMode.value = ''
  filterOnlyActive.value = true
  page.value = 1
}

watch([search, filterStatus, filterType, filterMode, filterOnlyActive, perPage], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Dossiers</span>
        <VBtn
          prepend-icon="tabler-plus"
          color="primary"
          @click="openCreate"
        >
          Ajouter
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VRow class="mb-2">
          <VCol cols="12" sm="6" md="3">
            <AppTextField
              v-model="search"
              placeholder="Rechercher (référence, titre)..."
              prepend-inner-icon="tabler-search"
              density="compact"
              clearable
            />
          </VCol>
          <VCol cols="12" sm="4" md="2">
            <AppSelect
              v-model="filterStatus"
              :items="STATUS_OPTIONS"
              density="compact"
              placeholder="Statut"
            />
          </VCol>
          <VCol cols="12" sm="4" md="2">
            <AppSelect
              v-model="filterType"
              :items="TYPE_OPTIONS"
              density="compact"
              placeholder="Type"
            />
          </VCol>
          <VCol cols="12" sm="4" md="2">
            <AppSelect
              v-model="filterMode"
              :items="[{ title: 'Tous modes', value: '' }, ...modeOptions]"
              density="compact"
              placeholder="Mode"
            />
          </VCol>
          <VCol cols="12" sm="4" md="2" class="d-flex align-center">
            <VSwitch
              v-model="filterOnlyActive"
              label="Actifs uniquement"
              color="primary"
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="12" md="1" class="d-flex align-center">
            <VBtn
              variant="tonal"
              icon="tabler-x"
              size="small"
              title="Réinitialiser"
              @click="resetFilters"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="dossiers"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
        <template #item.reference="{ item }">
          <RouterLink
            :to="`/dossiers/${item.id}`"
            class="text-primary font-weight-medium"
            style="text-decoration: none"
          >
            {{ item.reference }}
          </RouterLink>
        </template>

        <template #item.title="{ item }">
          {{ item.title }}
        </template>

        <template #item.client="{ item }">
          {{ clientName(item.client) }}
        </template>

        <template #item.type="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="item.type === 'vehicle' ? 'info' : 'primary'"
          >
            <VIcon
              start
              :icon="item.type === 'vehicle' ? 'tabler-car' : 'tabler-package'"
              size="14"
            />
            {{ item.type === 'vehicle' ? 'Véhicule' : 'Marchandises' }}
          </VChip>
        </template>

        <template #item.mode="{ item }">
          <VChip
            v-if="item.transport_mode"
            size="small"
            variant="tonal"
            color="primary"
          >
            <VIcon
              v-if="item.transport_mode.icon"
              start
              :icon="item.transport_mode.icon"
              size="14"
            />
            {{ item.transport_mode.label }}
          </VChip>
          <span v-else class="text-medium-emphasis">–</span>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="STATUS_COLORS[item.status] ?? 'default'"
            size="small"
          >
            {{ STATUS_LABELS[item.status] ?? item.status }}
          </VChip>
        </template>

        <template #item.estimated_arrival="{ item }">
          {{ formatDate(item.estimated_arrival) }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <VBtn
              icon
              size="small"
              variant="text"
              :to="`/dossiers/${item.id}`"
            >
              <VIcon size="18" icon="tabler-eye" />
            </VBtn>
            <VBtn
              icon
              size="small"
              variant="text"
              color="error"
              @click="openDelete(item)"
            >
              <VIcon size="18" icon="tabler-trash" />
            </VBtn>
          </div>
        </template>
      </VDataTable>

      <VCardText class="d-flex align-center justify-space-between flex-wrap gap-2 py-3">
        <div class="d-flex align-center gap-3 flex-wrap">
          <span class="text-body-2 text-medium-emphasis">{{ total }} dossier(s) au total</span>
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

    <!-- Create dialog -->
    <VDialog v-model="dialog" max-width="800" scrollable>
      <VCard title="Nouveau dossier">
        <VCardText style="max-block-size: 75vh;">
          <VForm ref="formRef">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="form.title"
                  label="Titre du dossier"
                  :rules="[requiredValidator]"
                  placeholder="Importation Toyota Corolla 2020"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.client_id"
                  :items="userOptions"
                  label="Client"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.type"
                  :items="[
                    { title: 'Véhicule', value: 'vehicle' },
                    { title: 'Marchandises', value: 'goods' },
                  ]"
                  label="Type"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.transport_mode_id"
                  :items="modeOptions"
                  label="Mode de transport"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.carrier_id"
                  :items="[{ title: '–', value: null }, ...filteredCarrierOptions]"
                  label="Transporteur (optionnel)"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.origin_port_id"
                  :items="[{ title: '–', value: null }, ...portOptions]"
                  label="Port d'origine"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.destination_port_id"
                  :items="[{ title: '–', value: null }, ...portOptions]"
                  label="Port de destination"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.estimated_departure"
                  type="date"
                  label="Départ estimé"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.estimated_arrival"
                  type="date"
                  label="Arrivée estimée"
                />
              </VCol>

              <VCol cols="12" md="8">
                <AppTextField
                  v-model.number="form.total_estimated_cost"
                  type="number"
                  label="Coût total estimé"
                  placeholder="0"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppSelect
                  v-model="form.currency"
                  :items="CURRENCY_OPTIONS"
                  label="Devise"
                />
              </VCol>

              <VCol cols="12">
                <AppTextarea
                  v-model="form.notes"
                  label="Notes"
                  rows="3"
                  placeholder="Informations complémentaires..."
                />
              </VCol>

              <VCol cols="12">
                <div class="d-flex align-center justify-space-between mb-2">
                  <span class="text-h6">Produits</span>
                  <VBtn
                    size="small"
                    variant="tonal"
                    color="primary"
                    prepend-icon="tabler-plus"
                    @click="addItem"
                  >
                    Ajouter un produit
                  </VBtn>
                </div>

                <VAlert
                  v-if="!form.items.length"
                  type="info"
                  variant="tonal"
                  density="compact"
                >
                  Aucun produit ajouté. Cliquez sur "Ajouter un produit" pour en ajouter.
                </VAlert>

                <div
                  v-for="(it, idx) in form.items"
                  :key="idx"
                  class="d-flex align-start gap-2 mb-2"
                >
                  <div style="flex: 2;">
                    <VAutocomplete
                      v-model="it.product_id"
                      v-model:search="productSearch"
                      :items="productResults"
                      :loading="isSearchingProducts"
                      item-title="title"
                      item-value="value"
                      no-filter
                      label="Produit"
                      density="compact"
                      placeholder="Rechercher..."
                      no-data-text="Tapez au moins 2 caractères"
                    />
                  </div>
                  <div style="flex: 1;">
                    <AppTextField
                      v-model.number="it.quantity"
                      type="number"
                      label="Qté"
                      min="1"
                      density="compact"
                    />
                  </div>
                  <div style="flex: 1;">
                    <AppTextField
                      v-model.number="it.unit_estimated_cost"
                      type="number"
                      label="Coût unitaire"
                      density="compact"
                    />
                  </div>
                  <VBtn
                    icon
                    size="small"
                    variant="text"
                    color="error"
                    class="mt-1"
                    @click="removeItem(idx)"
                  >
                    <VIcon size="18" icon="tabler-trash" />
                  </VBtn>
                </div>
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
          <VBtn color="primary" :loading="saving" @click="save">
            Créer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Delete dialog -->
    <VDialog v-model="dialogDelete" max-width="400">
      <VCard title="Supprimer le dossier">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer <strong>{{ selectedItem?.reference }} – {{ selectedItem?.title }}</strong> ?
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
  </div>
</template>
