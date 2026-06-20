<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const filterType = ref('')
const filterCountry = ref('')
const page = ref(1)
const perPage = ref(25)

const { data: countriesData } = await useApi('/countries?per_page=200')
const countryOptions = computed(() => countriesData.value?.data?.map(c => ({ title: c.name, value: c.id })) ?? [])

const typeOptions = [
  { title: 'Maritime', value: 'sea' },
  { title: 'Aérien', value: 'air' },
  { title: 'Port sec', value: 'dry' },
]

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value })
  if (search.value) params.append('search', search.value)
  if (filterType.value) params.append('type', filterType.value)
  if (filterCountry.value) params.append('country_id', filterCountry.value)
  return params.toString()
})

const { data: portsData, execute: refresh, isFetching } = useApi(
  computed(() => `/ports?${queryParams.value}`),
)

const ports = computed(() => portsData.value?.data ?? [])
const total = computed(() => portsData.value?.meta?.total ?? portsData.value?.total ?? 0)
const lastPage = computed(() => portsData.value?.meta?.last_page ?? portsData.value?.last_page ?? 1)

const headers = [
  { title: 'Code', key: 'code' },
  { title: 'Nom', key: 'name' },
  { title: 'Ville', key: 'city' },
  { title: 'Type', key: 'type' },
  { title: 'Pays', key: 'country' },
  { title: 'Statut', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const defaultForm = () => ({
  country_id: null,
  code: '',
  name: '',
  city: '',
  type: 'sea',
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
    country_id: item.country_id ?? item.country?.id ?? null,
    code: item.code,
    name: item.name,
    city: item.city ?? '',
    type: item.type,
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
      await $api(`/ports/${selectedItem.value.id}`, { method: 'PUT', body: form.value })
    }
    else {
      await $api('/ports', { method: 'POST', body: form.value })
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
    await $api(`/ports/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

const resetFilters = () => {
  search.value = ''
  filterType.value = ''
  filterCountry.value = ''
  page.value = 1
}

const typeMeta = type => {
  if (type === 'sea') return { label: 'Maritime', icon: 'tabler-ship', color: 'info' }
  if (type === 'air') return { label: 'Aérien', icon: 'tabler-plane', color: 'warning' }
  if (type === 'dry') return { label: 'Port sec', icon: 'tabler-building-warehouse', color: 'secondary' }
  return { label: type, icon: 'tabler-map-pin', color: 'default' }
}

watch([search, filterType, filterCountry, perPage], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Ports</span>
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
              v-model="filterType"
              :items="[{ title: 'Tous types', value: '' }, ...typeOptions]"
              density="compact"
              placeholder="Type"
            />
          </VCol>
          <VCol cols="12" sm="4" md="3">
            <AppSelect
              v-model="filterCountry"
              :items="[{ title: 'Tous pays', value: '' }, ...countryOptions]"
              density="compact"
              placeholder="Pays"
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
        :items="ports"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
        <template #item.city="{ item }">
          {{ item.city || '–' }}
        </template>

        <template #item.type="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="typeMeta(item.type).color"
          >
            <VIcon
              start
              :icon="typeMeta(item.type).icon"
              size="14"
            />
            {{ typeMeta(item.type).label }}
          </VChip>
        </template>

        <template #item.country="{ item }">
          {{ item.country?.name ?? '–' }}
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
          <span class="text-body-2 text-medium-emphasis">{{ total }} port(s) au total</span>
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
      max-width="600"
    >
      <VCard :title="editMode ? 'Modifier le port' : 'Nouveau port'">
        <VCardText>
          <VForm ref="formRef">
            <VRow>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.country_id"
                  :items="countryOptions"
                  label="Pays"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.type"
                  :items="typeOptions"
                  label="Type"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="form.code"
                  label="Code (UN/LOCODE)"
                  :rules="[requiredValidator]"
                  hint="UN/LOCODE: TGLOM"
                  persistent-hint
                />
              </VCol>
              <VCol cols="12" md="8">
                <AppTextField
                  v-model="form.name"
                  label="Nom"
                  :rules="[requiredValidator]"
                  placeholder="Port autonome de Lomé"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.city"
                  label="Ville"
                  placeholder="Lomé"
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
      <VCard title="Supprimer le port">
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
