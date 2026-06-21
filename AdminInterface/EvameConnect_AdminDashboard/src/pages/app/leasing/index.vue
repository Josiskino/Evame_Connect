<script setup>
definePage({ meta: { layout: 'default', action: 'read', subject: 'leasing' } })

const router = useRouter()

const page = ref(1)
const perPage = ref(15)
const enRetard = ref(false)

const queryUrl = computed(() => {
  const p = new URLSearchParams()
  p.set('page', String(page.value))
  p.set('per_page', String(perPage.value))
  if (enRetard.value) p.set('en_retard', '1')

  return `/leasing?${p.toString()}`
})

watch([enRetard, perPage], () => { page.value = 1 })

const { data, isFetching, execute } = useApi(queryUrl)

// Temps réel : contrat ou paiement ailleurs -> rafraîchit la liste
const { lastActivity } = useRealtimeActivity()
watch(lastActivity, ev => {
  if (ev && ['leasing', 'paiement'].includes(ev.resource)) execute()
})

const contrats = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta ?? { last_page: 1, total: 0, from: 0, to: 0 })

const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`

const headers = [
  { title: 'Client', key: 'client' },
  { title: 'Moto', key: 'moto' },
  { title: 'Montant contrat', key: 'montant_total', align: 'end' },
  { title: 'Payé', key: 'montant_paye', align: 'end' },
  { title: 'Reste', key: 'montant_restant', align: 'end' },
  { title: 'Progression', key: 'progression' },
  { title: 'Statut', key: 'statut_paiement', align: 'center' },
]
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">Contrats leasing</h4>
        <p class="text-medium-emphasis mb-0">{{ meta.total }} contrat(s)</p>
      </div>
      <VBtnToggle v-model="enRetard" mandatory density="compact" color="primary" variant="outlined">
        <VBtn :value="false">Tous</VBtn>
        <VBtn :value="true">En retard</VBtn>
      </VBtnToggle>
    </div>

    <VCard>
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
        <template #no-data>
          <div class="text-center text-medium-emphasis py-8">Aucun contrat de leasing.</div>
        </template>
      </VDataTable>
    </VCard>

    <div v-if="contrats.length" class="d-flex flex-wrap align-center justify-space-between gap-4 mt-6">
      <span class="text-body-2 text-medium-emphasis">{{ meta.from }}–{{ meta.to }} sur {{ meta.total }}</span>
      <VPagination v-model="page" :length="meta.last_page" :total-visible="5" rounded="circle" />
    </div>
  </div>
</template>
