<script setup>
import { useRouter } from 'vue-router'

definePage({ meta: { layout: 'default' } })

const router = useRouter()
const DRAFT_KEY = 'logixtix:calculator:draft'

const snackbar = reactive({ show: false, color: 'success', message: '' })
const notify = (message, color = 'success') => {
  snackbar.message = message
  snackbar.color = color
  snackbar.show = true
}

// ─── Constants ────────────────────────────────────────────────────────────
// TODO: fetch from a forex service (e.g. /forex/rates) — kept hardcoded for now.
const USD_XOF = 595
const EUR_XOF = 656

// ─── Mode toggle ──────────────────────────────────────────────────────────
const mode = ref('vehicle') // 'vehicle' | 'goods'

// ─── Shared HS code resolution helpers ────────────────────────────────────
const truncate = (str, max = 80) => {
  if (!str) return ''

  return str.length > max ? `${str.slice(0, max)}…` : str
}

const formatHsCodeItem = c => ({
  title: `${c.code} — ${truncate(c.description ?? '', 80)}`,
  value: c.id,
  raw: c,
})

// Pretty-print an HS code (e.g. "8418100000" → "8418.10.00.00")
const formatHsCodePretty = code => {
  if (!code) return ''
  const digits = String(code).replace(/\D/g, '')
  if (digits.length <= 4) return digits
  if (digits.length <= 6) return `${digits.slice(0, 4)}.${digits.slice(4)}`
  if (digits.length <= 8) return `${digits.slice(0, 4)}.${digits.slice(4, 6)}.${digits.slice(6)}`

  return `${digits.slice(0, 4)}.${digits.slice(4, 6)}.${digits.slice(6, 8)}.${digits.slice(8)}`
}

const buildHsCodeSearcher = (resultsRef, loadingRef) => {
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
          `/hs-codes?search=${encodeURIComponent(val)}&only_active=1&with_rates=1&per_page=20`,
        )
        const list = res.data ?? res

        resultsRef.value = (Array.isArray(list) ? list : []).map(formatHsCodeItem)
      }
      catch {
        resultsRef.value = []
      }
      finally {
        loadingRef.value = false
      }
    }, 300)
  }
}

// Fetch full detail (with rates) when a code is picked from a partial list.
const fetchHsCodeDetail = async id => {
  if (!id) return null
  try {
    const res = await $api(`/hs-codes/${id}`)

    return res.data ?? res
  }
  catch {
    return null
  }
}

// Currency → XOF
function toXof(amount, currency) {
  const n = Number(amount) || 0
  if (currency === 'USD') return n * USD_XOF
  if (currency === 'EUR') return n * EUR_XOF

  return n
}

// ─── Formatting ───────────────────────────────────────────────────────────
const fmt = n => `${Math.round(Number(n) || 0).toLocaleString('fr-FR')} FCFA`
const fmtPct = v => {
  const n = Number(v ?? 0)

  return `${n.toLocaleString('fr-FR', { maximumFractionDigits: 2 })}%`
}

// ─── Mode "Véhicule" state ────────────────────────────────────────────────
const vehicle = reactive({
  type: 'tourisme', // tourisme|utilitaire|pickup|camion|bus|agricole|tracteur|moto
  fuel: 'essence', // essence | diesel | hybride | electrique
  displacement: '', // cylindrée cm³ (cc)
  year: '',
  condition: 'OCCASION',
  origin: 'EUROPE',
  fobAmount: '',
  fobCurrency: 'XOF',
  poids: '', // tonnes (TPI ; aussi PTAC pour les camions)
  cv: '', // puissance fiscale (TVM + seuil ADA)
  seats: '', // bus
  powerKw: '', // tracteur (kW)
  tractorSubtype: 'agricole', // essieu_simple|routier_semi_remorque|chenilles|agricole
  declarationDate: new Date().toISOString().slice(0, 10), // pilote l'âge + la proration TVM
})

// Front type → resolver family (classification TEC).
const familyForType = type => ({
  tourisme: 'passenger_car', suv_tourisme: 'passenger_car',
  utilitaire: 'goods_vehicle', pickup: 'goods_vehicle', camion: 'goods_vehicle', btp: 'goods_vehicle',
  bus: 'bus', moto: 'motorcycle', agricole: 'tractor', tracteur: 'tractor',
}[type] ?? null)
const vehicleFamily = computed(() => familyForType(vehicle.type))

// Clear the per-vehicle inputs so a previous VIN / selection (poids, CV,
// date de déclaration, places, kW…) never leaks into the next one. The
// type stays (user-controlled); the incoming lookup then fills what it can.
const resetVehicleInputs = () => {
  Object.assign(vehicle, {
    fuel: 'essence', displacement: '', year: '', condition: 'OCCASION',
    origin: 'EUROPE', fobAmount: '', fobCurrency: 'XOF',
    poids: '', cv: '', seats: '', powerKw: '', tractorSubtype: 'agricole',
    declarationDate: new Date().toISOString().slice(0, 10),
  })
}

const vehicleSearchTab = ref('vin') // 'vin' | 'product'

// ─── "Par véhicule" — 3 selectors (brand → model → year) + free search ─────
const brandOptions = ref([])
const brandSelected = ref(null)
const brandLoading = ref(false)
const modelOptions = ref([])
const modelSelected = ref(null)
const modelLoading = ref(false)
const yearOptions = ref([])
const yearSelected = ref(null)
const yearLoading = ref(false)

const loadBrands = async () => {
  if (brandOptions.value.length) return
  brandLoading.value = true
  try {
    const res = await $api('/brands?per_page=200')
    const list = res?.data ?? res
    brandOptions.value = (Array.isArray(list) ? list : [])
      .filter(b => b?.name)
      .map(b => ({ title: b.name, value: b.id, raw: b }))
  }
  catch { brandOptions.value = [] }
  finally { brandLoading.value = false }
}

// Load brands once when the user opens the "Par véhicule" tab so we don't
// pay the request on first paint.
watch(vehicleSearchTab, val => { if (val === 'product') loadBrands() })

// Filtered model list: every product attached to the selected brand —
// we collapse duplicates by model name so each appears once.
watch(brandSelected, async brand => {
  modelSelected.value = null
  yearSelected.value = null
  modelOptions.value = []
  yearOptions.value = []
  if (!brand?.title) return
  modelLoading.value = true
  try {
    const res = await $api(
      `/products?type=vehicle&brand=${encodeURIComponent(brand.title)}&per_page=200`,
    )
    const list = res?.data ?? res
    const seen = new Map()
    for (const p of (Array.isArray(list) ? list : [])) {
      const name = p?.model?.name ?? p?.vehicleModel?.name ?? p?.name
      if (!name || seen.has(name)) continue
      seen.set(name, { title: name, value: name, raw: p })
    }
    modelOptions.value = [...seen.values()]
  }
  catch { modelOptions.value = [] }
  finally { modelLoading.value = false }
})

// Available years for the selected brand+model pair.
watch(modelSelected, async model => {
  yearSelected.value = null
  yearOptions.value = []
  if (!model?.title || !brandSelected.value?.title) return
  yearLoading.value = true
  try {
    const res = await $api(
      `/products?type=vehicle&brand=${encodeURIComponent(brandSelected.value.title)}&search=${encodeURIComponent(model.title)}&per_page=200`,
    )
    const list = res?.data ?? res
    const years = new Set()
    for (const p of (Array.isArray(list) ? list : [])) {
      const y = p?.vehicle?.year
      if (y) years.add(Number(y))
    }
    yearOptions.value = [...years].sort((a, b) => b - a).map(y => ({ title: String(y), value: y }))
  }
  catch { yearOptions.value = [] }
  finally { yearLoading.value = false }
})

// Once all three selectors are picked, fetch the matching product and
// reuse applyProduct() so the regular pre-fill path runs.
watch([brandSelected, modelSelected, yearSelected], async ([brand, model, year]) => {
  if (!brand?.title || !model?.title || !year?.value) return
  try {
    const res = await $api(
      `/products?type=vehicle&brand=${encodeURIComponent(brand.title)}&search=${encodeURIComponent(model.title)}&per_page=20`,
    )
    const list = res?.data ?? res
    const arr = Array.isArray(list) ? list : []
    const match = arr.find(p => Number(p?.vehicle?.year) === Number(year.value)) ?? arr[0]
    if (!match) return
    selectedProduct.value = {
      title: [match.brand?.name, match.model?.name ?? match.name, match.vehicle?.year].filter(Boolean).join(' '),
      value: match.id,
      raw: match,
    }
    await applyProduct(selectedProduct.value)
  }
  catch { /* silent */ }
})

// Chassis lookup — searches the vehicles table by chassis_number and
// pre-fills displacement / year / FOB from the persisted vehicle record.
const chassisInput = ref('')
const chassisLoading = ref(false)
const chassisError = ref('')
const chassisVehicle = ref(null) // matched vehicle (raw) after a successful search
const chassisSource = ref(null) // 'internal' | 'nhtsa' — provenance of the match

const searchByChassis = async () => {
  const value = String(chassisInput.value ?? '').trim()
  chassisError.value = ''
  chassisVehicle.value = null
  chassisSource.value = null
  if (value.length < 5) {
    chassisError.value = 'Saisis au moins 5 caractères.'
    return
  }
  resetVehicleInputs() // repart d'une base propre pour ce nouveau VIN
  chassisLoading.value = true
  try {
    // 1) Internal vehicles table first — it carries the customs valuation
    //    (FOB / CIF), which the public VIN APIs never provide.
    const res = await $api(`/vehicles?search=${encodeURIComponent(value)}&per_page=5`)
    const list = res?.data ?? res
    const matches = Array.isArray(list) ? list : []
    const upper = value.toUpperCase()
    const exact = matches.find(v => String(v.chassis_number ?? '').toUpperCase() === upper)
    const found = exact ?? matches[0] ?? null
    if (found) {
      chassisVehicle.value = found
      chassisSource.value = 'internal'
      await applyChassisVehicle(found)
      return
    }

    // 2) Fallback — decode the VIN through our NHTSA proxy to at least
    //    pre-fill the technical specs (fuel, displacement, year, type).
    await decodeViaNhtsa(value)
  }
  catch {
    chassisError.value = 'Recherche impossible — réessaie dans un instant.'
  }
  finally {
    chassisLoading.value = false
  }
}

