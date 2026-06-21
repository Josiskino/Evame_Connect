<script setup>
import { refDebounced } from '@vueuse/core'

definePage({ meta: { layout: 'default', action: 'read', subject: 'leasing' } })

const router = useRouter()

const page = ref(1)
const perPage = ref(15)
const enRetard = ref(false)
const searchRaw = ref('')
const search = refDebounced(searchRaw, 400)
const dateFrom = ref('')
const dateTo = ref('')

const queryUrl = computed(() => {
  const p = new URLSearchParams()
  p.set('page', String(page.value))
  p.set('per_page', String(perPage.value))
  if (enRetard.value) p.set('en_retard', '1')
  if (search.value) p.set('search', search.value)
  if (dateFrom.value) p.set('date_from', dateFrom.value)
  if (dateTo.value) p.set('date_to', dateTo.value)

  return `/leasing?${p.toString()}`
})

watch([enRetard, perPage, search, dateFrom, dateTo], () => { page.value = 1 })

const { data, isFetching, execute } = useApi(queryUrl)

// Mini-dashboard
const { data: statsData, execute: refreshStats } = useApi('/leasing/stats')
const stats = computed(() => statsData.value?.data ?? {})

// Temps réel : contrat ou paiement ailleurs -> rafraîchit liste + stats
const { lastActivity } = useRealtimeActivity()
watch(lastActivity, ev => {
  if (ev && ['leasing', 'paiement'].includes(ev.resource)) {
    execute()
    refreshStats()
  }
})

const contrats = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta ?? { last_page: 1, total: 0, from: 0, to: 0 })

const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`
const fmtNumber = n => new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))

const headers = [
  { title: 'Client', key: 'client' },
  { title: 'Moto', key: 'moto' },
  { title: 'Montant contrat', key: 'montant_total', align: 'end' },
  { title: 'Payé', key: 'montant_paye', align: 'end' },
  { title: 'Reste', key: 'montant_restant', align: 'end' },
  { title: 'Progression', key: 'progression' },
  { title: 'Statut', key: 'statut_paiement', align: 'center' },
  { title: 'Contrat', key: 'actions', align: 'center', sortable: false },
]

// Aperçu / téléchargement du contrat
const contractDialog = ref(false)
const contratSelectionne = ref(null)
const openContract = item => {
  contratSelectionne.value = item
  contractDialog.value = true
}
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">Contrats leasing</h4>
        <p class="text-medium-emphasis mb-0">{{ meta.total }} contrat(s)</p>
      </div>
      <VBtnToggle v-model="enRetard" mandatory color="primary" variant="outlined" :height="40">
        <VBtn :value="false" :min-width="120">Tous</VBtn>
        <VBtn :value="true" :min-width="140">En retard</VBtn>
      </VBtnToggle>
    </div>

    <!-- Mini-dashboard -->
    <VRow class="mb-2">
      <VCol cols="12" sm="6" md="3">
        <StatCard title="Contrats actifs" :value="fmtNumber(stats.contrats_actifs)" icon="tabler-file-dollar" color="primary" />
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <StatCard title="Encaissements" :value="fmtMoney(stats.encaissements_total)" icon="tabler-cash" color="success" />
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <StatCard title="Reste à recouvrer" :value="fmtMoney(stats.reste_a_recouvrer)" icon="tabler-wallet" color="warning" />
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <StatCard title="Clients en retard" :value="fmtNumber(stats.clients_en_retard)" icon="tabler-alert-triangle" color="error" />
      </VCol>
    </VRow>

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <AppTextField
              v-model="searchRaw"
              placeholder="Rechercher par client…"
              prepend-inner-icon="tabler-search"
              clearable
            />
          </VCol>
          <VCol cols="12" md="6">
            <DateRangeFilter v-model:from="dateFrom" v-model:to="dateTo" />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTable
        :headers="headers"
        :items="contrats"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="perPage"
        class="cursor-pointer"
        @click:row="(_, { item }) => router.push(`/leasing/${item.id}`)"
      >
        <template #item.client="{ item }">
          <div class="font-weight-medium">{{ item.client?.nom ?? '—' }}</div>
          <div v-if="item.client?.telephone" class="text-caption text-medium-emphasis">
            {{ item.client.telephone }}
          </div>
        </template>
        <template #item.moto="{ item }">{{ item.moto?.modele ?? '—' }}</template>
        <template #item.montant_total="{ item }">{{ fmtMoney(item.montant_total) }}</template>
        <template #item.montant_paye="{ item }">
          <span class="text-success">{{ fmtMoney(item.montant_paye) }}</span>
        </template>
        <template #item.montant_restant="{ item }">
          <span :class="item.montant_restant > 0 ? 'text-warning' : 'text-success'">
            {{ fmtMoney(item.montant_restant) }}
          </span>
        </template>
        <template #item.progression="{ item }">
          <div class="d-flex align-center gap-2" style="min-inline-size: 140px;">
            <VProgressLinear
              :model-value="item.progression"
              :color="item.progression >= 100 ? 'success' : 'primary'"
              height="6"
              rounded
              style="flex: 1;"
            />
            <span class="text-caption font-weight-medium">{{ item.progression }}%</span>
          </div>
        </template>
        <template #item.statut_paiement="{ item }">
          <VChip size="small" label :color="item.en_retard ? 'error' : 'success'">
            {{ item.en_retard ? 'En retard' : 'À jour' }}
          </VChip>
        </template>
        <template #item.actions="{ item }">
          <VBtn size="small" variant="tonal" prepend-icon="tabler-file-text" @click.stop="openContract(item)">
            Contrat
          </VBtn>
        </template>
        <template #no-data>
          <div class="text-center text-medium-emphasis py-8">Aucun contrat de leasing.</div>
        </template>
      </VDataTable>
    </VCard>

    <div v-if="contrats.length" class="d-flex flex-wrap align-center justify-space-between gap-4 mt-6">
      <span class="text-body-2 text-medium-emphasis">{{ meta.from }}–{{ meta.to }} sur {{ meta.total }}</span>
      <VPagination v-model="page" :length="meta.last_page" :total-visible="5" rounded="circle" />
    </div>

    <LeasingContractDialog v-model="contractDialog" :contrat="contratSelectionne" />
  </div>
</template>
