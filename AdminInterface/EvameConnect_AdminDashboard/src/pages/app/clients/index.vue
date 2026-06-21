<script setup>
import { refDebounced } from '@vueuse/core'

definePage({ meta: { layout: 'default', action: 'read', subject: 'clients' } })

const ability = useAbility()
const canCreate = computed(() => ability.can('create', 'client'))

// --- Liste + recherche + pagination -------------------------------------
const page = ref(1)
const perPage = ref(15)
const searchRaw = ref('')
const search = refDebounced(searchRaw, 400)

watch([search, perPage], () => { page.value = 1 })

const queryUrl = computed(() => {
  const p = new URLSearchParams()
  p.set('page', String(page.value))
  p.set('per_page', String(perPage.value))
  if (search.value) p.set('search', search.value)

  return `/clients?${p.toString()}`
})

const { data, isFetching, execute } = useApi(queryUrl)

// Mini-dashboard
const { data: statsData, execute: refreshStats } = useApi('/clients/stats')
const stats = computed(() => statsData.value?.data ?? {})

const clients = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta ?? { last_page: 1, total: 0, from: 0, to: 0 })

const fmtNumber = n => new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))

const headers = [
  { title: 'Nom', key: 'nom' },
  { title: 'Téléphone', key: 'telephone' },
  { title: 'E-mail', key: 'email' },
  { title: 'Adresse', key: 'adresse' },
]

// --- Notification (système global avec timer) ----------------------------
const { notify: pushNotif } = useNotifications()
const notify = (message, color = 'success') =>
  pushNotif({ message, color, title: color === 'error' ? 'Erreur' : 'Succès' })

// --- Temps réel : rafraîchit la liste si un client change ailleurs --------
const { lastActivity } = useRealtimeActivity()
watch(lastActivity, ev => {
  if (ev?.resource === 'client') {
    execute()
    refreshStats()
  }
})

// --- Création client (formulaire réutilisable avec CNI) ------------------
const dialog = ref(false)

const onClientCreated = () => {
  notify('Client enregistré avec succès.')
  execute()
  refreshStats()
}
</script>

<template>
  <div>
    <!-- En-tête -->
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">Clients</h4>
        <p class="text-medium-emphasis mb-0">{{ meta.total }} client(s) enregistré(s)</p>
      </div>
      <VBtn v-if="canCreate" prepend-icon="tabler-plus" @click="dialog = true">
        Nouveau client
      </VBtn>
    </div>

    <!-- Mini-dashboard -->
    <VRow class="mb-2">
      <VCol cols="12" sm="4">
        <StatCard title="Clients au total" :value="fmtNumber(stats.total)" icon="tabler-users" color="primary" />
      </VCol>
      <VCol cols="12" sm="4">
        <StatCard title="Nouveaux ce mois" :value="fmtNumber(stats.nouveaux_ce_mois)" icon="tabler-user-plus" color="success" />
      </VCol>
      <VCol cols="12" sm="4">
        <StatCard title="Avec CNI enregistrée" :value="fmtNumber(stats.avec_cni)" icon="tabler-id" color="info" />
      </VCol>
    </VRow>

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="5">
            <AppTextField
              v-model="searchRaw"
              placeholder="Rechercher par nom ou téléphone…"
              prepend-inner-icon="tabler-search"
              clearable
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTable
        :headers="headers"
        :items="clients"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="perPage"
      >
        <template #item.nom="{ item }">
          <div class="d-flex align-center gap-2">
            <VAvatar size="34" color="primary" variant="tonal">
              <span class="text-caption">{{ (item.nom || '?').charAt(0).toUpperCase() }}</span>
            </VAvatar>
            <span class="font-weight-medium">{{ item.nom }}</span>
          </div>
        </template>
        <template #item.telephone="{ item }">{{ item.telephone || '—' }}</template>
        <template #item.email="{ item }">{{ item.email || '—' }}</template>
        <template #item.adresse="{ item }">{{ item.adresse || '—' }}</template>
        <template #no-data>
          <div class="text-center text-medium-emphasis py-8">Aucun client trouvé.</div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Pagination -->
    <div v-if="clients.length" class="d-flex flex-wrap align-center justify-space-between gap-4 mt-6">
      <span class="text-body-2 text-medium-emphasis">{{ meta.from }}–{{ meta.to }} sur {{ meta.total }}</span>
      <VPagination v-model="page" :length="meta.last_page" :total-visible="5" rounded="circle" />
    </div>

    <!-- Dialog création (formulaire réutilisable avec CNI) -->
    <ClientFormDialog v-model="dialog" @created="onClientCreated" />
  </div>
</template>