const decodeViaNhtsa = async vin => {
  try {
    const res = await $api(`/vehicles/vin/${encodeURIComponent(vin)}`)
    const d = res?.data ?? res
    if (!d) {
      chassisError.value = 'Aucun véhicule trouvé pour ce numéro de châssis.'
      return
    }
    applyNhtsaDecode(d)
    chassisSource.value = 'nhtsa'
    chassisVehicle.value = {
      chassis_number: vin,
      brand: d.make ? { name: d.make } : null,
      model: d.model ? { name: d.model } : null,
      year: vehicle.year || d.year || null, // corrected year (30-year cycle)
    }
  }
  catch (err) {
    // 404 = decoded but nothing usable; 502 = NHTSA unreachable. Either way
    // the user can still fill the form manually.
    chassisError.value = err?.response?.status === 404 || err?.status === 404
      ? 'Ce VIN n\'est pas reconnu (souvent le cas pour les véhicules JDM / hors marché US). Renseigne les champs manuellement.'
      : 'Aucun véhicule trouvé pour ce numéro de châssis.'
  }
}

// Pre-fill the form from a normalized NHTSA decode. We only set fields the
// API actually returned, so it never clobbers a value the user already typed.
const applyNhtsaDecode = d => {
  if (d.fuel) vehicle.fuel = d.fuel
  if (d.displacement) vehicle.displacement = Number(d.displacement)
  // VIN year letters cycle every 30 years (e.g. "W" = 1998 OR 2028). NHTSA
  // defaults to the most recent, which is wrong for a used import. A model
  // year in the future is impossible here → roll it back one 30-year cycle.
  if (d.year) {
    const nowYear = new Date().getFullYear()
    let yr = Number(d.year)
    if (yr > nowYear) yr -= 30
    vehicle.year = String(yr)
  }
  if (d.type === 'MO' || (d.displacement && d.displacement <= 250)) vehicle.type = 'moto'
  else if (d.type === 'UT') vehicle.type = 'utilitaire'
  else if (d.type === 'VP') vehicle.type = 'tourisme'

  // Re-resolve the HS code from the freshly decoded attributes.
  resolveHsFromVehicleState()
}

const applyChassisVehicle = async v => {
  if (!v) return

  if (v.engine_displacement) vehicle.displacement = Number(v.engine_displacement)
  if (v.year) vehicle.year = String(v.year)

  // Customs CIF (post-depreciation) is the most accurate FOB proxy; fall back
  // to the raw official_rate when no customs valuation has been recorded yet.
  const fobCandidate = Number(
    v.customs_duty?.customs_value ?? v.official_rate ?? 0,
  )
  if (fobCandidate > 0) {
    vehicle.fobAmount = fobCandidate
    vehicle.fobCurrency = 'XOF'
  }

  // Condition — when the chassis already has a customs evaluation on file or
  // a depreciation rate strictly > 0, the vehicle is necessarily used.
  if (v.customs_duty?.customs_value || v.evaluation_date || Number(v.depreciation_rate) > 0) {
    vehicle.condition = 'OCCASION'
  }

  // Provenance — derived from the country.continent we now expose alongside
  // the brand/model relations on the vehicle resource.
  const continent = String(v.country?.continent ?? '').toUpperCase()
  if (continent) {
    vehicle.origin = ['EUROPE', 'AMÉRIQUE', 'ASIE'].includes(continent) ? continent : 'AUTRES'
  }

  // Type — light heuristic: very small displacements are motorbikes, the rest
  // defaults to "tourisme". Trucks/buses keep the manual selector.
  if (vehicle.displacement && vehicle.displacement <= 250) {
    vehicle.type = 'moto'
  }
  else if (!vehicle.type) {
    vehicle.type = 'tourisme'
  }

  // Auto-resolve the TEC CEDEAO HS code from current vehicle state.
  await resolveHsFromVehicleState()
}

// Shared HS-code resolver: from the current (type, fuel, displacement,
// condition) state, pick the TEC CEDEAO position and load its rates. Used by
// the catalogue path, the chassis path and the NHTSA fallback alike.
const resolveHsFromVehicleState = async () => {
  const tecFamily = tecFamilyForType(vehicle.type)
  if (!tecFamily) return // bus/agricole/tracteur: classification déférée
  const prefix = resolveVehicleHsPrefix(tecFamily, vehicle.fuel, vehicle.displacement)
  if (!prefix) return
  try {
    const res = await $api(`/hs-codes?search=${prefix}&only_active=1&with_rates=1&per_page=20`)
    const list = res.data ?? res
    if (!Array.isArray(list) || list.length === 0) return
    const wantOccasion = String(vehicle.condition ?? '').toUpperCase() === 'OCCASION'
    const matchesCondition = c => {
      const desc = String(c.description ?? '').toLowerCase()
      return wantOccasion
        ? /occasion|usag[eé]|immatricul/.test(desc)
        : /\bneuf|neufs\b/.test(desc)
    }
    const chosen = list.find(matchesCondition) ?? list[0]
    const item = formatHsCodeItem(chosen)
    vehicleHsSelected.value = item
    vehicleHsDetail.value = chosen
  }
  catch { /* silent */ }
}

const chassisVehicleLabel = computed(() => {
  const v = chassisVehicle.value
  if (!v) return ''
  const parts = [v.brand?.name, v.model?.name, v.year].filter(Boolean)
  return parts.join(' ') || v.chassis_number
})

// Product autocomplete (existing brand/model search)
const productSearch = ref('')
const productResults = ref([])
const productLoading = ref(false)
const selectedProduct = ref(null)

let productTimer = null
watch(productSearch, val => {
  clearTimeout(productTimer)
  if (!val || val.length < 2) {
    productResults.value = []

    return
  }
  productLoading.value = true
  productTimer = setTimeout(async () => {
    try {
      const res = await $api(`/products?type=vehicle&search=${encodeURIComponent(val)}&per_page=20`)
      const list = res.data ?? res
      productResults.value = (Array.isArray(list) ? list : []).map(p => ({
        title: [p.brand?.name, p.model?.name ?? p.name, p.vehicle?.year].filter(Boolean).join(' '),
        value: p.id,
        raw: p,
      }))
    }
    catch {
      productResults.value = []
    }
    finally {
      productLoading.value = false
    }
  }, 300)
})

// Highlight the user's search tokens inside a suggestion label so they can
// see WHY a row matched (e.g. "**Ford** **Edge** 2019"). Output is fed to
// v-html, so we HTML-escape first and only inject our own <strong> tags.
const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, c => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[c]))
const highlightMatch = (text, query) => {
  const safe = escapeHtml(text)
  const tokens = String(query ?? '').trim().split(/\s+/).filter(Boolean)
  if (!tokens.length) return safe
  const pattern = tokens
    .map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))
    .join('|')
  return safe.replace(new RegExp(`(${pattern})`, 'gi'), '<strong>$1</strong>')
}

// HS code autocomplete for vehicle mode
const vehicleHsSearch = ref('')
const vehicleHsResults = ref([])
const vehicleHsLoading = ref(false)
const vehicleHsSelected = ref(null)
const vehicleHsDetail = ref(null) // full HS code with current_tariff
const vehicleHsLoadingDetail = ref(false)

watch(vehicleHsSearch, buildHsCodeSearcher(vehicleHsResults, vehicleHsLoading))

watch(vehicleHsSelected, async val => {
  if (!val?.value) {
    vehicleHsDetail.value = null

    return
  }

  // Prefer rates already on the search result if present
  if (val.raw?.current_tariff?.rates) {
    vehicleHsDetail.value = val.raw

    return
  }
  vehicleHsLoadingDetail.value = true
  try {
    vehicleHsDetail.value = await fetchHsCodeDetail(val.value)
  }
  finally {
    vehicleHsLoadingDetail.value = false
  }
})

// Map (type, fuel, displacement) to the appropriate 6-digit TEC CEDEAO
// prefix for chapter 87 (motor vehicles). The backend `search` parameter
// matches on either the code (LIKE %prefix%) or the description, so passing
// a numeric prefix returns every position under that family.
const resolveVehicleHsPrefix = (type, fuel, cyl) => {
  const c = Number(cyl) || 0
  if (type === 'MO') {
    if (c <= 250) return '871120'
    if (c <= 500) return '871130'
    return '871140'
  }
  if (type === 'UT') {
    // Camions / tracteurs routiers / utilitaires
    return fuel === 'diesel' ? '870421' : '870431'
  }
  // Voiture particulière (VP) — fallback
  if (fuel === 'electrique') return '870380'
  if (fuel === 'hybride')    return '870340'
  if (fuel === 'diesel') {
    if (c <= 1500) return '870331'
    if (c <= 2500) return '870332'
    return '870333'
  }
  // essence
  if (c <= 1000) return '870321'
  if (c <= 1500) return '870322'
  if (c <= 3000) return '870323'
  return '870324'
}

// Apply selected product to vehicle form, then attempt auto-resolve of HS code.
const applyProduct = async product => {
  if (!product?.raw) return
  const p = product.raw
  resetVehicleInputs() // repart d'une base propre pour ce nouveau véhicule

  if (p.vehicle?.fuel) {
    const f = String(p.vehicle.fuel).toLowerCase()
    vehicle.fuel = f.includes('diesel') ? 'diesel'
      : f.includes('electric') || f.includes('élec') ? 'electrique'
      : f.includes('hybrid') ? 'hybride'
      : 'essence'
  }
  if (p.vehicle?.engine_displacement) vehicle.displacement = Number(p.vehicle.engine_displacement)
  if (p.vehicle?.year) vehicle.year = String(p.vehicle.year)

  // Pre-fill the weight (for TPI) from the product's physical data, in tonnes.
  // Prefer gross weight (matches the DDU "poids brut"), fall back to net.
  const weightKg = Number(p.physical?.weight_gross ?? p.physical?.weight_net ?? 0)
  if (weightKg > 0) vehicle.poids = Math.round((weightKg / 1000) * 1000) / 1000

  // Pre-fill FOB from the aggregated customs valuation (post-depreciation)
  // exposed at the product level, falling back to the per-vehicle CIF.
  const fobCandidate = Number(
    p.customs_duty?.customs_value ?? p.vehicle?.official_rate ?? 0,
  )
  if (fobCandidate > 0) {
    vehicle.fobAmount = fobCandidate
    vehicle.fobCurrency = 'XOF'
  }

  if (p.origin?.country?.continent) {
    const c = String(p.origin.country.continent).toUpperCase()
    vehicle.origin = ['EUROPE', 'AMÉRIQUE', 'ASIE'].includes(c) ? c : 'AUTRES'
  }

  // Auto-resolve HS code by TEC CEDEAO prefix from the current state.
  await resolveHsFromVehicleState()
}

