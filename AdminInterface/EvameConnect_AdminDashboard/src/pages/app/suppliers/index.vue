<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const filterCategory = ref('')
const filterCountry = ref('')
const filterVerified = ref('')
const filterActive = ref('')
const page = ref(1)
const perPage = ref(25)

const { data: categoriesData } = await useApi('/supplier-categories?only_active=1&per_page=200')
const { data: countriesData } = await useApi('/countries?per_page=200')

const categoryOptions = computed(() => categoriesData.value?.data?.map(c => ({ title: c.label, value: c.id })) ?? [])
const countryOptions = computed(() => countriesData.value?.data?.map(c => ({ title: c.name, value: c.id })) ?? [])

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value })
  if (search.value) params.append('search', search.value)
  if (filterCategory.value) params.append('category_id', filterCategory.value)
  if (filterCountry.value) params.append('country_id', filterCountry.value)
  if (filterVerified.value !== '') params.append('only_verified', filterVerified.value)
  if (filterActive.value !== '') params.append('only_active', filterActive.value)
  return params.toString()
})

const { data: suppliersData, execute: refresh, isFetching } = useApi(
  computed(() => `/suppliers?${queryParams.value}`),
)

const suppliers = computed(() => suppliersData.value?.data ?? [])
const total = computed(() => suppliersData.value?.meta?.total ?? suppliersData.value?.total ?? 0)
const lastPage = computed(() => suppliersData.value?.meta?.last_page ?? suppliersData.value?.last_page ?? 1)

