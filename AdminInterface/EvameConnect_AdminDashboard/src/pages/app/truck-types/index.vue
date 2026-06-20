<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const page = ref(1)
const perPage = ref(25)

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value })
  if (search.value) params.append('search', search.value)
  return params.toString()
})

const { data: trucksData, execute: refresh, isFetching } = useApi(
  computed(() => `/truck-types?${queryParams.value}`),
)

const trucks = computed(() => trucksData.value?.data ?? [])
const total = computed(() => trucksData.value?.meta?.total ?? trucksData.value?.total ?? 0)
const lastPage = computed(() => trucksData.value?.meta?.last_page ?? trucksData.value?.last_page ?? 1)

const headers = [
  { title: 'Libellé', key: 'label' },
  { title: 'Code', key: 'code' },
  { title: 'Charge max', key: 'max_payload_kg' },
  { title: 'Volume', key: 'volume_m3' },
  { title: 'Essieux', key: 'axles_count' },
  { title: 'Réfrigéré', key: 'is_refrigerated' },
  { title: 'Statut', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const defaultForm = () => ({
  code: '',
  label: '',
  description: '',
  max_payload_kg: null,
  volume_m3: null,
  axles_count: null,
  is_refrigerated: false,
  is_active: true,
  sort_order: 0,
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
    code: item.code,
    label: item.label,
    description: item.description ?? '',
    max_payload_kg: item.max_payload_kg ?? null,
    volume_m3: item.volume_m3 ?? null,
    axles_count: item.axles_count ?? null,
    is_refrigerated: !!item.is_refrigerated,
    is_active: !!item.is_active,
    sort_order: item.sort_order ?? 0,
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
      await $api(`/truck-types/${selectedItem.value.id}`, { method: 'PUT', body: form.value })
    }
    else {
      await $api('/truck-types', { method: 'POST', body: form.value })
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
    await $api(`/truck-types/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

watch([search, perPage], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Types de camions</span>
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
        <AppTextField
          v-model="search"
          placeholder="Rechercher un camion..."
          prepend-inner-icon="tabler-search"
          density="compact"
          clearable
          style="max-width: 300px"
        />
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="trucks"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
        <template #item.max_payload_kg="{ item }">
          {{ item.max_payload_kg ? `${Number(item.max_payload_kg).toLocaleString('fr-FR')} kg` : '–' }}
        </template>

        <template #item.volume_m3="{ item }">
          {{ item.volume_m3 ? `${item.volume_m3} m³` : '–' }}
        </template>

        <template #item.axles_count="{ item }">
          {{ item.axles_count ?? '–' }}
        </template>

        <template #item.is_refrigerated="{ item }">
          <VChip
            v-if="item.is_refrigerated"
            size="x-small"
            color="info"
            variant="tonal"
          >
            Reefer
          </VChip>
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
          <span class="text-body-2 text-medium-emphasis">{{ total }} camion(s) au total</span>
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
      <VCard :title="editMode ? 'Modifier le camion' : 'Nouveau type de camion'">
        <VCardText>
          <VForm ref="formRef">
            <VRow>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="form.code"
                  label="Code"
                  :rules="[requiredValidator]"
                  placeholder="SEMI"
                />
              </VCol>
              <VCol cols="12" md="8">
                <AppTextField
                  v-model="form.label"
                  label="Libellé"
                  :rules="[requiredValidator]"
                  placeholder="Semi-remorque"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="form.description"
                  label="Description"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model.number="form.max_payload_kg"
                  type="number"
                  label="Charge max (kg)"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model.number="form.volume_m3"
                  type="number"
                  step="0.1"
                  label="Volume (m³)"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model.number="form.axles_count"
                  type="number"
                  min="1"
                  max="10"
                  label="Nombre d'essieux"
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
                  v-model="form.is_refrigerated"
                  label="Réfrigéré"
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
      <VCard title="Supprimer le camion">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer <strong>{{ selectedItem?.label }}</strong> ?
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
