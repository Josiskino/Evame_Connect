<script setup>
import { useTheme } from 'vuetify'
import VueApexCharts from 'vue3-apexcharts'

definePage({ meta: { layout: 'default' } })

const vuetifyTheme = useTheme()

// GET /api/v1/dashboard -> { status, message, data: { activite_commerciale, stock, leasing } }
const { data: response, isFetching, execute: refresh } = await useApi('/dashboard')

const dash = computed(() => response.value?.data ?? null)

// --- Helpers d'affichage --------------------------------------------------
const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`
const fmtNumber = n => new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))

const fmtMonth = ym => {
  const [y, m] = String(ym).split('-')

  return new Intl.DateTimeFormat('fr-FR', { month: 'short', year: '2-digit' })
    .format(new Date(Number(y), Number(m) - 1, 1))
}

// --- Cartes indicateurs ---------------------------------------------------
const statCards = computed(() => {
  const d = dash.value
  if (!d)
    return []

  return [
    {
      title: "Chiffre d'affaires",
      value: fmtMoney(d.activite_commerciale.chiffre_affaires_total),
      icon: 'tabler-cash',
      color: 'primary',
    },
    {
      title: 'Ventes réalisées',
      value: fmtNumber(d.activite_commerciale.nombre_ventes),
      icon: 'tabler-shopping-cart',
      color: 'info',
    },
    {
      title: 'Contrats leasing actifs',
      value: fmtNumber(d.leasing.contrats_actifs),
      icon: 'tabler-file-dollar',
      color: 'success',
    },
    {
      title: 'Clients en retard',
      value: fmtNumber(d.leasing.clients_en_retard),
      icon: 'tabler-alert-triangle',
      color: 'warning',
    },
  ]
})

// --- Graphique évolution mensuelle ---------------------------------------
const chartSeries = computed(() => {
  const evo = dash.value?.activite_commerciale?.evolution_mensuelle ?? []

  return [
    { name: "Chiffre d'affaires", type: 'column', data: evo.map(e => e.chiffre_affaires) },
    { name: 'Ventes', type: 'line', data: evo.map(e => e.nombre_ventes) },
  ]
})

const chartOptions = computed(() => {
  const current = vuetifyTheme.current.value
  const theme = current.colors
  const isDark = current.dark
  const evo = dash.value?.activite_commerciale?.evolution_mensuelle ?? []

  // Couleurs de texte/grille dérivées du thème (lisibles en clair ET en sombre)
  const base = theme['on-surface'] || (isDark ? '#E7E3FC' : '#2E263D')
  const labelColor = `${base}b3` // ~70 % d'opacité
  const gridColor = `${base}1f` // ~12 % d'opacité

  return {
    chart: { type: 'line', toolbar: { show: false }, stacked: false, foreColor: labelColor },
    theme: { mode: isDark ? 'dark' : 'light' },
    colors: [theme.primary, theme.info],
    stroke: { width: [0, 3], curve: 'smooth' },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '40%' } },
    dataLabels: { enabled: false },
    legend: { position: 'top', labels: { colors: labelColor } },
    grid: { strokeDashArray: 6, borderColor: gridColor },
    xaxis: {
      categories: evo.map(e => fmtMonth(e.mois)),
      labels: { style: { colors: labelColor } },
      axisBorder: { color: gridColor },
      axisTicks: { color: gridColor },
    },
    yaxis: [
      {
        title: { text: 'FCFA', style: { color: labelColor } },
        labels: { style: { colors: labelColor }, formatter: v => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v) },
      },
      {
        opposite: true,
        min: 0,
        title: { text: 'Ventes', style: { color: labelColor } },
        labels: { style: { colors: labelColor } },
      },
    ],
    tooltip: {
      theme: isDark ? 'dark' : 'light',
      y: {
        formatter: (val, { seriesIndex }) => (seriesIndex === 0 ? fmtMoney(val) : `${val} vente(s)`),
      },
    },
  }
})

const motosDisponibles = computed(() => dash.value?.stock?.motos_disponibles ?? 0)
const alertes = computed(() => dash.value?.stock?.alertes_stock_faible ?? [])
</script>

<template>
  <div>
    <!-- 👉 En-tête -->
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">
          Tableau de bord
        </h4>
        <p class="text-medium-emphasis mb-0">
          Vue d'ensemble en temps réel de l'activité EVAME
        </p>
      </div>
      <VBtn
        prepend-icon="tabler-refresh"
        variant="tonal"
        :loading="isFetching"
        @click="refresh()"
      >
        Actualiser
      </VBtn>
    </div>

    <!-- 👉 Cartes indicateurs -->
    <VRow>
      <VCol
        v-for="card in statCards"
        :key="card.title"
        cols="12"
        sm="6"
        md="3"
      >
        <VCard>
          <VCardText class="d-flex align-center gap-4">
            <VAvatar
              :color="card.color"
              variant="tonal"
              rounded
              size="48"
            >
              <VIcon
                :icon="card.icon"
                size="28"
              />
            </VAvatar>
            <div>
              <div class="text-body-2 text-medium-emphasis">
                {{ card.title }}
              </div>
              <h5 class="text-h5 font-weight-bold">
                {{ card.value }}
              </h5>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow class="mt-1">
      <!-- 👉 Graphique évolution mensuelle -->
      <VCol
        cols="12"
        md="8"
      >
        <VCard>
          <VCardItem>
            <VCardTitle>Évolution mensuelle</VCardTitle>
            <VCardSubtitle>Chiffre d'affaires et nombre de ventes — 6 derniers mois</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              type="line"
              height="320"
              :options="chartOptions"
              :series="chartSeries"
            />
          </VCardText>
        </VCard>
      </VCol>

      <!-- 👉 Stock + Leasing -->
      <VCol
        cols="12"
        md="4"
      >
        <VCard class="mb-6">
          <VCardItem>
            <VCardTitle>Stock motos</VCardTitle>
          </VCardItem>
          <VCardText class="d-flex flex-column gap-4">
            <div class="d-flex align-center justify-space-between">
              <span class="d-flex align-center gap-2"><VIcon icon="tabler-motorbike" color="primary" /> Motos disponibles</span>
              <span class="font-weight-bold">{{ fmtNumber(motosDisponibles) }}</span>
            </div>
            <div class="d-flex align-center justify-space-between">
              <span class="d-flex align-center gap-2"><VIcon icon="tabler-checkbox" color="success" /> Motos vendues</span>
              <span class="font-weight-bold">{{ fmtNumber(dash?.stock?.motos_vendues) }}</span>
            </div>
            <div class="d-flex align-center justify-space-between">
              <span class="d-flex align-center gap-2"><VIcon icon="tabler-alert-octagon" color="error" /> Ruptures</span>
              <span class="font-weight-bold">{{ fmtNumber(dash?.stock?.ruptures) }}</span>
            </div>
          </VCardText>
        </VCard>

        <VCard>
          <VCardItem>
            <VCardTitle>Leasing</VCardTitle>
          </VCardItem>
          <VCardText class="d-flex flex-column gap-4">
            <div class="d-flex align-center justify-space-between">
              <span class="d-flex align-center gap-2"><VIcon icon="tabler-cash-banknote" color="success" /> Encaissements</span>
              <span class="font-weight-bold">{{ fmtMoney(dash?.leasing?.encaissements_total) }}</span>
            </div>
            <div class="d-flex align-center justify-space-between">
              <span class="d-flex align-center gap-2"><VIcon icon="tabler-wallet" color="warning" /> Reste à recouvrer</span>
              <span class="font-weight-bold">{{ fmtMoney(dash?.leasing?.reste_a_recouvrer) }}</span>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- 👉 Alertes stock faible -->
    <VRow class="mt-1">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle class="d-flex align-center gap-2">
              <VIcon icon="tabler-alert-triangle" color="warning" />
              Alertes stock faible
            </VCardTitle>
            <VCardSubtitle>Modèles dont le stock a atteint le seuil d'alerte</VCardSubtitle>
          </VCardItem>
          <VCardText v-if="!alertes.length" class="text-medium-emphasis">
            Aucune alerte — tous les stocks sont au-dessus du seuil. 👍
          </VCardText>
          <VTable v-else>
            <thead>
              <tr>
                <th>Modèle</th>
                <th>Couleur</th>
                <th class="text-center">Stock</th>
                <th class="text-center">Seuil</th>
                <th class="text-center">Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="moto in alertes"
                :key="moto.id"
              >
                <td class="font-weight-medium">{{ moto.modele }}</td>
                <td>{{ moto.couleur }}</td>
                <td class="text-center">{{ moto.stock }}</td>
                <td class="text-center">{{ moto.seuil_alerte }}</td>
                <td class="text-center">
                  <VChip color="warning" size="small" label>Stock faible</VChip>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
