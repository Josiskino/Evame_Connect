<script setup>
import { VForm } from 'vuetify/components/VForm'

definePage({ meta: { layout: 'default', action: 'read', subject: 'admin' } })

const { notify } = useNotifications()

// Libellés lisibles des permissions
const LABELS = {
  'view.dashboard': 'Tableau de bord',
  'view.catalogue': 'Catalogue motos',
  'view.ventes': 'Ventes',
  'view.clients': 'Clients',
  'view.leasing': 'Leasing',
  'view.interventions': 'Interventions (SAV)',
  'view.admin': 'Administration',
  'client.create': 'Créer un client',
  'vente.create': 'Créer une vente',
  'leasing.create': 'Créer un contrat de leasing',
  'paiement.create': 'Enregistrer un paiement',
  'intervention.create': 'Créer une intervention',
  'intervention.update': 'Mettre à jour une intervention',
  'rbac.manage': 'Gérer rôles & permissions',
}
const labelOf = name => LABELS[name] ?? name

const PROTECTED = ['super-admin']

// --- Données -------------------------------------------------------------
const { data: rolesData, execute: refreshRoles, isFetching: loadingRoles } = useApi('/admin/roles')
const { data: permsData } = useApi('/admin/permissions')

const roles = computed(() => rolesData.value?.data ?? [])
const permissions = computed(() => permsData.value?.data ?? [])

const groups = computed(() => ({
  'Accès aux écrans': permissions.value.filter(p => p.name.startsWith('view.')),
  'Actions autorisées': permissions.value.filter(p => !p.name.startsWith('view.')),
}))

// --- Dialog création / édition ------------------------------------------
const dialog = ref(false)
const editing = ref(null) // role en cours d'édition (null = création)
const refForm = ref()
const saving = ref(false)
const form = ref({ name: '', permissions: [] })
const fieldErrors = ref({})

const openCreate = () => {
  editing.value = null
  form.value = { name: '', permissions: [] }
  fieldErrors.value = {}
  dialog.value = true
}

const openEdit = role => {
  editing.value = role
  form.value = { name: role.name, permissions: [...(role.permissions ?? [])] }
  fieldErrors.value = {}
  dialog.value = true
}

const submit = async () => {
  const { valid } = await refForm.value.validate()
  if (!valid) return

  saving.value = true
  fieldErrors.value = {}
  try {
    if (editing.value) {
      await $api(`/admin/roles/${editing.value.id}/permissions`, {
        method: 'PUT',
        body: { permissions: form.value.permissions },
      })
      notify({ title: 'Succès', message: `Permissions du rôle « ${editing.value.name} » mises à jour.` })
    }
    else {
      await $api('/admin/roles', {
        method: 'POST',
        body: { name: form.value.name, permissions: form.value.permissions },
      })
      notify({ title: 'Succès', message: `Rôle « ${form.value.name} » créé.` })
    }
    dialog.value = false
    refreshRoles()
  }
  catch (err) {
    fieldErrors.value = err?.response?._data?.errors ?? {}
    notify({ color: 'error', title: 'Erreur', message: err?.response?._data?.message || 'Échec de l\'opération.' })
  }
  finally { saving.value = false }
}

// --- Suppression ---------------------------------------------------------
const deleteDialog = ref(false)
const roleToDelete = ref(null)
const deleting = ref(false)

const askDelete = role => {
  roleToDelete.value = role
  deleteDialog.value = true
}
const confirmDelete = async () => {
  deleting.value = true
  try {
    await $api(`/admin/roles/${roleToDelete.value.id}`, { method: 'DELETE' })
    notify({ title: 'Succès', message: `Rôle « ${roleToDelete.value.name} » supprimé.` })
    deleteDialog.value = false
    refreshRoles()
  }
  catch (err) {
    notify({ color: 'error', title: 'Erreur', message: err?.response?._data?.message || 'Suppression impossible.' })
  }
  finally { deleting.value = false }
}

