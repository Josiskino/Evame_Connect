<script setup>
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import { getEcho } from '@/utils/echo'

definePage({ meta: { layout: 'default' } })

const router = useRouter()
const vuetifyTheme = useTheme()

const { data: statsData, isFetching, execute: refreshStats } = await useApi('/stats/dashboard')

const stats = computed(() => statsData.value?.data ?? null)

// Throttle Pusher-driven refetches so a burst of events doesn't hammer the API.
let _refreshTimer = null
const queueRefresh = () => {
  if (_refreshTimer) return
  _refreshTimer = setTimeout(() => {
    _refreshTimer = null
    refreshStats()
  }, 800)
}

onMounted(() => {
  try {
    getEcho().private('admin-feed')
      .listen('.quote.created', queueRefresh)
      .listen('.quote.status_changed', queueRefresh)
      .listen('.dossier.created', queueRefresh)
      .listen('.dossier.status_changed', queueRefresh)
      .listen('.dossier.updated', queueRefresh)
  }
  catch { /* Pusher not configured — silent fallback */ }
})

onUnmounted(() => {
  if (_refreshTimer) clearTimeout(_refreshTimer)
  try { getEcho()?.leave('admin-feed') }
  catch { /* noop */ }
})

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const numberFmt = new Intl.NumberFormat('fr-FR')

const formatNumber = value => {
  if (value === null || value === undefined || Number.isNaN(Number(value)))
    return '–'

  return numberFmt.format(Number(value))
}

const formatMonth = value => {
  if (!value)
    return ''
  // value is "YYYY-MM" — append a day to make it parseable
  const date = new Date(`${ value }-01T00:00:00`)
  if (Number.isNaN(date.getTime()))
    return value

  return new Intl.DateTimeFormat('fr-FR', { month: 'short', year: '2-digit' })
    .format(date)
    .replace('.', '')
}

const formatPercent = value => {
  if (value === null || value === undefined || Number.isNaN(Number(value)))
    return '–'

  return `${ Math.round(Number(value) * 100) }%`
}

// ---------------------------------------------------------------------------
// KPI cards
// ---------------------------------------------------------------------------

const kpiCards = computed(() => {
  const s = stats.value
  if (!s)
    return []

  const documentsTotal = (s.documents?.received_total ?? 0)
    + (s.documents?.validated_total ?? 0)
    + (s.documents?.missing_total ?? 0)
    + (s.documents?.rejected_total ?? 0)

  const documentsOk = (s.documents?.received_total ?? 0) + (s.documents?.validated_total ?? 0)

  const alertsTotal = (s.alerts?.dossiers_overdue ?? 0)
    + (s.alerts?.stages_blocked ?? 0)

  return [
    {
      key: 'active',
      title: 'Dossiers actifs',
      value: formatNumber(s.dossiers?.active),
      subtitle: `sur ${ formatNumber(s.dossiers?.total) } au total`,
      icon: 'tabler-folders',
      color: 'primary',
    },
    {
      key: 'quotes',
      title: 'Devis',
      value: formatNumber(s.quotes?.total),
      subtitle: `${ formatPercent(s.quotes?.conversion_rate) } convertis`,
      icon: 'tabler-file-invoice',
      color: 'info',
    },
    {
      key: 'docs',
      title: 'Documents reçus',
      value: formatNumber(documentsOk),
      subtitle: `sur ${ formatNumber(documentsTotal) } documents`,
      icon: 'tabler-file-check',
      color: 'success',
    },
    {
      key: 'alerts',
      title: 'Alertes',
      value: formatNumber(alertsTotal),
      subtitle: alertsTotal > 0 ? 'À traiter rapidement' : 'Aucune alerte',
      icon: 'tabler-alert-triangle',
      color: alertsTotal > 0 ? 'error' : 'warning',
    },
  ]
})

// ---------------------------------------------------------------------------
// Monthly volume area chart
// ---------------------------------------------------------------------------

const monthlyVolume = computed(() => stats.value?.dossiers?.monthly_volume ?? [])