// Vehicle calculation (live)
const vehicleFobXof = computed(() => toXof(vehicle.fobAmount, vehicle.fobCurrency))
const vehicleRates = computed(() => vehicleHsDetail.value?.current_tariff?.rates ?? null)

// ─── Mode "Marchandise" state ─────────────────────────────────────────────
// La valeur en douane (CAF) est saisie directement — c'est exactement la base
// d'imposition de la DDU, sans estimation de fret hasardeuse.
const goods = reactive({
  caf: '',           // valeur en douane (CAF) en XOF
  poids: '',         // poids brut en kg (pour la TPI)
  transport: 'port', // 'port' (conteneurs → DPS) | 'route' (pas de DPS)
  containers: 1,     // nb de conteneurs au scanner
})

const goodsHsSearch = ref('')
const goodsHsResults = ref([])
const goodsHsLoading = ref(false)
const goodsHsSelected = ref(null)
const goodsHsDetail = ref(null)
const goodsHsLoadingDetail = ref(false)

watch(goodsHsSearch, buildHsCodeSearcher(goodsHsResults, goodsHsLoading))

watch(goodsHsSelected, async val => {
  if (!val?.value) {
    goodsHsDetail.value = null

    return
  }
  if (val.raw?.current_tariff?.rates) {
    goodsHsDetail.value = val.raw

    return
  }
  goodsHsLoadingDetail.value = true
  try {
    goodsHsDetail.value = await fetchHsCodeDetail(val.value)
  }
  finally {
    goodsHsLoadingDetail.value = false
  }
})

// Taux issus du code SH résolu — affichés et AJUSTABLES manuellement (fallback
// si le tarif en base est incomplet, fréquent hors boissons). En pourcentage.
const goodsDdRate = ref(null)
const goodsAdaRate = ref(null)
const goodsTvaRate = ref(null)

// (Re)synchronise les taux éditables dès qu'un code SH (et son tarif) est chargé.
watch(goodsHsDetail, detail => {
  const r = detail?.current_tariff?.rates
  goodsDdRate.value = r?.dd != null ? Number(r.dd) : null
  goodsAdaRate.value = r?.da != null ? Number(r.da) : 0
  goodsTvaRate.value = r?.vat != null ? Number(r.vat) : 18
})

const goodsCafXof = computed(() => Number(goods.caf) || 0)

// ── Moteur douanier réel (backend = source de vérité pour les marchandises) ──
const goodsEngine = ref(null)
const goodsEngineLoading = ref(false)

// Champs requis avant tout montant fiable (finance : ne jamais deviner).
const goodsMissing = computed(() => {
  const m = []
  if (goodsCafXof.value <= 0) m.push('la valeur en douane (CAF)')
  if (goods.poids === '' || Number(goods.poids) < 0) m.push('le poids brut (kg)')
  if (goodsDdRate.value == null || goodsDdRate.value === '') m.push('le taux de droit de douane (DD)')

  return m
})

let goodsEngineTimer = null
const runGoodsEngine = () => {
  clearTimeout(goodsEngineTimer)
  if (goodsMissing.value.length) {
    goodsEngine.value = null

    return
  }
  goodsEngineTimer = setTimeout(async () => {
    goodsEngineLoading.value = true
    try {
      const body = {
        caf: Math.round(goodsCafXof.value),
        poids_kg: Number(goods.poids) || 0,
        dd_rate: Number(goodsDdRate.value || 0) / 100,
        ada_rate: Number(goodsAdaRate.value || 0) / 100,
        tva_rate: Number(goodsTvaRate.value ?? 18) / 100,
        containers: goods.transport === 'port' ? (Number(goods.containers) || 0) : 0,
      }
      const res = await $api('/customs/goods/calculate', { method: 'POST', body })
      goodsEngine.value = res?.data ?? res
    }
    catch {
      goodsEngine.value = null
    }
    finally {
      goodsEngineLoading.value = false
    }
  }, 350)
}

watch(
  () => [
    mode.value, goodsCafXof.value, goods.poids, goods.transport, goods.containers,
    goodsDdRate.value, goodsAdaRate.value, goodsTvaRate.value,
  ],
  () => { if (mode.value === 'goods') runGoodsEngine() },
  { immediate: true },
)

// Coût total rendu marchandise = valeur CAF + total droits & taxes.
const goodsTotalRendu = computed(() =>
  goodsEngine.value ? goodsCafXof.value + goodsEngine.value.total : 0,
)

// Vehicle CIF (treat FOB as CIF if user only enters FOB; freight not modelled in vehicle mode)
// Vehicle-mode extra costs (visible in the sourcing breakdown). They
// flow into the CIF/total when present but stay 0 by default so the
// existing TEC CEDEAO calculation isn't perturbed unless the user
// types something.
const vehicleFreight = ref(0)
const vehicleTransitWarehousing = ref(0)

const vehicleCif = computed(() => vehicleFobXof.value + (Number(vehicleFreight.value) || 0))

// ─── Active mode aggregations (shared bits between the two modes) ──────────
const activeHs = computed(() =>
  mode.value === 'vehicle' ? vehicleHsDetail.value : goodsHsDetail.value,
)
// Valeur CAF affichée dans la base de calcul : véhicule = FOB + fret ;
// marchandise = la valeur en douane saisie directement.
const activeCif = computed(() =>
  mode.value === 'vehicle' ? vehicleCif.value : goodsCafXof.value,
)

// Classification refs declared before the engine watcher (which reads them on
// its immediate run) to avoid a temporal-dead-zone error.
const vehicleClassification = ref(null) // { code, designation, rates } | null
const vehicleClassificationStatus = ref(null) // ok|needs_more_info|not_found|ambiguous

// ─── Real customs engine (backend = source of truth for vehicle amounts) ──
// VCAF passed to the engine = CIF (FOB + freight). The backend applies the
// age-based abattement, the cascade and the national taxes (TPI/TVM/BIC…).
const vehicleEngine = ref(null)
const vehicleEngineLoading = ref(false)

// Fields the engine needs before it can return a 100 %-reliable amount.
// Until they're all present we show nothing (finance: never guess).
const vehicleMissing = computed(() => {
  const m = []
  if (vehicleCif.value <= 0) m.push('la valeur FOB / CAF')
  if (!vehicle.year) m.push("l'année de fabrication")
  if (!vehicle.type) m.push('le type de véhicule')
  if (vehicle.poids === '' || Number(vehicle.poids) <= 0) m.push('le poids (en tonnes)')
  if (vehicle.cv === '' || Number(vehicle.cv) <= 0) m.push('la puissance fiscale (CV)')

  return m
})

let engineTimer = null
const runVehicleEngine = () => {
  clearTimeout(engineTimer)
  if (vehicleMissing.value.length) {
    vehicleEngine.value = null

    return
  }
  engineTimer = setTimeout(async () => {
    vehicleEngineLoading.value = true
    try {
      const body = {
        vcaf: Math.round(vehicleCif.value),
        annee_fabrication: Number(vehicle.year),
        energie: vehicle.fuel,
        condition: String(vehicle.condition).toUpperCase() === 'NEUF' ? 'neuf' : 'occasion',
        // Un tracteur AGRICOLE reste exonéré ; un tracteur routier est taxé.
        type: vehicle.type === 'tracteur' && vehicle.tractorSubtype === 'agricole'
          ? 'agricole'
          : vehicle.type,
        poids: Number(vehicle.poids),
        cv: Number(vehicle.cv),
        cc: Number(vehicle.displacement) || 0,
        reference_date: vehicle.declarationDate || undefined,
      }
      // Prefer the DD rate from the resolved official TEC code when we have one.
      const ddPct = vehicleClassification.value?.rates?.dd ?? vehicleRates.value?.dd
      if (ddPct != null) body.dd_rate = Number(ddPct) / 100
      const res = await $api('/customs/vehicles/calculate', { method: 'POST', body })
      vehicleEngine.value = res?.data ?? res
    }
    catch {
      vehicleEngine.value = null
    }
    finally {
      vehicleEngineLoading.value = false
    }
  }, 350)
}

watch(
  () => [
    mode.value, vehicle.type, vehicle.fuel, vehicle.condition, vehicle.displacement,
    vehicle.year, vehicle.poids, vehicle.cv, vehicle.declarationDate, vehicleCif.value,
    vehicleClassification.value?.rates?.dd, vehicleRates.value?.dd,
  ],
  () => { if (mode.value === 'vehicle') runVehicleEngine() },
  { immediate: true },
)

// Coût total rendu = valeur CAF + droits/taxes + transit & magasinage.
const vehicleTotalRendu = computed(() => {
  if (!vehicleEngine.value) return 0

  return vehicleCif.value + vehicleEngine.value.total + (Number(vehicleTransitWarehousing.value) || 0)
})

