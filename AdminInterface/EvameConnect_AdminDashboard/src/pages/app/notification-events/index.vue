<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const filterCategory = ref('')
const onlyActive = ref(false)
const page = ref(1)
const perPage = ref(25)

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value })
  if (search.value) params.append('search', search.value)
  if (filterCategory.value) params.append('category', filterCategory.value)
  if (onlyActive.value) params.append('only_active', '1')
  return params.toString()
})

const { data: eventsData, execute: refresh, isFetching } = useApi(
  computed(() => `/notification-events?${queryParams.value}`),
)

const events = computed(() => eventsData.value?.data ?? [])
const total = computed(() => eventsData.value?.meta?.total ?? eventsData.value?.total ?? 0)
const lastPage = computed(() => eventsData.value?.meta?.last_page ?? eventsData.value?.last_page ?? 1)

const categoryOptions = computed(() => {
  const set = new Set()
  for (const e of events.value) {
    if (e.category) set.add(e.category)
  }
  return [
    { title: 'Toutes catégories', value: '' },
    ...[...set].sort().map(c => ({ title: c, value: c })),
  ]
})

const headers = [
  { title: 'Libellé', key: 'label' },
  { title: 'Slug', key: 'slug' },
  { title: 'Catégorie', key: 'category' },
  { title: 'Ordre', key: 'sort_order' },
  { title: 'Actif', key: 'is_active' },
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
  category: '',
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
    slug: item.slug,
    label: item.label,
    description: item.description ?? '',
    category: item.category ?? '',
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
    const payload = {
      ...form.value,
      category: form.value.category || null,
    }
    if (editMode.value) {
      await $api(`/notification-events/${selectedItem.value.id}`, { method: 'PATCH', body: payload })
    }
    else {
      await $api('/notification-events', { method: 'POST', body: payload })
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
    await $api(`/notification-events/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

const resetFilters = () => {
  search.value = ''
  filterCategory.value = ''
  onlyActive.value = false
  page.value = 1
}

watch([search, filterCategory, onlyActive, perPage], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Événements de notification</span>
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
              v-model="filterCategory"
              :items="categoryOptions"
              density="compact"
              placeholder="Catégorie"
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
        :items="events"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
        <template #item.category="{ item }">
          <VChip
            v-if="item.category"
            size="small"
            variant="tonal"
            color="info"
          >
            {{ item.category }}
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
          <span class="text-body-2 text-medium-emphasis">{{ total }} événement(s) au total</span>
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
      <VCard :title="editMode ? 'Modifier l\'événement' : 'Nouvel événement de notification'">
        <VCardText>
          <VForm ref="formRef">
            <VRow>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.slug"
                  label="Slug"
                  :rules="[requiredValidator, alphaDashValidator]"
                  hint="ex: dossier.created"
                  persistent-hint
                  placeholder="dossier.created"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.label"
                  label="Libellé"
                  :rules="[requiredValidator]"
                  placeholder="Dossier créé"
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
                  v-model="form.category"
                  label="Catégorie"
                  hint="ex: dossier, document"
                  persistent-hint
                  placeholder="dossier"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.sort_order"
                  type="number"
                  label="Ordre d'affichage"
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
      <VCard title="Supprimer l'événement">
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