const monthlySeries = computed(() => [
  {
    name: 'Dossiers',
    data: monthlyVolume.value.map(m => Number(m.count) || 0),
  },
])

const monthlyChartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables
  const labelColor = `rgba(${ hexToRgb(currentTheme['on-surface']) }, ${ variableTheme['medium-emphasis-opacity'] })`
  const borderColor = `rgba(${ hexToRgb(currentTheme['on-surface']) }, 0.08)`

  return {
    chart: {
      type: 'area',
      parentHeightOffset: 0,
      toolbar: { show: false },
      zoom: { enabled: false },
      animations: { enabled: true },
    },
    colors: [currentTheme.primary],
    stroke: { curve: 'smooth', width: 3 },
    fill: {
      type: 'gradient',
      gradient: {
        shade: 'light',
        shadeIntensity: 0.6,
        opacityFrom: 0.5,
        opacityTo: 0.05,
        stops: [0, 95, 100],
      },
    },
    dataLabels: { enabled: false },
    grid: {
      borderColor,
      strokeDashArray: 4,
      padding: { top: 0, right: 8, bottom: 0, left: 8 },
      yaxis: { lines: { show: true } },
      xaxis: { lines: { show: false } },
    },
    xaxis: {
      categories: monthlyVolume.value.map(m => formatMonth(m.month)),
      labels: { style: { colors: labelColor, fontSize: '13px' } },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      min: 0,
      decimalsInFloat: 0,
      labels: {
        style: { colors: labelColor, fontSize: '13px' },
        formatter: val => formatNumber(Math.round(val)),
      },
    },
    tooltip: {
      y: { formatter: val => `${ formatNumber(val) } dossier${ val > 1 ? 's' : '' }` },
    },
    markers: {
      size: 4,
      strokeWidth: 2,
      strokeColors: currentTheme.surface,
      colors: [currentTheme.primary],
      hover: { size: 6 },
    },
  }
})

// ---------------------------------------------------------------------------
// Mode donut chart
// ---------------------------------------------------------------------------

const modeData = computed(() => stats.value?.dossiers?.by_mode ?? [])

const modeSeries = computed(() => modeData.value.map(m => Number(m.count) || 0))

const modeChartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables
  const headingColor = `rgba(${ hexToRgb(currentTheme['on-surface']) }, ${ variableTheme['high-emphasis-opacity'] })`
  const labelColor = `rgba(${ hexToRgb(currentTheme['on-surface']) }, ${ variableTheme['medium-emphasis-opacity'] })`

  const palette = [
    currentTheme.primary,
    currentTheme.info,
    currentTheme.success,
    currentTheme.warning,
    currentTheme.error,
    currentTheme.secondary,
  ]

  return {
    chart: {
      type: 'donut',
      parentHeightOffset: 0,
    },
    labels: modeData.value.map(m => m.label || m.mode_slug || ''),
    colors: palette.slice(0, Math.max(modeData.value.length, 1)),
    stroke: { width: 0 },
    dataLabels: { enabled: false },
    legend: {
      position: 'bottom',
      labels: { colors: labelColor },
      fontSize: '13px',
      markers: { offsetX: -3 },
      itemMargin: { horizontal: 8, vertical: 4 },
    },
    plotOptions: {
      pie: {
        donut: {
          size: '70%',
          labels: {
            show: true,
            value: {
              fontSize: '1.5rem',
              fontWeight: 600,
              color: headingColor,
              formatter: val => formatNumber(val),
            },
            name: {
              fontSize: '0.875rem',
              color: labelColor,
            },
            total: {
              show: true,
              showAlways: true,
              label: 'Total',
              color: labelColor,
              fontSize: '0.875rem',
              formatter: w =>
                formatNumber(w.globals.seriesTotals.reduce((a, b) => a + b, 0)),
            },
          },
        },
      },
    },
    tooltip: {
      y: { formatter: val => `${ formatNumber(val) } dossier${ val > 1 ? 's' : '' }` },
    },
  }
})