// ── Classification TEC officielle (resolver déterministe backend) ──
// Renvoie le code 10 chiffres exact + ses taux ; le DD alimente le moteur.
// (refs déclarées plus haut pour le moteur)
let classifyTimer = null
const resolveClassification = () => {
  clearTimeout(classifyTimer)
  const family = vehicleFamily.value
  if (!family) {
    vehicleClassification.value = null
    vehicleClassificationStatus.value = null

    return
  }
  classifyTimer = setTimeout(async () => {
    const params = new URLSearchParams({ family })
    if (vehicle.fuel) params.set('fuel', vehicle.fuel)
    params.set('condition', String(vehicle.condition).toUpperCase() === 'OCCASION' ? 'usage' : 'neuf')
    if (vehicle.displacement) params.set('cylindree', String(Number(vehicle.displacement)))
    if (family === 'bus' && vehicle.seats) params.set('seats', String(Number(vehicle.seats)))
    if (family === 'goods_vehicle' && vehicle.poids) params.set('weight', String(Math.round(Number(vehicle.poids) * 1000)))
    if (family === 'tractor') {
      params.set('tractor_subtype', vehicle.tractorSubtype)
      if (vehicle.powerKw) params.set('power_kw', String(Number(vehicle.powerKw)))
    }
    try {
      const res = await $api(`/customs/vehicles/resolve?${params.toString()}`)
      vehicleClassification.value = res?.data ?? res
      vehicleClassificationStatus.value = 'ok'
    }
    catch (err) {
      const errors = err?.data?.errors ?? err?.response?._data?.errors ?? null
      vehicleClassification.value = null
      vehicleClassificationStatus.value = errors?.status ?? 'not_found'
    }
  }, 350)
}
watch(
  () => [
    mode.value, vehicle.type, vehicle.fuel, vehicle.condition, vehicle.displacement,
    vehicle.seats, vehicle.poids, vehicle.powerKw, vehicle.tractorSubtype,
  ],
  () => { if (mode.value === 'vehicle') resolveClassification() },
  { immediate: true },
)

// ─── Reset ────────────────────────────────────────────────────────────────
const reset = () => {
  Object.assign(vehicle, {
    type: 'tourisme', fuel: 'essence', displacement: '', year: '',
    condition: 'OCCASION', origin: 'EUROPE', fobAmount: '', fobCurrency: 'XOF',
    poids: '', cv: '', seats: '', powerKw: '', tractorSubtype: 'agricole',
    declarationDate: new Date().toISOString().slice(0, 10),
  })
  vehicleFreight.value = 0
  vehicleTransitWarehousing.value = 0
  vehicleEngine.value = null
  vehicleClassification.value = null
  vehicleClassificationStatus.value = null
  Object.assign(goods, {
    caf: '', poids: '', transport: 'port', containers: 1,
  })
  goodsEngine.value = null
  goodsDdRate.value = null
  goodsAdaRate.value = null
  goodsTvaRate.value = null
  vehicleHsSearch.value = ''
  vehicleHsResults.value = []
  vehicleHsSelected.value = null
  vehicleHsDetail.value = null
  goodsHsSearch.value = ''
  goodsHsResults.value = []
  goodsHsSelected.value = null
  goodsHsDetail.value = null
  productSearch.value = ''
  productResults.value = []
  selectedProduct.value = null
  chassisInput.value = ''
  chassisVehicle.value = null
  chassisError.value = ''
  chassisSource.value = null
  brandSelected.value = null
  modelSelected.value = null
  yearSelected.value = null
  modelOptions.value = []
  yearOptions.value = []
}

// ─── Action handlers ──────────────────────────────────────────────────────
const buildCalculationSnapshot = () => {
  const hs = activeHs.value
  const lines = []
  lines.push(`Mode : ${mode.value === 'vehicle' ? 'Véhicule' : 'Marchandise'}`)
  if (hs?.code) lines.push(`Code SH : ${formatHsCodePretty(hs.code)} — ${truncate(hs.description, 80)}`)

  if (mode.value === 'vehicle') {
    lines.push(`Type : ${vehicle.type} | Carburant : ${vehicle.fuel} | Cylindrée : ${vehicle.displacement || '–'} cm³ | Année : ${vehicle.year || '–'} | État : ${vehicle.condition}`)
    lines.push(`Valeur CAF : ${fmt(vehicleCif.value)}`)
    const e = vehicleEngine.value
    if (e) {
      const L = e.lines
      lines.push(`Taxes : DD ${fmt(L.dd)} · RS ${fmt(L.rs)} · PCS ${fmt(L.pcs)} · PC ${fmt(L.pc)} · PUA ${fmt(L.pua)} · PNS ${fmt(L.pns)} · TPI ${fmt(L.tpi)} · TVM ${fmt(L.tvm)} · ADA ${fmt(L.ada)} · BIC ${fmt(L.bic)} · TVA ${fmt(L.tva)} · RI ${fmt(L.ri)} · RIV ${fmt(L.riv)} = ${fmt(e.total)}`)
      lines.push(`Coût total rendu : ${fmt(vehicleTotalRendu.value)} XOF`)
    }
  }
  else {
    const transport = goods.transport === 'port'
      ? `Port — ${goods.containers || 0} conteneur(s)`
      : 'Route (hors scanner)'
    lines.push(`Valeur CAF : ${fmt(goodsCafXof.value)} | Poids : ${goods.poids || '–'} kg | ${transport}`)
    lines.push(`Taux : DD ${fmtPct(goodsDdRate.value)} · ADA ${fmtPct(goodsAdaRate.value)} · TVA ${fmtPct(goodsTvaRate.value)}`)
    const e = goodsEngine.value
    if (e) {
      const L = e.lines
      lines.push(`Taxes : DD ${fmt(L.dd)} · TPI ${fmt(L.tpi)} · RS ${fmt(L.rs)} · PCS ${fmt(L.pcs)} · PC ${fmt(L.pc)} · PUA ${fmt(L.pua)} · PNS ${fmt(L.pns)} · ADA ${fmt(L.ada)} · BIC ${fmt(L.bic)} · TVA ${fmt(L.tva)} · RIV ${fmt(L.riv)} · RI ${fmt(L.ri)} · DPS ${fmt(L.dps)} = ${fmt(e.total)}`)
      lines.push(`Coût total rendu : ${fmt(goodsTotalRendu.value)} XOF`)
    }
  }

  return lines.join('\n')
}

const creatingQuote = ref(false)
const onCreateQuote = async () => {
  const productId = selectedProduct.value?.raw?.id
  if (!productId) {
    notify('Sélectionne d\'abord un véhicule depuis le catalogue (onglet « Depuis Le Catalogue »).', 'warning')
    return
  }
  if (!activeHs.value) {
    notify('Sélectionne un code SH pour calculer les taxes avant de créer le devis.', 'warning')
    return
  }
  creatingQuote.value = true
  try {
    const res = await $api('/quotes', {
      method: 'POST',
      body: {
        product_id: productId,
        quantity: 1,
        notes: buildCalculationSnapshot(),
      },
    })
    const id = res?.data?.id ?? res?.id
    notify('Devis créé avec succès.')
    localStorage.removeItem(DRAFT_KEY)
    setTimeout(() => router.push(id ? `/quotes/${id}` : '/quotes'), 400)
  }
  catch (err) {
    notify(err?.data?.message ?? 'Erreur lors de la création du devis.', 'error')
  }
  finally {
    creatingQuote.value = false
  }
}

const onSaveDraft = () => {
  const draft = {
    mode: mode.value,
    vehicle: { ...vehicle },
    goods: { ...goods },
    productId: selectedProduct.value?.raw?.id ?? null,
    productTitle: selectedProduct.value?.title ?? null,
    hsCode: activeHs.value?.code ?? null,
    hsSnapshot: buildCalculationSnapshot(),
    savedAt: new Date().toISOString(),
  }
  try {
    localStorage.setItem(DRAFT_KEY, JSON.stringify(draft))
    notify('Brouillon enregistré localement. Tu pourras le restaurer à ta prochaine visite.')
  }
  catch {
    notify('Impossible d\'enregistrer le brouillon (stockage local indisponible).', 'error')
  }
}

const restorableDraft = ref(null)
onMounted(() => {
  try {
    const raw = localStorage.getItem(DRAFT_KEY)
    if (!raw) return
    const draft = JSON.parse(raw)
    restorableDraft.value = draft
  }
  catch { /* corrupt draft — ignore */ }
})

const restoreDraft = () => {
  const d = restorableDraft.value
  if (!d) return
  mode.value = d.mode || 'vehicle'
  if (d.vehicle) Object.assign(vehicle, d.vehicle)
  if (d.goods) Object.assign(goods, d.goods)
  restorableDraft.value = null
  notify('Brouillon restauré. Re-sélectionne le code SH pour recharger les taux.')
}

const dismissDraft = () => {
  localStorage.removeItem(DRAFT_KEY)
  restorableDraft.value = null
}

// ─── Static lookups ───────────────────────────────────────────────────────
const vehicleTypes = [
  { title: 'Voiture de tourisme', value: 'tourisme' },
  { title: 'SUV / 4x4 tourisme', value: 'suv_tourisme' },
  { title: 'Pick-up / double cabine', value: 'pickup' },
  { title: 'Utilitaire léger', value: 'utilitaire' },
  { title: 'Camion', value: 'camion' },
  { title: 'Bus / autocar', value: 'bus' },
  { title: 'Matériel agricole', value: 'agricole' },
  { title: 'Tracteur', value: 'tracteur' },
  { title: 'Moto', value: 'moto' },
  { title: 'Engin BTP', value: 'btp' },
]

// Map the engine vehicle type onto the legacy VP/UT/MO family the existing
// TEC prefix resolver understands, for the classification label only.
const tecFamilyForType = type => ({
  tourisme: 'VP', suv_tourisme: 'VP',
  utilitaire: 'UT', pickup: 'UT', camion: 'UT', btp: 'UT', moto: 'MO',
}[type] ?? null)
const fuelTypes = [
  { title: 'Essence', value: 'essence' },
  { title: 'Diesel', value: 'diesel' },
  { title: 'Hybride', value: 'hybride' },
  { title: 'Électrique', value: 'electrique' },
]
const conditions = [
  { title: 'Occasion', value: 'OCCASION' },
  { title: 'Neuf', value: 'NEUF' },
]
const tractorSubtypes = [
  { title: 'Essieu simple', value: 'essieu_simple' },
  { title: 'Routier (semi-remorque)', value: 'routier_semi_remorque' },
  { title: 'À chenilles', value: 'chenilles' },
  { title: 'Agricole / autre', value: 'agricole' },
]
const origins = ['AMÉRIQUE', 'EUROPE', 'ASIE', 'AUTRES']
const currencies = ['XOF', 'USD', 'EUR']
const goodsTransportOptions = [
  { title: 'Port — conteneurisé', value: 'port' },
  { title: 'Route / véhicule', value: 'route' },
]
</script>