const roleColor = name => (PROTECTED.includes(name) ? 'error' : 'primary')
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">Rôles & permissions</h4>
        <p class="text-medium-emphasis mb-0">Créez des rôles et attribuez-leur des permissions.</p>
      </div>
      <VBtn prepend-icon="tabler-plus" @click="openCreate">Nouveau rôle</VBtn>
    </div>

    <VRow>
      <VCol v-for="role in roles" :key="role.id" cols="12" md="6" lg="4">
        <VCard class="h-100">
          <VCardItem>
            <template #prepend>
              <VAvatar :color="roleColor(role.name)" variant="tonal" rounded>
                <VIcon icon="tabler-shield-lock" />
              </VAvatar>
            </template>
            <VCardTitle class="text-capitalize">{{ role.name }}</VCardTitle>
            <VCardSubtitle>{{ (role.permissions?.length ?? 0) }} permission(s)</VCardSubtitle>
            <template #append>
              <VChip v-if="PROTECTED.includes(role.name)" size="x-small" color="error" label>Système</VChip>
            </template>
          </VCardItem>

          <VCardText>
            <div v-if="PROTECTED.includes(role.name)" class="text-body-2 text-medium-emphasis">
              Accès complet à toutes les fonctionnalités.
            </div>
            <div v-else-if="role.permissions?.length" class="d-flex flex-wrap gap-1">
              <VChip v-for="p in role.permissions" :key="p" size="x-small" label variant="tonal">
                {{ labelOf(p) }}
              </VChip>
            </div>
            <div v-else class="text-body-2 text-medium-emphasis">Aucune permission attribuée.</div>
          </VCardText>

          <VCardText v-if="!PROTECTED.includes(role.name)" class="d-flex gap-2 pt-0">
            <VBtn size="small" variant="tonal" prepend-icon="tabler-edit" @click="openEdit(role)">Permissions</VBtn>
            <VBtn size="small" variant="tonal" color="error" icon @click="askDelete(role)">
              <VIcon icon="tabler-trash" size="18" />
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>

      <VCol v-if="!loadingRoles && !roles.length" cols="12">
        <VCard><VCardText class="text-center text-medium-emphasis py-8">Aucun rôle.</VCardText></VCard>
      </VCol>
    </VRow>

    <!-- Dialog création / édition -->
    <VDialog v-model="dialog" max-width="640" persistent scrollable>
      <VCard>
        <VCardItem>
          <VCardTitle>{{ editing ? `Permissions — ${editing.name}` : 'Nouveau rôle' }}</VCardTitle>
        </VCardItem>
        <VCardText style="max-block-size: 70vh;">
          <VForm ref="refForm" @submit.prevent="submit">
            <AppTextField
              v-if="!editing"
              v-model="form.name"
              label="Nom du rôle *"
              placeholder="ex. superviseur"
              :rules="[requiredValidator]"
              :error-messages="fieldErrors.name"
              class="mb-4"
            />

            <template v-for="(perms, groupName) in groups" :key="groupName">
              <div class="text-subtitle-2 font-weight-bold mt-2 mb-1">{{ groupName }}</div>
              <VRow dense>
                <VCol v-for="p in perms" :key="p.id" cols="12" sm="6">
                  <VCheckbox v-model="form.permissions" :value="p.name" :label="labelOf(p.name)" density="compact" hide-details />
                </VCol>
              </VRow>
            </template>
          </VForm>
        </VCardText>
        <VDivider />
        <VCardText class="d-flex justify-end gap-3">
          <VBtn variant="tonal" color="secondary" :disabled="saving" @click="dialog = false">Annuler</VBtn>
          <VBtn :loading="saving" @click="submit">{{ editing ? 'Mettre à jour' : 'Créer le rôle' }}</VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Dialog suppression -->
    <VDialog v-model="deleteDialog" max-width="440">
      <VCard>
        <VCardItem><VCardTitle>Supprimer le rôle</VCardTitle></VCardItem>
        <VCardText>
          Confirmez-vous la suppression du rôle « <strong>{{ roleToDelete?.name }}</strong> » ?
        </VCardText>
        <VCardText class="d-flex justify-end gap-3">
          <VBtn variant="tonal" color="secondary" :disabled="deleting" @click="deleteDialog = false">Annuler</VBtn>
          <VBtn color="error" :loading="deleting" @click="confirmDelete">Supprimer</VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
