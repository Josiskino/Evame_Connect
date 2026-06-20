<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const filterAppliesTo = ref('')
const onlyActive = ref(false)
const page = ref(1)
const perPage = ref(25)

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value })
  if (search.value) params.append('search', search.value)
  if (filterAppliesTo.value) params.append('applies_to', filterAppliesTo.value)
  if (onlyActive.value) params.append('only_active', '1')
  return params.toString()
})

const { data: docTypesData, execute: refresh, isFetching } = useApi(
  computed(() => `/document-types?${queryParams.value}`),
)

const docTypes = computed(() => docTypesData.value?.data ?? [])
const total = computed(() => docTypesData.value?.meta?.total ?? docTypesData.value?.total ?? 0)
const lastPage = computed(() => docTypesData.value?.meta?.last_page ?? docTypesData.value?.last_page ?? 1)

const appliesToOptions = [
  { title: 'Tous', value: '' },
  { title: 'Véhicule', value: 'vehicle' },
  { title: 'Marchandise', value: 'goods' },
  { title: 'Les deux', value: 'both' },
]

const appliesToFormOptions = [
  { title: 'Véhicule', value: 'vehicle' },
  { title: 'Marchandise', value: 'goods' },
  { title: 'Les deux', value: 'both' },
]

const appliesToMeta = {
  vehicle: { icon: 'tabler-car', color: 'info', label: 'Véhicule' },
  goods: { icon: 'tabler-package', color: 'success', label: 'Marchandise' },
  both: { icon: 'tabler-stack', color: 'primary', label: 'Les deux' },
}

const headers = [
  { title: 'Libellé', key: 'label' },
  { title: 'Slug', key: 'slug' },
  { title: 'S\'applique à', key: 'applies_to' },
  { title: 'Requis par défaut', key: 'is_required_default' },
  { title: 'Ordre', key: 'sort_order' },
  { title: 'Statut', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const defaultForm = () => ({
  slug: '',
  label: '',
  description: '',
  applies_to: 'both',
  is_required_default: false,
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
    slug: item.slug,
    label: item.label,
    description: item.description ?? '',
    applies_to: item.applies_to ?? 'both',
    is_required_default: !!item.is_required_default,
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
      await $api(`/document-types/${selectedItem.value.id}`, { method: 'PATCH', body: form.value })
    }
    else {
      await $api('/document-types', { method: 'POST', body: form.value })
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
    await $api(`/document-types/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

const resetFilters = () => {
  search.value = ''
  filterAppliesTo.value = ''
  onlyActive.value = false
  page.value = 1
}

watch([search, filterAppliesTo, onlyActive, perPage], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Types de documents</span>
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
              v-model="filterAppliesTo"
              :items="appliesToOptions"
              density="compact"
              placeholder="S'applique à"
            />
          </VCol>
          <VCol cols="12" sm="4" md="3">
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
        :items="docTypes"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
        <template #item.applies_to="{ item }">
          <VChip
            v-if="appliesToMeta[item.applies_to]"
            size="small"
            variant="tonal"
            :color="appliesToMeta[item.applies_to].color"
          >
            <VIcon
              start
              :icon="appliesToMeta[item.applies_to].icon"
              size="14"
            />
            {{ appliesToMeta[item.applies_to].label }}
          </VChip>
          <span
            v-else
            class="text-medium-emphasis"
          >–</span>
        </template>

        <template #item.is_required_default="{ item }">
          <VIcon
            v-if="item.is_required_default"
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
          <span class="text-body-2 text-medium-emphasis">{{ total }} type(s) au total</span>
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
      <VCard :title="editMode ? 'Modifier le type de document' : 'Nouveau type de document'">
        <VCardText>
          <VForm ref="formRef">
            <VRow>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.slug"
                  label="Slug"
                  :rules="[requiredValidator, alphaDashValidator]"
                  hint="lowercase, no spaces"
                  persistent-hint
                  placeholder="passport"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.label"
                  label="Libellé"
                  :rules="[requiredValidator]"
                  placeholder="Passeport"
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
                <AppSelect
                  v-model="form.applies_to"
                  :items="appliesToFormOptions"
                  label="S'applique à"
                  :rules="[requiredValidator]"
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
                  v-model="form.is_required_default"
                  label="Requis par défaut"
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
      <VCard title="Supprimer le type de document">
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
