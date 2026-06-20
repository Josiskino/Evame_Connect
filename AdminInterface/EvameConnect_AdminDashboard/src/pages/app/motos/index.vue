<script setup>
import { refDebounced } from '@vueuse/core'

definePage({ meta: { layout: 'default', action: 'read', subject: 'catalogue' } })

const router = useRouter()

// --- Vue (grille / tableau) ----------------------------------------------
const viewMode = ref('grid')

// --- Filtres + pagination ------------------------------------------------
const page = ref(1)
const perPage = ref(12)
const searchRaw = ref('')
const search = refDebounced(searchRaw, 400)
const famille = ref(null)
const classeCc = ref(null)
const disponible = ref(false)
const prixMax = ref(null)

const familleOptions = [
  { title: 'Colonne vertébrale', value: 'colonne_vertebrale' },
  { title: 'Scooter', value: 'scooter' },
  { title: "Sous l'os", value: 'sous_los' },
]
const classeOptions = ['110CC', '115CC', '125CC', '150CC', '160CC', '300CC']
const perPageOptions = [12, 24, 48]

const familleLabel = code => familleOptions.find(f => f.value === code)?.title ?? code

// Retour à la page 1 dès qu'un filtre change
watch([search, famille, classeCc, disponible, prixMax, perPage], () => {
  page.value = 1
})

// URL réactive -> useApi refetch automatiquement (pagination côté serveur)
const queryUrl = computed(() => {
  const p = new URLSearchParams()
  p.set('page', String(page.value))
  p.set('per_page', String(perPage.value))
  if (search.value) p.set('search', search.value)
  if (famille.value) p.set('famille', famille.value)
  if (classeCc.value) p.set('classe_cc', classeCc.value)
  if (disponible.value) p.set('disponible', '1')
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
  disponible.value = false
  prixMax.value = null
}

const goToDetail = id => router.push(`/motos/${id}`)

const tableHeaders = [
  { title: '', key: 'image', sortable: false, width: 72 },
  { title: 'Modèle', key: 'modele' },
  { title: 'Famille', key: 'famille' },
  { title: 'Classe', key: 'classe_cc' },
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
        density="comfortable"
        variant="outlined"
        divided
      >
        <VBtn value="grid" prepend-icon="tabler-layout-grid">Catalogue</VBtn>
        <VBtn value="table" prepend-icon="tabler-list">Tableau</VBtn>
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
          <VCol cols="6" md="3">
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
              placeholder="Ex : 900000"
              clearable
            />
          </VCol>
          <VCol cols="6" md="1" class="d-flex align-center">
            <VSwitch
              v-model="disponible"
              label="Dispo"
              density="compact"
            />
          </VCol>
        </VRow>
        <div class="d-flex justify-end">
          <VBtn variant="text" size="small" prepend-icon="tabler-refresh" @click="resetFilters">
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
            <div class="position-relative">
              <VImg
                :src="moto.image_url || ''"
                height="170"
                cover
                class="bg-grey-lighten-4"
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

            <VCardItem class="pb-2">
              <VCardTitle class="text-body-1 font-weight-bold">{{ moto.modele }}</VCardTitle>
              <div class="d-flex gap-1 mt-1">
                <VChip size="x-small" label color="primary" variant="tonal">{{ moto.classe_cc }}</VChip>
                <VChip size="x-small" label variant="tonal">{{ familleLabel(moto.famille) }}</VChip>
              </div>
            </VCardItem>

            <VCardText class="pt-0 mt-auto">
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
          <VAvatar rounded size="48" class="bg-grey-lighten-4 my-1">
            <VImg :src="item.image_url || ''">
              <template #placeholder>
                <VIcon icon="tabler-motorbike" class="text-disabled" />
              </template>
            </VImg>
          </VAvatar>
        </template>
        <template #item.modele="{ item }">
          <span class="font-weight-medium">{{ item.modele }}</span>
        </template>
        <template #item.famille="{ item }">{{ familleLabel(item.famille) }}</template>
        <template #item.classe_cc="{ item }">
          <VChip size="x-small" label color="primary" variant="tonal">{{ item.classe_cc }}</VChip>
        </template>
        <template #item.prix="{ item }">{{ fmtMoney(item.prix) }}</template>
        <template #item.disponible="{ item }">
          <VChip v-if="!item.disponible" color="error" size="small" label>Rupture</VChip>
          <VChip v-else-if="item.stock_faible" color="warning" size="small" label>Faible</VChip>
          <VChip v-else color="success" size="small" label>Disponible</VChip>
        </template>
        <template #item.actions="{ item }">
          <VBtn size="small" variant="tonal" @click="goToDetail(item.id)">Détail</VBtn>
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
