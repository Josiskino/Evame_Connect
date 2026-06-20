<script setup>
import { useRoute, useRouter } from 'vue-router'

definePage({ meta: { layout: 'default' } })

const route = useRoute()
const router = useRouter()

const productId = computed(() => route.params.id)

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
const storageBaseUrl = apiBaseUrl.replace(/\/api\/?$/, '')

const CURRENCY_OPTIONS = [
  { title: 'XOF (FCFA)', value: 'XOF' },
  { title: 'USD ($)', value: 'USD' },
  { title: 'EUR (€)', value: 'EUR' },
]

const REVIEW_STATUS_COLORS = {
  pending: 'warning',
  published: 'success',
  rejected: 'error',
}

const REVIEW_STATUS_LABELS = {
  pending: 'En attente',
  published: 'Publié',
  rejected: 'Rejeté',
}

const REVIEW_STATUS_FILTERS = [
  { title: 'Tous', value: '' },
  { title: 'En attente', value: 'pending' },
  { title: 'Publiés', value: 'published' },
  { title: 'Rejetés', value: 'rejected' },
]

// ── Load product ────────────────────────────────────────────────────────
const { data: productData, execute: refresh, isFetching } = useApi(
  computed(() => `/products/${productId.value}?include=images,publishedReviews,suppliers&include_counts=1`),
)

const product = computed(() => productData.value?.data ?? productData.value ?? null)

// ── Helpers ──────────────────────────────────────────────────────────────
const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '–'
const formatPrice = (val, currency) => {
  if (val === null || val === undefined || val === '') return '–'
  return `${Number(val).toLocaleString('fr-FR')} ${currency ?? ''}`.trim()
}
const formatBytes = bytes => {
  if (!bytes) return '–'
  if (bytes < 1024) return `${bytes} o`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`
  return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`
}

const imageUrl = img => {
  if (!img) return ''
  if (img.url) return img.url
  if (img.file_path) return `${storageBaseUrl}/storage/${img.file_path}`
  return ''
}

// ── Tabs ─────────────────────────────────────────────────────────────────
const currentTab = ref('overview')

// ── Header / Delete ──────────────────────────────────────────────────────
const dialogDelete = ref(false)
const deleting = ref(false)

const deleteProduct = async () => {
  deleting.value = true
  try {
    await $api(`/products/${productId.value}`, { method: 'DELETE' })
    dialogDelete.value = false
    router.push('/products')
  }
  catch {
    dialogDelete.value = false
  }
  finally {
    deleting.value = false
  }
}

// ── Edit dialog (basic local form) ───────────────────────────────────────
const editDialog = ref(false)
const editFormRef = ref()
const editForm = ref({})
const editErrorMsg = ref('')
const editSaving = ref(false)

const openEdit = () => {
  const p = product.value
  if (!p) return
  editForm.value = {
    name: p.name ?? '',
    description: p.description ?? '',
    supplier_price: p.commercial?.supplier_price ?? null,
    currency: p.commercial?.currency ?? 'XOF',
    moq: p.commercial?.moq ?? null,
    weight_kg: p.specifications?.weight_kg ?? null,
    cbm: p.specifications?.cbm ?? null,
    incoterm: p.commercial?.incoterm ?? '',
  }
  editErrorMsg.value = ''
  editDialog.value = true
}

const saveEdit = async () => {
  const { valid } = await editFormRef.value.validate()
  if (!valid) return
  editSaving.value = true
  editErrorMsg.value = ''
  try {
    const body = { ...editForm.value }
    Object.keys(body).forEach(k => { if (body[k] === '') body[k] = null })
    await $api(`/products/${productId.value}`, { method: 'PATCH', body })
    editDialog.value = false
    refresh()
  }
  catch (err) {
    editErrorMsg.value = err?.response?._data?.message ?? 'Une erreur est survenue'
  }
  finally {
    editSaving.value = false
  }
}

// ── Images / Gallery ─────────────────────────────────────────────────────
const uploadDialog = ref(false)
const uploadFile = ref(null)
const uploadIsPrimary = ref(false)
const uploadAltText = ref('')
const uploading = ref(false)
const uploadErrorMsg = ref('')

const openUpload = () => {
  uploadFile.value = null
  uploadIsPrimary.value = false
  uploadAltText.value = ''
  uploadErrorMsg.value = ''
  uploadDialog.value = true
}