// ---------------------------------------------------------------------------
// Top carriers
// ---------------------------------------------------------------------------

const topCarriers = computed(() =>
  (stats.value?.carriers?.top_used ?? []).slice(0, 10),
)

// ---------------------------------------------------------------------------
// Fastest routes
// ---------------------------------------------------------------------------

const fastestRoutes = computed(() => {
  const routes = stats.value?.transit?.average_days_by_route ?? []

  return [...routes]
    .sort((a, b) => Number(a.average_days) - Number(b.average_days))
    .slice(0, 8)
})

// ---------------------------------------------------------------------------
// Statuses
// ---------------------------------------------------------------------------

const STATUS_COLORS = {
  draft: 'default',
  preparing: 'info',
  in_transit: 'warning',
  at_customs: 'warning',
  delivered: 'success',
  archived: 'secondary',
  cancelled: 'error',
}

const STATUS_LABELS = {
  draft: 'Brouillon',
  preparing: 'En préparation',
  in_transit: 'En transit',
  at_customs: 'En douane',
  delivered: 'Livré',
  archived: 'Archivé',
  cancelled: 'Annulé',
}

const STATUS_ORDER = [
  'draft',
  'preparing',
  'in_transit',
  'at_customs',
  'delivered',
  'archived',
  'cancelled',
]

const statusEntries = computed(() => {
  const byStatus = stats.value?.dossiers?.by_status ?? {}
  const total = Object.values(byStatus).reduce((a, b) => a + Number(b || 0), 0)

  return STATUS_ORDER
    .map(key => ({
      key,
      label: STATUS_LABELS[key] || key,
      color: STATUS_COLORS[key] || 'default',
      count: Number(byStatus[key] || 0),
      pct: total > 0 ? Math.round((Number(byStatus[key] || 0) / total) * 100) : 0,
    }))
    .filter(entry => entry.count > 0 || stats.value)
})

const statusTotal = computed(() =>
  statusEntries.value.reduce((acc, entry) => acc + entry.count, 0),
)

// ---------------------------------------------------------------------------
// Alerts
// ---------------------------------------------------------------------------

const alerts = computed(() => stats.value?.alerts ?? null)

const alertItems = computed(() => {
  const a = alerts.value
  if (!a)
    return []

  const items = []
  if ((a.dossiers_overdue ?? 0) > 0) {
    items.push({
      key: 'overdue',
      label: `${ a.dossiers_overdue } dossier(s) en retard`,
      query: { status: 'in_transit', overdue: 1 },
    })
  }
  if ((a.documents_missing_critical ?? 0) > 0) {
    items.push({
      key: 'docs_missing',
      label: `${ a.documents_missing_critical } document(s) critique(s) manquant(s)`,
      query: { documents: 'missing_critical' },
    })
  }
  if ((a.stages_blocked ?? 0) > 0) {
    items.push({
      key: 'stages_blocked',
      label: `${ a.stages_blocked } étape(s) bloquée(s)`,
      query: { stages: 'blocked' },
    })
  }

  return items
})

