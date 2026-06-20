<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const page = ref(1)
const perPage = 50

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage })
  if (search.value) params.append('search', search.value)
  return params.toString()
})

const { data: brandsData, execute: refresh, isFetching } = useApi(
  computed(() => `/brands?${queryParams.value}`),
)

const brands = computed(() => brandsData.value?.data ?? [])
const total = computed(() => brandsData.value?.meta?.total ?? brandsData.value?.total ?? 0)
const lastPage = computed(() => brandsData.value?.meta?.last_page ?? brandsData.value?.last_page ?? 1)

const headers = [
  { title: 'Nom', key: 'name' },
  { title: 'Créé le', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const form = ref({ name: '' })
const formRef = ref()
const saving = ref(false)
const errorMsg = ref('')

const openCreate = () => {
  editMode.value = false
  form.value = { name: '' }
  errorMsg.value = ''
  dialog.value = true
}

const openEdit = item => {
  editMode.value = true
  selectedItem.value = item
  form.value = { name: item.name }
  errorMsg.value = ''
  dialog.value = true
}

const openDelete = item => {
  selectedItem.value = item
  dialogDelete.value = true
}

const dialogBrandModels = ref(false)
const selectedBrand = ref(null)

const openBrandModels = item => {
  selectedBrand.value = item
  dialogBrandModels.value = true
}

const save = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  errorMsg.value = ''
  try {
    if (editMode.value) {
      await $api(`/brands/${selectedItem.value.id}`, { method: 'PUT', body: form.value })
    }
    else {
      await $api('/brands', { method: 'POST', body: form.value })
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
    await $api(`/brands/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

const formatDate = date => date ? new Date(date).toLocaleDateString('fr-FR') : '-'

watch([search], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Marques</span>
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
          placeholder="Rechercher une marque..."
          prepend-inner-icon="tabler-search"
          density="compact"
          clearable
          style="max-width: 300px"
        />
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="brands"
        :loading="isFetching"
        hide-default-footer
        class="text-no-wrap"
      >
        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <VBtn
              icon
              size="small"
              variant="text"
              title="Voir les modèles"
              @click="openBrandModels(item)"
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
        <span class="text-body-2 text-medium-emphasis">{{ total }} marque(s) au total</span>
        <VPagination
          v-model="page"
          :length="lastPage"
          :total-visible="5"
          rounded
        />
      </VCardText>
    </VCard>

    <BrandModelsDialog
      v-if="dialogBrandModels"
      v-model="dialogBrandModels"
      :brand="selectedBrand"
    />

    <VDialog
      v-model="dialog"
      max-width="400"
    >
      <VCard :title="editMode ? 'Modifier la marque' : 'Nouvelle marque'">
        <VCardText>
          <VForm ref="formRef">
            <AppTextField
              v-model="form.name"
              label="Nom de la marque"
              :rules="[requiredValidator]"
              placeholder="Toyota"
            />
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
      <VCard title="Supprimer la marque">
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
