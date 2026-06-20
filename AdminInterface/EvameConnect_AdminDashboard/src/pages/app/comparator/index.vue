<script setup>
definePage({ meta: { layout: 'default' } })

// ─── State ────────────────────────────────────────────────────────────────
const searchA = ref('')
const searchB = ref('')

const resultsA = ref([])
const resultsB = ref([])

const loadingA = ref(false)
const loadingB = ref(false)

// Selected items as returned from the search (lightweight)
const selectedA = ref(null)
const selectedB = ref(null)

// Detailed product (after /products/{id} fetch)
const detailA = ref(null)
const detailB = ref(null)

const detailLoadingA = ref(false)
const detailLoadingB = ref(false)

// ─── Search (debounced 300ms) ─────────────────────────────────────────────
const buildSearchHandler = (searchRef, resultsRef, loadingRef) => {
  let timer = null

  return val => {
    clearTimeout(timer)
    if (!val || val.length < 2) {
      resultsRef.value = []

      return
    }
    loadingRef.value = true
    timer = setTimeout(async () => {
      try {
        const res = await $api(
          `/products?type=vehicle&per_page=20&search=${encodeURIComponent(val)}`,
        )
        const list = res.data ?? res

        resultsRef.value = (Array.isArray(list) ? list : []).map(p => ({
          title: formatItemTitle(p),
          value: p.id,
          raw: p,
        }))
      } catch {
        resultsRef.value = []
      } finally {
        loadingRef.value = false
      }
    }, 300)
  }
}

const formatItemTitle = p => {
  const brand = p.brand?.name ?? ''
  const model = p.model?.name ?? p.name ?? ''
  const year = p.vehicle?.year ? ` — ${p.vehicle.year}` : ''
  const chassis = p.vehicle?.chassis_number ? ` (Chassis: ${p.vehicle.chassis_number})` : ''

  return `${brand} ${model}${year}${chassis}`.trim()
}

watch(searchA, buildSearchHandler(searchA, resultsA, loadingA))
watch(searchB, buildSearchHandler(searchB, resultsB, loadingB))

// ─── Detail fetch on selection ────────────────────────────────────────────
const fetchDetail = async (item, target, loadingRef) => {
  if (!item?.value) {
    target.value = null

    return
  }
  loadingRef.value = true
  try {
    const res = await $api(`/products/${item.value}`)

    target.value = res.data ?? res
  } catch {
    target.value = item.raw ?? null
  } finally {
    loadingRef.value = false
  }
}

watch(selectedA, val => fetchDetail(val, detailA, detailLoadingA))
watch(selectedB, val => fetchDetail(val, detailB, detailLoadingB))

// ─── Reset ────────────────────────────────────────────────────────────────
const resetAll = () => {
  selectedA.value = null
  selectedB.value = null
  detailA.value = null
  detailB.value = null
  searchA.value = ''
  searchB.value = ''
  resultsA.value = []
  resultsB.value = []
}

// ─── Helpers ──────────────────────────────────────────────────────────────
const fmtNumber = val => {
  if (val === null || val === undefined || val === '') return '—'
  const n = Number(val)

  if (!Number.isFinite(n)) return String(val)

  return n.toLocaleString('fr-FR')
}

const fmtMoney = (val, currency) => {
  if (val === null || val === undefined || val === '') return '—'
  const n = Number(val)

  if (!Number.isFinite(n)) return String(val)

  return `${n.toLocaleString('fr-FR')} ${currency ?? 'FCFA'}`
}

const fmtPercent = pct => `${(pct * 100).toFixed(1)} %`

const isComplete = computed(() => Boolean(detailA.value && detailB.value))

const bothLoading = computed(() => detailLoadingA.value || detailLoadingB.value)

