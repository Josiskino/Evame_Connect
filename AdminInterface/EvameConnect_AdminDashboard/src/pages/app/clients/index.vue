<script setup>
import { VForm } from 'vuetify/components/VForm'
import { refDebounced } from '@vueuse/core'

definePage({ meta: { layout: 'default', action: 'read', subject: 'clients' } })

const ability = useAbility()
const canCreate = computed(() => ability.can('create', 'client'))

// --- Liste + recherche + pagination -------------------------------------
const page = ref(1)
const perPage = ref(15)
const searchRaw = ref('')
const search = refDebounced(searchRaw, 400)

watch([search, perPage], () => { page.value = 1 })

const queryUrl = computed(() => {
  const p = new URLSearchParams()
  p.set('page', String(page.value))
  p.set('per_page', String(perPage.value))
  if (search.value) p.set('search', search.value)

  return `/clients?${p.toString()}`
})

const { data, isFetching, execute } = useApi(queryUrl)

const clients = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta ?? { last_page: 1, total: 0, from: 0, to: 0 })

const headers = [
  { title: 'Nom', key: 'nom' },
  { title: 'Téléphone', key: 'telephone' },
  { title: 'E-mail', key: 'email' },
  { title: 'Adresse', key: 'adresse' },
]

// --- Notification --------------------------------------------------------
const snackbar = ref({ show: false, message: '', color: 'success' })
const notify = (message, color = 'success') => {
  snackbar.value = { show: true, message, color }
}

// --- Création client -----------------------------------------------------
const dialog = ref(false)
const refForm = ref()
const saving = ref(false)
const form = ref({ nom: '', telephone: '', email: '', adresse: '' })
const fieldErrors = ref({})

const openCreate = () => {
  form.value = { nom: '', telephone: '', email: '', adresse: '' }
  fieldErrors.value = {}
  dialog.value = true
}

const submit = async () => {
  const { valid } = await refForm.value.validate()
  if (!valid) return

  saving.value = true
  fieldErrors.value = {}
  try {
    await $api('/clients', { method: 'POST', body: form.value })
    notify('Client enregistré avec succès.')
    dialog.value = false
    execute()
  }
  catch (err) {
    const d = err?.response?._data
    fieldErrors.value = d?.errors ?? {}
    notify(d?.message || "Échec de l'enregistrement.", 'error')
  }
  finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <!-- En-tête -->
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">Clients</h4>
        <p class="text-medium-emphasis mb-0">{{ meta.total }} client(s) enregistré(s)</p>
      </div>
      <VBtn v-if="canCreate" prepend-icon="tabler-plus" @click="openCreate">
        Nouveau client
      </VBtn>
    </div>

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="5">
            <AppTextField
              v-model="searchRaw"
              placeholder="Rechercher par nom ou téléphone…"
              prepend-inner-icon="tabler-search"
              clearable
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTable
        :headers="headers"
        :items="clients"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="perPage"
      >
        <template #item.nom="{ item }">
          <div class="d-flex align-center gap-2">
            <VAvatar size="34" color="primary" variant="tonal">
              <span class="text-caption">{{ (item.nom || '?').charAt(0).toUpperCase() }}</span>
            </VAvatar>
            <span class="font-weight-medium">{{ item.nom }}</span>
          </div>
        </template>
        <template #item.telephone="{ item }">{{ item.telephone || '—' }}</template>
        <template #item.email="{ item }">{{ item.email || '—' }}</template>
        <template #item.adresse="{ item }">{{ item.adresse || '—' }}</template>
        <template #no-data>
          <div class="text-center text-medium-emphasis py-8">Aucun client trouvé.</div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Pagination -->
    <div v-if="clients.length" class="d-flex flex-wrap align-center justify-space-between gap-4 mt-6">
      <span class="text-body-2 text-medium-emphasis">{{ meta.from }}–{{ meta.to }} sur {{ meta.total }}</span>
      <VPagination v-model="page" :length="meta.last_page" :total-visible="5" rounded="circle" />
    </div>

    <!-- Dialog création -->
    <VDialog v-model="dialog" max-width="520" persistent>
      <VCard>
        <VCardItem>
          <VCardTitle>Nouveau client</VCardTitle>
        </VCardItem>
        <VCardText>
          <VForm ref="refForm" @submit.prevent="submit">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="form.nom"
                  label="Nom complet *"
                  placeholder="KOFFI Mensah"
                  :rules="[requiredValidator]"
                  :error-messages="fieldErrors.nom"
                />
              </VCol>
              <VCol cols="12" sm="6">
                <AppTextField
                  v-model="form.telephone"
                  label="Téléphone"
                  placeholder="+228 90 00 00 00"
                  :error-messages="fieldErrors.telephone"
                />
              </VCol>
              <VCol cols="12" sm="6">
                <AppTextField
                  v-model="form.email"
                  label="E-mail"
                  type="email"
                  placeholder="client@example.com"
                  :error-messages="fieldErrors.email"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="form.adresse"
                  label="Adresse"
                  placeholder="Lomé"
                  :error-messages="fieldErrors.adresse"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3">
          <VBtn variant="tonal" color="secondary" :disabled="saving" @click="dialog = false">
            Annuler
          </VBtn>
          <VBtn :loading="saving" @click="submit">
            Enregistrer
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Notification -->
    <VSnackbar v-model="snackbar.show" location="top end" :color="snackbar.color" :timeout="3500">
      {{ snackbar.message }}
    </VSnackbar>
  </div>
</template>
