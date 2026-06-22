<script setup>
import { useTheme } from 'vuetify'
import VueApexCharts from 'vue3-apexcharts'

definePage({ meta: { layout: 'default', action: 'read', subject: 'dashboard' } })

const vuetifyTheme = useTheme()

const { data, isFetching } = useApi('/stats/sav')
const s = computed(() => data.value?.data ?? {})

const fmtNum = n => new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))

const donutSeries = computed(() => [s.value.nouvelles ?? 0, s.value.en_traitement ?? 0, s.value.terminees ?? 0])
const donutOptions = computed(() => ({
  chart: { type: 'donut', background: 'transparent' },
  theme: { mode: vuetifyTheme.global.name.value === 'dark' ? 'dark' : 'light' },
  labels: ['Nouvelles', 'En traitement', 'Terminées'],
  colors: ['#2196F3', '#FB8C00', '#43A047'],
  legend: { position: 'bottom' },
  dataLabels: { enabled: true },
  stroke: { width: 0 },
}))
</script>

<template>
  <div>
    <h4 class="text-h4 font-weight-bold mb-1">Statistiques SAV</h4>
    <p class="text-medium-emphasis mb-6">Suivi de l'activité du service après-vente.</p>

    <VRow>
      <VCol cols="12" sm="6" md="3"><StatCard title="Total interventions" :value="fmtNum(s.total)" icon="tabler-tool" color="primary" /></VCol>
      <VCol cols="12" sm="6" md="3"><StatCard title="En traitement" :value="fmtNum(s.en_traitement)" icon="tabler-timer" color="warning" /></VCol>
      <VCol cols="12" sm="6" md="3"><StatCard title="Terminées" :value="fmtNum(s.terminees)" icon="tabler-circle-check" color="success" /></VCol>
      <VCol cols="12" sm="6" md="3"><StatCard title="Taux de résolution" :value="`${s.taux_resolution ?? 0} %`" icon="tabler-percentage" color="info" /></VCol>
    </VRow>

    <VRow class="mt-1">
      <VCol cols="12" md="5">
        <VCard class="h-100">
          <VCardItem><VCardTitle>Répartition par statut</VCardTitle></VCardItem>
          <VCardText>
            <VueApexCharts v-if="s.total" type="donut" height="300" :options="donutOptions" :series="donutSeries" />
            <div v-else class="text-center text-medium-emphasis py-10">Aucune donnée.</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="7">
        <VCard class="h-100">
          <VCardItem>
            <VCardTitle>Charge par technicien</VCardTitle>
            <VCardSubtitle>{{ fmtNum(s.interventions_du_jour) }} intervention(s) aujourd'hui</VCardSubtitle>
          </VCardItem>
          <VTable>
            <thead><tr><th>Technicien</th><th class="text-right">Interventions</th></tr></thead>
            <tbody>
              <tr v-for="t in s.par_technicien" :key="t.technicien">
                <td class="font-weight-medium">{{ t.technicien }}</td>
                <td class="text-right">{{ t.interventions }}</td>
              </tr>
              <tr v-if="!s.par_technicien?.length"><td colspan="2" class="text-center text-medium-emphasis py-4">—</td></tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
    </VRow>

    <VProgressLinear v-if="isFetching" indeterminate color="primary" class="mt-4" />
  </div>
</template>
