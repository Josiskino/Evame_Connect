<script setup>
import { VForm } from 'vuetify/components/VForm'
import { watchDebounced } from '@vueuse/core'

definePage({ meta: { layout: 'default', action: 'read', subject: 'interventions' } })

const ability = useAbility()
const canCreate = computed(() => ability.can('create', 'intervention'))
const { notify } = useNotifications()

// --- Liste + filtre + temps réel ----------------------------------------
const page = ref(1)
const perPage = ref(15)
const statut = ref(null)

const statutOptions = [
  { title: 'Tous les statuts', value: null },
  { title: 'Nouvelle', value: 'nouvelle' },
  { title: 'En traitement', value: 'en_traitement' },
  { title: 'Terminée', value: 'terminee' },
]

watch([statut, perPage], () => { page.value = 1 })

const queryUrl = computed(() => {
  const p = new URLSearchParams()
  p.set('page', String(page.value))
  p.set('per_page', String(perPage.value))
  if (statut.value) p.set('statut', statut.value)

  return `/interventions?${p.toString()}`
})

const { data, isFetching, execute } = useApi(queryUrl)
const items = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta ?? { last_page: 1, total: 0, from: 0, to: 0 })

const { lastActivity } = useRealtimeActivity()
watch(lastActivity, ev => {
  if (ev?.resource === 'intervention') execute()
})

const fmtDate = d => (d ? new Intl.DateTimeFormat('fr-FR').format(new Date(d)) : '—')
const statutColor = s => ({ nouvelle: 'info', en_traitement: 'warning', terminee: 'success' }[s] ?? 'secondary')

const headers = [
  { title: 'Date', key: 'date_intervention' },
  { title: 'Client', key: 'client' },
  { title: 'Moto', key: 'moto' },
  { title: 'Problème', key: 'probleme' },
  { title: 'Technicien', key: 'technicien' },
  { title: 'Statut', key: 'statut', align: 'center' },
  { title: 'Actions', key: 'actions', align: 'center', sortable: false },
]

// --- Techniciens SAV -----------------------------------------------------
const technicians = ref([])
const loadTechnicians = async () => {
  const res = await $api('/technicians')
  technicians.value = (res?.data ?? []).map(t => ({ title: t.name, value: t.id }))
}
onMounted(loadTechnicians)

// --- Autocomplete clients / motos ---------------------------------------
const clientItems = ref([])
const clientSearch = ref('')
const fetchClients = async q => {
  const res = await $api(`/clients?per_page=20${q ? `&search=${encodeURIComponent(q)}` : ''}`)
  clientItems.value = res?.data ?? []
}
watchDebounced(clientSearch, fetchClients, { debounce: 350 })

const motoItems = ref([])
const motoSearch = ref('')
const fetchMotos = async q => {
  const res = await $api(`/motos?per_page=20${q ? `&search=${encodeURIComponent(q)}` : ''}`)
  motoItems.value = res?.data ?? []
}
watchDebounced(motoSearch, fetchMotos, { debounce: 350 })

// --- Dialog création / assignation --------------------------------------
const dialog = ref(false)
const refForm = ref()
const saving = ref(false)
const fieldErrors = ref({})
const form = ref({ client: null, moto: null, technicien_id: null, probleme: '', date_intervention: new Date().toISOString().slice(0, 10) })

const openCreate = () => {
  form.value = { client: null, moto: null, technicien_id: null, probleme: '', date_intervention: new Date().toISOString().slice(0, 10) }
  fieldErrors.value = {}
  fetchClients('')
  fetchMotos('')
  dialog.value = true
}

const submit = async () => {
  const { valid } = await refForm.value.validate()
  if (!valid) return
  saving.value = true
  fieldErrors.value = {}
  try {
    await $api('/interventions', {
      method: 'POST',
      body: {
        client_id: form.value.client?.id,
        moto_id: form.value.moto?.id,
        technicien_id: form.value.technicien_id,
        probleme: form.value.probleme,
        date_intervention: form.value.date_intervention,
        statut: 'nouvelle',
      },
    })
    notify({ title: 'Succès', message: 'Intervention créée et assignée au technicien.' })
    dialog.value = false
    execute()
  }
  catch (err) {
    fieldErrors.value = err?.response?._data?.errors ?? {}
    notify({ color: 'error', title: 'Erreur', message: err?.response?._data?.message || "Échec de l'assignation." })
  }
  finally { saving.value = false }
}

// --- Réassignation rapide ------------------------------------------------
const reassignDialog = ref(false)
const reassignTarget = ref(null)
const reassignTech = ref(null)
const reassigning = ref(false)

