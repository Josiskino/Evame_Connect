<script setup>
import { useRouter } from 'vue-router'
import { getEcho } from '@/utils/echo'

definePage({ meta: { layout: 'default' } })

const router = useRouter()

const filterStatus = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')
const filterBrand = ref('')
const page = ref(1)
const perPage = ref(25)

const { data: brandsData } = await useApi('/brands?per_page=300')
const brandOptions = computed(() => brandsData.value?.data?.map(b => ({ title: b.name, value: b.name })) ?? [])

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage.value })
  if (filterStatus.value) params.append('status', filterStatus.value)
  if (filterDateFrom.value) params.append('date_from', filterDateFrom.value)
  if (filterDateTo.value) params.append('date_to', filterDateTo.value)
  if (filterBrand.value) params.append('brand', filterBrand.value)
  return params.toString()
})

const { data: quotesData, isFetching, execute: refresh } = useApi(
  computed(() => `/quotes?${queryParams.value}`),
)

// Live refresh on quote events from admin-feed
let _refreshTimer = null
const queueRefresh = () => {
  if (_refreshTimer) return
  _refreshTimer = setTimeout(() => { _refreshTimer = null; refresh() }, 600)
}

onMounted(() => {
  try {
    getEcho().private('admin-feed')
      .listen('.quote.created', queueRefresh)
      .listen('.quote.status_changed', queueRefresh)
      .listen('.quote.deleted', queueRefresh)
  }
  catch { /* Pusher not configured */ }
})

onUnmounted(() => {
  if (_refreshTimer) clearTimeout(_refreshTimer)
  try { getEcho()?.leave('admin-feed') }
  catch { /* noop */ }
})

const quotes = computed(() => quotesData.value?.data ?? [])
const total = computed(() => quotesData.value?.meta?.total ?? quotesData.value?.total ?? 0)
const lastPage = computed(() => quotesData.value?.meta?.last_page ?? quotesData.value?.last_page ?? 1)

const headers = [
  { title: '#', key: 'id' },
  { title: 'Produit', key: 'product' },
  { title: 'Client', key: 'user' },
  { title: 'Quantité', key: 'quantity', align: 'center' },
  { title: 'Statut', key: 'status' },
  { title: 'Date', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end' },
]

// Status taxonomy mirrors App\Enums\QuoteStatus on the backend.
const statusOptions = ['pending', 'processing', 'sent', 'closed']

const statusColor = status => ({
  pending: 'warning',
  processing: 'info',
  sent: 'success',
  closed: 'secondary',
}[status] ?? 'default')

const statusLabel = status => ({
  pending: 'En attente',
  processing: 'En traitement',
  sent: 'Envoyé',
  closed: 'Clôturé',
}[status] ?? status)

const formatDate = date => date ? new Date(date).toLocaleDateString('fr-FR') : '-'

watch([filterStatus, filterDateFrom, filterDateTo, filterBrand, perPage], () => { page.value = 1 })

const resetFilters = () => {
  filterStatus.value = ''
  filterDateFrom.value = ''
  filterDateTo.value = ''
  filterBrand.value = ''
  page.value = 1
}
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Demandes de devis</span>
        <span class="text-body-2 text-medium-emphasis">{{ total }} demande(s)</span>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VRow class="mb-2">
          <VCol cols="12" sm="6" md="3">
            <AppSelect
              v-model="filterStatus"
              :items="[{ title: 'Tous les statuts', value: '' }, ...statusOptions.map(s => ({ title: statusLabel(s), value: s }))]"
              density="compact"
              placeholder="Statut"
            />
          </VCol>
          <VCol cols="12" sm="6" md="3">
            <AppSelect
              v-model="filterBrand"
              :items="[{ title: 'Toutes marques', value: '' }, ...brandOptions]"
              density="compact"
              placeholder="Marque"
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <AppTextField
              v-model="filterDateFrom"
              type="date"
              density="compact"
              label="Du"
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <AppTextField
              v-model="filterDateTo"
              type="date"
              density="compact"
              label="Au"
            />
          </VCol>
          <VCol cols="12" md="1">
            <VBtn
              variant="tonal"
              icon="tabler-x"
              size="small"
              title="Réinitialiser les filtres"
              @click="resetFilters"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="quotes"
        :loading="isFetching"
        hide-default-footer
        class="text-no-wrap"
      >
        <template #item.product="{ item }">
          {{ item.product?.name ?? '–' }}
        </template>

        <template #item.user="{ item }">
          {{ item.user?.name ?? '–' }}
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="statusColor(item.status)"
            size="small"
            variant="tonal"
          >
            {{ statusLabel(item.status) }}
          </VChip>
        </template>

        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #item.actions="{ item }">
          <VBtn
            icon
            size="small"
            variant="text"
            color="primary"
            title="Voir le détail"
            @click="router.push(`/quotes/${item.id}`)"
          >
            <VIcon size="18" icon="tabler-arrow-right" />
          </VBtn>
        </template>
      </VDataTable>

      <VCardText class="d-flex align-center justify-space-between flex-wrap gap-2 py-3">
        <span class="text-body-2 text-medium-emphasis">
          Page {{ page }} / {{ lastPage }} — {{ total }} demande(s)
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
