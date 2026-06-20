<script setup>
import { useRoute, useRouter } from 'vue-router'

definePage({ meta: { layout: 'default' } })

const route = useRoute()
const router = useRouter()
const quoteId = computed(() => route.params.id)

const snackbar = reactive({ show: false, color: 'success', message: '' })
const notify = (message, color = 'success') => {
  snackbar.message = message
  snackbar.color = color
  snackbar.show = true
}

const { data, isFetching, execute: refresh } = useApi(
  computed(() => `/quotes/${quoteId.value}`),
)
const quote = computed(() => data.value?.data ?? null)

const STATUS_LABEL = {
  pending: 'En attente',
  processing: 'En traitement',
  sent: 'Envoyé',
  closed: 'Clôturé',
}
const STATUS_COLOR = {
  pending: 'warning',
  processing: 'info',
  sent: 'success',
  closed: 'secondary',
}
// Mirror App\Enums\QuoteStatus::allowedTransitions().
const STATUS_NEXT = {
  pending: ['processing', 'closed'],
  processing: ['sent', 'closed'],
  sent: ['closed'],
  closed: [],
}

const formatDate = d => d ? new Date(d).toLocaleString('fr-FR') : '–'
const formatPrice = v => v == null ? '–' : `${Number(v).toLocaleString('fr-FR')} XOF`

const updatingStatus = ref(false)
const transitionNote = ref('')
const dialogTransition = ref(false)
const pendingTarget = ref(null)

const openTransitionDialog = target => {
  pendingTarget.value = target
  transitionNote.value = ''
  dialogTransition.value = true
}

const confirmTransition = async () => {
  const target = pendingTarget.value
  if (!target) return
  updatingStatus.value = true
  try {
    await $api(`/quotes/${quoteId.value}/status`, {
      method: 'PATCH',
      body: { status: target, note: transitionNote.value || null },
    })
    notify(`Statut mis à jour : ${STATUS_LABEL[target]}.`)
    dialogTransition.value = false
    pendingTarget.value = null
    refresh()
  }
  catch (err) {
    notify(err?.data?.message ?? 'Mise à jour du statut refusée.', 'error')
  }
  finally {
    updatingStatus.value = false
  }
}

// ── PDF export
const downloadingPdf = ref(false)
const downloadPdf = async () => {
  downloadingPdf.value = true
  try {
    const accessToken = useCookie('accessToken').value
    const base = import.meta.env.VITE_API_BASE_URL || '/api'
    const response = await fetch(`${base}/quotes/${quoteId.value}/pdf`, {
      headers: { Authorization: `Bearer ${accessToken}`, Accept: 'application/pdf' },
    })
    if (!response.ok) throw new Error('PDF generation failed')
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `devis-${quoteId.value}.pdf`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  }
  catch {
    notify('Erreur lors de la génération du PDF.', 'error')
  }
  finally {
    downloadingPdf.value = false
  }
}

// ── Convert to dossier dialog
const { data: transportModesData } = await useApi('/transport-modes?per_page=50')
const { data: carriersData } = await useApi('/carriers?per_page=200')
const { data: portsData } = await useApi('/ports?per_page=200')

const transportModes = computed(() => transportModesData.value?.data ?? [])
const carriers = computed(() => carriersData.value?.data ?? [])
const ports = computed(() => portsData.value?.data ?? [])

const dialogConvert = ref(false)
const converting = ref(false)
const convertForm = reactive({
  type: 'vehicle',
  title: '',
  transport_mode_id: null,
  carrier_id: null,
  origin_port_id: null,
  destination_port_id: null,
  estimated_departure: null,
  estimated_arrival: null,
  total_estimated_cost: 0,
  currency: 'XOF',
  notes: '',
})

const openConvert = () => {
  if (!quote.value) return
  convertForm.title = `Conversion devis #${quote.value.id}`
  convertForm.notes = quote.value.notes ?? ''
  convertForm.total_estimated_cost = Number(quote.value.product?.commercial?.supplier_price ?? 0)
  dialogConvert.value = true
}

