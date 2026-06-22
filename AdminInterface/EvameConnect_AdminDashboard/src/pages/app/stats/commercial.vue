<script setup>
import { useTheme } from 'vuetify'
import VueApexCharts from 'vue3-apexcharts'

definePage({ meta: { layout: 'default', action: 'read', subject: 'dashboard' } })

const vuetifyTheme = useTheme()

const { data, isFetching } = useApi('/stats/commercial')
const s = computed(() => data.value?.data ?? {})

const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`
const fmtNum = n => new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))
const fmtMonth = ym => {
  const [y, m] = String(ym).split('-')
  return new Date(Number(y), Number(m) - 1).toLocaleDateString('fr-FR', { month: 'short' })
}

const evolution = computed(() => s.value.evolution_mensuelle ?? [])

const chartSeries = computed(() => [{ name: "Chiffre d'affaires", data: evolution.value.map(e => e.chiffre_affaires) }])
const chartOptions = computed(() => ({
  chart: { type: 'area', toolbar: { show: false }, background: 'transparent' },
  theme: { mode: vuetifyTheme.global.name.value === 'dark' ? 'dark' : 'light' },
  colors: ['#E53935'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
  xaxis: { categories: evolution.value.map(e => fmtMonth(e.mois)) },
  yaxis: { labels: { formatter: v => `${Math.round(v / 1000)}k` } },
  grid: { borderColor: vuetifyTheme.current.value.colors['on-surface'] + '14' },
  tooltip: { y: { formatter: v => fmtMoney(v) } },
}))
</script>

<template>
  <div>
    <h4 class="text-h4 font-weight-bold mb-1">Statistiques commerciales</h4>
    <p class="text-medium-emphasis mb-6">Vue d'ensemble pour le pilotage des ventes.</p>

    <VRow>
      <VCol cols="12" sm="6" md="3"><StatCard title="Chiffre d'affaires" :value="fmtMoney(s.chiffre_affaires)" icon="tabler-cash" color="primary" /></VCol>
      <VCol cols="12" sm="6" md="3"><StatCard title="Nombre de ventes" :value="fmtNum(s.nombre_ventes)" icon="tabler-shopping-cart" color="info" /></VCol>
      <VCol cols="12" sm="6" md="3"><StatCard title="Panier moyen" :value="fmtMoney(s.panier_moyen)" icon="tabler-receipt" color="success" /></VCol>
      <VCol cols="12" sm="6" md="3"><StatCard title="Ventes en leasing" :value="fmtNum(s.nombre_leasing)" icon="tabler-calendar-repeat" color="warning" /></VCol>
    </VRow>

    <VRow class="mt-1">
      <VCol cols="12" md="7">
        <VCard>
          <VCardItem><VCardTitle>Évolution du chiffre d'affaires</VCardTitle></VCardItem>
          <VCardText>
            <VueApexCharts v-if="evolution.length" type="area" height="280" :options="chartOptions" :series="chartSeries" />
            <div v-else class="text-center text-medium-emphasis py-10">Aucune donnée.</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="5">
        <VCard class="h-100">
          <VCardItem><VCardTitle>Top motos vendues</VCardTitle></VCardItem>
          <VTable>
            <thead><tr><th>Modèle</th><th class="text-right">Ventes</th><th class="text-right">CA</th></tr></thead>
            <tbody>
              <tr v-for="m in s.top_motos" :key="m.modele">
                <td>{{ m.modele }}</td>
                <td class="text-right">{{ m.ventes }}</td>
                <td class="text-right">{{ fmtMoney(m.chiffre_affaires) }}</td>
              </tr>
              <tr v-if="!s.top_motos?.length"><td colspan="3" class="text-center text-medium-emphasis py-4">—</td></tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
    </VRow>

    <VCard class="mt-6">
      <VCardItem><VCardTitle>Performance par commercial</VCardTitle></VCardItem>
      <VTable>
        <thead><tr><th>Commercial</th><th class="text-right">Ventes</th><th class="text-right">Chiffre d'affaires</th></tr></thead>
        <tbody>
          <tr v-for="c in s.par_commercial" :key="c.commercial">
            <td class="font-weight-medium">{{ c.commercial }}</td>
            <td class="text-right">{{ c.ventes }}</td>
            <td class="text-right">{{ fmtMoney(c.chiffre_affaires) }}</td>
          </tr>
          <tr v-if="!s.par_commercial?.length"><td colspan="3" class="text-center text-medium-emphasis py-4">—</td></tr>
        </tbody>
      </VTable>
    </VCard>

    <VProgressLinear v-if="isFetching" indeterminate color="primary" class="mt-4" />
  </div>
</template>
