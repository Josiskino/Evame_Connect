<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const filterMode = ref('')
const filterType = ref('')
const onlyActive = ref(false)
const page = ref(1)
const perPage = ref(25)

const { data: modesData } = await useApi('/transport-modes?only_active=1&per_page=200')
const modeOptions = computed(() => modesData.value?.data?.map(m => ({ title: m.label, value: m.id, icon: m.icon })) ?? [])

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value })
  if (search.value) params.append('search', search.value)
  if (filterMode.value) params.append('transport_mode_id', filterMode.value)
  if (filterType.value) params.append('type', filterType.value)
  if (onlyActive.value) params.append('only_active', '1')
  return params.toString()
})

const { data: stagesData, execute: refresh, isFetching } = useApi(
  computed(() => `/stage-templates?${queryParams.value}`),
)

const rawStages = computed(() => stagesData.value?.data ?? [])
const stages = computed(() => {
  return [...rawStages.value].sort((a, b) => {
    const ma = a.transport_mode_id ?? a.transport_mode?.id ?? 0
    const mb = b.transport_mode_id ?? b.transport_mode?.id ?? 0
    if (ma !== mb) return ma - mb
    return (a.sort_order ?? 0) - (b.sort_order ?? 0)
  })
})
const total = computed(() => stagesData.value?.meta?.total ?? stagesData.value?.total ?? 0)
const lastPage = computed(() => stagesData.value?.meta?.last_page ?? stagesData.value?.last_page ?? 1)

const typeOptions = [
  { title: 'Tous', value: '' },
  { title: 'Véhicule', value: 'vehicle' },
  { title: 'Marchandise', value: 'goods' },
  { title: 'Les deux', value: 'both' },
]

const typeFormOptions = [
  { title: 'Véhicule', value: 'vehicle' },
  { title: 'Marchandise', value: 'goods' },
  { title: 'Les deux', value: 'both' },
]

const typeMeta = {
  vehicle: { color: 'info', label: 'Véhicule' },
  goods: { color: 'success', label: 'Marchandise' },
  both: { color: 'primary', label: 'Les deux' },
}

const headers = [
  { title: 'Nom', key: 'name' },
  { title: 'Mode de transport', key: 'transport_mode' },
  { title: 'Type', key: 'type' },
  { title: 'Ordre', key: 'sort_order' },
  { title: 'Durée par défaut', key: 'default_duration_days' },
  { title: 'Visible client', key: 'visible_to_client_default' },
  { title: 'Actif', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const defaultForm = () => ({
  transport_mode_id: null,
  type: 'both',
  sort_order: 10,
  name: '',
  description: '',
  default_duration_days: null,
  visible_to_client_default: true,
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
    type: item.type ?? 'both',
    sort_order: item.sort_order ?? 10,
    name: item.name,
    description: item.description ?? '',
    default_duration_days: item.default_duration_days ?? null,
    visible_to_client_default: !!item.visible_to_client_default,
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
    const payload = {
      ...form.value,
      default_duration_days: form.value.default_duration_days === '' ? null : form.value.default_duration_days,
    }
    if (editMode.value) {
      await $api(`/stage-templates/${selectedItem.value.id}`, { method: 'PATCH', body: payload })
    }
    else {
      await $api('/stage-templates', { method: 'POST', body: payload })
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
    await $api(`/stage-templates/${selectedItem.value.id}`, { method: 'DELETE' })
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
  filterType.value = ''
  onlyActive.value = false
  page.value = 1
}

watch([search, filterMode, filterType, onlyActive, perPage], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Étapes types</span>
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
              v-model="filterType"
              :items="typeOptions"
              density="compact"
              placeholder="Type"
            />
          </VCol>
          <VCol cols="12" sm="4" md="2">
            <VSwitch
              v-model="onlyActive"
              label="Actifs uniquement"
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
        :items="stages"
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

        <template #item.type="{ item }">
          <VChip
            v-if="typeMeta[item.type]"
            size="small"
            variant="tonal"
            :color="typeMeta[item.type].color"
          >
            {{ typeMeta[item.type].label }}
          </VChip>
          <span
            v-else
            class="text-medium-emphasis"
          >–</span>
        </template>

        <template #item.default_duration_days="{ item }">
          <span v-if="item.default_duration_days != null">{{ item.default_duration_days }} jours</span>
          <span
            v-else
            class="text-medium-emphasis"
          >—</span>
        </template>

        <template #item.visible_to_client_default="{ item }">
          <VIcon
            v-if="item.visible_to_client_default"
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
          <span class="text-body-2 text-medium-emphasis">{{ total }} étape(s) au total</span>
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
      max-width="700"
    >
      <VCard :title="editMode ? 'Modifier l\'étape type' : 'Nouvelle étape type'">
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
                  v-model="form.type"
                  :items="typeFormOptions"
                  label="Type"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.name"
                  label="Nom"
                  :rules="[requiredValidator]"
                  :maxlength="160"
                  placeholder="Chargement au port"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.sort_order"
                  type="number"
                  label="Ordre"
                  hint="L'ordre détermine la position de l'étape dans le workflow"
                  persistent-hint
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Description"
                  rows="2"
                  auto-grow
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.default_duration_days"
                  type="number"
                  :min="0"
                  :max="365"
                  label="Durée par défaut"
                  suffix="jours"
                  placeholder="0"
                />
              </VCol>
              <VCol cols="12" md="6" />
              <VCol cols="12" md="6">
                <VSwitch
                  v-model="form.visible_to_client_default"
                  label="Visible par le client par défaut"
                  color="primary"
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
      <VCard title="Supprimer l'étape type">
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
