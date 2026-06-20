<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const filterBrand = ref('')
const filterCountry = ref('')
const filterYear = ref('')
const page = ref(1)
const perPage = ref(25)

const { data: brandsData } = await useApi('/brands?per_page=300')
const { data: countriesData } = await useApi('/countries?per_page=200')

const brandOptions = computed(() => brandsData.value?.data?.map(b => ({ title: b.name, value: b.name })) ?? [])
const countryOptions = computed(() => countriesData.value?.data?.map(c => ({ title: c.name, value: c.name })) ?? [])

const yearOptions = computed(() => {
  const now = new Date().getFullYear()
  const years = []
  for (let y = now; y >= 1980; y--) years.push({ title: String(y), value: String(y) })
  return years
})

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value })
  if (search.value) params.append('search', search.value)
  if (filterBrand.value) params.append('brand', filterBrand.value)
  if (filterCountry.value) params.append('country', filterCountry.value)
  if (filterYear.value) params.append('year', filterYear.value)
  return params.toString()
})

const { data: vehiclesData, isFetching } = useApi(
  computed(() => `/vehicles?${queryParams.value}`),
)

const vehicles = computed(() => vehiclesData.value?.data ?? [])
const total = computed(() => vehiclesData.value?.meta?.total ?? vehiclesData.value?.total ?? 0)
const lastPage = computed(() => vehiclesData.value?.meta?.last_page ?? vehiclesData.value?.last_page ?? 1)

const headers = [
  { title: 'N° Châssis', key: 'chassis_number' },
  { title: 'Marque', key: 'brand' },
  { title: 'Modèle', key: 'model' },
  { title: 'Année', key: 'year', align: 'center' },
  { title: 'Pays', key: 'country' },
  { title: 'Cylindrée', key: 'engine_displacement', align: 'end' },
  { title: 'Valeur imposable', key: 'taxable_value', align: 'end' },
  { title: 'Impôt IMF/RaD', key: 'tax', align: 'end' },
]

const formatNumber = val => val == null || val === '' ? '–' : Number(val).toLocaleString('fr-FR')
const formatMoney = val => val == null || val === '' ? '–' : `${Number(val).toLocaleString('fr-FR')} XOF`

const resetFilters = () => {
  search.value = ''
  filterBrand.value = ''
  filterCountry.value = ''
  filterYear.value = ''
  page.value = 1
}

watch([search, filterBrand, filterCountry, filterYear, perPage], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Véhicules</span>
        <span class="text-body-2 text-medium-emphasis">{{ total }} véhicule(s)</span>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VRow class="mb-2">
          <VCol cols="12" sm="6" md="3">
            <AppTextField
              v-model="search"
              placeholder="Rechercher un n° de châssis..."
              prepend-inner-icon="tabler-search"
              density="compact"
              clearable
            />
          </VCol>
          <VCol cols="12" sm="4" md="2">
            <AppSelect
              v-model="filterBrand"
              :items="[{ title: 'Toutes marques', value: '' }, ...brandOptions]"
              density="compact"
              placeholder="Marque"
            />
          </VCol>
          <VCol cols="12" sm="4" md="2">
            <AppSelect
              v-model="filterCountry"
              :items="[{ title: 'Tous pays', value: '' }, ...countryOptions]"
              density="compact"
              placeholder="Pays"
            />
          </VCol>
          <VCol cols="12" sm="4" md="2">
            <AppSelect
              v-model="filterYear"
              :items="[{ title: 'Toutes années', value: '' }, ...yearOptions]"
              density="compact"
              placeholder="Année"
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
        :items="vehicles"
        :loading="isFetching"
        :items-per-page="-1"
        hide-default-footer
        class="text-no-wrap"
      >
        <template #item.brand="{ item }">
          {{ item.brand?.name ?? '–' }}
        </template>

        <template #item.model="{ item }">
          {{ item.model?.name ?? '–' }}
        </template>

        <template #item.country="{ item }">
          {{ item.country?.name ?? '–' }}
        </template>

        <template #item.engine_displacement="{ item }">
          {{ item.engine_displacement ? `${formatNumber(item.engine_displacement)} cc` : '–' }}
        </template>

        <template #item.taxable_value="{ item }">
          {{ formatMoney(item.customs_duty?.customs_value) }}
        </template>

        <template #item.tax="{ item }">
          {{ formatMoney(item.customs_duty?.total_taxes) }}
        </template>
      </VDataTable>

      <VCardText class="d-flex align-center justify-space-between flex-wrap gap-2 py-3">
        <span class="text-body-2 text-medium-emphasis">
          Page {{ page }} / {{ lastPage }} — {{ total }} véhicule(s)
        </span>
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
        <VPagination
          v-model="page"
          :length="lastPage"
          :total-visible="5"
          rounded
        />
      </VCardText>
    </VCard>
  </div>
</template>