<template>
  <div>
    <!-- ─── Page header ──────────────────────────────────────── -->
    <div class="d-flex align-center gap-3 mb-2 flex-wrap">
      <VAvatar
        variant="tonal"
        size="44"
        rounded
        class="header-avatar"
      >
        <VIcon icon="tabler-calculator" size="24" />
      </VAvatar>
      <div>
        <h4 class="text-h4 font-weight-bold mb-0">
          Calculateur Pro
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Estimez les droits de douane UEMOA selon le Tarif Extérieur Commun CEDEAO (codes SH 10 chiffres).
        </p>
      </div>
      <VSpacer />
      <VChip size="small" variant="tonal" class="header-chip">
        <VIcon start icon="tabler-shield-check" size="14" />
        TEC CEDEAO / UEMOA — Togo
      </VChip>
    </div>

    <!-- ─── Mode switch — iOS-style segmented control ─────────── -->
    <div class="ios-segment mt-4 mb-5">
      <button
        type="button"
        class="ios-segment__item"
        :class="{ 'ios-segment__item--active': mode === 'vehicle' }"
        @click="mode = 'vehicle'"
      >
        <VIcon icon="tabler-car" size="18" />
        <span>Voiture</span>
      </button>
      <button
        type="button"
        class="ios-segment__item"
        :class="{ 'ios-segment__item--active': mode === 'goods' }"
        @click="mode = 'goods'"
      >
        <VIcon icon="tabler-package" size="18" />
        <span>Marchandise</span>
      </button>
    </div>

    <!-- ─── Main two-column layout ───────────────────────────── -->
    <VRow>
      <!-- ───── LEFT — form ─────────────────────────────── -->
      <VCol cols="12" lg="8">
        <!-- ============================================================
             MODE: VÉHICULE
             ============================================================ -->
        <template v-if="mode === 'vehicle'">
          <!-- Card 1 — Identification -->
          <VCard variant="outlined" rounded="lg" class="mb-4">
            <VCardItem class="pb-2">
              <template #prepend>
                <VAvatar variant="tonal" size="36" rounded class="section-avatar">
                  <VIcon icon="tabler-search" size="18" />
                </VAvatar>
              </template>
              <VCardTitle>Identification</VCardTitle>
              <VCardSubtitle>Code SH du véhicule (TEC CEDEAO)</VCardSubtitle>
            </VCardItem>

            <VCardText>
              <VTabs
                v-model="vehicleSearchTab"
                color="primary"
                density="comfortable"
                class="mb-4"
              >
                <VTab value="vin">
                  <VIcon start icon="tabler-barcode" size="16" />
                  Par VIN
                </VTab>
                <VTab value="product">
                  <VIcon start icon="tabler-list-search" size="16" />
                  Par véhicule
                </VTab>
              </VTabs>

              <VWindow v-model="vehicleSearchTab" class="mb-2">
                <!-- ─── Par VIN — single chassis input ─── -->
                <VWindowItem value="vin">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Numéro de châssis (VIN)
                  </p>
                  <div class="d-flex gap-2 align-start vin-row">
                    <AppTextField
                      v-model="chassisInput"
                      placeholder="Ex: SB1KZ28E10E042954"
                      prepend-inner-icon="tabler-barcode"
                      density="comfortable"
                      :error-messages="chassisError"
                      hide-details="auto"
                      style="flex: 1;"
                      @keyup.enter="searchByChassis"
                    />
                    <VBtn
                      color="secondary"
                      variant="flat"
                      class="vin-row__btn"
                      :loading="chassisLoading"
                      :disabled="chassisLoading"
                      prepend-icon="tabler-search"
                      @click="searchByChassis"
                    >
                      Rechercher
                    </VBtn>
                  </div>
                  <p
                    v-if="chassisVehicle"
                    class="text-success text-body-2 mt-2 mb-0 d-flex align-center gap-1"
                  >
                    <VIcon icon="tabler-check" size="14" />
                    <template v-if="chassisSource === 'nhtsa'">
                      {{ chassisVehicleLabel }} — specs décodées via NHTSA (FOB &amp; provenance à compléter)
                    </template>
                    <template v-else>
                      {{ chassisVehicleLabel }} — caractéristiques pré-remplies depuis la fiche
                    </template>
                  </p>
                </VWindowItem>

                <!-- ─── Par véhicule — 3 selectors + free search ─── -->
                <VWindowItem value="product">
                  <VRow dense>
                    <VCol cols="12" md="4">
                      <AppAutocomplete
                        v-model="brandSelected"
                        :items="brandOptions"
                        :loading="brandLoading"
                        item-title="title"
                        item-value="value"
                        return-object
                        clearable
                        density="comfortable"
                        label="Marque"
                        placeholder="Toyota, Hyundai…"
                        prepend-inner-icon="tabler-tag"
                        no-data-text="Aucune marque"
                      />
                    </VCol>
                    <VCol cols="12" md="4">
                      <AppAutocomplete
                        v-model="modelSelected"
                        :items="modelOptions"
                        :loading="modelLoading"
                        :disabled="!brandSelected"
                        item-title="title"
                        item-value="value"
                        return-object
                        clearable
                        density="comfortable"
                        label="Modèle"
                        placeholder="Corolla, Santa Fe…"
                        prepend-inner-icon="tabler-car"
                        no-data-text="Choisis d'abord une marque"
                      />
                    </VCol>
                    <VCol cols="12" md="4">
                      <AppAutocomplete
                        v-model="yearSelected"
                        :items="yearOptions"
                        :loading="yearLoading"
                        :disabled="!modelSelected"
                        item-title="title"
                        item-value="value"
                        return-object
                        clearable
                        density="comfortable"
                        label="Année"
                        placeholder="2020…"
                        prepend-inner-icon="tabler-calendar"
                        no-data-text="Choisis d'abord un modèle"
                      />
                    </VCol>
                  </VRow>

                  <div class="text-caption text-medium-emphasis my-2 d-flex align-center gap-1">
                    <VDivider style="flex: 1;" />
                    <span>ou recherche libre</span>
                    <VDivider style="flex: 1;" />
                  </div>

                  <AppAutocomplete
                    v-model="selectedProduct"
                    v-model:search="productSearch"
                    :items="productResults"
                    :loading="productLoading"
                    item-title="title"
                    item-value="value"
                    return-object
                    no-filter
                    clearable
                    density="comfortable"
                    placeholder="Tape librement: Toyota Corolla 2018…"
                    prepend-inner-icon="tabler-search"
                    no-data-text="Aucun véhicule trouvé"
                    @update:model-value="applyProduct"
                  >
                    <template #item="{ props: itemProps, item }">
                      <VListItem v-bind="itemProps" title="">
                        <template #title>
                          <span v-html="highlightMatch(item.raw.title, productSearch)" />
                        </template>
                      </VListItem>
                    </template>
                  </AppAutocomplete>

                  <p
                    v-if="selectedProduct"
                    class="text-success text-body-2 mt-2 mb-0 d-flex align-center gap-1"
                  >
                    <VIcon icon="tabler-check" size="14" />
                    {{ selectedProduct.title }} — caractéristiques pré-remplies
                  </p>
                </VWindowItem>
              </VWindow>

              <VDivider class="my-4" />

              <p class="text-body-2 font-weight-medium mb-2">
                Code SH (TEC CEDEAO)
              </p>
              <AppAutocomplete
                v-model="vehicleHsSelected"
                v-model:search="vehicleHsSearch"
                :items="vehicleHsResults"
                :loading="vehicleHsLoading || vehicleHsLoadingDetail"
                item-title="title"
                item-value="value"
                return-object
                no-filter
                clearable
                density="comfortable"
                placeholder="Recherchez par code (ex: 8703.21) ou par mots-clés (ex: voiture essence 1500)"
                prepend-inner-icon="tabler-barcode"
                no-data-text="Saisissez au moins 2 caractères pour rechercher"
                aria-label="Code SH du véhicule"
              />
              <p class="text-caption text-medium-emphasis mt-2 mb-0 d-flex align-center gap-1">
                <VIcon icon="tabler-info-circle" size="14" />
                Le code SH détermine les taux DD, RS, TVA, etc. appliqués.
              </p>
            </VCardText>
          </VCard>

          <!-- Card 2 — Caractéristiques -->
          <VCard variant="outlined" rounded="lg" class="mb-4">
            <VCardItem class="pb-2">
              <template #prepend>
                <VAvatar variant="tonal" size="36" rounded class="section-avatar">
                  <VIcon icon="tabler-settings" size="18" />
                </VAvatar>
              </template>
              <VCardTitle>Caractéristiques</VCardTitle>
              <VCardSubtitle>Type, motorisation et état du véhicule</VCardSubtitle>
            </VCardItem>

            <VCardText>
              <VRow>
                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Type
                  </p>
                  <AppSelect
                    v-model="vehicle.type"
                    :items="vehicleTypes"
                    item-title="title"
                    item-value="value"
                    density="comfortable"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Carburant
                  </p>
                  <AppSelect
                    v-model="vehicle.fuel"
                    :items="fuelTypes"
                    item-title="title"
                    item-value="value"
                    density="comfortable"
                  />
                </VCol>

                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Cylindrée (cm³)
                  </p>
                  <AppTextField
                    v-model.number="vehicle.displacement"
                    type="number"
                    density="comfortable"
                    prepend-inner-icon="tabler-engine"
                    placeholder="Ex: 1600"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Année
                  </p>
                  <AppTextField
                    v-model="vehicle.year"
                    type="number"
                    density="comfortable"
                    prepend-inner-icon="tabler-calendar"
                    placeholder="Ex: 2020"
                  />
                </VCol>

                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    État
                  </p>
                  <AppSelect
                    v-model="vehicle.condition"
                    :items="conditions"
                    item-title="title"
                    item-value="value"
                    density="comfortable"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Provenance
                  </p>
                  <AppSelect
                    v-model="vehicle.origin"
                    :items="origins"
                    density="comfortable"
                  />
                </VCol>

                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Poids (tonnes)
                  </p>
                  <AppTextField
                    v-model.number="vehicle.poids"
                    type="number"
                    step="0.001"
                    density="comfortable"
                    prepend-inner-icon="tabler-scale"
                    placeholder="Ex: 1.713"
                  />
                  <p class="text-caption text-medium-emphasis mt-1 mb-0">
                    Sert à la TPI (arrondi au tonne supérieure × 2000).
                  </p>
                </VCol>
                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Puissance fiscale (CV)
                  </p>
                  <AppTextField
                    v-model.number="vehicle.cv"
                    type="number"
                    density="comfortable"
                    prepend-inner-icon="tabler-bolt"
                    placeholder="Ex: 15"
                  />
                  <p class="text-caption text-medium-emphasis mt-1 mb-0">
                    Détermine la TVM et le seuil ADA (&gt; 13 CV).
                  </p>
                </VCol>

                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Date de déclaration
                  </p>
                  <AppTextField
                    v-model="vehicle.declarationDate"
                    type="date"
                    density="comfortable"
                    prepend-inner-icon="tabler-calendar-event"
                  />
                  <p class="text-caption text-medium-emphasis mt-1 mb-0">
                    Proratise la TVM sur les jours restants de l'année.
                  </p>
                </VCol>

                <!-- Bus : nombre de places -->
                <VCol v-if="vehicleFamily === 'bus'" cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Nombre de places (chauffeur inclus)
                  </p>
                  <AppTextField
                    v-model.number="vehicle.seats"
                    type="number"
                    density="comfortable"
                    prepend-inner-icon="tabler-users"
                    placeholder="Ex: 25"
                  />
                </VCol>

                <!-- Tracteur : sous-type + puissance kW -->
                <template v-if="vehicleFamily === 'tractor'">
                  <VCol cols="12" md="6">
                    <p class="text-body-2 font-weight-medium mb-2">
                      Sous-type de tracteur
                    </p>
                    <AppSelect
                      v-model="vehicle.tractorSubtype"
                      :items="tractorSubtypes"
                      item-title="title"
                      item-value="value"
                      density="comfortable"
                    />
                  </VCol>
                  <VCol v-if="vehicle.tractorSubtype === 'agricole'" cols="12" md="6">
                    <p class="text-body-2 font-weight-medium mb-2">
                      Puissance moteur (kW)
                    </p>
                    <AppTextField
                      v-model.number="vehicle.powerKw"
                      type="number"
                      density="comfortable"
                      prepend-inner-icon="tabler-bolt"
                      placeholder="Ex: 75"
                    />
                  </VCol>
                </template>
              </VRow>

              <p
                v-if="vehicleFamily === 'goods_vehicle'"
                class="text-caption text-medium-emphasis mt-1 mb-0 d-flex align-center gap-1"
              >
                <VIcon icon="tabler-info-circle" size="14" />
                Pour un camion, le « Poids (tonnes) » sert aussi de PTAC pour la classification (≤ 5 t / 5–20 t / &gt; 20 t).
              </p>
            </VCardText>
          </VCard>

          <!-- Card 3 — Provenance & valeur -->
          <VCard variant="outlined" rounded="lg" class="mb-4">
            <VCardItem class="pb-2">
              <template #prepend>
                <VAvatar variant="tonal" size="36" rounded class="section-avatar">
                  <VIcon icon="tabler-world" size="18" />
                </VAvatar>
              </template>
              <VCardTitle>Provenance &amp; valeur</VCardTitle>
              <VCardSubtitle>Valeur FOB déclarée du véhicule</VCardSubtitle>
            </VCardItem>

            <VCardText>
              <VRow>
                <VCol cols="12" md="8">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Valeur FOB
                  </p>
                  <AppTextField
                    v-model.number="vehicle.fobAmount"
                    type="number"
                    density="comfortable"
                    prepend-inner-icon="tabler-currency-dollar"
                    placeholder="0"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Devise
                  </p>
                  <AppSelect
                    v-model="vehicle.fobCurrency"
                    :items="currencies"
                    density="comfortable"
                  />
                </VCol>
              </VRow>
              <p
                v-if="vehicle.fobCurrency !== 'XOF' && Number(vehicle.fobAmount) > 0"
                class="text-caption text-medium-emphasis mt-2 mb-0"
              >
                Soit environ <strong>{{ fmt(vehicleFobXof) }}</strong>
                (taux {{ vehicle.fobCurrency }} → XOF estimé)
              </p>
            </VCardText>
          </VCard>
        </template>

        <!-- ============================================================
             MODE: MARCHANDISE
             ============================================================ -->
        <template v-else>
          <!-- Card 1 — Identification (HS code) -->
          <VCard variant="outlined" rounded="lg" class="mb-4">
            <VCardItem class="pb-2">
              <template #prepend>
                <VAvatar variant="tonal" size="36" rounded class="section-avatar">
                  <VIcon icon="tabler-search" size="18" />
                </VAvatar>
              </template>
              <VCardTitle>Identification</VCardTitle>
              <VCardSubtitle>Code SH de la marchandise (TEC CEDEAO)</VCardSubtitle>
            </VCardItem>

            <VCardText>
              <p class="text-body-2 font-weight-medium mb-2">
                Code SH (TEC CEDEAO)
              </p>
              <AppAutocomplete
                v-model="goodsHsSelected"
                v-model:search="goodsHsSearch"
                :items="goodsHsResults"
                :loading="goodsHsLoading || goodsHsLoadingDetail"
                item-title="title"
                item-value="value"
                return-object
                no-filter
                clearable
                density="comfortable"
                placeholder="Recherchez par code SH (ex: 8418.10) ou par mots-clés (ex: réfrigérateur)"
                prepend-inner-icon="tabler-barcode"
                no-data-text="Saisissez au moins 2 caractères pour rechercher"
                aria-label="Code SH de la marchandise"
              />
              <p class="text-caption text-medium-emphasis mt-2 mb-0 d-flex align-center gap-1">
                <VIcon icon="tabler-info-circle" size="14" />
                Recherchez par code SH ou par mots-clés (la nomenclature CEDEAO est utilisée).
              </p>
            </VCardText>
          </VCard>

          <!-- Card 2 — Caractéristiques (shipment) -->
          <VCard variant="outlined" rounded="lg" class="mb-4">
            <VCardItem class="pb-2">
              <template #prepend>
                <VAvatar variant="tonal" size="36" rounded class="section-avatar">
                  <VIcon icon="tabler-settings" size="18" />
                </VAvatar>
              </template>
              <VCardTitle>Caractéristiques</VCardTitle>
              <VCardSubtitle>Poids et acheminement</VCardSubtitle>
            </VCardItem>

            <VCardText>
              <VRow>
                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Poids brut (kg)
                  </p>
                  <AppTextField
                    v-model.number="goods.poids"
                    type="number"
                    density="comfortable"
                    prepend-inner-icon="tabler-weight"
                    placeholder="Ex: 132217"
                  />
                  <p class="text-caption text-medium-emphasis mt-1 mb-0">
                    Sert à la TPI (arrondi à la tonne supérieure × 2000).
                  </p>
                </VCol>
                <VCol cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Acheminement
                  </p>
                  <AppSelect
                    v-model="goods.transport"
                    :items="goodsTransportOptions"
                    item-title="title"
                    item-value="value"
                    density="comfortable"
                  />
                  <p class="text-caption text-medium-emphasis mt-1 mb-0">
                    Le DPS (scanner) ne s'applique qu'aux conteneurs au port.
                  </p>
                </VCol>

                <VCol v-if="goods.transport === 'port'" cols="12" md="6">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Nombre de conteneurs
                  </p>
                  <AppTextField
                    v-model.number="goods.containers"
                    type="number"
                    min="0"
                    density="comfortable"
                    prepend-inner-icon="tabler-box"
                    placeholder="Ex: 1"
                  />
                  <p class="text-caption text-medium-emphasis mt-1 mb-0">
                    DPS = 50 000 FCFA × nombre de conteneurs scannés.
                  </p>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <!-- Card 3 — Provenance & valeur -->
          <VCard variant="outlined" rounded="lg" class="mb-4">
            <VCardItem class="pb-2">
              <template #prepend>
                <VAvatar variant="tonal" size="36" rounded class="section-avatar">
                  <VIcon icon="tabler-world" size="18" />
                </VAvatar>
              </template>
              <VCardTitle>Valeur &amp; taux</VCardTitle>
              <VCardSubtitle>Valeur en douane et taux applicables</VCardSubtitle>
            </VCardItem>

            <VCardText>
              <VRow>
                <VCol cols="12">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Valeur en douane (CAF)
                  </p>
                  <AppTextField
                    v-model.number="goods.caf"
                    type="number"
                    density="comfortable"
                    prepend-inner-icon="tabler-cash"
                    placeholder="Ex: 11039771"
                    suffix="FCFA"
                  />
                  <p class="text-caption text-medium-emphasis mt-1 mb-0">
                    Base d'imposition (FOB + fret + assurance), telle que retenue par la douane.
                  </p>
                </VCol>

                <VCol cols="12" md="4">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Taux DD
                  </p>
                  <AppTextField
                    v-model.number="goodsDdRate"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    density="comfortable"
                    prepend-inner-icon="tabler-percentage"
                    placeholder="Ex: 35"
                    suffix="%"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Accise (ADA)
                  </p>
                  <AppTextField
                    v-model.number="goodsAdaRate"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    density="comfortable"
                    prepend-inner-icon="tabler-percentage"
                    placeholder="Ex: 10"
                    suffix="%"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <p class="text-body-2 font-weight-medium mb-2">
                    Taux TVA
                  </p>
                  <AppTextField
                    v-model.number="goodsTvaRate"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    density="comfortable"
                    prepend-inner-icon="tabler-percentage"
                    placeholder="18"
                    suffix="%"
                  />
                </VCol>
              </VRow>
              <p class="text-caption text-medium-emphasis mt-2 mb-0 d-flex align-center gap-1">
                <VIcon icon="tabler-info-circle" size="14" />
                Taux pré-remplis depuis le code SH — ajustables si besoin.
              </p>
            </VCardText>
          </VCard>
        </template>
      </VCol>

      <!-- ───── RIGHT — live summary (sticky) ───────────── -->
      <VCol cols="12" lg="4">
        <div class="calc-summary-sticky">
          <VCard
            variant="outlined"
            rounded="lg"
            class="calc-summary-card"
          >
            <VCardItem class="pb-3">
              <template #prepend>
                <VIcon icon="tabler-receipt-tax" size="20" class="text-medium-emphasis" />
              </template>
              <VCardTitle class="text-body-1 font-weight-medium">
                Résumé
              </VCardTitle>
              <VCardSubtitle class="text-caption">
                Mis à jour en temps réel
              </VCardSubtitle>
            </VCardItem>

            <VCardText class="pt-0">
              <!-- ══════════ MODE VÉHICULE : moteur douanier réel ══════════ -->
              <template v-if="mode === 'vehicle'">
                <!-- Classification TEC officielle (resolver déterministe) -->
                <div v-if="vehicleClassification?.code" class="hs-info-block pa-3 rounded-lg mb-4">
                  <div class="text-caption text-medium-emphasis mb-1">
                    Code SH officiel (TEC CEDEAO)
                  </div>
                  <div class="hs-code-mono text-subtitle-1 font-weight-bold mb-1">
                    {{ formatHsCodePretty(vehicleClassification.code) }}
                  </div>
                  <div
                    v-if="vehicleClassification.designation"
                    class="text-caption text-medium-emphasis"
                    :title="vehicleClassification.designation"
                  >
                    {{ truncate(vehicleClassification.designation, 90) }}
                  </div>
                </div>
                <div
                  v-else-if="vehicleClassificationStatus && vehicleClassificationStatus !== 'ok'"
                  class="hint-box mb-4"
                >
                  <VIcon icon="tabler-info-circle" size="16" class="hint-box__icon" />
                  <span>
                    <template v-if="vehicleClassificationStatus === 'ambiguous'">
                      Plusieurs codes TEC possibles — précise le type (ex. hybride essence/diesel) pour fixer la classification.
                    </template>
                    <template v-else-if="vehicleClassificationStatus === 'not_found'">
                      Aucun code TEC ne correspond exactement à ce profil.
                    </template>
                    <template v-else>
                      Renseigne les caractéristiques pour déterminer le code TEC officiel.
                    </template>
                  </span>
                </div>

                <!-- Blocage : profil incomplet → aucun montant -->
                <template v-if="vehicleMissing.length">
                  <div class="empty-state d-flex flex-column align-center text-center py-6 px-4">
                    <VIcon icon="tabler-clipboard-list" size="32" class="text-disabled mb-3" />
                    <div class="text-body-2 text-medium-emphasis mb-2">
                      Pour calculer les droits à 100 %, renseigne :
                    </div>
                    <ul class="text-body-2 text-medium-emphasis text-start" style="margin:0;padding-inline-start:18px;">
                      <li v-for="m in vehicleMissing" :key="m">
                        {{ m }}
                      </li>
                    </ul>
                  </div>
                </template>

                <!-- Chargement -->
                <div v-else-if="vehicleEngineLoading && !vehicleEngine" class="d-flex justify-center py-6">
                  <VProgressCircular indeterminate color="primary" size="28" />
                </div>

                <!-- Résultat du moteur -->
                <template v-else-if="vehicleEngine">
                  <div class="d-flex flex-wrap gap-1 mb-3">
                    <VChip size="x-small" variant="tonal">
                      Âge : {{ vehicleEngine.age }} an(s)
                    </VChip>
                    <VChip
                      size="x-small"
                      :color="vehicleEngine.abattement_rate > 0 ? 'success' : undefined"
                      variant="tonal"
                    >
                      Abattement : {{ fmtPct(vehicleEngine.abattement_rate * 100) }}
                    </VChip>
                    <VChip v-if="vehicleEngine.is_recent" size="x-small" color="info" variant="tonal">
                      ≤ 5 ans : PNS/BIC/TVA exonérés
                    </VChip>
                  </div>

                  <div class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">
                    Base de calcul
                  </div>
                  <table class="calc-table">
                    <tbody>
                      <tr>
                        <td class="text-body-2 text-medium-emphasis">Valeur FOB</td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleFobXof) }}</td>
                      </tr>
                      <tr>
                        <td class="text-body-2 text-medium-emphasis">Fret (Maritime)</td>
                        <td class="calc-num">
                          <input v-model.number="vehicleFreight" type="number" min="0" class="inline-num-input" placeholder="0">
                        </td>
                      </tr>
                      <tr class="calc-row-total">
                        <td class="text-body-2 font-weight-bold">= Valeur CAF</td>
                        <td class="calc-num text-body-2 font-weight-bold">{{ fmt(activeCif) }}</td>
                      </tr>
                      <tr>
                        <td class="text-body-2 text-medium-emphasis">Base taxable (après abattement)</td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.base_taxable) }}</td>
                      </tr>
                    </tbody>
                  </table>

                  <VDivider class="my-3" />
                  <div class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">
                    Droits &amp; taxes
                  </div>
                  <table class="calc-table">
                    <tbody>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">Droits douane</span><span class="text-caption text-medium-emphasis ms-1">({{ fmtPct(vehicleEngine.dd_rate * 100) }})</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.dd) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">RS</span><span class="text-caption text-medium-emphasis ms-1">(base × 1 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.rs) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">PCS</span><span class="text-caption text-medium-emphasis ms-1">(CAF × 0,8 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.pcs) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">PC</span><span class="text-caption text-medium-emphasis ms-1">(CAF × 0,5 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.pc) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">PUA</span><span class="text-caption text-medium-emphasis ms-1">(CAF × 0,2 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.pua) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">PNS</span><span class="text-caption text-medium-emphasis ms-1">{{ vehicleEngine.is_recent ? 'exonéré (≤ 5 ans)' : '(CAF × 0,5 %)' }}</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.pns) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">TPI</span><span class="text-caption text-medium-emphasis ms-1">({{ vehicleEngine.poids_arrondi }} t × 2000)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.tpi) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">TVM</span><span class="text-caption text-medium-emphasis ms-1">(CV {{ vehicle.cv }})</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.tvm) }}</td>
                      </tr>
                      <tr v-if="vehicleEngine.is_ada_applicable">
                        <td><span class="text-body-2 font-weight-medium">ADA</span><span class="text-caption text-medium-emphasis ms-1">(Somme1 × 5 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.ada) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">BIC</span><span class="text-caption text-medium-emphasis ms-1">{{ vehicleEngine.is_recent ? 'exonéré' : '(S1+ADA × 1 %)' }}</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.bic) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">TVA</span><span class="text-caption text-medium-emphasis ms-1">{{ vehicleEngine.is_recent ? 'exonéré (≤ 5 ans)' : '(S1+ADA × 18 %)' }}</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.tva) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">RI</span><span class="text-caption text-medium-emphasis ms-1">(forfait)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.ri) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">RIV</span><span class="text-caption text-medium-emphasis ms-1">(base × 0,75 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(vehicleEngine.lines.riv) }}</td>
                      </tr>
                      <tr>
                        <td>
                          <span class="text-body-2 font-weight-medium">Transit &amp; magasinage</span>
                          <div class="text-caption text-medium-emphasis">Port de Lomé → entrepôt</div>
                        </td>
                        <td class="calc-num">
                          <input v-model.number="vehicleTransitWarehousing" type="number" min="0" class="inline-num-input" placeholder="0">
                        </td>
                      </tr>
                    </tbody>
                  </table>

                  <VDivider class="my-3" />
                  <div class="d-flex justify-space-between align-center mb-3">
                    <span class="text-body-1 font-weight-bold">Total droits &amp; taxes</span>
                    <span class="text-body-1 font-weight-bold text-error calc-num-cell">{{ fmt(vehicleEngine.total) }}</span>
                  </div>
                  <div class="total-import pa-3 rounded-lg d-flex align-center justify-space-between">
                    <div>
                      <div class="text-caption text-medium-emphasis text-uppercase">Coût total rendu</div>
                      <div class="text-caption text-medium-emphasis">CAF + taxes + transit</div>
                    </div>
                    <div class="text-h5 font-weight-bold text-success calc-num-cell">{{ fmt(vehicleTotalRendu) }}</div>
                  </div>
                </template>
              </template>

              <!-- ══════════ MODE MARCHANDISE : moteur douanier réel ══════════ -->
              <template v-else>
                <!-- Code SH sélectionné -->
                <div v-if="activeHs?.code" class="hs-info-block pa-3 rounded-lg mb-4">
                  <div class="text-caption text-medium-emphasis mb-1">
                    Code SH (TEC CEDEAO)
                  </div>
                  <div class="hs-code-mono text-subtitle-1 font-weight-bold mb-1">
                    {{ formatHsCodePretty(activeHs.code) }}
                  </div>
                  <div
                    v-if="activeHs.description"
                    class="text-caption text-medium-emphasis"
                    :title="activeHs.description"
                  >
                    {{ truncate(activeHs.description, 90) }}
                  </div>
                </div>
                <div v-else class="hint-box mb-4">
                  <VIcon icon="tabler-info-circle" size="16" class="hint-box__icon" />
                  <span>Sélectionne un code SH pour pré-remplir les taux (DD, accise, TVA).</span>
                </div>

                <!-- Blocage : champs requis manquants → aucun montant -->
                <template v-if="goodsMissing.length">
                  <div class="empty-state d-flex flex-column align-center text-center py-6 px-4">
                    <VIcon icon="tabler-clipboard-list" size="32" class="text-disabled mb-3" />
                    <div class="text-body-2 text-medium-emphasis mb-2">
                      Pour calculer les droits à 100 %, renseigne :
                    </div>
                    <ul class="text-body-2 text-medium-emphasis text-start" style="margin:0;padding-inline-start:18px;">
                      <li v-for="m in goodsMissing" :key="m">
                        {{ m }}
                      </li>
                    </ul>
                  </div>
                </template>

                <!-- Chargement -->
                <div v-else-if="goodsEngineLoading && !goodsEngine" class="d-flex justify-center py-6">
                  <VProgressCircular indeterminate color="primary" size="28" />
                </div>

                <!-- Résultat du moteur -->
                <template v-else-if="goodsEngine">
                  <div class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">
                    Base de calcul
                  </div>
                  <table class="calc-table">
                    <tbody>
                      <tr class="calc-row-total">
                        <td class="text-body-2 font-weight-bold">= Valeur CAF</td>
                        <td class="calc-num text-body-2 font-weight-bold">{{ fmt(goodsCafXof) }}</td>
                      </tr>
                    </tbody>
                  </table>

                  <VDivider class="my-3" />
                  <div class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">
                    Droits &amp; taxes
                  </div>
                  <table class="calc-table">
                    <tbody>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">Droits douane</span><span class="text-caption text-medium-emphasis ms-1">({{ fmtPct(goodsDdRate) }})</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.dd) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">TPI</span><span class="text-caption text-medium-emphasis ms-1">({{ goodsEngine.poids_arrondi }} t × 2000)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.tpi) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">RS</span><span class="text-caption text-medium-emphasis ms-1">(CAF × 1 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.rs) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">PCS</span><span class="text-caption text-medium-emphasis ms-1">(CAF × 0,8 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.pcs) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">PC</span><span class="text-caption text-medium-emphasis ms-1">(CAF × 0,5 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.pc) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">PUA</span><span class="text-caption text-medium-emphasis ms-1">(CAF × 0,2 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.pua) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">PNS</span><span class="text-caption text-medium-emphasis ms-1">(CAF × 0,5 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.pns) }}</td>
                      </tr>
                      <tr v-if="goodsEngine.lines.ada">
                        <td><span class="text-body-2 font-weight-medium">ADA</span><span class="text-caption text-medium-emphasis ms-1">(Somme1 × {{ fmtPct(goodsAdaRate) }})</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.ada) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">BIC</span><span class="text-caption text-medium-emphasis ms-1">(S1+ADA × 1 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.bic) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">TVA</span><span class="text-caption text-medium-emphasis ms-1">(S1+ADA × {{ fmtPct(goodsTvaRate) }})</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.tva) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">RIV</span><span class="text-caption text-medium-emphasis ms-1">(CAF × 0,75 %)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.riv) }}</td>
                      </tr>
                      <tr>
                        <td><span class="text-body-2 font-weight-medium">RI</span><span class="text-caption text-medium-emphasis ms-1">(forfait)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.ri) }}</td>
                      </tr>
                      <tr v-if="goods.transport === 'port'">
                        <td><span class="text-body-2 font-weight-medium">DPS</span><span class="text-caption text-medium-emphasis ms-1">(50 000 × {{ goods.containers || 0 }} cont.)</span></td>
                        <td class="calc-num text-body-2">{{ fmt(goodsEngine.lines.dps) }}</td>
                      </tr>
                    </tbody>
                  </table>

                  <VDivider class="my-3" />
                  <div class="d-flex justify-space-between align-center mb-3">
                    <span class="text-body-1 font-weight-bold">Total droits &amp; taxes</span>
                    <span class="text-body-1 font-weight-bold text-error calc-num-cell">{{ fmt(goodsEngine.total) }}</span>
                  </div>
                  <div class="total-import pa-3 rounded-lg d-flex align-center justify-space-between">
                    <div>
                      <div class="text-caption text-medium-emphasis text-uppercase">Coût total rendu</div>
                      <div class="text-caption text-medium-emphasis">CAF + droits &amp; taxes</div>
                    </div>
                    <div class="text-h5 font-weight-bold text-success calc-num-cell">{{ fmt(goodsTotalRendu) }}</div>
                  </div>
                </template>
              </template>
            </VCardText>

            <!-- ─── Action buttons ────────────────────────────── -->
            <VDivider />
            <VCardActions class="d-flex flex-column align-stretch gap-2 pa-4">
              <VBtn
                color="primary"
                variant="flat"
                block
                prepend-icon="tabler-file-invoice"
                :loading="creatingQuote"
                :disabled="!activeHs || !selectedProduct?.raw?.id || mode !== 'vehicle'"
                @click="onCreateQuote"
              >
                Créer un devis
              </VBtn>
              <div
                v-if="mode === 'vehicle' && activeHs && !selectedProduct?.raw?.id"
                class="text-caption text-medium-emphasis text-center"
              >
                Choisis un véhicule via l'onglet « Depuis Le Catalogue » pour créer un devis.
              </div>
              <div class="d-flex gap-2">
                <VBtn
                  variant="outlined"
                  block
                  prepend-icon="tabler-device-floppy"
                  :disabled="!activeHs"
                  @click="onSaveDraft"
                >
                  Brouillon
                </VBtn>
                <VBtn
                  variant="text"
                  block
                  prepend-icon="tabler-refresh"
                  @click="reset"
                >
                  Réinitialiser
                </VBtn>
              </div>
            </VCardActions>
          </VCard>

          <!-- Footnote -->
          <p class="text-caption text-medium-emphasis mt-3 mb-0 d-flex align-start gap-1">
            <VIcon icon="tabler-info-circle" size="12" class="mt-1" />
            <span>
              Estimation indicative. Les taux peuvent évoluer ; consultez la douane togolaise
              pour la valeur officielle.
            </span>
          </p>
        </div>
      </VCol>
    </VRow>

    <!-- Draft restoration banner -->
    <VAlert
      v-if="restorableDraft"
      type="info"
      variant="tonal"
      density="compact"
      class="mt-4"
      closable
      @click:close="dismissDraft"
    >
      <div class="d-flex align-center justify-space-between flex-wrap gap-2">
        <div>
          Un brouillon enregistré le
          <strong>{{ new Date(restorableDraft.savedAt).toLocaleString('fr-FR') }}</strong>
          est disponible{{ restorableDraft.productTitle ? ` — ${restorableDraft.productTitle}` : '' }}.
        </div>
        <VBtn
          size="small"
          color="primary"
          variant="flat"
          prepend-icon="tabler-restore"
          @click="restoreDraft"
        >
          Restaurer
        </VBtn>
      </div>
    </VAlert>

    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="4000"
      location="top right"
    >
      {{ snackbar.message }}
      <template #actions>
        <VBtn variant="text" @click="snackbar.show = false">
          Fermer
        </VBtn>
      </template>
    </VSnackbar>
  </div>
