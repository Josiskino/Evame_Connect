<script setup>
import { useRouter } from 'vue-router'
import { $api } from '@/utils/api'

definePage({ meta: { layout: 'default' } })

const router = useRouter()

const search = ref('')
const filterBrand = ref('')
const filterType = ref('')
const filterCountry = ref('')
const page = ref(1)
const perPage = 50

// AI semantic search mode — when ON, bypasses the standard /products list
// and queries /v1/ai/products/search instead. Score is shown on each row.
const aiMode = ref(false)
const aiResults = ref([])
const aiLoading = ref(false)

const { data: brandsData } = await useApi('/brands?per_page=200')
const { data: typesData } = await useApi('/product-types?per_page=200')
const { data: countriesData } = await useApi('/countries?per_page=200')

const brandOptions = computed(() => brandsData.value?.data?.map(b => ({ title: b.name, value: b.name })) ?? [])
const typeOptions = computed(() => typesData.value?.data?.map(t => ({ title: t.label, value: t.slug })) ?? [])
const countryOptions = computed(() => countriesData.value?.data?.map(c => ({ title: c.name, value: c.name })) ?? [])

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage })
  if (search.value) params.append('search', search.value)
  if (filterBrand.value) params.append('brand', filterBrand.value)
  if (filterType.value) params.append('type', filterType.value)
  if (filterCountry.value) params.append('provenance', filterCountry.value)
  return params.toString()
})

// Only fetch the classic list when AI mode is off
const { data: productsData, execute: refresh, isFetching } = useApi(
  computed(() => aiMode.value ? null : `/products?${queryParams.value}`),
)

const products = computed(() => aiMode.value ? aiResults.value : (productsData.value?.data ?? []))
const total = computed(() => aiMode.value ? aiResults.value.length : (productsData.value?.meta?.total ?? productsData.value?.total ?? 0))
const lastPage = computed(() => aiMode.value ? 1 : (productsData.value?.meta?.last_page ?? productsData.value?.last_page ?? 1))

const runAiSearch = async () => {
  const q = search.value.trim()
  if (!q) { aiResults.value = []; return }
  aiLoading.value = true
  try {
    const res = await $api('/ai/products/search', { method: 'POST', body: { q, top_k: 20 } })
    aiResults.value = res?.data?.data ?? []
  }
  catch { aiResults.value = [] }
  finally { aiLoading.value = false }
}

// Debounce AI search so we don't fire on every keystroke
let _aiTimer = null
watch([search, aiMode], () => {
  if (!aiMode.value) { aiResults.value = []; return }
  if (_aiTimer) clearTimeout(_aiTimer)
  _aiTimer = setTimeout(runAiSearch, 350)
})

const headers = computed(() => {
  const base = [
    { title: 'Nom', key: 'name' },
    { title: 'Type', key: 'type' },
    { title: 'Marque', key: 'brand' },
    { title: 'Modèle', key: 'model' },
    { title: 'Pays', key: 'country' },
    { title: 'Prix fournisseur', key: 'price' },
  ]
  if (aiMode.value) base.push({ title: 'Pertinence', key: '_score', sortable: false })
  else base.push({ title: 'Date', key: 'created_at' })
  base.push({ title: 'Actions', key: 'actions', sortable: false })
  return base
})

const formatDate = date => date ? new Date(date).toLocaleDateString('fr-FR') : '-'
const formatPrice = (val, currency) => {
  if (!val) return '-'
  return `${Number(val).toLocaleString('fr-FR')} ${currency ?? ''}`
}

// Dialog suppression
const dialogDelete = ref(false)
const selectedProduct = ref(null)

const openDelete = product => {
  selectedProduct.value = product
  dialogDelete.value = true
}

// Dialog détails marque/modèles
const dialogBrandModels = ref(false)
const selectedBrand = ref(null)

const openBrandModels = product => {
  if (!product.brand) return
  selectedBrand.value = product.brand
  dialogBrandModels.value = true
}

