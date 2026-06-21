<script setup>
import { refDebounced } from '@vueuse/core'

definePage({ meta: { layout: 'default', action: 'read', subject: 'catalogue' } })

const router = useRouter()

const viewMode = ref('grid')

const page = ref(1)
const perPage = ref(12)
const searchRaw = ref('')
const search = refDebounced(searchRaw, 400)
const famille = ref(null)
const classeCc = ref(null)
const statut = ref(null)
const prixMax = ref(null)

const familleOptions = [
  { title: 'Routière', value: 'colonne_vertebrale' },
  { title: 'Scooter', value: 'scooter' },
  { title: 'Cub', value: 'sous_los' },
]
const classeOptions = ['110CC', '115CC', '125CC', '150CC', '160CC', '300CC']
const statutOptions = [
  { title: 'Disponible', value: 'disponible' },
  { title: 'Stock faible', value: 'stock_faible' },
  { title: 'Rupture', value: 'rupture' },
]
const perPageOptions = [12, 24, 48]

const familleLabel = code => familleOptions.find(f => f.value === code)?.title ?? code

watch([search, famille, classeCc, statut, prixMax, perPage], () => {
  page.value = 1
})

const queryUrl = computed(() => {
  const p = new URLSearchParams()
  p.set('page', String(page.value))
  p.set('per_page', String(perPage.value))
  if (search.value) p.set('search', search.value)
  if (famille.value) p.set('famille', famille.value)
  if (classeCc.value) p.set('classe_cc', classeCc.value)
  if (statut.value) p.set('statut', statut.value)
  if (prixMax.value) p.set('prix_max', String(prixMax.value))

  return `/motos?${p.toString()}`
})

const { data, isFetching } = useApi(queryUrl)

const motos = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta ?? { current_page: 1, last_page: 1, total: 0, from: 0, to: 0 })

