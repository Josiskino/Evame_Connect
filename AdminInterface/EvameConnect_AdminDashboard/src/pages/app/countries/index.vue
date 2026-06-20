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

const { data: countriesData, execute: refresh, isFetching } = useApi(
  computed(() => `/countries?${queryParams.value}`),
)

const countries = computed(() => countriesData.value?.data ?? [])
const total = computed(() => countriesData.value?.meta?.total ?? countriesData.value?.total ?? 0)
const lastPage = computed(() => countriesData.value?.meta?.last_page ?? countriesData.value?.last_page ?? 1)

const headers = [
  { title: 'Nom', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Continent', key: 'continent' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const form = ref({ name: '', code: '', continent: '' })
const formRef = ref()
const saving = ref(false)
const errorMsg = ref('')

const openCreate = () => {
  editMode.value = false
  form.value = { name: '', code: '', continent: '' }
  errorMsg.value = ''
  dialog.value = true
}

const openEdit = item => {
  editMode.value = true
  selectedItem.value = item
  form.value = { name: item.name, code: item.code ?? '', continent: item.continent ?? '' }
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
      await $api(`/countries/${selectedItem.value.id}`, { method: 'PUT', body: form.value })
    }
    else {
      await $api('/countries', { method: 'POST', body: form.value })
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
    await $api(`/countries/${selectedItem.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

watch([search], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Pays</span>
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
          placeholder="Rechercher un pays..."
          prepend-inner-icon="tabler-search"
          density="compact"
          clearable
          style="max-width: 300px"
        />
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="countries"
        :loading="isFetching"
        hide-default-footer
        class="text-no-wrap"
      >
        <template #item.code="{ item }">
          {{ item.code ?? '–' }}
        </template>

        <template #item.continent="{ item }">
          {{ item.continent ?? '–' }}
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
        <span class="text-body-2 text-medium-emphasis">{{ total }} pays au total</span>
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
      max-width="450"
    >
      <VCard :title="editMode ? 'Modifier le pays' : 'Nouveau pays'">
        <VCardText>
          <VForm ref="formRef">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="form.name"
                  label="Nom"
                  :rules="[requiredValidator]"
                  placeholder="France"
                />
              </VCol>
              <VCol cols="6">
                <AppTextField
                  v-model="form.code"
                  label="Code (ISO)"
                  placeholder="FR"
                />
              </VCol>
              <VCol cols="6">
                <AppTextField
                  v-model="form.continent"
                  label="Continent"
                  placeholder="Europe"
                />
              </VCol>
              <VCol
                v-if="errorMsg"
                cols="12"
              >
                <VAlert
                  type="error"
                  variant="tonal"
                >
                  {{ errorMsg }}
                </VAlert>
              </VCol>
            </VRow>
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
      <VCard title="Supprimer le pays">
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