// ─── Section descriptors (label + getter + numeric flag + currency-aware) ─
const sections = computed(() => [
  {
    title: 'Identification',
    icon: 'tabler-id',
    rows: [
      { label: 'Marque', get: p => p.brand?.name },
      { label: 'Modèle', get: p => p.model?.name ?? p.name },
      { label: 'Année', get: p => p.vehicle?.year, numeric: true },
      { label: 'Châssis', get: p => p.vehicle?.chassis_number, mono: true },
      { label: 'État', get: p => p.condition ?? p.vehicle?.condition ?? '—' },
    ],
  },
  {
    title: 'Caractéristiques',
    icon: 'tabler-engine',
    rows: [
      {
        label: 'Cylindrée (cm³)',
        get: p => p.vehicle?.engine_displacement,
        numeric: true,
        suffix: ' cm³',
      },
      {
        label: 'Puissance fiscale (CV)',
        get: p => p.vehicle?.fiscal_power,
        numeric: true,
        suffix: ' CV',
      },
      { label: 'Énergie', get: p => p.vehicle?.fuel ?? p.fuel ?? p.energy ?? '—' },
      {
        label: 'Provenance',
        get: p =>
          p.origin?.country?.name
          ?? p.origin?.country_of_origin
          ?? '—',
      },
    ],
  },
  {
    title: "Coûts d'achat",
    icon: 'tabler-currency-dollar',
    rows: [
      {
        label: 'Prix fournisseur',
        get: p => p.commercial?.supplier_price,
        money: true,
        currencyGet: p => p.commercial?.currency,
        numeric: true,
      },
      { label: 'Devise', get: p => p.commercial?.currency },
    ],
  },
  {
    title: 'Transport',
    icon: 'tabler-truck',
    rows: [
      { label: 'Mode', get: p => p.transport?.mode },
      {
        label: 'Coût transport principal',
        get: p => p.transport?.main_cost,
        money: true,
        currencyGet: p => p.commercial?.currency,
        numeric: true,
      },
      {
        label: 'Assurance',
        get: p => p.transport?.insurance_cost,
        money: true,
        currencyGet: p => p.commercial?.currency,
        numeric: true,
      },
      {
        label: 'Coût post-dédouanement',
        get: p => p.transport?.post_clearance_cost,
        money: true,
        currencyGet: p => p.commercial?.currency,
        numeric: true,
      },
      {
        label: 'Durée (jours)',
        get: p => p.transport?.duration_days,
        numeric: true,
        suffix: ' j',
      },
    ],
  },
  {
    title: 'Douane UEMOA',
    icon: 'tabler-receipt-tax',
    rows: [
      {
        label: 'Valeur CIF',
        get: p => p.customs_duty?.cif_value,
        money: true,
        numeric: true,
      },
      {
        label: 'Valeur taxable',
        get: p => p.customs_duty?.customs_value ?? p.vehicle?.taxable_value,
        money: true,
        numeric: true,
      },
      {
        label: 'Total taxes',
        get: p => p.customs_duty?.totals?.total_taxes,
        money: true,
        numeric: true,
      },
      {
        label: 'Total importé',
        get: p => p.customs_duty?.totals?.total_import_cost,
        money: true,
        numeric: true,
        emphasis: true,
      },
    ],
  },
])

// ─── Per-row computed: value display + diff state ─────────────────────────
const rawValue = (row, p) => (p ? row.get(p) : undefined)

const isEmpty = v => v === null || v === undefined || v === '' || v === '—'

const displayValue = (row, p) => {
  const v = rawValue(row, p)

  if (isEmpty(v)) return '—'
  if (row.money) return fmtMoney(v, row.currencyGet ? row.currencyGet(p) : 'FCFA')
  if (row.numeric) return `${fmtNumber(v)}${row.suffix ?? ''}`

  return String(v)
}

const valuesDiffer = row => {
  const a = rawValue(row, detailA.value)
  const b = rawValue(row, detailB.value)

  if (isEmpty(a) || isEmpty(b)) return false

  // Compare numerically when possible
  const na = Number(a)
  const nb = Number(b)

  if (Number.isFinite(na) && Number.isFinite(nb)) return na !== nb

  return String(a) !== String(b)
}

