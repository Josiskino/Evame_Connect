<script setup>
import { VForm } from 'vuetify/components/VForm'
import { refDebounced } from '@vueuse/core'

definePage({ meta: { layout: 'default', action: 'read', subject: 'admin' } })

const { notify } = useNotifications()

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

  return `/admin/users?${p.toString()}`
})

const { data, isFetching, execute } = useApi(queryUrl)

const users = computed(() => data.value?.data ?? [])

// Rôles disponibles (pour le formulaire de création)
const { data: rolesData } = useApi('/admin/roles')
const roleItems = computed(() =>
  (rolesData.value?.data ?? []).map(r => ({ title: roleLabelFor(r.name), value: r.name })))
const meta = computed(() => data.value?.meta ?? { last_page: 1, total: 0, from: 0, to: 0 })

const headers = [
  { title: 'Utilisateur', key: 'name' },
  { title: 'E-mail', key: 'email' },
  { title: 'Téléphone', key: 'telephone' },
  { title: 'Rôles', key: 'roles' },
  { title: 'Inscription', key: 'created_at' },
]

const roleLabel = { 'super-admin': 'Super Admin', manager: 'Manager', commercial: 'Commercial', sav: 'Technicien SAV' }
const roleColor = r => ({ 'super-admin': 'error', manager: 'warning', commercial: 'info', sav: 'success' }[r] ?? 'secondary')

const fmtDate = d => (d ? new Intl.DateTimeFormat('fr-FR').format(new Date(d)) : '—')
const roleLabelFor = r => roleLabel[r] || r

// --- Création d'un utilisateur ------------------------------------------
const dialog = ref(false)
const refForm = ref()
const saving = ref(false)
const fieldErrors = ref({})
const form = ref({ name: '', email: '', password: '', telephone: '', role: null })

const openCreate = () => {
  form.value = { name: '', email: '', password: '', telephone: '', role: null }
  fieldErrors.value = {}
  dialog.value = true
}

const submit = async () => {
  const { valid } = await refForm.value.validate()
  if (!valid) return
  saving.value = true
  fieldErrors.value = {}
  try {
    await $api('/admin/users', {
      method: 'POST',
      body: {
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
        telephone: form.value.telephone || null,
        roles: form.value.role ? [form.value.role] : [],
      },
    })
    notify({ title: 'Succès', message: `Utilisateur « ${form.value.name} » créé.` })
    dialog.value = false
    execute()
  }
  catch (err) {
    fieldErrors.value = err?.response?._data?.errors ?? {}
    notify({ color: 'error', title: 'Erreur', message: err?.response?._data?.message || 'Échec de la création.' })
  }
  finally { saving.value = false }
}
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">Utilisateurs</h4>
        <p class="text-medium-emphasis mb-0">{{ meta.total }} utilisateur(s)</p>
      </div>
      <VBtn prepend-icon="tabler-plus" @click="openCreate">Nouvel utilisateur</VBtn>
    </div>

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="5">
            <AppTextField
              v-model="searchRaw"
              placeholder="Rechercher par nom ou e-mail…"
              prepend-inner-icon="tabler-search"
              clearable
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTable
        :headers="headers"
        :items="users"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="perPage"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-2">
            <VAvatar size="34" color="primary" variant="tonal">
              <span class="text-caption">{{ (item.name || '?').charAt(0).toUpperCase() }}</span>
            </VAvatar>
            <span class="font-weight-medium">{{ item.name }}</span>
          </div>
        </template>
        <template #item.telephone="{ item }">{{ item.telephone || '—' }}</template>
        <template #item.roles="{ item }">
          <div class="d-flex flex-wrap gap-1">
            <VChip v-for="r in item.roles" :key="r" size="small" label :color="roleColor(r)">
              {{ roleLabel[r] || r }}
            </VChip>
            <span v-if="!item.roles?.length" class="text-medium-emphasis">—</span>
          </div>
        </template>
        <template #item.created_at="{ item }">{{ fmtDate(item.created_at) }}</template>
        <template #no-data>
          <div class="text-center text-medium-emphasis py-8">Aucun utilisateur.</div>
        </template>
      </VDataTable>
    </VCard>

    <div v-if="users.length" class="d-flex flex-wrap align-center justify-space-between gap-4 mt-6">
      <span class="text-body-2 text-medium-emphasis">{{ meta.from }}–{{ meta.to }} sur {{ meta.total }}</span>
      <VPagination v-model="page" :length="meta.last_page" :total-visible="5" rounded="circle" />
    </div>

    <!-- Dialog création utilisateur -->
    <VDialog v-model="dialog" max-width="540" persistent>
      <VCard>
        <VCardItem><VCardTitle>Nouvel utilisateur</VCardTitle></VCardItem>
        <VCardText>
          <VForm ref="refForm" @submit.prevent="submit">
            <VRow>
              <VCol cols="12" sm="6">
                <AppTextField v-model="form.name" label="Nom complet *" :rules="[requiredValidator]" :error-messages="fieldErrors.name" />
              </VCol>
              <VCol cols="12" sm="6">
                <AppTextField v-model="form.telephone" label="Téléphone" :error-messages="fieldErrors.telephone" />
              </VCol>
              <VCol cols="12">
                <AppTextField v-model="form.email" label="E-mail *" type="email" :rules="[requiredValidator, emailValidator]" :error-messages="fieldErrors.email" />
              </VCol>
              <VCol cols="12" sm="6">
                <AppTextField v-model="form.password" label="Mot de passe *" type="password" :rules="[requiredValidator]" :error-messages="fieldErrors.password" />
              </VCol>
              <VCol cols="12" sm="6">
                <AppSelect v-model="form.role" :items="roleItems" label="Rôle *" :rules="[v => !!v || 'Rôle requis']" :error-messages="fieldErrors.roles" />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VCardText class="d-flex justify-end gap-3">
          <VBtn variant="tonal" color="secondary" :disabled="saving" @click="dialog = false">Annuler</VBtn>
          <VBtn :loading="saving" @click="submit">Créer</VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