const submitUpload = async () => {
  if (!uploadFile.value) {
    uploadErrorMsg.value = 'Veuillez sélectionner une image'
    return
  }
  const file = Array.isArray(uploadFile.value) ? uploadFile.value[0] : uploadFile.value
  if (file?.size > 8 * 1024 * 1024) {
    uploadErrorMsg.value = 'Le fichier dépasse 8 Mo'
    return
  }
  uploading.value = true
  uploadErrorMsg.value = ''
  try {
    const fd = new FormData()
    fd.append('file', file)
    if (uploadIsPrimary.value) fd.append('is_primary', '1')
    if (uploadAltText.value) fd.append('alt_text', uploadAltText.value)
    await $api(`/products/${productId.value}/images/upload`, {
      method: 'POST',
      body: fd,
    })
    uploadDialog.value = false
    refresh()
  }
  catch (err) {
    uploadErrorMsg.value = err?.response?._data?.message ?? 'Erreur d\'envoi'
  }
  finally {
    uploading.value = false
  }
}

const deleteImage = async img => {
  if (!confirm('Supprimer cette image ?')) return
  try {
    await $api(`/products/${productId.value}/images/${img.id}`, { method: 'DELETE' })
    refresh()
  }
  catch {
    // silent
  }
}

const reorderImages = async newOrder => {
  try {
    await $api(`/products/${productId.value}/images/reorder`, {
      method: 'PATCH',
      body: { image_ids: newOrder.map(i => i.id) },
    })
    refresh()
  }
  catch {
    // silent
  }
}

const moveImage = (img, direction) => {
  const list = [...(product.value?.images ?? [])]
  const idx = list.findIndex(i => i.id === img.id)
  if (idx === -1) return
  const targetIdx = direction === 'up' ? idx - 1 : idx + 1
  if (targetIdx < 0 || targetIdx >= list.length) return
  ;[list[idx], list[targetIdx]] = [list[targetIdx], list[idx]]
  reorderImages(list)
}

// ── Reviews ──────────────────────────────────────────────────────────────
const reviewsStatusFilter = ref('')
const reviewsToggleSaving = ref(false)
const reviewsEnabledLocal = ref(false)

watch(product, p => {
  if (p) reviewsEnabledLocal.value = !!p.reviews_enabled
}, { immediate: true })

const reviewsQuery = computed(() => {
  const params = new URLSearchParams({ per_page: '100' })
  if (reviewsStatusFilter.value) params.append('status', reviewsStatusFilter.value)
  return params.toString()
})

const { data: reviewsData, execute: refreshReviews, isFetching: reviewsFetching } = useApi(
  computed(() => `/products/${productId.value}/reviews?${reviewsQuery.value}`),
)

const reviews = computed(() => reviewsData.value?.data ?? [])

const toggleReviews = async val => {
  reviewsToggleSaving.value = true
  try {
    await $api(`/products/${productId.value}/settings/reviews`, {
      method: 'PATCH',
      body: { enabled: !!val },
    })
    refresh()
  }
  catch {
    // revert local state on error
    reviewsEnabledLocal.value = !val
  }
  finally {
    reviewsToggleSaving.value = false
  }
}

const moderateReview = async (review, action) => {
  try {
    await $api(`/products/${productId.value}/reviews/${review.id}/moderate`, {
      method: 'PATCH',
      body: { action },
    })
    refreshReviews()
    refresh()
  }
  catch {
    // silent
  }
}

const rejectDialog = ref(false)
const rejectReview = ref(null)
const rejectReason = ref('')
const rejectLoading = ref(false)
const rejectErrorMsg = ref('')

const openReject = review => {
  rejectReview.value = review
  rejectReason.value = ''
  rejectErrorMsg.value = ''
  rejectDialog.value = true
}

const submitReject = async () => {
  rejectLoading.value = true
  rejectErrorMsg.value = ''
  try {
    await $api(`/products/${productId.value}/reviews/${rejectReview.value.id}/moderate`, {
      method: 'PATCH',
      body: { action: 'reject', rejection_reason: rejectReason.value },
    })
    rejectDialog.value = false
    refreshReviews()
    refresh()
  }
  catch (err) {
    rejectErrorMsg.value = err?.response?._data?.message ?? 'Erreur'
  }
  finally {
    rejectLoading.value = false
  }
}

const deleteReview = async review => {
  if (!confirm('Supprimer cet avis ?')) return
  try {
    await $api(`/products/${productId.value}/reviews/${review.id}`, { method: 'DELETE' })
    refreshReviews()
    refresh()
  }
  catch {
    // silent
  }
}

// ── Suppliers ────────────────────────────────────────────────────────────
const { data: suppliersListData } = await useApi('/suppliers?only_active=1&per_page=200')