const deleteProduct = async () => {
  try {
    await $api(`/products/${selectedProduct.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

const resetFilters = () => {
  search.value = ''
  filterBrand.value = ''
  filterType.value = ''
  filterCountry.value = ''
  page.value = 1
}

watch([search, filterBrand, filterType, filterCountry], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Produits</span>
        <span class="text-body-2 text-medium-emphasis">{{ total }} produit(s)</span>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VRow class="mb-2">
          <VCol cols="12" sm="6" md="3">
            <AppTextField
              v-model="search"
              :placeholder="aiMode ? 'Décris ce que tu cherches…' : 'Rechercher…'"
              :prepend-inner-icon="aiMode ? 'tabler-sparkles' : 'tabler-search'"
              :loading="aiLoading"
              density="compact"
              clearable
            />
          </VCol>
          <VCol
            cols="12"
            sm="4"
            md="2"
          >
            <AppSelect
              v-model="filterBrand"
              :items="[{ title: 'Toutes marques', value: '' }, ...brandOptions]"
              density="compact"
              placeholder="Marque"
            />
          </VCol>
          <VCol
            cols="12"
            sm="4"
            md="2"
          >
            <AppSelect
              v-model="filterType"
              :items="[{ title: 'Tous types', value: '' }, ...typeOptions]"
              density="compact"
              placeholder="Type"
            />
          </VCol>
          <VCol
            cols="12"
            sm="4"
            md="2"
          >
            <AppSelect
              v-model="filterCountry"
              :items="[{ title: 'Tous pays', value: '' }, ...countryOptions]"
              density="compact"
              placeholder="Pays d'origine"
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
          <VCol cols="12" md="2" class="d-flex align-center">
            <VSwitch
              v-model="aiMode"
              color="primary"
              density="compact"
              hide-details
              label="Recherche IA"
            />
          </VCol>
        </VRow>
        <div v-if="aiMode" class="text-caption text-medium-emphasis mb-2">
          <VIcon size="14" icon="tabler-sparkles" class="me-1" />
          Mode sémantique : décris en langage naturel (ex. "SUV familial diesel récent" ou "petite citadine essence").
        </div>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="products"
        :loading="isFetching || aiLoading"
        hide-default-footer
        class="text-no-wrap"
      >
        <template #item.type="{ item }">
          <VChip
            v-if="item.type"
            size="small"
            variant="tonal"
            color="primary"
          >
            {{ item.type.label }}
          </VChip>
          <span
            v-else
            class="text-medium-emphasis"
          >–</span>
        </template>

        <template #item.brand="{ item }">
          {{ item.brand?.name ?? '–' }}
        </template>

        <template #item.model="{ item }">
          {{ item.model?.name ?? '–' }}
        </template>

        <template #item.country="{ item }">
          {{ item.origin?.country?.name ?? item.origin?.country_of_origin ?? '–' }}
        </template>

        <template #item.price="{ item }">
          {{ formatPrice(item.commercial?.supplier_price, item.commercial?.currency) }}
        </template>

        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #item._score="{ item }">
          <VChip
            v-if="item._score !== undefined"
            :color="item._score > 0.6 ? 'success' : item._score > 0.45 ? 'primary' : 'default'"
            size="x-small"
            variant="tonal"
          >
            {{ Math.round(item._score * 100) }}%
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <VBtn
              icon
              size="small"
              variant="text"
              title="Voir le détail du produit"
              @click="router.push(`/products/${item.id}`)"
            >
              <VIcon
                size="18"
                icon="tabler-arrow-right"
              />
            </VBtn>
            <VBtn
              icon
              size="small"
              variant="text"
              :disabled="!item.brand"
              title="Voir les détails de la marque"
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
        <span class="text-body-2 text-medium-emphasis">
          Page {{ page }} / {{ lastPage }}
        </span>
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

    <!-- Dialog suppression -->
    <VDialog
      v-model="dialogDelete"
      max-width="400"
    >
      <VCard title="Supprimer le produit">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer <strong>{{ selectedProduct?.name }}</strong> ?
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
            @click="deleteProduct"
          >
            Supprimer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
