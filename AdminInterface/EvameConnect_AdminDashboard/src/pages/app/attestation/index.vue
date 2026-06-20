<script setup>
/* eslint-disable camelcase -- les clés du payload reprennent le contrat backend / IDs du document officiel */
import AttestationContactsDialog from '@/views/apps/attestation/AttestationContactsDialog.vue'
import AttestationDocument from '@/views/apps/attestation/AttestationDocument.vue'
import AttestationSettingsPanel from '@/views/apps/attestation/AttestationSettingsPanel.vue'
import { computeDerived, DEFAULT_SETTINGS } from '@/composables/useAttestationCalc'
import { printAttestation } from '@/utils/attestation-print'
import { useAttestationStore } from '@/stores/attestation'
import { $api } from '@/utils/api'

definePage({ meta: { layout: 'default' } })

const store = useAttestationStore()
const route = useRoute()
const router = useRouter()

// ─── État du formulaire ────────────────────────────────────────────────────
const defaultForm = () => ({
  company_name: 'EDIMART TOGO',
  imp_adresse: '',
  id_number: 'TG1000372886',
  phone: '90 05 29 41',
  city: 'LOME - TOGO',
  fournisseur_nom: '',
  fournisseur_adresse: '',
  fournisseur_tel: '',
  fournisseur_pays: 'FRANCE',
  regime: 'IM4',
  origine: 'FRANCE',
  provenance: 'FRANCE',
  num_facture: '',
  designation: '',
  facture_caf: '',
  facture_franco: '',
  date_declaration: '',
  devise: 'XOF',
  devise_fob: 'EUR',
  devise_caf: 'EUR',
  devise_franco: 'XOF',
  num_dossier: '',
  titulaire_dossier: '',
  bureau: 'TG121 LOME TOGO',
  declaration_num: '',
  date_enregistrement: '',
  goods: [{ tarif: '', quantite: '', poids: '', valeur: '' }],
})

const form = reactive(defaultForm())
const settings = reactive({ ...DEFAULT_SETTINGS })
const currentEditId = ref(null)

const derived = computed(() => computeDerived(form, settings))

// ─── Pays (Origine / Provenance / Fournisseur) ─────────────────────────────
const countryItems = ref([])

const loadCountries = async () => {
  try {
    const res = await $api('/countries?per_page=300')
    const list = res?.data ?? []

    countryItems.value = list
      .map(c => String(c.name ?? '').toUpperCase())
      .filter(Boolean)
      .map(name => ({ title: name, value: name }))
  }
  catch {
    countryItems.value = []
  }
}

// ─── UI ────────────────────────────────────────────────────────────────────
const showSettings = ref(false)
const contactsOpen = ref(false)
const saving = ref(false)

const snackbar = reactive({ show: false, color: 'success', message: '' })

const notify = (message, color = 'success') => {
  snackbar.message = message
  snackbar.color = color
  snackbar.show = true
}

const editLabel = computed(() => {
  if (!currentEditId.value)
    return 'Nouvelle attestation'
  const att = store.attestations.find(a => a.id === currentEditId.value)

  return att ? `Modification : ${att.label}` : 'Modification en cours'
})

// ─── Actions ───────────────────────────────────────────────────────────────
const applyForm = data => {
  Object.assign(form, defaultForm(), data)
  if (!Array.isArray(form.goods) || !form.goods.length)
    form.goods = [{ tarif: '', quantite: '', poids: '', valeur: '' }]
}

const newAttestation = () => {
  currentEditId.value = null
  applyForm(defaultForm())
  Object.assign(settings, DEFAULT_SETTINGS)

  // Repartir d'une URL propre si l'on éditait une attestation existante.
  if (route.query.id)
    router.replace({ query: {} })
}

const save = async () => {
  saving.value = true
  try {
    const body = { payload: { ...form }, settings: { ...settings } }
    if (currentEditId.value) {
      await store.update(currentEditId.value, body)
      notify('Attestation mise à jour !')
    }
    else {
      const created = await store.create(body)

      currentEditId.value = created?.id ?? null
      notify('Attestation enregistrée !')
    }
  }
  catch {
    notify("Échec de l'enregistrement.", 'error')
  }
  finally {
    saving.value = false
  }
}

const onLoadAttestation = att => {
  applyForm(att.payload || {})
  Object.assign(settings, DEFAULT_SETTINGS, att.settings || {})
  currentEditId.value = att.id
  notify('Attestation chargée')
}

const openContacts = () => {
  contactsOpen.value = true
}

const onUseContact = contact => {
  if (contact.type === 'importer') {
    form.company_name = contact.name || ''
    form.imp_adresse = contact.address || ''
    form.phone = contact.phone || ''
    form.id_number = contact.nif || ''
    form.city = contact.city || ''
  }
  else {
    form.fournisseur_nom = contact.name || ''
    form.fournisseur_tel = contact.phone || ''
    form.fournisseur_adresse = contact.address || ''
    form.fournisseur_pays = (contact.country || '').toUpperCase()
  }
  notify(`${contact.name} appliqué`)
}

const print = () => {
  printAttestation(
    { form: { ...form }, derived: derived.value, settings: { ...settings } },
    () => notify("Impression impossible. Réessayez ou vérifiez les paramètres du navigateur.", 'error'),
  )
}

const goToList = () => router.push({ name: 'attestation-list' })

// Charger une attestation passée en query (?id=) — depuis « Mes attestations ».
const loadFromQuery = async () => {
  const id = route.query.id
  if (!id)
    return
  try {
    const att = await store.load(id)
    if (att)
      onLoadAttestation(att)
  }
  catch {
    notify('Attestation introuvable.', 'error')
  }
}

onMounted(() => {
  loadCountries()
  loadFromQuery()
})

watch(() => route.query.id, loadFromQuery)
</script>

<template>
  <div>
    <!-- Barre d'actions -->
    <VCard class="mb-4">
      <VCardText class="d-flex align-center flex-wrap gap-y-3">
        <div class="me-auto pe-4">
          <h5 class="text-h5 mb-0">
            Attestation d'importation
          </h5>
          <span class="text-caption text-medium-emphasis">{{ editLabel }}</span>
        </div>
        <div class="d-flex align-center flex-wrap gap-2">
          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-settings"
            @click="showSettings = !showSettings"
          >
            Paramètres
          </VBtn>
          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-plus"
            @click="newAttestation"
          >
            Nouveau
          </VBtn>
          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-list"
            @click="goToList"
          >
            Mes attestations
          </VBtn>
          <VBtn
            variant="tonal"
            color="primary"
            prepend-icon="tabler-printer"
            @click="print"
          >
            Imprimer / PDF
          </VBtn>
          <VBtn
            color="primary"
            prepend-icon="tabler-device-floppy"
            :loading="saving"
            @click="save"
          >
            Enregistrer
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <VExpandTransition>
      <AttestationSettingsPanel
        v-if="showSettings"
        v-model:settings="settings"
      />
    </VExpandTransition>

    <AttestationDocument
      v-model:form="form"
      :derived="derived"
      :country-items="countryItems"
      @pick-importer="openContacts"
      @pick-supplier="openContacts"
    />

    <AttestationContactsDialog
      v-model="contactsOpen"
      @use-contact="onUseContact"
    />

    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      location="top end"
    >
      {{ snackbar.message }}
    </VSnackbar>
  </div>
</template>
