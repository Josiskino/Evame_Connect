<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const filterMode = ref('')
const filterConsolidator = ref('')
const page = ref(1)
const perPage = ref(25)

const { data: modesData } = await useApi('/transport-modes?only_active=1&per_page=200')
const { data: countriesData } = await useApi('/countries?per_page=200')

const modeOptions = computed(() => modesData.value?.data?.map(m => ({ title: m.label, value: m.id })) ?? [])
const countryOptions = computed(() => countriesData.value?.data?.map(c => ({ title: c.name, value: c.id })) ?? [])

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value })
  if (search.value) params.append('search', search.value)
  if (filterMode.value) params.append('transport_mode_id', filterMode.value)
  if (filterConsolidator.value !== '') params.append('is_consolidator', filterConsolidator.value)
  return params.toString()
})

const { data: carriersData, execute: refresh, isFetching } = useApi(
  computed(() => `/carriers?${queryParams.value}`),
)

const carriers = computed(() => carriersData.value?.data ?? [])
const total = computed(() => carriersData.value?.meta?.total ?? carriersData.value?.total ?? 0)
const lastPage = computed(() => carriersData.value?.meta?.last_page ?? carriersData.value?.last_page ?? 1)

const headers = [
  { title: 'Nom', key: 'name' },
  { title: 'Mode', key: 'transport_mode' },
  { title: 'Pays', key: 'country' },
  { title: 'Code', key: 'code' },
  { title: 'Consolidateur', key: 'is_consolidator' },
  { title: 'Statut', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const defaultForm = () => ({
  transport_mode_id: null,
  country_id: null,
  name: '',
  code: '',
  website: '',
  contact_email: '',
  phone: '',
  is_consolidator: false,
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
    transport_mode_id: item.transport_mode_id ?? item.transport_mode?.id ?? null,
    country_id: item.country_id ?? item.country?.id ?? null,
    name: item.name,
    code: item.code ?? '',
    website: item.website ?? '',
    contact_email: item.contact_email ?? '',
    phone: item.phone ?? '',
    is_consolidator: !!item.is_consolidator,
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
      await $api(`/carriers/${selectedItem.value.id}`, { method: 'PUT', body: form.value })
    }
    else {
      await $api('/carriers', { method: 'POST', body: form.value })
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
    await $api(`/carriers/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

const resetFilters = () => {
  search.value = ''
  filterMode.value = ''
  filterConsolidator.value = ''
  page.value = 1
}

watch([search, filterMode, filterConsolidator, perPage], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Transporteurs</span>
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
              placeholder="Rechercher..."
              prepend-inner-icon="tabler-search"
              density="compact"
              clearable
            />
          </VCol>
          <VCol cols="12" sm="4" md="3">
            <AppSelect
              v-model="filterMode"
              :items="[{ title: 'Tous modes', value: '' }, ...modeOptions]"
              density="compact"
              placeholder="Mode"
            />
          </VCol>
          <VCol cols="12" sm="4" md="3">
            <AppSelect
              v-model="filterConsolidator"
              :items="[
                { title: 'Tous', value: '' },
                { title: 'Consolidateurs', value: '1' },
                { title: 'Non consolidateurs', value: '0' },
              ]"
              density="compact"
              placeholder="Consolidateur"
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
        :items="carriers"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
        <template #item.transport_mode="{ item }">
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

        <template #item.country="{ item }">
          {{ item.country?.name ?? '–' }}
        </template>

        <template #item.code="{ item }">
          {{ item.code || '–' }}
        </template>

        <template #item.is_consolidator="{ item }">
          <VIcon
            v-if="item.is_consolidator"
            icon="tabler-check"
            color="success"
          />
          <span
            v-else
            class="text-medium-emphasis"
          >–</span>
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
          <span class="text-body-2 text-medium-emphasis">{{ total }} transporteur(s) au total</span>
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
      max-width="650"
    >
      <VCard :title="editMode ? 'Modifier le transporteur' : 'Nouveau transporteur'">
        <VCardText>
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
                  v-model="form.country_id"
                  :items="[{ title: '–', value: null }, ...countryOptions]"
                  label="Pays (optionnel)"
                />
              </VCol>
              <VCol cols="12" md="8">
                <AppTextField
                  v-model="form.name"
                  label="Nom"
                  :rules="[requiredValidator]"
                  placeholder="Maersk Line"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="form.code"
                  label="Code"
                  placeholder="MSK"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.website"
                  label="Site web"
                  :rules="[urlValidator]"
                  placeholder="https://example.com"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.contact_email"
                  label="Email de contact"
                  :rules="[emailValidator]"
                  placeholder="contact@example.com"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.phone"
                  label="Téléphone"
                />
              </VCol>
              <VCol cols="12" md="6">
                <VSwitch
                  v-model="form.is_consolidator"
                  label="Consolidateur"
                  color="primary"
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
      <VCard title="Supprimer le transporteur">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer <strong>{{ selectedItem?.name }}</strong> ?
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
