<script setup>
definePage({ meta: { layout: 'default' } })

const filterMode = ref('')
const filterOriginPort = ref('')
const filterDestPort = ref('')
const filterCarrier = ref('')
const validNow = ref(false)
const page = ref(1)
const perPage = ref(25)

// Fetch dependencies once at setup
const { data: modesData } = await useApi('/transport-modes?only_active=1&per_page=200')
const { data: portsData } = await useApi('/ports?per_page=200')
const { data: carriersData } = await useApi('/carriers?per_page=200&only_active=1')
const { data: containersData } = await useApi('/container-types?per_page=200&only_active=1')
const { data: trucksData } = await useApi('/truck-types?per_page=200&only_active=1')

const modes = computed(() => modesData.value?.data ?? [])
const modeOptions = computed(() => modes.value.map(m => ({ title: m.label, value: m.id, slug: m.slug, icon: m.icon })))
const portOptions = computed(() => portsData.value?.data?.map(p => ({ title: `${p.code} — ${p.name}`, value: p.id })) ?? [])
const allCarriers = computed(() => carriersData.value?.data ?? [])
const containerOptions = computed(() => containersData.value?.data?.map(c => ({ title: `${c.code} — ${c.label}`, value: c.id })) ?? [])
const truckOptions = computed(() => trucksData.value?.data?.map(t => ({ title: `${t.code} — ${t.label}`, value: t.id })) ?? [])

const lclPricingUnitOptions = [
  { title: 'CBM (m³)', value: 'cbm' },
  { title: 'Kilogramme', value: 'kg' },
]

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value })
  if (filterMode.value) params.append('mode_id', filterMode.value)
  if (filterOriginPort.value) params.append('origin_port_id', filterOriginPort.value)
  if (filterDestPort.value) params.append('destination_port_id', filterDestPort.value)
  if (filterCarrier.value) params.append('carrier_id', filterCarrier.value)
  if (validNow.value) params.append('valid_now', '1')
  return params.toString()
})

const { data: ratesData, execute: refresh, isFetching } = useApi(
  computed(() => `/transport-rates?${queryParams.value}`),
)

const rates = computed(() => ratesData.value?.data ?? [])
const total = computed(() => ratesData.value?.meta?.total ?? ratesData.value?.total ?? 0)
const lastPage = computed(() => ratesData.value?.meta?.last_page ?? ratesData.value?.last_page ?? 1)