</template>

<style scoped>
/* ─── Mode selector ──────────────────────────────────────────── */
.mode-card {
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.mode-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}
.mode-card:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}
.mode-card--active {
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
}

/* ─── Sticky right column ───────────────────────────────────── */
.calc-summary-sticky {
  position: sticky;
  inset-block-start: 80px;
}

.calc-summary-card {
  overflow: hidden;
}

/* ─── HS info block ─────────────────────────────────────────── */
.hs-info-block {
  background: rgba(var(--v-theme-on-surface), 0.03);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.hs-code-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.04em;
}

.tariff-mini {
  background: rgba(var(--v-theme-on-surface), 0.04);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.empty-state {
  min-block-size: 120px;
}

/* ─── Calc table ────────────────────────────────────────────── */
.calc-table {
  inline-size: 100%;
  border-collapse: collapse;
}
.calc-table td {
  padding-block: 6px;
  padding-inline: 0;
  vertical-align: top;
}
.calc-table tr + tr td {
  border-block-start: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}
.calc-num,
.calc-num-cell {
  font-variant-numeric: tabular-nums;
  text-align: end;
  white-space: nowrap;
}
.calc-row-total td {
  border-block-start: 1px solid rgba(var(--v-theme-on-surface), 0.16) !important;
  padding-block-start: 8px !important;
}

/* ─── Total import card ─────────────────────────────────────── */
.total-import {
  background: rgba(var(--v-theme-on-surface), 0.04);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
}

/* Inline numeric input inside the cost breakdown table — small, neutral,
   right-aligned to line up with the read-only numbers above/below it. */
.inline-num-input {
  inline-size: 120px;
  font-size: 13px;
  font-variant-numeric: tabular-nums;
  text-align: end;
  padding: 4px 8px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.16);
  border-radius: 6px;
  background: transparent;
  color: inherit;
  outline: none;
  transition: border-color 0.15s;
}
.inline-num-input:focus {
  border-color: rgb(var(--v-theme-primary));
}
.inline-num-input::-webkit-inner-spin-button,
.inline-num-input::-webkit-outer-spin-button {
  appearance: none;
  margin: 0;
}

