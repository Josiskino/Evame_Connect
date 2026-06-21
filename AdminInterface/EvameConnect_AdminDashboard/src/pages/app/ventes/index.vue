<script setup>
definePage({ meta: { layout: 'default', action: 'read', subject: 'ventes' } })

const router = useRouter()
const ability = useAbility()
const canCreate = computed(() => ability.can('create', 'vente'))

const page = ref(1)
const perPage = ref(15)

const queryUrl = computed(() => `/ventes?page=${page.value}&per_page=${perPage.value}`)
const { data, isFetching } = useApi(queryUrl)

const ventes = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta ?? { last_page: 1, total: 0, from: 0, to: 0 })

const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`
const fmtDate = d => (d ? new Intl.DateTimeFormat('fr-FR').format(new Date(d)) : '—')

const headers = [
  { title: 'Date', key: 'date_vente' },
  { title: 'Client', key: 'client' },
  { title: 'Moto', key: 'moto' },
  { title: "Mode", key: 'mode', align: 'center' },
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

    <VCard>
      <VDataTable
        :headers="headers"
        :items="ventes"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="perPage"
      >
        <template #item.date_vente="{ item }">{{ fmtDate(item.date_vente) }}</template>
        <template #item.client="{ item }">
          <span class="font-weight-medium">{{ item.client?.nom ?? '—' }}</span>
        </template>
        <template #item.moto="{ item }">{{ item.moto?.modele ?? '—' }}</template>
        <template #item.mode="{ item }">
          <VChip size="small" label :color="item.mode === 'leasing' ? 'warning' : 'success'">
            {{ item.mode === 'leasing' ? 'Leasing' : 'Direct' }}
          </VChip>
        </template>
        <template #item.montant="{ item }">
          <span class="font-weight-medium">{{ fmtMoney(item.montant) }}</span>
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