const openReassign = item => {
  reassignTarget.value = item
  reassignTech.value = item.technicien?.id ?? null
  reassignDialog.value = true
}
const submitReassign = async () => {
  reassigning.value = true
  try {
    await $api(`/interventions/${reassignTarget.value.id}`, {
      method: 'PUT',
      body: { technicien_id: reassignTech.value },
    })
    notify({ title: 'Succès', message: 'Technicien réassigné.' })
    reassignDialog.value = false
    execute()
  }
  catch (err) {
    notify({ color: 'error', title: 'Erreur', message: err?.response?._data?.message || 'Échec.' })
  }
  finally { reassigning.value = false }
}
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">Interventions SAV</h4>
        <p class="text-medium-emphasis mb-0">{{ meta.total }} intervention(s)</p>
      </div>
      <VBtn v-if="canCreate" prepend-icon="tabler-plus" @click="openCreate">
        Assigner une intervention
      </VBtn>
    </div>

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <AppSelect v-model="statut" :items="statutOptions" placeholder="Statut" />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTable :headers="headers" :items="items" :loading="isFetching" hide-default-footer :items-per-page="perPage">
        <template #item.date_intervention="{ item }">{{ fmtDate(item.date_intervention) }}</template>
        <template #item.client="{ item }">{{ item.client?.nom ?? '—' }}</template>
        <template #item.moto="{ item }">{{ item.moto?.modele ?? '—' }}</template>
        <template #item.probleme="{ item }">
          <span class="d-inline-block text-truncate" style="max-inline-size: 220px;">{{ item.probleme }}</span>
        </template>
        <template #item.technicien="{ item }">
          <span v-if="item.technicien">{{ item.technicien.name }}</span>
          <VChip v-else size="small" color="error" label>Non assignée</VChip>
        </template>
        <template #item.statut="{ item }">
          <VChip size="small" label :color="statutColor(item.statut)">{{ item.statut_label }}</VChip>
        </template>
        <template #item.actions="{ item }">
          <VBtn v-if="canCreate" size="small" variant="tonal" prepend-icon="tabler-user-check" @click="openReassign(item)">
            Réassigner
          </VBtn>
        </template>
        <template #no-data>
          <div class="text-center text-medium-emphasis py-8">Aucune intervention.</div>
        </template>
      </VDataTable>
    </VCard>

    <div v-if="items.length" class="d-flex flex-wrap align-center justify-space-between gap-4 mt-6">
      <span class="text-body-2 text-medium-emphasis">{{ meta.from }}–{{ meta.to }} sur {{ meta.total }}</span>
      <VPagination v-model="page" :length="meta.last_page" :total-visible="5" rounded="circle" />
    </div>

    <!-- Dialog création / assignation -->
    <VDialog v-model="dialog" max-width="600" persistent>
      <VCard>
        <VCardItem><VCardTitle>Assigner une intervention</VCardTitle></VCardItem>
        <VCardText>
          <VForm ref="refForm" @submit.prevent="submit">
            <VRow>
              <VCol cols="12">
                <VAutocomplete
                  v-model="form.client"
                  :items="clientItems"
                  item-title="nom"
                  item-value="id"
                  return-object
                  no-filter
                  label="Client *"
                  :rules="[v => !!v || 'Client requis']"
                  :error-messages="fieldErrors.client_id"
                  @update:search="clientSearch = $event"
                />
              </VCol>
              <VCol cols="12">
                <VAutocomplete
                  v-model="form.moto"
                  :items="motoItems"
                  item-title="modele"
                  item-value="id"
                  return-object
                  no-filter
                  label="Moto"
                  :error-messages="fieldErrors.moto_id"
                  @update:search="motoSearch = $event"
                />
              </VCol>
              <VCol cols="12" sm="8">
                <AppSelect
                  v-model="form.technicien_id"
                  :items="technicians"
                  label="Technicien SAV *"
                  :rules="[v => !!v || 'Technicien requis']"
                  :error-messages="fieldErrors.technicien_id"
                />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField v-model="form.date_intervention" label="Date" type="date" />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="form.probleme"
                  label="Problème *"
                  :rules="[requiredValidator]"
                  :error-messages="fieldErrors.probleme"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3">
          <VBtn variant="tonal" color="secondary" :disabled="saving" @click="dialog = false">Annuler</VBtn>
          <VBtn :loading="saving" @click="submit">Assigner</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Dialog réassignation -->
    <VDialog v-model="reassignDialog" max-width="460">
      <VCard>
        <VCardItem><VCardTitle>Réassigner l'intervention</VCardTitle></VCardItem>
        <VCardText>
          <AppSelect v-model="reassignTech" :items="technicians" label="Technicien SAV" />
        </VCardText>
        <VCardText class="d-flex justify-end gap-3">
          <VBtn variant="tonal" color="secondary" :disabled="reassigning" @click="reassignDialog = false">Annuler</VBtn>
          <VBtn :loading="reassigning" @click="submitReassign">Réassigner</VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