const goToDossiers = query => {
  router.push({ path: '/dossiers', query })
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap gap-3">
      <div>
        <h4 class="text-h4 font-weight-bold">
          Tableau de bord
        </h4>
        <p class="text-medium-emphasis mb-0">
          Vue d'ensemble en temps réel de l'activité EVAME
        </p>
      </div>
      <VBtn
        v-if="!isFetching"
        variant="tonal"
        prepend-icon="tabler-folder-open"
        :to="{ path: '/dossiers' }"
      >
        Voir les dossiers
      </VBtn>
    </div>

    <!-- Section 4 — Alerts strip (rendered above the fold for visibility) -->
    <VAlert
      v-if="alertItems.length > 0"
      type="warning"
      variant="tonal"
      class="mb-6"
      icon="tabler-alert-triangle"
    >
      <template #title>
        <span class="font-weight-medium">Attention requise</span>
      </template>
      <div class="d-flex flex-wrap gap-x-6 gap-y-2 mt-1">
        <a
          v-for="item in alertItems"
          :key="item.key"
          href="#"
          class="text-warning-darken-2 text-decoration-none font-weight-medium"
          @click.prevent="goToDossiers(item.query)"
        >
          <VIcon
            icon="tabler-arrow-right"
            size="16"
            class="me-1"
          />
          {{ item.label }}
        </a>
      </div>
    </VAlert>

    <!-- Section 1 — KPI cards -->
    <VRow>
      <VCol
        v-for="card in kpiCards"
        :key="card.key"
        cols="12"
        sm="6"
        lg="3"
      >
        <VCard>
          <VCardText class="d-flex align-center gap-4 pa-5">
            <VAvatar
              :color="card.color"
              variant="tonal"
              size="52"
              rounded
            >
              <VIcon
                :icon="card.icon"
                size="28"
              />
            </VAvatar>
            <div class="flex-grow-1 overflow-hidden">
              <div class="text-h4 font-weight-bold text-truncate">
                {{ card.value }}
              </div>
              <div class="text-body-1 font-weight-medium text-truncate">
                {{ card.title }}
              </div>
              <div class="text-caption text-medium-emphasis text-truncate">
                {{ card.subtitle }}
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Section 2 — Charts -->
    <VRow class="mt-2">
      <VCol
        cols="12"
        lg="8"
      >
        <VCard>
          <VCardItem>
            <VCardTitle>Volume mensuel des dossiers</VCardTitle>
            <VCardSubtitle>12 derniers mois</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              v-if="monthlyVolume.length > 0"
              type="area"
              :options="monthlyChartOptions"
              :series="monthlySeries"
              height="280"
            />
            <div
              v-else
              class="d-flex flex-column align-center justify-center text-medium-emphasis"
              style="min-height: 280px;"
            >
              <VIcon
                icon="tabler-chart-line"
                size="48"
                class="mb-2"
              />
              <span>Aucun dossier pour le moment</span>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        lg="4"
      >
        <VCard>
          <VCardItem>
            <VCardTitle>Répartition par mode</VCardTitle>
            <VCardSubtitle>Mode de transport</VCardSubtitle>
          </VCardItem>
          <VCardText>
            <VueApexCharts
              v-if="modeSeries.length > 0 && modeSeries.some(v => v > 0)"
              type="donut"
              :options="modeChartOptions"
              :series="modeSeries"
              height="280"
            />
            <div
              v-else
              class="d-flex flex-column align-center justify-center text-medium-emphasis"
              style="min-height: 280px;"
            >
              <VIcon
                icon="tabler-chart-donut"
                size="48"
                class="mb-2"
              />
              <span>Aucun dossier pour le moment</span>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Section 3 — Lists -->
    <VRow class="mt-2">
      <!-- Top carriers -->
      <VCol
        cols="12"
        md="6"
        lg="4"
      >
        <VCard>
          <VCardItem>
            <VCardTitle>Top transporteurs</VCardTitle>
            <VCardSubtitle>Les plus utilisés</VCardSubtitle>
          </VCardItem>
          <VCardText class="pa-0">
            <VList
              v-if="topCarriers.length > 0"
              lines="two"
              density="comfortable"
              class="py-0"
            >
              <VListItem
                v-for="(carrier, idx) in topCarriers"
                :key="carrier.id"
              >
                <template #prepend>
                  <VAvatar
                    :color="idx === 0 ? 'primary' : 'default'"
                    :variant="idx === 0 ? 'tonal' : 'outlined'"
                    size="36"
                    class="me-3 font-weight-bold"
                  >
                    {{ idx + 1 }}
                  </VAvatar>
                </template>
                <VListItemTitle class="font-weight-medium">
                  {{ carrier.name }}
                </VListItemTitle>
                <VListItemSubtitle>
                  {{ formatNumber(carrier.dossiers_count) }} dossier{{ carrier.dossiers_count > 1 ? 's' : '' }}
                </VListItemSubtitle>
                <template #append>
                  <VChip
                    size="small"
                    color="primary"
                    variant="tonal"
                  >
                    {{ formatNumber(carrier.dossiers_count) }}
                  </VChip>
                </template>
              </VListItem>
            </VList>
            <div
              v-else
              class="d-flex flex-column align-center justify-center text-medium-emphasis pa-8"
            >
              <VIcon
                icon="tabler-truck-off"
                size="40"
                class="mb-2"
              />
              <span>Aucun transporteur utilisé</span>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Fastest routes -->
      <VCol
        cols="12"
        md="6"
        lg="4"
      >
        <VCard>
          <VCardItem>
            <VCardTitle>Routes les plus rapides</VCardTitle>
            <VCardSubtitle>Délai moyen de transit</VCardSubtitle>
          </VCardItem>
          <VCardText class="pa-0">
            <VList
              v-if="fastestRoutes.length > 0"
              lines="two"
              density="comfortable"
              class="py-0"
            >
              <VListItem
                v-for="route in fastestRoutes"
                :key="`${ route.origin_port }-${ route.destination_port }`"
              >
                <template #prepend>
                  <VAvatar
                    color="success"
                    variant="tonal"
                    size="36"
                    class="me-3"
                  >
                    <VIcon
                      icon="tabler-route"
                      size="20"
                    />
                  </VAvatar>
                </template>
                <VListItemTitle class="font-weight-medium">
                  {{ route.origin_port }}
                  <VIcon
                    icon="tabler-arrow-right"
                    size="14"
                    class="mx-1 text-medium-emphasis"
                  />
                  {{ route.destination_port }}
                </VListItemTitle>
                <VListItemSubtitle>
                  {{ formatNumber(route.samples) }} échantillon{{ route.samples > 1 ? 's' : '' }}
                </VListItemSubtitle>
                <template #append>
                  <div class="text-end">
                    <div class="text-body-1 font-weight-bold">
                      {{ Number(route.average_days).toFixed(1) }} j
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      moyenne
                    </div>
                  </div>
                </template>
              </VListItem>
            </VList>
            <div
              v-else
              class="d-flex flex-column align-center justify-center text-medium-emphasis pa-8"
            >
              <VIcon
                icon="tabler-route-off"
                size="40"
                class="mb-2"
              />
              <span>Aucune route mesurée</span>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Statuses -->
      <VCol
        cols="12"
        md="12"
        lg="4"
      >
        <VCard>
          <VCardItem>
            <VCardTitle>Statuts dossiers</VCardTitle>
            <VCardSubtitle>
              {{ formatNumber(statusTotal) }} dossier{{ statusTotal > 1 ? 's' : '' }} au total
            </VCardSubtitle>
          </VCardItem>
          <VCardText>
            <div
              v-if="statusTotal > 0"
              class="d-flex flex-column gap-4"
            >
              <div
                v-for="entry in statusEntries"
                :key="entry.key"
                class="d-flex flex-column gap-1"
              >
                <div class="d-flex align-center justify-space-between">
                  <div class="d-flex align-center gap-2">
                    <VChip
                      :color="entry.color"
                      size="small"
                      variant="tonal"
                      label
                    >
                      {{ entry.label }}
                    </VChip>
                  </div>
                  <div class="text-body-2 font-weight-medium">
                    {{ formatNumber(entry.count) }}
                    <span class="text-medium-emphasis text-caption ms-1">
                      ({{ entry.pct }}%)
                    </span>
                  </div>
                </div>
                <VProgressLinear
                  :model-value="entry.pct"
                  :color="entry.color === 'default' ? 'primary' : entry.color"
                  height="6"
                  rounded
                />
              </div>
            </div>
            <div
              v-else
              class="d-flex flex-column align-center justify-center text-medium-emphasis py-6"
            >
              <VIcon
                icon="tabler-folder-off"
                size="40"
                class="mb-2"
              />
              <span>Aucun dossier pour le moment</span>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
