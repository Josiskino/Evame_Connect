<script setup>
import { refDebounced } from '@vueuse/core'

definePage({ meta: { layout: 'default', action: 'read', subject: 'ventes' } })

const router = useRouter()
const ability = useAbility()
const canCreate = computed(() => ability.can('create', 'vente'))

const page = ref(1)
const perPage = ref(15)
const searchRaw = ref('')
const search = refDebounced(searchRaw, 400)
const mode = ref(null)

const modeOptions = [
  { title: 'Tous les modes', value: null },
  { title: 'Achat direct', value: 'direct' },
  { title: 'Leasing', value: 'leasing' },
]

watch([search, mode, perPage], () => { page.value = 1 })

const filterQs = computed(() => {
  const p = new URLSearchParams()
  if (search.value) p.set('search', search.value)
  if (mode.value) p.set('mode', mode.value)

  return p.toString()
})

const queryUrl = computed(() => `/ventes?page=${page.value}&per_page=${perPage.value}${filterQs.value ? `&${filterQs.value}` : ''}`)
const { data, isFetching, execute } = useApi(queryUrl)

// Mini-dashboard (respecte les filtres)
const statsUrl = computed(() => `/ventes/stats${filterQs.value ? `?${filterQs.value}` : ''}`)
const { data: statsData, execute: refreshStats } = useApi(statsUrl)
const stats = computed(() => statsData.value?.data ?? {})

// Temps réel : une vente/paiement/contrat ailleurs -> rafraîchit liste + stats
const { lastActivity } = useRealtimeActivity()
watch(lastActivity, ev => {
  if (ev && ['vente', 'paiement', 'leasing'].includes(ev.resource)) {
    execute()
    refreshStats()
  }
})

const ventes = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta ?? { last_page: 1, total: 0, from: 0, to: 0 })

const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`
const fmtDate = d => (d ? new Intl.DateTimeFormat('fr-FR').format(new Date(d)) : '—')

const headers = [
  { title: 'Date', key: 'date_vente' },
  { title: 'Client', key: 'client' },
  { title: 'Moto', key: 'moto' },
  { title: 'Mode', key: 'mode', align: 'center' },
  { title: 'Montant', key: 'montant', align: 'end' },
  { title: 'Commercial', key: 'commercial' },
]
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">Ventes</h4>
        <p class="text-medium-emphasis mb-0">{{ meta.total }} vente(s) enregistrée(s)</p>
      </div>
      <VBtn v-if="canCreate" prepend-icon="tabler-plus" @click="router.push('/ventes/nouvelle')">
        Nouvelle vente
      </VBtn>
    </div>

    <!-- Mini-dashboard -->
    <VRow class="mb-2">
      <VCol cols="12" sm="6" md="3">
        <StatCard title="Chiffre d'affaires" :value="fmtMoney(stats.chiffre_affaires)" icon="tabler-cash" color="primary" />
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <StatCard title="Nombre de ventes" :value="stats.nombre_ventes ?? 0" icon="tabler-shopping-cart" color="info" />
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <StatCard title="Achats directs" :value="stats.nombre_direct ?? 0" icon="tabler-cash-banknote" color="success" />
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <StatCard title="Ventes en leasing" :value="stats.nombre_leasing ?? 0" icon="tabler-calendar-repeat" color="warning" />
      </VCol>
    </VRow>

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="5">
            <AppTextField
              v-model="searchRaw"
              placeholder="Rechercher par client ou moto…"
              prepend-inner-icon="tabler-search"
              clearable
            />
          </VCol>
          <VCol cols="12" md="4">
            <AppSelect v-model="mode" :items="modeOptions" placeholder="Mode d'achat" />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTable
        :headers="headers"
        :items="ventes"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="perPage"
      >
        <template #item.date_vente="{ item }">{{ fmtDate(item.date_vente) }}</template>
        <template #item.client="{ item }">
          <div class="font-weight-medium">{{ item.client?.nom ?? '—' }}</div>
          <div v-if="item.client?.telephone" class="text-caption text-medium-emphasis">
            {{ item.client.telephone }}
          </div>
        </template>
        <template #item.moto="{ item }">{{ item.moto?.modele ?? '—' }}</template>
        <template #item.mode="{ item }">
          <VChip size="small" label :color="item.mode === 'leasing' ? 'warning' : 'success'">
            {{ item.mode === 'leasing' ? 'Leasing' : 'Direct' }}
          </VChip>
        </template>
        <template #item.montant="{ item }">
          <div class="font-weight-medium">{{ fmtMoney(item.montant) }}</div>
          <div v-if="item.contrat && item.contrat.montant_restant > 0" class="text-caption text-warning">
            Reste : {{ fmtMoney(item.contrat.montant_restant) }}
          </div>
          <div v-else-if="item.contrat" class="text-caption text-success">Soldé</div>
        </template>
        <template #item.commercial="{ item }">{{ item.commercial?.name ?? '—' }}</template>
        <template #no-data>
          <div class="text-center text-medium-emphasis py-8">Aucune vente enregistrée.</div>
        </template>
      </VDataTable>
    </VCard>

    <div v-if="ventes.length" class="d-flex flex-wrap align-center justify-space-between gap-4 mt-6">
      <span class="text-body-2 text-medium-emphasis">{{ meta.from }}–{{ meta.to }} sur {{ meta.total }}</span>
      <VPagination v-model="page" :length="meta.last_page" :total-visible="5" rounded="circle" />
    </div>
  </div>
</template>