const headers = [
  { title: 'Mode', key: 'mode' },
  { title: 'Origine', key: 'origin' },
  { title: 'Destination', key: 'destination' },
  { title: 'Transporteur', key: 'carrier' },
  { title: 'Type', key: 'type_detail' },
  { title: 'Prix TTC', key: 'price_ttc' },
  { title: 'Transit', key: 'transit_days' },
  { title: 'Validité', key: 'validity' },
  { title: 'Statut', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const defaultForm = () => ({
  transport_mode_id: null,
  origin_port_id: null,
  destination_port_id: null,
  carrier_id: null,
  container_type_id: null,
  truck_type_id: null,
  lcl_pricing_unit: null,
  price_ht: null,
  price_ttc: null,
  currency: 'XOF',
  transit_days: null,
  valid_from: '',
  valid_until: '',
  notes: '',
  is_active: true,
})
const form = ref(defaultForm())
const formRef = ref()
const saving = ref(false)
const errorMsg = ref('')

const currentMode = computed(() => modes.value.find(m => m.id === form.value.transport_mode_id))
const currentModeSlug = computed(() => currentMode.value?.slug ?? null)

// Carriers filtered by mode
const formCarrierOptions = computed(() => {
  if (!form.value.transport_mode_id) return []
  return allCarriers.value
    .filter(c => (c.transport_mode_id ?? c.transport_mode?.id) === form.value.transport_mode_id)
    .map(c => ({ title: c.name, value: c.id }))
})

// Watch transport_mode_id to clear conditional fields when mode changes
watch(() => form.value.transport_mode_id, (newVal, oldVal) => {
  if (newVal === oldVal) return
  // Reset carrier (mode-specific list)
  form.value.carrier_id = null
  // Reset all mode-conditional fields
  form.value.container_type_id = null
  form.value.truck_type_id = null
  form.value.lcl_pricing_unit = null
})

const destPortRule = v => {
  if (!v) return 'This field is required'
  if (form.value.origin_port_id && v === form.value.origin_port_id) return 'La destination doit être différente de l\'origine'
  return true
}

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
    transport_mode_id: item.transport_mode_id ?? item.transport_mode?.id ?? null,
    origin_port_id: item.origin_port_id ?? item.origin_port?.id ?? null,
    destination_port_id: item.destination_port_id ?? item.destination_port?.id ?? null,
    carrier_id: item.carrier_id ?? item.carrier?.id ?? null,
    container_type_id: item.container_type_id ?? item.container_type?.id ?? null,
    truck_type_id: item.truck_type_id ?? item.truck_type?.id ?? null,
    lcl_pricing_unit: item.lcl_pricing_unit ?? null,
    price_ht: item.price_ht ?? null,
    price_ttc: item.price_ttc ?? null,
    currency: item.currency ?? 'XOF',
    transit_days: item.transit_days ?? null,
    valid_from: item.valid_from ?? '',
    valid_until: item.valid_until ?? '',
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
      await $api(`/transport-rates/${selectedItem.value.id}`, { method: 'PUT', body: form.value })
    }
    else {
      await $api('/transport-rates', { method: 'POST', body: form.value })
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
    await $api(`/transport-rates/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

const resetFilters = () => {
  filterMode.value = ''
  filterOriginPort.value = ''
  filterDestPort.value = ''
  filterCarrier.value = ''
  validNow.value = false
  page.value = 1
}

const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '–'
const formatPrice = (val, currency) => {
  if (val === null || val === undefined || val === '') return '–'
  return `${Number(val).toLocaleString('fr-FR')} ${currency ?? 'XOF'}`
}

const validityRange = item => {
  const from = item.valid_from ? formatDate(item.valid_from) : null
  const until = item.valid_until ? formatDate(item.valid_until) : null
  if (!from && !until) return '–'
  if (from && until) return `${from} → ${until}`
  if (from) return `dès ${from}`
  return `jusqu'au ${until}`
}

const typeDetail = item => {
  if (item.container_type) return item.container_type.code
  if (item.truck_type) return item.truck_type.code
  if (item.lcl_pricing_unit) return `LCL · ${item.lcl_pricing_unit.toUpperCase()}`
  return '–'
}

watch([filterMode, filterOriginPort, filterDestPort, filterCarrier, validNow, perPage], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Tarifs de transport</span>
        <VBtn
          prepend-icon="tabler-plus"
          color="primary"
          @click="openCreate"
        >
          Ajouter
        </VBtn>
      </VCardTitle>

      <VAlert
        type="info"
        variant="tonal"
        class="ma-4"
      >
        Les champs requis dépendent du mode : <strong>Maritime</strong> exige un type de conteneur,
        <strong>Routier</strong> exige un type de camion, <strong>LCL</strong> exige une unité de tarification (CBM ou KG).
      </VAlert>

      <VDivider />

      <VCardText>
        <VRow class="mb-2">
          <VCol cols="12" sm="6" md="2">
            <AppSelect
              v-model="filterMode"
              :items="[{ title: 'Tous modes', value: '' }, ...modeOptions]"
              density="compact"
              placeholder="Mode"
            />
          </VCol>
          <VCol cols="12" sm="6" md="3">
            <AppSelect
              v-model="filterOriginPort"
              :items="[{ title: 'Toute origine', value: '' }, ...portOptions]"
              density="compact"
              placeholder="Origine"
            />
          </VCol>
          <VCol cols="12" sm="6" md="3">
            <AppSelect
              v-model="filterDestPort"
              :items="[{ title: 'Toute destination', value: '' }, ...portOptions]"
              density="compact"
              placeholder="Destination"
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <AppSelect
              v-model="filterCarrier"
              :items="[{ title: 'Tous transporteurs', value: '' }, ...allCarriers.map(c => ({ title: c.name, value: c.id }))]"
              density="compact"
              placeholder="Transporteur"
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <VSwitch
              v-model="validNow"
              label="Valides maintenant"
              color="primary"
              density="compact"
              hide-details
            />
          </VCol>
          <VCol cols="12" md="1">
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
        :items="rates"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
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
          <span
            v-else
            class="text-medium-emphasis"
          >–</span>
        </template>

        <template #item.origin="{ item }">
          {{ item.origin_port?.code ?? '–' }}
        </template>

        <template #item.destination="{ item }">
          {{ item.destination_port?.code ?? '–' }}
        </template>

        <template #item.carrier="{ item }">
          {{ item.carrier?.name ?? '—' }}
        </template>

        <template #item.type_detail="{ item }">
          {{ typeDetail(item) }}
        </template>

        <template #item.price_ttc="{ item }">
          {{ formatPrice(item.price_ttc, item.currency) }}
        </template>

        <template #item.transit_days="{ item }">
          {{ item.transit_days ? `${item.transit_days} j` : '–' }}
        </template>

        <template #item.validity="{ item }">
          {{ validityRange(item) }}
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
          <div class="d-flex gap-1">
            <VBtn
              icon
              size="small"
              variant="text"
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

      <VCardText class="d-flex align-center justify-space-between flex-wrap gap-2 py-3">
        <div class="d-flex align-center gap-3 flex-wrap">
          <span class="text-body-2 text-medium-emphasis">{{ total }} tarif(s) au total</span>
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

    <VDialog
      v-model="dialog"
      max-width="900"
      scrollable
    >
      <VCard :title="editMode ? 'Modifier le tarif' : 'Nouveau tarif de transport'">
        <VCardText style="max-block-size: 70vh;">
          <VForm ref="formRef">
            <VRow>
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
                  :items="[{ title: '— Aucun —', value: null }, ...formCarrierOptions]"
                  label="Transporteur"
                  :disabled="!form.transport_mode_id"
                  :hint="!form.transport_mode_id ? 'Choisir d\'abord un mode' : ''"
                  persistent-hint
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.origin_port_id"
                  :items="portOptions"
                  label="Port d'origine"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.destination_port_id"
                  :items="portOptions"
                  label="Port de destination"
                  :rules="[destPortRule]"
                />
              </VCol>

              <!-- Mode-specific fields -->
              <VCol
                v-if="currentModeSlug === 'maritime'"
                cols="12"
              >
                <AppSelect
                  v-model="form.container_type_id"
                  :items="containerOptions"
                  label="Type de conteneur"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol
                v-if="currentModeSlug === 'ground'"
                cols="12"
              >
                <AppSelect
                  v-model="form.truck_type_id"
                  :items="truckOptions"
                  label="Type de camion"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol
                v-if="currentModeSlug === 'lcl'"
                cols="12"
              >
                <AppSelect
                  v-model="form.lcl_pricing_unit"
                  :items="lclPricingUnitOptions"
                  label="Unité de tarification LCL"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <VCol cols="12">
                <VDivider class="mb-2" />
                <div class="text-subtitle-2 mb-2">
                  Tarification
                </div>
              </VCol>

              <VCol cols="12" md="4">
                <AppTextField
                  v-model.number="form.price_ht"
                  type="number"
                  step="0.01"
                  label="Prix HT"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model.number="form.price_ttc"
                  type="number"
                  step="0.01"
                  label="Prix TTC"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="form.currency"
                  label="Devise"
                  placeholder="XOF"
                />
              </VCol>

              <VCol cols="12" md="4">
                <AppTextField
                  v-model.number="form.transit_days"
                  type="number"
                  label="Jours de transit"
                  suffix="j"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="form.valid_from"
                  type="date"
                  label="Valide à partir du"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="form.valid_until"
                  type="date"
                  label="Valide jusqu'au"
                />
              </VCol>

              <VCol cols="12">
                <AppTextarea
                  v-model="form.notes"
                  label="Notes"
                  rows="2"
                />
              </VCol>
              <VCol cols="12">
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

    <VDialog
      v-model="dialogDelete"
      max-width="400"
    >
      <VCard title="Supprimer le tarif">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer ce tarif
          <strong v-if="selectedItem">
            {{ selectedItem.origin_port?.code }} → {{ selectedItem.destination_port?.code }}
          </strong>
          ?
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
  </div>
</template>
