<script setup>
import { VForm } from 'vuetify/components/VForm'

definePage({ meta: { layout: 'default', action: 'read', subject: 'leasing' } })

const route = useRoute()
const router = useRouter()
const ability = useAbility()
const canPayer = computed(() => ability.can('create', 'paiement'))

const { data, isFetching, execute } = useApi(`/leasing/${route.params.id}`)
const contrat = computed(() => data.value?.data ?? null)

// Aperçu / téléchargement du contrat
const contractDialog = ref(false)

// Temps réel : un paiement sur ce contrat ailleurs -> rafraîchit la fiche
const { lastActivity } = useRealtimeActivity()
watch(lastActivity, ev => {
  if (ev && ['paiement', 'leasing'].includes(ev.resource) && Number(ev.id) === Number(route.params.id))
    execute()
})

const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`
const fmtDate = d => (d ? new Intl.DateTimeFormat('fr-FR').format(new Date(d)) : '—')

const freqLabel = { journalier: 'Journalier', hebdomadaire: 'Hebdomadaire', mensuel: 'Mensuel' }

// Échéancier calculé (cohérent avec la simulation backend)
const echeancier = computed(() => {
  const c = contrat.value
  if (!c) return { hebdomadaire: 0, mensuel: 0, nombre_mois: 0 }
  const mois = Math.max(1, Math.ceil(c.duree_jours / 30))

  return {
    hebdomadaire: c.montant_journalier * 7,
    nombre_mois: mois,
    mensuel: Math.round(c.montant_total / mois),
  }
})

// --- Enregistrement d'un paiement ---------------------------------------
const { notify: pushNotif } = useNotifications()
const notify = (message, color = 'success') =>
  pushNotif({ message, color, title: color === 'error' ? 'Erreur' : 'Succès' })

const dialog = ref(false)
const refForm = ref()
const saving = ref(false)
const form = ref({ montant: null, date_paiement: new Date().toISOString().slice(0, 10) })
const fieldErrors = ref({})

const openDialog = () => {
  form.value = { montant: contrat.value?.montant_journalier ?? null, date_paiement: new Date().toISOString().slice(0, 10) }
  fieldErrors.value = {}
  dialog.value = true
}

const submit = async () => {
  const { valid } = await refForm.value.validate()
  if (!valid) return

  saving.value = true
  fieldErrors.value = {}
  try {
    await $api(`/leasing/${route.params.id}/paiements`, {
      method: 'POST',
      body: { montant: Number(form.value.montant), date_paiement: form.value.date_paiement },
    })
    notify('Paiement enregistré.')
    dialog.value = false
    execute()
  }
  catch (err) {
    fieldErrors.value = err?.response?._data?.errors ?? {}
    notify(err?.response?._data?.message || "Échec de l'enregistrement du paiement.", 'error')
  }
  finally { saving.value = false }
}
</script>

<template>
  <div>
    <VBtn variant="text" color="secondary" prepend-icon="tabler-arrow-left" class="mb-4" @click="router.push('/leasing')">
      Retour aux contrats
    </VBtn>

    <div v-if="isFetching && !contrat" class="d-flex justify-center py-12">
      <VProgressCircular indeterminate color="primary" />
    </div>

    <template v-else-if="contrat">
      <!-- En-tête -->
      <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
        <div>
          <h4 class="text-h4 font-weight-bold">{{ contrat.client?.nom }}</h4>
          <p class="text-medium-emphasis mb-0">
            {{ contrat.moto?.modele }}
            <span v-if="contrat.client?.telephone"> · {{ contrat.client.telephone }}</span>
          </p>
        </div>
        <div class="d-flex align-center gap-3">
          <VChip label :color="contrat.en_retard ? 'error' : 'success'">
            {{ contrat.en_retard ? 'En retard' : 'À jour' }}
          </VChip>
          <VBtn variant="tonal" prepend-icon="tabler-file-text" @click="contractDialog = true">
            Contrat
          </VBtn>
          <VBtn v-if="canPayer && contrat.montant_restant > 0" prepend-icon="tabler-cash" @click="openDialog">
            Enregistrer un paiement
          </VBtn>
        </div>
      </div>

      <VRow>
        <!-- Suivi du remboursement -->
        <VCol cols="12" md="8">
          <VCard class="mb-6">
            <VCardItem><VCardTitle>Suivi du remboursement</VCardTitle></VCardItem>
            <VCardText>
              <div class="d-flex flex-wrap justify-space-between gap-6 mb-4">
                <div>
                  <div class="text-caption text-medium-emphasis">Montant du contrat</div>
                  <div class="text-h6">{{ fmtMoney(contrat.montant_total) }}</div>
                </div>
                <div>
                  <div class="text-caption text-medium-emphasis">Payé</div>
                  <div class="text-h6 text-success">{{ fmtMoney(contrat.montant_paye) }}</div>
                </div>
                <div>
                  <div class="text-caption text-medium-emphasis">Reste</div>
                  <div class="text-h6 text-warning">{{ fmtMoney(contrat.montant_restant) }}</div>
                </div>
              </div>

              <div class="d-flex align-center gap-3">
                <VProgressLinear
                  :model-value="contrat.progression"
                  :color="contrat.progression >= 100 ? 'success' : 'primary'"
                  height="10"
                  rounded
                  style="flex: 1;"
                />
                <span class="font-weight-bold">{{ contrat.progression }}%</span>
              </div>
            </VCardText>
          </VCard>

          <!-- Historique des paiements -->
          <VCard>
            <VCardItem><VCardTitle>Historique des paiements</VCardTitle></VCardItem>
            <VTable>
              <thead>
                <tr><th>Date</th><th class="text-right">Montant</th></tr>
              </thead>
              <tbody>
                <tr v-for="p in contrat.paiements" :key="p.id">
                  <td>{{ fmtDate(p.date_paiement) }}</td>
                  <td class="text-right">{{ fmtMoney(p.montant) }}</td>
                </tr>
                <tr v-if="!contrat.paiements?.length">
                  <td colspan="2" class="text-center text-medium-emphasis py-4">Aucun paiement enregistré.</td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </VCol>

        <!-- Conditions du contrat -->
        <VCol cols="12" md="4">
          <VCard>
            <VCardItem><VCardTitle>Conditions du contrat</VCardTitle></VCardItem>
            <VList>
              <VListItem title="Date de début" :subtitle="fmtDate(contrat.date_debut)" />
              <VListItem title="Durée" :subtitle="`${contrat.duree_jours} jours`" />
              <VListItem title="Montant journalier" :subtitle="fmtMoney(contrat.montant_journalier)" />
              <VListItem title="Fréquence de paiement" :subtitle="freqLabel[contrat.frequence] || contrat.frequence" />
              <VDivider class="my-1" />
              <VListItem title="Par semaine" :subtitle="fmtMoney(echeancier.hebdomadaire)" />
              <VListItem :title="`Par mois (${echeancier.nombre_mois} mois)`" :subtitle="fmtMoney(echeancier.mensuel)" />
            </VList>
          </VCard>
        </VCol>
      </VRow>
    </template>

    <!-- Dialog paiement -->
    <VDialog v-model="dialog" max-width="460" persistent>
      <VCard>
        <VCardItem><VCardTitle>Enregistrer un paiement</VCardTitle></VCardItem>
        <VCardText>
          <VForm ref="refForm" @submit.prevent="submit">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="form.montant"
                  label="Montant (FCFA) *"
                  type="number"
                  :rules="[requiredValidator]"
                  :error-messages="fieldErrors.montant"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="form.date_paiement"
                  label="Date du paiement"
                  type="date"
                  :error-messages="fieldErrors.date_paiement"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3">
          <VBtn variant="tonal" color="secondary" :disabled="saving" @click="dialog = false">Annuler</VBtn>
          <VBtn :loading="saving" @click="submit">Enregistrer</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <LeasingContractDialog v-model="contractDialog" :contrat="contrat" />
  </div>
</template>