// Returns percentage diff B vs A, or null if not comparable
const percentDiff = row => {
  if (!row.numeric) return null
  const a = Number(rawValue(row, detailA.value))
  const b = Number(rawValue(row, detailB.value))

  if (!Number.isFinite(a) || !Number.isFinite(b) || a === 0) return null

  return (b - a) / a
}

// ─── Synthesis ────────────────────────────────────────────────────────────
const totalA = computed(() =>
  Number(detailA.value?.customs_duty?.totals?.total_import_cost) || 0,
)
const totalB = computed(() =>
  Number(detailB.value?.customs_duty?.totals?.total_import_cost) || 0,
)

const cheaper = computed(() => {
  if (!isComplete.value) return null
  if (totalA.value <= 0 && totalB.value <= 0) return null
  if (totalA.value === totalB.value) return 'equal'

  return totalA.value < totalB.value ? 'A' : 'B'
})

const cheaperLabel = computed(() => {
  if (!cheaper.value || cheaper.value === 'equal') return ''
  const winner = cheaper.value === 'A' ? detailA.value : detailB.value

  return [winner?.brand?.name, winner?.model?.name ?? winner?.name].filter(Boolean).join(' ')
})

const priceGap = computed(() => Math.abs(totalA.value - totalB.value))
</script>