const supplierLookup = computed(() =>
  suppliersListData.value?.data?.map(s => ({ title: s.name, value: s.id, _raw: s })) ?? [],
)

const attachedSuppliers = computed(() => product.value?.suppliers ?? [])

const supplierDialog = ref(false)
const supplierEditing = ref(null)
const supplierForm = ref({})
const supplierErrorMsg = ref('')
const supplierSaving = ref(false)

const openAddSupplier = () => {
  supplierEditing.value = null
  supplierForm.value = {
    supplier_id: null,
    supplier_price: null,
    currency: 'XOF',
    moq: null,
    lead_time_days: null,
    is_primary: false,
    notes: '',
  }
  supplierErrorMsg.value = ''
  supplierDialog.value = true
}

const openEditSupplier = supplier => {
  supplierEditing.value = supplier
  const pivot = supplier.pivot ?? {}
  supplierForm.value = {
    supplier_id: supplier.id,
    supplier_price: pivot.supplier_price ?? null,
    currency: pivot.currency ?? 'XOF',
    moq: pivot.moq ?? null,
    lead_time_days: pivot.lead_time_days ?? null,
    is_primary: !!pivot.is_primary,
    notes: pivot.notes ?? '',
  }
  supplierErrorMsg.value = ''
  supplierDialog.value = true
}

const submitSupplier = async () => {
  if (!supplierForm.value.supplier_id) {
    supplierErrorMsg.value = 'Veuillez sélectionner un fournisseur'
    return
  }
  supplierSaving.value = true
  supplierErrorMsg.value = ''
  try {
    const body = { ...supplierForm.value }
    Object.keys(body).forEach(k => { if (body[k] === '') body[k] = null })
    if (supplierEditing.value) {
      await $api(`/products/${productId.value}/suppliers/${supplierEditing.value.id}`, {
        method: 'PATCH',
        body,
      })
    }
    else {
      await $api(`/products/${productId.value}/suppliers`, {
        method: 'POST',
        body,
      })
    }
    supplierDialog.value = false
    refresh()
  }
  catch (err) {
    supplierErrorMsg.value = err?.response?._data?.message ?? 'Erreur'
  }
  finally {
    supplierSaving.value = false
  }
}

const detachSupplier = async supplier => {
  if (!confirm(`Détacher le fournisseur "${supplier.name}" ?`)) return
  try {
    await $api(`/products/${productId.value}/suppliers/${supplier.id}`, { method: 'DELETE' })
    refresh()
  }
  catch {
    // silent
  }
}

// ── File rules ───────────────────────────────────────────────────────────
const imageFileRules = [
  v => {
    if (!v) return true
    const file = Array.isArray(v) ? v[0] : v
    if (!file) return true
    return file.size <= 8 * 1024 * 1024 || 'Le fichier doit faire moins de 8 Mo'
  },
]
</script>