const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`

const resetFilters = () => {
  searchRaw.value = ''
  famille.value = null
  classeCc.value = null
  statut.value = null
  prixMax.value = null
}

const goToDetail = id => router.push(`/motos/${id}`)

const tableHeaders = [
  { title: 'Photo', key: 'image', sortable: false, width: 110 },
  { title: 'Modèle', key: 'modele' },
  { title: 'Famille', key: 'famille' },
  { title: 'Classe', key: 'classe_cc' },
  { title: 'Coloris', key: 'couleurs', sortable: false },
  { title: 'Prix', key: 'prix', align: 'end' },
  { title: 'Stock', key: 'stock', align: 'center' },
  { title: 'Statut', key: 'disponible', align: 'center' },
  { title: '', key: 'actions', sortable: false, align: 'end' },
]
</script>

<template>
  <div>
    <!-- 👉 En-tête -->
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">
          Catalogue motos
        </h4>
        <p class="text-medium-emphasis mb-0">
          {{ meta.total }} modèle(s) — recherchez, filtrez et consultez le détail
        </p>
      </div>

      <VBtnToggle
        v-model="viewMode"
        mandatory
        density="compact"
        variant="outlined"
        color="primary"
        divided
        class="flex-shrink-0"
      >
        <VBtn value="grid" :min-width="150" :height="40" class="text-none">
          <VIcon start icon="tabler-layout-grid" size="20" />
          Catalogue
        </VBtn>
        <VBtn value="table" :min-width="130" :height="40" class="text-none">
          <VIcon start icon="tabler-list" size="20" />
          Tableau
        </VBtn>
      </VBtnToggle>
    </div>

    <!-- 👉 Filtres -->
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <AppTextField
              v-model="searchRaw"
              label="Recherche"
              placeholder="Modèle, couleur, cylindrée…"
              prepend-inner-icon="tabler-search"
              clearable
            />
          </VCol>
          <VCol cols="6" md="2">
            <AppSelect
              v-model="famille"
              label="Famille"
              :items="familleOptions"
              placeholder="Toutes"
              clearable
            />
          </VCol>
          <VCol cols="6" md="2">
            <AppSelect
              v-model="classeCc"
              label="Cylindrée"
              :items="classeOptions"
              placeholder="Toutes"
              clearable
            />
          </VCol>
          <VCol cols="6" md="2">
            <AppTextField
              v-model="prixMax"
              label="Prix max (FCFA)"
              type="number"
              placeholder="900000"
              clearable
            />
          </VCol>
          <VCol cols="6" md="2">
            <AppSelect
              v-model="statut"
              label="Statut"
              :items="statutOptions"
              placeholder="Tous"
              clearable
            />
          </VCol>
        </VRow>
        <div class="d-flex justify-end">
          <VBtn variant="text" size="small" color="secondary" prepend-icon="tabler-refresh" @click="resetFilters">
            Réinitialiser
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <!-- 👉 Chargement -->
    <div v-if="isFetching && !motos.length" class="d-flex justify-center py-12">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <!-- 👉 Aucun résultat -->
    <VCard v-else-if="!motos.length">
      <VCardText class="text-center text-medium-emphasis py-12">
        <VIcon icon="tabler-mood-empty" size="48" class="mb-2" />
        <div>Aucune moto ne correspond à ces critères.</div>
      </VCardText>
    </VCard>

    <!-- 👉 Vue grille (catalogue) -->
    <template v-else-if="viewMode === 'grid'">
      <VRow>
        <VCol
          v-for="moto in motos"
          :key="moto.id"
          cols="12"
          sm="6"
          md="4"
          lg="3"
        >
          <VCard class="h-100 d-flex flex-column" @click="goToDetail(moto.id)" style="cursor: pointer;">
            <div class="position-relative bg-white">
              <VImg
                :src="moto.image_url || ''"
                height="180"
                contain
                class="pa-2"
              >
                <template #placeholder>
                  <div class="d-flex align-center justify-center h-100">
                    <VIcon icon="tabler-motorbike" size="48" class="text-disabled" />
                  </div>
                </template>
              </VImg>
              <VChip
                v-if="!moto.disponible"
                color="error" size="x-small" label
                class="position-absolute" style="top: 8px; right: 8px;"
              >
                Rupture
              </VChip>
              <VChip
                v-else-if="moto.stock_faible"
                color="warning" size="x-small" label
                class="position-absolute" style="top: 8px; right: 8px;"
              >
                Stock faible
              </VChip>
            </div>

            <VDivider />

            <VCardItem class="pb-2">
              <VCardTitle class="text-body-1 font-weight-bold">{{ moto.modele }}</VCardTitle>
              <div class="d-flex gap-1 mt-1 flex-wrap">
                <VChip size="x-small" label color="primary" variant="tonal">{{ moto.classe_cc }}</VChip>
                <VChip size="x-small" label variant="tonal" color="secondary">{{ familleLabel(moto.famille) }}</VChip>
              </div>
            </VCardItem>

            <VCardText class="pt-0 mt-auto">
              <!-- Coloris -->
              <div v-if="moto.couleurs?.length" class="d-flex gap-1 mb-2">
                <span
                  v-for="c in moto.couleurs"
                  :key="c.hex"
                  :title="c.nom"
                  class="d-inline-block rounded-sm border"
                  :style="{ backgroundColor: c.hex, inlineSize: '16px', blockSize: '16px' }"
                />
              </div>
              <div class="d-flex align-center justify-space-between">
                <span class="text-h6 text-primary font-weight-bold">{{ fmtMoney(moto.prix) }}</span>
                <span class="text-caption text-medium-emphasis">Stock : {{ moto.stock }}</span>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </template>

    <!-- 👉 Vue tableau -->
    <VCard v-else>
      <VDataTable
        :headers="tableHeaders"
        :items="motos"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="perPage"
      >
        <template #item.image="{ item }">
          <div class="bg-white rounded border my-2 d-flex align-center justify-center" style="inline-size: 92px; block-size: 60px;">
            <VImg :src="item.image_url || ''" height="56" width="88" contain>
              <template #placeholder>
                <VIcon icon="tabler-motorbike" class="text-disabled" />
              </template>
            </VImg>
          </div>
        </template>
        <template #item.modele="{ item }">
          <span class="font-weight-medium">{{ item.modele }}</span>
        </template>
        <template #item.famille="{ item }">{{ familleLabel(item.famille) }}</template>
        <template #item.classe_cc="{ item }">
          <VChip size="x-small" label color="primary" variant="tonal">{{ item.classe_cc }}</VChip>
        </template>
        <template #item.couleurs="{ item }">
          <div class="d-flex gap-1">
            <span
              v-for="c in item.couleurs"
              :key="c.hex"
              :title="c.nom"
              class="d-inline-block rounded-sm border"
              :style="{ backgroundColor: c.hex, inlineSize: '14px', blockSize: '14px' }"
            />
          </div>
        </template>
        <template #item.prix="{ item }">
          <span class="font-weight-medium">{{ fmtMoney(item.prix) }}</span>
        </template>
        <template #item.disponible="{ item }">
          <VChip v-if="!item.disponible" color="error" size="small" label>Rupture</VChip>
          <VChip v-else-if="item.stock_faible" color="warning" size="small" label>Faible</VChip>
          <VChip v-else color="success" size="small" label>Disponible</VChip>
        </template>
        <template #item.actions="{ item }">
          <VBtn size="small" variant="tonal" color="secondary" prepend-icon="tabler-eye" @click="goToDetail(item.id)">
            Détail
          </VBtn>
        </template>
      </VDataTable>
    </VCard>

    <!-- 👉 Pagination (contrôles front, données paginées côté serveur) -->
    <div v-if="motos.length" class="d-flex flex-wrap align-center justify-space-between gap-4 mt-6">
      <div class="d-flex align-center gap-2">
        <span class="text-body-2 text-medium-emphasis">Par page</span>
        <AppSelect
          v-model="perPage"
          :items="perPageOptions"
          density="compact"
          style="inline-size: 90px;"
        />
        <span class="text-body-2 text-medium-emphasis ms-2">
          {{ meta.from }}–{{ meta.to }} sur {{ meta.total }}
        </span>
      </div>
      <VPagination
        v-model="page"
        :length="meta.last_page"
        :total-visible="5"
        rounded="circle"
      />
    </div>
  </div>
</template>