<template>
  <div>
    <!-- ─── Header ───────────────────────────────────────────── -->
    <div class="d-flex align-center gap-3 mb-2 flex-wrap">
      <VIcon
        icon="tabler-arrows-left-right"
        size="28"
        color="primary"
      />
      <h4 class="text-h4 font-weight-bold mb-0">
        Comparateur de véhicules
      </h4>
    </div>
    <p class="text-body-2 text-medium-emphasis mb-6">
      Comparez deux véhicules côte à côte (taxes UEMOA, transport, total importé)
    </p>

    <!-- ─── Selection row ────────────────────────────────────── -->
    <VCard
      class="mb-6"
      rounded="lg"
    >
      <VCardText>
        <VRow align="center">
          <VCol
            cols="12"
            md="5"
          >
            <p class="text-body-2 font-weight-medium mb-2">
              <VIcon
                icon="tabler-car"
                size="16"
                class="me-1"
              />
              Véhicule A
            </p>
            <VAutocomplete
              v-model="selectedA"
              v-model:search="searchA"
              :items="resultsA"
              :loading="loadingA"
              item-title="title"
              item-value="value"
              return-object
              no-filter
              clearable
              placeholder="Rechercher un véhicule…"
              prepend-inner-icon="tabler-search"
              no-data-text="Aucun véhicule trouvé"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="5"
          >
            <p class="text-body-2 font-weight-medium mb-2">
              <VIcon
                icon="tabler-car"
                size="16"
                class="me-1"
              />
              Véhicule B
            </p>
            <VAutocomplete
              v-model="selectedB"
              v-model:search="searchB"
              :items="resultsB"
              :loading="loadingB"
              item-title="title"
              item-value="value"
              return-object
              no-filter
              clearable
              placeholder="Rechercher un véhicule…"
              prepend-inner-icon="tabler-search"
              no-data-text="Aucun véhicule trouvé"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="2"
            class="d-flex justify-end"
          >
            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-refresh"
              :disabled="!selectedA && !selectedB"
              @click="resetAll"
            >
              Réinitialiser
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- ─── Empty state ──────────────────────────────────────── -->
    <VCard
      v-if="!selectedA || !selectedB"
      rounded="lg"
      variant="tonal"
      color="primary"
      class="text-center"
    >
      <VCardText class="py-12">
        <VAvatar
          size="80"
          color="primary"
          variant="tonal"
          class="mb-4"
        >
          <VIcon
            icon="tabler-arrows-left-right"
            size="40"
          />
        </VAvatar>
        <h5 class="text-h5 font-weight-bold mb-2">
          Sélectionnez deux véhicules à comparer
        </h5>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Utilisez les champs ci-dessus pour choisir un véhicule A et un véhicule B.
        </p>
      </VCardText>
    </VCard>

    <!-- ─── Loading state ────────────────────────────────────── -->
    <VCard
      v-else-if="bothLoading && !isComplete"
      rounded="lg"
      class="text-center"
    >
      <VCardText class="py-10">
        <VProgressCircular
          indeterminate
          color="primary"
          size="48"
        />
        <p class="text-body-2 text-medium-emphasis mt-3 mb-0">
          Chargement des détails…
        </p>
      </VCardText>
    </VCard>

    <!-- ─── Comparison ───────────────────────────────────────── -->
    <template v-if="isComplete">
      <VRow>
        <VCol
          v-for="(p, idx) in [detailA, detailB]"
          :key="idx"
          cols="12"
          lg="6"
        >
          <VCard
            rounded="lg"
            class="h-100"
          >
            <!-- Card header -->
            <VCardText class="text-center pb-2">
              <VAvatar
                size="96"
                color="primary"
                variant="tonal"
                class="mb-3"
              >
                <VIcon
                  icon="tabler-car"
                  size="48"
                />
              </VAvatar>
              <div class="d-flex align-center justify-center gap-2 mb-1">
                <VChip
                  size="x-small"
                  color="primary"
                  variant="tonal"
                >
                  {{ idx === 0 ? 'A' : 'B' }}
                </VChip>
                <h6 class="text-h6 font-weight-bold mb-0">
                  {{ p?.brand?.name }} {{ p?.model?.name ?? p?.name }}
                </h6>
              </div>
              <p
                v-if="p?.vehicle?.year"
                class="text-body-2 text-medium-emphasis mb-0"
              >
                {{ p.vehicle.year }}
                <span v-if="p?.vehicle?.chassis_number">
                  · {{ p.vehicle.chassis_number }}
                </span>
              </p>
            </VCardText>

            <VDivider />

            <!-- Sections -->
            <VCardText>
              <div
                v-for="section in sections"
                :key="section.title"
                class="mb-5"
              >
                <div class="d-flex align-center gap-2 mb-3">
                  <VIcon
                    :icon="section.icon"
                    size="18"
                    color="primary"
                  />
                  <span class="text-body-1 font-weight-bold">{{ section.title }}</span>
                </div>

                <div
                  v-for="row in section.rows"
                  :key="row.label"
                  class="py-2"
                  style="border-bottom: 1px solid rgba(0,0,0,0.06)"
                >
                  <div class="d-flex align-center justify-space-between gap-3">
                    <span class="text-body-2 text-medium-emphasis">{{ row.label }}</span>
                    <span
                      class="text-body-2 font-weight-medium text-end"
                      :class="[
                        valuesDiffer(row) ? 'text-warning' : '',
                        row.emphasis ? 'font-weight-bold' : '',
                        row.mono ? 'font-mono' : '',
                      ]"
                      style="word-break: break-word"
                    >
                      {{ displayValue(row, p) }}
                    </span>
                  </div>
                  <!-- Percent diff caption — only on B card so we don't double-show -->
                  <div
                    v-if="idx === 1 && percentDiff(row) !== null && valuesDiffer(row)"
                    class="d-flex justify-end mt-1"
                  >
                    <span
                      class="text-caption"
                      :class="percentDiff(row) > 0 ? 'text-error' : 'text-success'"
                    >
                      {{ percentDiff(row) > 0 ? '+' : '' }}{{ fmtPercent(percentDiff(row)) }} vs A
                    </span>
                  </div>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- ─── Synthesis card ─────────────────────────────────── -->
      <VCard
        class="mt-6"
        rounded="lg"
      >
        <VCardText>
          <div class="d-flex align-center gap-2 mb-4">
            <VIcon
              icon="tabler-trophy"
              size="20"
              color="success"
            />
            <span class="text-h6 font-weight-bold">
              Quel véhicule a le coût total le plus bas ?
            </span>
          </div>

          <VRow>
            <VCol
              cols="12"
              md="6"
            >
              <div
                class="pa-4 rounded-lg h-100"
                :class="cheaper === 'A' ? 'border-success-strong' : 'border-soft'"
                :style="cheaper === 'A'
                  ? 'background: rgba(40, 199, 111, 0.08); border: 1.5px solid rgb(40, 199, 111)'
                  : 'background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.08)'"
              >
                <div class="d-flex align-center gap-2 mb-2">
                  <VChip
                    size="small"
                    color="primary"
                    variant="tonal"
                  >
                    A
                  </VChip>
                  <span class="text-body-2 font-weight-medium">
                    {{ detailA?.brand?.name }} {{ detailA?.model?.name ?? detailA?.name }}
                  </span>
                  <VIcon
                    v-if="cheaper === 'A'"
                    icon="tabler-circle-check-filled"
                    color="success"
                    size="20"
                    class="ms-auto"
                  />
                </div>
                <p class="text-h4 font-weight-bold mb-0">
                  {{ totalA > 0 ? fmtMoney(totalA, 'FCFA') : '—' }}
                </p>
                <p class="text-caption text-medium-emphasis mb-0 mt-1">
                  Coût total importé
                </p>
              </div>
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <div
                class="pa-4 rounded-lg h-100"
                :style="cheaper === 'B'
                  ? 'background: rgba(40, 199, 111, 0.08); border: 1.5px solid rgb(40, 199, 111)'
                  : 'background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.08)'"
              >
                <div class="d-flex align-center gap-2 mb-2">
                  <VChip
                    size="small"
                    color="primary"
                    variant="tonal"
                  >
                    B
                  </VChip>
                  <span class="text-body-2 font-weight-medium">
                    {{ detailB?.brand?.name }} {{ detailB?.model?.name ?? detailB?.name }}
                  </span>
                  <VIcon
                    v-if="cheaper === 'B'"
                    icon="tabler-circle-check-filled"
                    color="success"
                    size="20"
                    class="ms-auto"
                  />
                </div>
                <p class="text-h4 font-weight-bold mb-0">
                  {{ totalB > 0 ? fmtMoney(totalB, 'FCFA') : '—' }}
                </p>
                <p class="text-caption text-medium-emphasis mb-0 mt-1">
                  Coût total importé
                </p>
              </div>
            </VCol>
          </VRow>

          <div
            v-if="cheaper && cheaper !== 'equal' && priceGap > 0"
            class="mt-4 d-flex align-center gap-2 flex-wrap"
          >
            <VIcon
              icon="tabler-info-circle"
              size="16"
              color="success"
            />
            <span class="text-body-2">
              <strong>{{ cheaperLabel }}</strong> est moins cher de
              <strong class="text-success">{{ fmtMoney(priceGap, 'FCFA') }}</strong>
              <span
                v-if="totalA > 0 && totalB > 0"
                class="text-medium-emphasis"
              >
                ({{ fmtPercent(priceGap / Math.max(totalA, totalB)) }})
              </span>
            </span>
          </div>

          <div
            v-else-if="cheaper === 'equal'"
            class="mt-4 d-flex align-center gap-2"
          >
            <VIcon
              icon="tabler-equal"
              size="16"
              color="info"
            />
            <span class="text-body-2">Les deux véhicules ont le même coût total importé.</span>
          </div>

          <VDivider class="my-4" />

          <p class="text-caption text-medium-emphasis mb-0">
            Cette comparaison est basée sur les barèmes UEMOA et les coûts saisis.
            Ne tient pas compte des coûts d'usage (carburant, entretien, assurance véhicule).
          </p>
        </VCardText>
      </VCard>
    </template>
  </div>
</template>

<style scoped>
.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.85em;
}
</style>