<template>
  <div>
    <div v-if="isFetching && !product" class="d-flex justify-center pa-8">
      <VProgressCircular indeterminate color="primary" />
    </div>

    <template v-else-if="product">
      <!-- Header bar -->
      <div class="d-flex align-center flex-wrap gap-3 mb-4">
        <VBtn
          variant="text"
          prepend-icon="tabler-arrow-left"
          to="/products"
        >
          {{ $t('Catalogue véhicules') }}
        </VBtn>

        <div class="d-flex align-center flex-wrap gap-2 flex-grow-1">
          <span class="text-h5 font-weight-bold">
            {{ product.brand?.name ?? '' }} {{ product.model?.name ?? '' }}
          </span>
          <span class="text-h6 text-medium-emphasis">— {{ product.name }}</span>
          <VChip
            v-if="product.type"
            size="small"
            variant="tonal"
            color="primary"
          >
            <VIcon
              start
              :icon="product.type?.slug === 'vehicle' ? 'tabler-car' : 'tabler-package'"
              size="14"
            />
            {{ product.type?.label }}
          </VChip>
        </div>

        <div class="d-flex align-center gap-2 flex-wrap">
          <VBtn
            variant="tonal"
            prepend-icon="tabler-edit"
            @click="openEdit"
          >
            Modifier
          </VBtn>
          <VBtn
            variant="tonal"
            color="error"
            prepend-icon="tabler-trash"
            @click="dialogDelete = true"
          >
            Supprimer
          </VBtn>
        </div>
      </div>

      <!-- KPI row -->
      <VRow class="mb-4">
        <VCol cols="12" sm="6" md="3">
          <VCard rounded="lg">
            <VCardText>
              <div class="text-body-2 text-medium-emphasis mb-1">
                Prix fournisseur
              </div>
              <div class="text-h5 font-weight-bold">
                {{ formatPrice(product.commercial?.supplier_price, product.commercial?.currency) }}
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" md="3">
          <VCard rounded="lg">
            <VCardText>
              <div class="text-body-2 text-medium-emphasis mb-1">
                Total importé
              </div>
              <div class="text-h5 font-weight-bold">
                {{ formatPrice(product.customs_duty?.totals?.total_import_cost, product.commercial?.currency) }}
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" md="3">
          <VCard rounded="lg">
            <VCardText>
              <div class="text-body-2 text-medium-emphasis mb-1">
                {{ $t('Note moyenne') }}
              </div>
              <div class="d-flex align-center gap-2">
                <div class="text-h5 font-weight-bold">
                  {{ product.average_rating ?? '–' }}
                </div>
                <VRating
                  v-if="product.average_rating"
                  :model-value="Number(product.average_rating)"
                  readonly
                  density="compact"
                  size="small"
                  half-increments
                />
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <VCol cols="12" sm="6" md="3">
          <VCard rounded="lg">
            <VCardText>
              <div class="text-body-2 text-medium-emphasis mb-1">
                {{ $t('Avis publiés') }}
              </div>
              <div class="text-h5 font-weight-bold">
                {{ product.reviews_count ?? 0 }}
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Tabs -->
      <VCard rounded="lg">
        <VTabs v-model="currentTab">
          <VTab value="overview">
            <VIcon start icon="tabler-info-circle" size="18" />
            {{ $t('Aperçu') }}
          </VTab>
          <VTab value="gallery">
            <VIcon start icon="tabler-photo" size="18" />
            {{ $t('Galerie photos') }}
          </VTab>
          <VTab value="reviews">
            <VIcon start icon="tabler-star" size="18" />
            {{ $t('Avis clients') }}
          </VTab>
          <VTab value="suppliers">
            <VIcon start icon="tabler-building-store" size="18" />
            {{ $t('Fournisseurs liés') }}
          </VTab>
        </VTabs>

        <VDivider />

        <VWindow v-model="currentTab">
          <!-- ── Onglet 1: Aperçu ─────────────────────────── -->
          <VWindowItem value="overview">
            <VCardText>
              <!-- Identification -->
              <div class="text-h6 mb-3">{{ $t('Identification') }}</div>
              <VRow>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Nom</div>
                  <div class="text-body-1 font-weight-medium mb-3">{{ product.name }}</div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Marque</div>
                  <div class="text-body-1 font-weight-medium mb-3">{{ product.brand?.name ?? '–' }}</div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Modèle</div>
                  <div class="text-body-1 font-weight-medium mb-3">{{ product.model?.name ?? '–' }}</div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Type</div>
                  <div class="text-body-1 font-weight-medium mb-3">{{ product.type?.label ?? '–' }}</div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Catégorie</div>
                  <div class="text-body-1 font-weight-medium mb-3">{{ product.category ?? '–' }}</div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Sous-catégorie</div>
                  <div class="text-body-1 font-weight-medium mb-3">{{ product.sub_category ?? '–' }}</div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Pays d'origine</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ product.origin?.country?.name ?? product.origin?.country_of_origin ?? '–' }}
                  </div>
                </VCol>
                <VCol cols="12" md="6">
                  <div class="text-body-2 text-medium-emphasis">Fournisseur principal</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ product.origin?.supplier ?? '–' }}
                  </div>
                </VCol>
              </VRow>

              <VDivider class="my-4" />

              <!-- Caractéristiques -->
              <div class="text-h6 mb-3">{{ $t('Caractéristiques') }}</div>
              <VRow>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Poids</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ product.specifications?.weight_kg ? `${product.specifications.weight_kg} kg` : '–' }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Dimensions</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ product.specifications?.dimensions ?? '–' }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">CBM</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ product.specifications?.cbm ?? '–' }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Prix fournisseur</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatPrice(product.commercial?.supplier_price, product.commercial?.currency) }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Devise</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ product.commercial?.currency ?? '–' }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">MOQ</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ product.commercial?.moq ?? '–' }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Incoterm</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ product.commercial?.incoterm ?? '–' }}
                  </div>
                </VCol>
              </VRow>

              <!-- Vehicle-specific -->
              <template v-if="product.vehicle">
                <VDivider class="my-4" />
                <div class="text-h6 mb-3">Véhicule</div>
                <VRow>
                  <VCol cols="12" md="4">
                    <div class="text-body-2 text-medium-emphasis">Châssis</div>
                    <div class="text-body-1 font-weight-medium mb-3">
                      {{ product.vehicle.chassis_number ?? product.vehicle.chassis ?? '–' }}
                    </div>
                  </VCol>
                  <VCol cols="12" md="4">
                    <div class="text-body-2 text-medium-emphasis">Année</div>
                    <div class="text-body-1 font-weight-medium mb-3">
                      {{ product.vehicle.year ?? '–' }}
                    </div>
                  </VCol>
                  <VCol cols="12" md="4">
                    <div class="text-body-2 text-medium-emphasis">Cylindrée</div>
                    <div class="text-body-1 font-weight-medium mb-3">
                      {{ product.vehicle.engine_displacement ?? '–' }}
                    </div>
                  </VCol>
                  <VCol cols="12" md="4">
                    <div class="text-body-2 text-medium-emphasis">Puissance fiscale</div>
                    <div class="text-body-1 font-weight-medium mb-3">
                      {{ product.vehicle.fiscal_power ?? '–' }}
                    </div>
                  </VCol>
                  <VCol cols="12" md="4">
                    <div class="text-body-2 text-medium-emphasis">Taux officiel</div>
                    <div class="text-body-1 font-weight-medium mb-3">
                      {{ product.vehicle.official_rate ?? '–' }}
                    </div>
                  </VCol>
                  <VCol cols="12" md="4">
                    <div class="text-body-2 text-medium-emphasis">Date d'évaluation</div>
                    <div class="text-body-1 font-weight-medium mb-3">
                      {{ formatDate(product.vehicle.evaluation_date) }}
                    </div>
                  </VCol>
                </VRow>
              </template>

              <!-- Transport -->
              <VDivider class="my-4" />
              <div class="text-h6 mb-3">Transport</div>
              <VRow>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Mode</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ product.transport?.mode?.label ?? product.transport?.mode ?? '–' }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Coût principal</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatPrice(product.transport?.main_cost, product.commercial?.currency) }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Assurance</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatPrice(product.transport?.insurance_cost, product.commercial?.currency) }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Coût post-dédouanement</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatPrice(product.transport?.post_clearance_cost, product.commercial?.currency) }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Durée (jours)</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ product.transport?.duration_days ?? '–' }}
                  </div>
                </VCol>
              </VRow>

              <!-- Douane UEMOA -->
              <VDivider class="my-4" />
              <div class="text-h6 mb-3">{{ $t('Douane UEMOA') }}</div>
              <VRow>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">CIF</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatPrice(product.customs_duty?.totals?.cif, product.commercial?.currency) }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Valeur en douane</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatPrice(product.customs_duty?.totals?.customs_value, product.commercial?.currency) }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Total taxes</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatPrice(product.customs_duty?.totals?.total_taxes, product.commercial?.currency) }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Total transport</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatPrice(product.customs_duty?.totals?.total_transport, product.commercial?.currency) }}
                  </div>
                </VCol>
                <VCol cols="12" md="4">
                  <div class="text-body-2 text-medium-emphasis">Total importé</div>
                  <div class="text-body-1 font-weight-medium mb-3">
                    {{ formatPrice(product.customs_duty?.totals?.total_import_cost, product.commercial?.currency) }}
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VWindowItem>

          <!-- ── Onglet 2: Galerie ────────────────────────── -->
          <VWindowItem value="gallery">
            <VCardText>
              <div class="d-flex justify-space-between align-center mb-3 flex-wrap gap-2">
                <span class="text-body-2 text-medium-emphasis">
                  {{ (product.images?.length ?? 0) }} image(s)
                </span>
                <VBtn
                  color="primary"
                  prepend-icon="tabler-upload"
                  @click="openUpload"
                >
                  {{ $t('Téléverser une photo') }}
                </VBtn>
              </div>

              <VAlert
                v-if="!product.images?.length"
                type="info"
                variant="tonal"
              >
                Aucune image pour ce produit.
              </VAlert>

              <VRow v-else>
                <VCol
                  v-for="(img, idx) in product.images"
                  :key="img.id"
                  cols="12"
                  sm="6"
                  md="4"
                  lg="3"
                >
                  <VCard rounded="lg" variant="outlined">
                    <div
                      class="position-relative"
                      style="aspect-ratio: 4/3; overflow: hidden; background: rgba(0,0,0,0.04);"
                    >
                      <VImg
                        :src="imageUrl(img)"
                        :alt="img.alt_text ?? img.original_filename ?? ''"
                        cover
                        height="100%"
                      />
                      <VChip
                        v-if="img.is_primary"
                        size="x-small"
                        color="success"
                        variant="elevated"
                        class="position-absolute"
                        style="top: 8px; left: 8px;"
                      >
                        <VIcon start icon="tabler-star" size="12" />
                        Principale
                      </VChip>
                    </div>

                    <VCardText class="py-2">
                      <div class="text-caption text-truncate" :title="img.original_filename">
                        {{ img.original_filename ?? `Image #${img.id}` }}
                      </div>
                      <div class="text-caption text-medium-emphasis">
                        {{ formatBytes(img.file_size) }}
                      </div>
                    </VCardText>

                    <VDivider />

                    <VCardActions class="py-1 px-2">
                      <VBtn
                        icon
                        size="x-small"
                        variant="text"
                        :disabled="idx === 0"
                        title="Monter"
                        @click="moveImage(img, 'up')"
                      >
                        <VIcon size="16" icon="tabler-arrow-up" />
                      </VBtn>
                      <VBtn
                        icon
                        size="x-small"
                        variant="text"
                        :disabled="idx === product.images.length - 1"
                        title="Descendre"
                        @click="moveImage(img, 'down')"
                      >
                        <VIcon size="16" icon="tabler-arrow-down" />
                      </VBtn>
                      <VSpacer />
                      <VBtn
                        icon
                        size="x-small"
                        variant="text"
                        color="error"
                        title="Supprimer"
                        @click="deleteImage(img)"
                      >
                        <VIcon size="16" icon="tabler-trash" />
                      </VBtn>
                    </VCardActions>
                  </VCard>
                </VCol>
              </VRow>
            </VCardText>
          </VWindowItem>

          <!-- ── Onglet 3: Avis clients ───────────────────── -->
          <VWindowItem value="reviews">
            <VCardText>
              <VAlert type="info" variant="tonal" density="compact" class="mb-4">
                Les avis sont publics dès qu'ils sont publiés. Vous pouvez activer ou désactiver
                leur soumission via le toggle ci-dessous.
              </VAlert>

              <div class="d-flex flex-wrap align-center gap-4 mb-4">
                <VSwitch
                  v-model="reviewsEnabledLocal"
                  :label="$t('Activer les avis')"
                  color="primary"
                  hide-details
                  :loading="reviewsToggleSaving"
                  @update:model-value="toggleReviews"
                />
                <VSpacer />
                <AppSelect
                  v-model="reviewsStatusFilter"
                  :items="REVIEW_STATUS_FILTERS"
                  density="compact"
                  hide-details
                  style="inline-size: 200px;"
                />
              </div>

              <VAlert
                v-if="!reviewsFetching && !reviews.length"
                type="info"
                variant="tonal"
              >
                Aucun avis pour le moment.
              </VAlert>

              <div v-else class="d-flex flex-column gap-3">
                <VCard
                  v-for="review in reviews"
                  :key="review.id"
                  variant="outlined"
                  rounded="lg"
                >
                  <VCardText>
                    <div class="d-flex align-start justify-space-between flex-wrap gap-2 mb-2">
                      <div class="d-flex align-center gap-2 flex-wrap">
                        <VRating
                          :model-value="Number(review.rating ?? 0)"
                          readonly
                          density="compact"
                          size="small"
                          half-increments
                        />
                        <VChip
                          :color="REVIEW_STATUS_COLORS[review.status] ?? 'default'"
                          size="x-small"
                        >
                          {{ REVIEW_STATUS_LABELS[review.status] ?? review.status }}
                        </VChip>
                      </div>
                      <span class="text-caption text-medium-emphasis">
                        {{ formatDate(review.created_at) }}
                      </span>
                    </div>

                    <div v-if="review.title" class="text-body-1 font-weight-bold mb-1">
                      {{ review.title }}
                    </div>
                    <div class="text-body-2 mb-2">
                      {{ review.comment }}
                    </div>
                    <div class="text-caption text-medium-emphasis mb-2">
                      Par {{ review.reviewer_name || 'Anonyme' }}
                      <span v-if="review.reviewer_email"> · {{ review.reviewer_email }}</span>
                    </div>

                    <div
                      v-if="review.status === 'rejected' && review.rejection_reason"
                      class="text-caption text-error mb-2"
                    >
                      Motif du rejet : {{ review.rejection_reason }}
                    </div>

                    <div class="d-flex flex-wrap gap-1">
                      <VBtn
                        v-if="review.status !== 'published'"
                        size="x-small"
                        variant="tonal"
                        color="success"
                        prepend-icon="tabler-check"
                        @click="moderateReview(review, 'publish')"
                      >
                        Publier
                      </VBtn>
                      <VBtn
                        v-if="review.status !== 'rejected'"
                        size="x-small"
                        variant="tonal"
                        color="error"
                        prepend-icon="tabler-x"
                        @click="openReject(review)"
                      >
                        Rejeter
                      </VBtn>
                      <VBtn
                        v-if="review.status !== 'pending'"
                        size="x-small"
                        variant="text"
                        color="error"
                        prepend-icon="tabler-trash"
                        @click="deleteReview(review)"
                      >
                        Supprimer
                      </VBtn>
                    </div>
                  </VCardText>
                </VCard>
              </div>
            </VCardText>
          </VWindowItem>

          <!-- ── Onglet 4: Fournisseurs liés ──────────────── -->
          <VWindowItem value="suppliers">
            <VCardText>
              <div class="d-flex justify-end mb-3">
                <VBtn
                  color="primary"
                  prepend-icon="tabler-plus"
                  @click="openAddSupplier"
                >
                  Ajouter un fournisseur
                </VBtn>
              </div>

              <VAlert
                v-if="!attachedSuppliers.length"
                type="info"
                variant="tonal"
              >
                <template v-if="product.suppliers === undefined">
                  Les fournisseurs liés ne sont pas encore exposés dans la réponse produit.
                  Le rattachement reste fonctionnel via le bouton « Ajouter un fournisseur ».
                </template>
                <template v-else>
                  Aucun fournisseur rattaché à ce produit.
                </template>
              </VAlert>

              <VTable v-else>
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Pays</th>
                    <th class="text-right">Prix</th>
                    <th class="text-right">MOQ</th>
                    <th class="text-right">Délai (j)</th>
                    <th class="text-center">Principal</th>
                    <th class="text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in attachedSuppliers" :key="s.id">
                    <td>
                      <div class="d-flex align-center gap-2">
                        <span>{{ s.name }}</span>
                        <VIcon
                          v-if="s.is_verified"
                          size="14"
                          color="success"
                          icon="tabler-rosette-discount-check"
                          title="Fournisseur vérifié"
                        />
                      </div>
                    </td>
                    <td>{{ s.category?.name ?? '–' }}</td>
                    <td>{{ s.country?.name ?? s.country_name ?? '–' }}</td>
                    <td class="text-right">
                      {{ formatPrice(s.pivot?.supplier_price, s.pivot?.currency) }}
                    </td>
                    <td class="text-right">{{ s.pivot?.moq ?? '–' }}</td>
                    <td class="text-right">{{ s.pivot?.lead_time_days ?? '–' }}</td>
                    <td class="text-center">
                      <VIcon
                        v-if="s.pivot?.is_primary"
                        size="18"
                        color="success"
                        icon="tabler-star-filled"
                      />
                      <span v-else class="text-medium-emphasis">–</span>
                    </td>
                    <td class="text-right">
                      <div class="d-flex justify-end gap-1">
                        <VBtn
                          icon
                          size="x-small"
                          variant="text"
                          title="Modifier"
                          @click="openEditSupplier(s)"
                        >
                          <VIcon size="16" icon="tabler-edit" />
                        </VBtn>
                        <VBtn
                          icon
                          size="x-small"
                          variant="text"
                          color="error"
                          title="Détacher"
                          @click="detachSupplier(s)"
                        >
                          <VIcon size="16" icon="tabler-trash" />
                        </VBtn>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </VCardText>
          </VWindowItem>
        </VWindow>
      </VCard>
    </template>

    <!-- ── Edit dialog ────────────────────────────────────── -->
    <VDialog v-model="editDialog" max-width="700" scrollable>
      <VCard title="Modifier le produit">
        <VCardText style="max-block-size: 70vh;">
          <VForm ref="editFormRef">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="editForm.name"
                  label="Nom"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="editForm.description"
                  label="Description"
                  rows="3"
                />
              </VCol>
              <VCol cols="12" md="8">
                <AppTextField
                  v-model.number="editForm.supplier_price"
                  type="number"
                  label="Prix fournisseur"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppSelect
                  v-model="editForm.currency"
                  :items="CURRENCY_OPTIONS"
                  label="Devise"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="editForm.moq"
                  type="number"
                  label="MOQ"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="editForm.incoterm"
                  label="Incoterm"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="editForm.weight_kg"
                  type="number"
                  label="Poids (kg)"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="editForm.cbm"
                  type="number"
                  label="CBM"
                />
              </VCol>
            </VRow>

            <VAlert
              v-if="editErrorMsg"
              type="error"
              variant="tonal"
              class="mt-3"
            >
              {{ editErrorMsg }}
            </VAlert>
          </VForm>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="editDialog = false">Annuler</VBtn>
          <VBtn color="primary" :loading="editSaving" @click="saveEdit">
            Enregistrer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Image upload dialog ────────────────────────────── -->
    <VDialog v-model="uploadDialog" max-width="500">
      <VCard :title="$t('Téléverser une photo')">
        <VCardText>
          <VFileInput
            v-model="uploadFile"
            label="Image"
            accept="image/jpeg,image/png,image/webp"
            prepend-icon=""
            prepend-inner-icon="tabler-photo"
            show-size
            :rules="imageFileRules"
          />
          <div class="text-caption text-medium-emphasis mb-3">
            Formats : JPG, PNG, WEBP. Taille max : 8 Mo.
          </div>

          <AppTextField
            v-model="uploadAltText"
            label="Texte alternatif (alt)"
            class="mb-3"
          />

          <VSwitch
            v-model="uploadIsPrimary"
            label="Définir comme image principale"
            color="primary"
            hide-details
          />

          <VAlert
            v-if="uploadErrorMsg"
            type="error"
            variant="tonal"
            class="mt-3"
          >
            {{ uploadErrorMsg }}
          </VAlert>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="uploadDialog = false">Annuler</VBtn>
          <VBtn
            color="primary"
            :loading="uploading"
            @click="submitUpload"
          >
            Téléverser
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Reject review dialog ───────────────────────────── -->
    <VDialog v-model="rejectDialog" max-width="500">
      <VCard title="Rejeter l'avis">
        <VCardText>
          <p class="text-body-2 mb-3">
            Indiquer la raison du rejet de cet avis.
          </p>
          <AppTextarea
            v-model="rejectReason"
            label="Raison du rejet"
            rows="3"
          />
          <VAlert
            v-if="rejectErrorMsg"
            type="error"
            variant="tonal"
            class="mt-3"
          >
            {{ rejectErrorMsg }}
          </VAlert>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="rejectDialog = false">Annuler</VBtn>
          <VBtn
            color="error"
            :loading="rejectLoading"
            @click="submitReject"
          >
            Rejeter
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Supplier add/edit dialog ───────────────────────── -->
    <VDialog v-model="supplierDialog" max-width="600">
      <VCard :title="supplierEditing ? 'Modifier le fournisseur lié' : 'Ajouter un fournisseur'">
        <VCardText>
          <VRow>
            <VCol cols="12">
              <AppAutocomplete
                v-model="supplierForm.supplier_id"
                :items="supplierLookup"
                label="Fournisseur"
                :disabled="!!supplierEditing"
              />
            </VCol>
            <VCol cols="12" md="8">
              <AppTextField
                v-model.number="supplierForm.supplier_price"
                type="number"
                label="Prix fournisseur"
              />
            </VCol>
            <VCol cols="12" md="4">
              <AppSelect
                v-model="supplierForm.currency"
                :items="CURRENCY_OPTIONS"
                label="Devise"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model.number="supplierForm.moq"
                type="number"
                label="MOQ"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model.number="supplierForm.lead_time_days"
                type="number"
                label="Délai (jours)"
              />
            </VCol>
            <VCol cols="12">
              <VSwitch
                v-model="supplierForm.is_primary"
                label="Fournisseur principal"
                color="primary"
                hide-details
              />
            </VCol>
            <VCol cols="12">
              <AppTextarea
                v-model="supplierForm.notes"
                label="Notes"
                rows="2"
              />
            </VCol>
          </VRow>

          <VAlert
            v-if="supplierErrorMsg"
            type="error"
            variant="tonal"
            class="mt-3"
          >
            {{ supplierErrorMsg }}
          </VAlert>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="supplierDialog = false">Annuler</VBtn>
          <VBtn
            color="primary"
            :loading="supplierSaving"
            @click="submitSupplier"
          >
            {{ supplierEditing ? 'Enregistrer' : 'Ajouter' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Delete dialog ──────────────────────────────────── -->
    <VDialog v-model="dialogDelete" max-width="400">
      <VCard title="Supprimer le produit">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer
          <strong>{{ product?.name }}</strong> ?
          Cette action est irréversible.
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="tonal" @click="dialogDelete = false">Annuler</VBtn>
          <VBtn
            color="error"
            :loading="deleting"
            @click="deleteProduct"
          >
            Supprimer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