/* ─── iOS-style segmented control (Voiture | Marchandise) ───── */
.ios-segment {
  display: flex;
  gap: 4px;
  padding: 4px;
  border-radius: 12px;
  background: rgba(var(--v-theme-on-surface), 0.06);
  max-inline-size: 460px;
}
.ios-segment__item {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 9px 14px;
  border: none;
  border-radius: 9px;
  background: transparent;
  font-size: 14px;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.6);
  cursor: pointer;
  transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}
.ios-segment__item:hover { color: rgba(var(--v-theme-on-surface), 0.8); }
.ios-segment__item--active {
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.06);
}

/* ─── VIN search row : input + button on one line, equal height ─── */
.vin-row {
  align-items: flex-start;
}
.vin-row :deep(.v-field) {
  min-height: 48px;
}
.vin-row :deep(.v-input__details) {
  min-height: 0;
}
.vin-row__btn {
  height: 48px;
  min-height: 48px;
  flex-shrink: 0;
}

/* ─── Neutral section pastilles & header (less green) ───────── */
.header-avatar,
.section-avatar {
  background: rgba(var(--v-theme-on-surface), 0.06) !important;
  color: rgba(var(--v-theme-on-surface), 0.7) !important;
}
.header-chip {
  background: rgba(var(--v-theme-on-surface), 0.06) !important;
  color: rgba(var(--v-theme-on-surface), 0.7) !important;
}

/* ─── Subtle hint box (replaces the loud orange alert) ──────── */
.hint-box {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 10px 12px;
  border-radius: 8px;
  background: rgba(var(--v-theme-on-surface), 0.04);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  font-size: 12.5px;
  color: rgba(var(--v-theme-on-surface), 0.65);
  line-height: 1.45;
}
.hint-box__icon {
  color: rgba(var(--v-theme-on-surface), 0.4);
  margin-top: 1px;
  flex-shrink: 0;
}
</style>
