<script setup>
import { watchDebounced } from '@vueuse/core'

definePage({ meta: { layout: 'default', action: 'create', subject: 'vente' } })

const router = useRouter()

const steps = ['Client', 'Moto', "Mode d'achat", 'Résumé']
const step = ref(1)

const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`

// --- Notification --------------------------------------------------------
const snackbar = ref({ show: false, message: '', color: 'success' })
const notify = (message, color = 'success') => { snackbar.value = { show: true, message, color } }

// ====================== Étape 1 : Client ================================
const selectedClient = ref(null)
const clientItems = ref([])
const clientSearch = ref('')
const loadingClients = ref(false)

const fetchClients = async q => {
  loadingClients.value = true
  try {
    const res = await $api(`/clients?per_page=20${q ? `&search=${encodeURIComponent(q)}` : ''}`)
    clientItems.value = res?.data ?? []
  }
  finally { loadingClients.value = false }
}
watchDebounced(clientSearch, q => fetchClients(q), { debounce: 350 })
onMounted(() => fetchClients(''))

// Création rapide d'un client (formulaire réutilisable avec CNI)
const clientDialog = ref(false)
const onClientCreated = created => {
  clientItems.value = [created, ...clientItems.value]
  selectedClient.value = created
  notify('Client créé et sélectionné.')
}

// ====================== Étape 2 : Moto ==================================
const selectedMoto = ref(null)
const motoItems = ref([])
const motoSearch = ref('')
const loadingMotos = ref(false)

const fetchMotos = async q => {
  loadingMotos.value = true
  try {
    const res = await $api(`/motos?per_page=20&statut=disponible${q ? `&search=${encodeURIComponent(q)}` : ''}`)
    motoItems.value = res?.data ?? []
  }
  finally { loadingMotos.value = false }
}
watchDebounced(motoSearch, q => fetchMotos(q), { debounce: 350 })
onMounted(() => fetchMotos(''))

// ====================== Étape 3 : Mode ==================================
const mode = ref('direct')

// Paramètres leasing
const leasing = ref({
  date_debut: new Date().toISOString().slice(0, 10),
  duree_jours: 180,
  montant_journalier: 2000,
  frequence: 'journalier',
})
const frequenceOptions = [
  { title: 'Journalier', value: 'journalier' },
  { title: 'Hebdomadaire', value: 'hebdomadaire' },
  { title: 'Mensuel', value: 'mensuel' },
]

const leasingCalc = computed(() => {
  const d = Math.max(1, Number(leasing.value.duree_jours) || 0)
  const j = Math.max(0, Number(leasing.value.montant_journalier) || 0)
  const total = d * j
  const mois = Math.ceil(d / 30)

  return {
    total,
    hebdomadaire: j * 7,
    nombre_mois: mois,
    mensuel: mois > 0 ? Math.round(total / mois) : 0,
  }
})

const montantVente = computed(() => (mode.value === 'leasing'
  ? leasingCalc.value.total
  : (selectedMoto.value?.prix ?? 0)))

// ====================== Navigation ======================================
const canNext = computed(() => {
  if (step.value === 1) return !!selectedClient.value
  if (step.value === 2) return !!selectedMoto.value
  if (step.value === 3) {
    if (mode.value === 'leasing')
      return leasing.value.duree_jours > 0 && leasing.value.montant_journalier > 0
    return true
  }

  return true
})

// ====================== Confirmation ====================================
const submitting = ref(false)

const confirmer = async () => {
  submitting.value = true
  try {
    const vente = await $api('/ventes', {
      method: 'POST',
      body: {
        client_id: selectedClient.value.id,
        moto_id: selectedMoto.value.id,
        mode: mode.value,
        montant: montantVente.value,
      },
    })

    if (mode.value === 'leasing') {
      await $api('/leasing', {
        method: 'POST',
        body: {
          client_id: selectedClient.value.id,
          moto_id: selectedMoto.value.id,
          vente_id: vente?.data?.id,
          date_debut: leasing.value.date_debut,
          duree_jours: Number(leasing.value.duree_jours),
          montant_journalier: Number(leasing.value.montant_journalier),
          frequence: leasing.value.frequence,
        },
      })
    }

    notify('Vente enregistrée avec succès.')
    setTimeout(() => router.push(mode.value === 'leasing' ? '/leasing' : '/ventes'), 800)
  }
  catch (err) {
    notify(err?.response?._data?.message || "Échec de l'enregistrement de la vente.", 'error')
    submitting.value = false
  }
}
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" prepend-icon="tabler-arrow-left" class="mb-4" @click="router.push('/ventes')">
      Retour aux ventes
    </VBtn>

    <VCard>
      <VCardItem>
        <VCardTitle>Nouvelle vente</VCardTitle>
        <VCardSubtitle>Suivez les étapes : client, moto, mode d'achat, puis validez.</VCardSubtitle>
      </VCardItem>

      <VCardText>
        <VStepper v-model="step" :items="steps" hide-actions>
          <!-- Étape 1 : Client -->
          <template #item.1>
            <div class="pa-2">
              <div class="d-flex flex-wrap gap-3 align-end">
                <VAutocomplete
                  v-model="selectedClient"
                  :items="clientItems"
                  item-title="nom"
                  item-value="id"
                  return-object
                  no-filter
                  :loading="loadingClients"
                  label="Sélectionner un client"
                  placeholder="Rechercher par nom…"
                  prepend-inner-icon="tabler-user"
                  class="flex-grow-1"
                  @update:search="clientSearch = $event"
                />
                <VBtn variant="tonal" prepend-icon="tabler-plus" @click="clientDialog = true">
                  Nouveau client
                </VBtn>
              </div>

              <VAlert v-if="selectedClient" type="info" variant="tonal" class="mt-4">
                <strong>{{ selectedClient.nom }}</strong>
                <span v-if="selectedClient.telephone"> · {{ selectedClient.telephone }}</span>
                <span v-if="selectedClient.adresse"> · {{ selectedClient.adresse }}</span>
              </VAlert>
            </div>
          </template>

          <!-- Étape 2 : Moto -->
          <template #item.2>
            <div class="pa-2">
              <VAutocomplete
                v-model="selectedMoto"
                :items="motoItems"
                item-title="modele"
                item-value="id"
                return-object
                no-filter
                :loading="loadingMotos"
                label="Sélectionner une moto (en stock)"
                placeholder="Rechercher un modèle…"
                prepend-inner-icon="tabler-motorbike"
                @update:search="motoSearch = $event"
              >
                <template #item="{ props, item }">
                  <VListItem v-bind="props" :title="item.raw.modele">
                    <template #prepend>
                      <VAvatar rounded size="40" class="bg-white border">
                        <VImg :src="item.raw.image_url || ''" contain />
                      </VAvatar>
                    </template>
                    <template #subtitle>
                      {{ item.raw.classe_cc }} · {{ fmtMoney(item.raw.prix) }} · Stock {{ item.raw.stock }}
                    </template>
                  </VListItem>
                </template>
              </VAutocomplete>

              <VCard v-if="selectedMoto" variant="tonal" class="mt-4">
                <VCardText class="d-flex align-center gap-4">
                  <VAvatar rounded size="64" class="bg-white border">
                    <VImg :src="selectedMoto.image_url || ''" contain />
                  </VAvatar>
                  <div>
                    <div class="text-h6">{{ selectedMoto.modele }}</div>
                    <div class="text-medium-emphasis">{{ selectedMoto.classe_cc }} · Stock {{ selectedMoto.stock }}</div>
                    <div class="text-primary font-weight-bold">{{ fmtMoney(selectedMoto.prix) }}</div>
                  </div>
                </VCardText>
              </VCard>
            </div>
          </template>

          <!-- Étape 3 : Mode d'achat -->
          <template #item.3>
            <div class="pa-2">
              <VRadioGroup v-model="mode" inline>
                <VRadio label="Achat direct" value="direct" />
                <VRadio label="Leasing" value="leasing" />
              </VRadioGroup>

              <template v-if="mode === 'leasing'">
                <VRow class="mt-2">
                  <VCol cols="12" sm="4">
                    <AppTextField v-model="leasing.date_debut" label="Date de début" type="date" />
                  </VCol>
                  <VCol cols="12" sm="4">
                    <AppTextField v-model="leasing.duree_jours" label="Durée (jours)" type="number" />
                  </VCol>
                  <VCol cols="12" sm="4">
                    <AppTextField v-model="leasing.montant_journalier" label="Montant journalier (FCFA)" type="number" />
                  </VCol>
                  <VCol cols="12" sm="4">
                    <AppSelect v-model="leasing.frequence" label="Fréquence de paiement" :items="frequenceOptions" />
                  </VCol>
                </VRow>

                <VCard variant="tonal" color="primary" class="mt-2">
                  <VCardText>
                    <div class="d-flex flex-wrap justify-space-between gap-4">
                      <div><div class="text-caption">Montant total</div><div class="text-h6">{{ fmtMoney(leasingCalc.total) }}</div></div>
                      <div><div class="text-caption">Par semaine</div><div class="text-h6">{{ fmtMoney(leasingCalc.hebdomadaire) }}</div></div>
                      <div><div class="text-caption">Par mois ({{ leasingCalc.nombre_mois }} mois)</div><div class="text-h6">{{ fmtMoney(leasingCalc.mensuel) }}</div></div>
                    </div>
                  </VCardText>
                </VCard>
              </template>
            </div>
          </template>

          <!-- Étape 4 : Résumé -->
          <template #item.4>
            <div class="pa-2">
              <VList lines="two">
                <VListItem prepend-icon="tabler-user" title="Client" :subtitle="selectedClient?.nom" />
                <VListItem prepend-icon="tabler-motorbike" title="Moto" :subtitle="`${selectedMoto?.modele} · ${selectedMoto?.classe_cc}`" />
                <VListItem prepend-icon="tabler-shopping-cart" title="Mode d'achat" :subtitle="mode === 'leasing' ? 'Leasing' : 'Achat direct'" />
                <VListItem
                  v-if="mode === 'leasing'"
                  prepend-icon="tabler-calendar"
                  title="Échéancier"
                  :subtitle="`${leasing.duree_jours} jours · ${fmtMoney(leasing.montant_journalier)}/jour · ${leasing.frequence}`"
                />
              </VList>
              <VDivider class="my-3" />
              <div class="d-flex justify-space-between align-center">
                <span class="text-h6">Montant {{ mode === 'leasing' ? 'du contrat' : 'de la vente' }}</span>
                <span class="text-h5 text-primary font-weight-bold">{{ fmtMoney(montantVente) }}</span>
              </div>
            </div>
          </template>
        </VStepper>

        <!-- Navigation -->
        <div class="d-flex justify-space-between mt-6">
          <VBtn variant="tonal" color="secondary" :disabled="step === 1 || submitting" @click="step--">
            Précédent
          </VBtn>
          <VBtn v-if="step < 4" :disabled="!canNext" @click="step++">
            Suivant
          </VBtn>
          <VBtn v-else color="success" :loading="submitting" prepend-icon="tabler-check" @click="confirmer">
            Confirmer la vente
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <!-- Dialog création client (formulaire réutilisable avec CNI) -->
    <ClientFormDialog v-model="clientDialog" @created="onClientCreated" />

    <VSnackbar v-model="snackbar.show" location="top end" :color="snackbar.color" :timeout="3500">
      {{ snackbar.message }}
    </VSnackbar>
  </div>
</template>