const submitConvert = async () => {
  if (!convertForm.transport_mode_id) {
    notify('Sélectionne un mode de transport.', 'warning')
    return
  }
  converting.value = true
  try {
    const res = await $api(`/quote-requests/${quoteId.value}/convert-to-dossier`, {
      method: 'POST',
      body: { ...convertForm },
    })
    const dossierId = res?.data?.id ?? res?.id
    notify('Devis converti en dossier avec succès.')
    dialogConvert.value = false
    setTimeout(() => router.push(dossierId ? `/dossiers/${dossierId}` : '/dossiers'), 400)
  }
  catch (err) {
    const msg = err?.data?.message
      ?? Object.values(err?.data?.errors ?? {}).flat().join(' ')
      ?? 'Impossible de convertir le devis.'
    notify(msg, 'error')
  }
  finally {
    converting.value = false
  }
}
</script>

<template>
  <div>
    <VBtn
      variant="text"
      prepend-icon="tabler-arrow-left"
      class="mb-4"
      @click="router.push('/quotes')"
    >
      Retour aux devis
    </VBtn>

    <VProgressLinear v-if="isFetching && !quote" indeterminate />

    <template v-if="quote">
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
          <div class="d-flex align-center gap-3">
            <span class="text-h6">Devis #{{ quote.id }}</span>
            <VChip :color="STATUS_COLOR[quote.status]" size="small" variant="tonal">
              {{ STATUS_LABEL[quote.status] ?? quote.status }}
            </VChip>
          </div>
          <span class="text-body-2 text-medium-emphasis">
            Créé le {{ formatDate(quote.created_at) }}
          </span>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <VRow>
            <VCol cols="12" md="6">
              <div class="text-overline text-medium-emphasis mb-2">Produit</div>
              <div class="text-body-1 font-weight-medium">
                {{ quote.product?.name ?? '–' }}
              </div>
              <div class="text-body-2 text-medium-emphasis mt-1">
                Marque : {{ quote.product?.brand?.name ?? '–' }} ·
                Modèle : {{ quote.product?.model?.name ?? '–' }} ·
                Pays : {{ quote.product?.origin?.country?.name ?? '–' }}
              </div>
              <div class="text-body-2 mt-2">
                Prix fournisseur :
                <strong>{{ formatPrice(quote.product?.commercial?.supplier_price) }}</strong>
              </div>
            </VCol>

            <VCol cols="12" md="6">
              <div class="text-overline text-medium-emphasis mb-2">Demandeur</div>
              <div class="text-body-1 font-weight-medium">
                {{ quote.user?.first_name }} {{ quote.user?.last_name }}
              </div>
              <div class="text-body-2 text-medium-emphasis">
                {{ quote.user?.email ?? '–' }}
              </div>
              <div class="text-body-2 mt-2">
                Quantité : <strong>{{ quote.quantity }}</strong>
              </div>
            </VCol>
          </VRow>

          <VDivider class="my-4" />

          <div class="text-overline text-medium-emphasis mb-2">Notes &amp; estimation</div>
          <VCard
            v-if="quote.notes"
            variant="tonal"
            class="pa-3 mb-2 text-body-2"
            style="white-space: pre-line; font-family: monospace, monospace;"
          >
            {{ quote.notes }}
          </VCard>
          <p v-else class="text-body-2 text-medium-emphasis">Aucune note.</p>

          <VDivider class="my-4" />

          <div class="text-overline text-medium-emphasis mb-2">Historique des transitions</div>
          <VTimeline
            v-if="quote.status_transitions && quote.status_transitions.length"
            density="compact"
            side="end"
            align="start"
            line-thickness="2"
            truncate-line="start"
          >
            <VTimelineItem
              v-for="t in quote.status_transitions"
              :key="t.id"
              :dot-color="STATUS_COLOR[t.to_status] ?? 'grey'"
              size="x-small"
            >
              <div class="d-flex align-center flex-wrap gap-2">
                <span class="text-body-2 font-weight-medium">
                  <template v-if="t.from_status">
                    <VChip
                      :color="STATUS_COLOR[t.from_status] ?? 'default'"
                      size="x-small"
                      variant="tonal"
                    >
                      {{ STATUS_LABEL[t.from_status] ?? t.from_status }}
                    </VChip>
                    <VIcon icon="tabler-arrow-right" size="14" class="mx-1" />
                  </template>
                  <VChip
                    :color="STATUS_COLOR[t.to_status] ?? 'default'"
                    size="x-small"
                    variant="tonal"
                  >
                    {{ STATUS_LABEL[t.to_status] ?? t.to_status }}
                  </VChip>
                </span>
                <span class="text-caption text-medium-emphasis">
                  par {{ t.changed_by?.name ?? 'Système' }} · {{ formatDate(t.created_at) }}
                </span>
              </div>
              <div
                v-if="t.note"
                class="text-caption text-medium-emphasis mt-1"
                style="white-space: pre-line"
              >
                {{ t.note }}
              </div>
            </VTimelineItem>
          </VTimeline>
          <p v-else class="text-body-2 text-medium-emphasis">Aucune transition enregistrée.</p>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4 d-flex flex-wrap gap-2">
          <template v-for="next in STATUS_NEXT[quote.status] ?? []" :key="next">
            <VBtn
              :color="STATUS_COLOR[next]"
              variant="flat"
              :loading="updatingStatus"
              @click="openTransitionDialog(next)"
            >
              Passer à : {{ STATUS_LABEL[next] }}
            </VBtn>
          </template>
          <VBtn
            v-if="quote.status === 'sent'"
            color="primary"
            variant="flat"
            prepend-icon="tabler-folder-plus"
            @click="openConvert"
          >
            Convertir en dossier
          </VBtn>
          <VSpacer />
          <VBtn
            variant="outlined"
            prepend-icon="tabler-file-download"
            :loading="downloadingPdf"
            @click="downloadPdf"
          >
            Télécharger PDF
          </VBtn>
        </VCardActions>
      </VCard>
    </template>

    <VDialog v-model="dialogConvert" max-width="640" persistent>
      <VCard title="Convertir le devis en dossier">
        <VCardText>
          <VRow>
            <VCol cols="12">
              <AppTextField v-model="convertForm.title" label="Titre du dossier" />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="convertForm.type"
                :items="[{ title: 'Véhicule', value: 'vehicle' }, { title: 'Marchandise', value: 'goods' }]"
                label="Type *"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="convertForm.transport_mode_id"
                :items="transportModes.map(m => ({ title: m.label ?? m.slug, value: m.id }))"
                label="Mode de transport *"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="convertForm.carrier_id"
                :items="[{ title: 'Aucun', value: null }, ...carriers.map(c => ({ title: c.name, value: c.id }))]"
                label="Transporteur"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model.number="convertForm.total_estimated_cost"
                label="Coût total estimé (XOF)"
                type="number"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="convertForm.origin_port_id"
                :items="[{ title: 'Aucun', value: null }, ...ports.map(p => ({ title: `${p.code} — ${p.name}`, value: p.id }))]"
                label="Port d'origine"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="convertForm.destination_port_id"
                :items="[{ title: 'Aucun', value: null }, ...ports.map(p => ({ title: `${p.code} — ${p.name}`, value: p.id }))]"
                label="Port de destination"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="convertForm.estimated_departure"
                label="Départ estimé"
                type="date"
              />
            </VCol>
            <VCol cols="12" md="6">
              <AppTextField
                v-model="convertForm.estimated_arrival"
                label="Arrivée estimée"
                type="date"
              />
            </VCol>
            <VCol cols="12">
              <AppTextarea
                v-model="convertForm.notes"
                label="Notes opérationnelles"
                rows="3"
                auto-grow
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="justify-end pa-4">
          <VBtn variant="tonal" :disabled="converting" @click="dialogConvert = false">
            Annuler
          </VBtn>
          <VBtn color="primary" :loading="converting" @click="submitConvert">
            Créer le dossier
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="dialogTransition" max-width="500" persistent>
      <VCard :title="`Passer à : ${STATUS_LABEL[pendingTarget] ?? ''}`">
        <VCardText>
          <p class="text-body-2 text-medium-emphasis mb-3">
            Tu peux laisser une note pour tracer pourquoi le statut change.
            Elle apparaîtra dans l'historique du devis.
          </p>
          <AppTextarea
            v-model="transitionNote"
            label="Note (optionnelle)"
            rows="3"
            auto-grow
            placeholder="Ex : cotation envoyée par email — 2 véhicules disponibles immédiatement."
          />
        </VCardText>
        <VCardActions class="justify-end pa-4">
          <VBtn variant="tonal" :disabled="updatingStatus" @click="dialogTransition = false">
            Annuler
          </VBtn>
          <VBtn :color="STATUS_COLOR[pendingTarget]" :loading="updatingStatus" @click="confirmTransition">
            Confirmer la transition
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="5000"
      location="top right"
    >
      {{ snackbar.message }}
      <template #actions>
        <VBtn variant="text" @click="snackbar.show = false">Fermer</VBtn>
      </template>
    </VSnackbar>
  </div>
</template>