const headers = [
  { title: 'Nom', key: 'name' },
  { title: 'Catégorie', key: 'category' },
  { title: 'Pays', key: 'country' },
  { title: 'Contact', key: 'contact' },
  { title: 'Note', key: 'rating' },
  { title: 'Produits', key: 'products_count' },
  { title: 'Statut', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedItem = ref(null)
const defaultForm = () => ({
  name: '',
  slug: '',
  description: '',
  supplier_category_id: null,
  country_id: null,
  website: '',
  contact_email: '',
  phone: '',
  whatsapp: '',
  address: '',
  is_verified: false,
  rating: 0,
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
    name: item.name,
    slug: item.slug ?? '',
    description: item.description ?? '',
    supplier_category_id: item.supplier_category_id ?? item.category?.id ?? null,
    country_id: item.country_id ?? item.country?.id ?? null,
    website: item.website ?? '',
    contact_email: item.contact_email ?? '',
    phone: item.phone ?? '',
    whatsapp: item.whatsapp ?? '',
    address: item.address ?? '',
    is_verified: !!item.is_verified,
    rating: Number(item.rating ?? 0),
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
    const payload = { ...form.value }
    if (!payload.slug) delete payload.slug

    if (editMode.value) {
      await $api(`/suppliers/${selectedItem.value.id}`, { method: 'PATCH', body: payload })
    }
    else {
      await $api('/suppliers', { method: 'POST', body: payload })
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
    await $api(`/suppliers/${selectedItem.value.id}`, { method: 'DELETE' })
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
  filterCountry.value = ''
  filterVerified.value = ''
  filterActive.value = ''
  page.value = 1
}

watch([search, filterCategory, filterCountry, filterVerified, filterActive, perPage], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Fournisseurs</span>
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
          <VCol cols="12" sm="6" md="2">
            <AppSelect
              v-model="filterCategory"
              :items="[{ title: 'Toutes catégories', value: '' }, ...categoryOptions]"
              density="compact"
              placeholder="Catégorie"
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <AppSelect
              v-model="filterCountry"
              :items="[{ title: 'Tous pays', value: '' }, ...countryOptions]"
              density="compact"
              placeholder="Pays"
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <AppSelect
              v-model="filterVerified"
              :items="[
                { title: 'Tous', value: '' },
                { title: 'Vérifiés', value: '1' },
                { title: 'Non vérifiés', value: '0' },
              ]"
              density="compact"
              placeholder="Vérifié"
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <AppSelect
              v-model="filterActive"
              :items="[
                { title: 'Tous', value: '' },
                { title: 'Actifs', value: '1' },
                { title: 'Inactifs', value: '0' },
              ]"
              density="compact"
              placeholder="Statut"
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
        :items="suppliers"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="-1"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <span class="d-inline-flex align-center gap-1">
            {{ item.name }}
            <VIcon
              v-if="item.is_verified"
              icon="tabler-discount-check"
              color="success"
              size="16"
            />
          </span>
        </template>

        <template #item.category="{ item }">
          <VChip
            v-if="item.category"
            size="small"
            variant="tonal"
            color="primary"
          >
            {{ item.category.label }}
          </VChip>
          <span
            v-else
            class="text-medium-emphasis"
          >–</span>
        </template>

        <template #item.country="{ item }">
          {{ item.country?.name ?? '—' }}
        </template>

        <template #item.contact="{ item }">
          <div
            v-if="item.contact_email || item.phone"
            class="d-flex flex-column"
            style="line-height: 1.2"
          >
            <span
              v-if="item.contact_email"
              class="text-caption"
            >{{ item.contact_email }}</span>
            <span
              v-if="item.phone"
              class="text-caption text-medium-emphasis"
            >{{ item.phone }}</span>
          </div>
          <span
            v-else
            class="text-medium-emphasis"
          >–</span>
        </template>

        <template #item.rating="{ item }">
          <VRating
            :model-value="Number(item.rating ?? 0)"
            readonly
            density="compact"
            half-increments
            size="small"
          />
        </template>

        <template #item.products_count="{ item }">
          <VChip
            size="small"
            variant="tonal"
            color="info"
          >
            {{ item.products_count ?? 0 }}
          </VChip>
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
          <span class="text-body-2 text-medium-emphasis">{{ total }} fournisseur(s) au total</span>
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
      max-width="720"
    >
      <VCard :title="editMode ? 'Modifier le fournisseur' : 'Nouveau fournisseur'">
        <VCardText>
          <VForm ref="formRef">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="form.name"
                  label="Nom"
                  :rules="[requiredValidator]"
                  placeholder="Acme Industries"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.supplier_category_id"
                  :items="[{ title: '—', value: null }, ...categoryOptions]"
                  label="Catégorie"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.country_id"
                  :items="[{ title: '—', value: null }, ...countryOptions]"
                  label="Pays"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.slug"
                  label="Slug"
                  hint="Sera généré automatiquement si vide"
                  persistent-hint
                  placeholder="acme-industries"
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Description"
                  rows="3"
                  auto-grow
                />
              </VCol>

              <VCol cols="12">
                <h6 class="text-subtitle-1 font-weight-medium mt-2">
                  Contact
                </h6>
                <VDivider class="mt-2" />
              </VCol>

              <VCol cols="12" md="4">
                <AppTextField
                  v-model="form.contact_email"
                  label="Email"
                  :rules="[emailValidator]"
                  placeholder="contact@example.com"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="form.phone"
                  label="Téléphone"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="form.whatsapp"
                  label="WhatsApp"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="form.website"
                  label="Site web"
                  :rules="[urlValidator]"
                  placeholder="https://example.com"
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="form.address"
                  label="Adresse"
                  rows="2"
                  auto-grow
                />
              </VCol>

              <VCol cols="12" md="4">
                <div class="d-flex flex-column gap-1">
                  <VRating
                    v-model="form.rating"
                    :length="5"
                    half-increments
                    size="large"
                  />
                  <span class="text-caption text-medium-emphasis">Note interne</span>
                </div>
              </VCol>
              <VCol cols="12" md="4">
                <VSwitch
                  v-model="form.is_verified"
                  label="Vérifié"
                  color="primary"
                />
              </VCol>
              <VCol cols="12" md="4">
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
      <VCard title="Supprimer le fournisseur">
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
