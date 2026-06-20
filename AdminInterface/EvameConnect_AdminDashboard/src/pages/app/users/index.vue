<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const roleFilter = ref('')
const page = ref(1)
const perPage = 50

const queryParams = computed(() => {
  const params = new URLSearchParams({ page: page.value, per_page: perPage })
  if (search.value) params.append('search', search.value)
  if (roleFilter.value) params.append('role', roleFilter.value)
  return params.toString()
})

const { data: usersData, execute: refresh, isFetching } = useApi(
  computed(() => `/users?${queryParams.value}`),
)

const users = computed(() => usersData.value?.data ?? [])
const total = computed(() => usersData.value?.meta?.total ?? usersData.value?.total ?? 0)
const lastPage = computed(() => usersData.value?.meta?.last_page ?? usersData.value?.last_page ?? 1)

const headers = [
  { title: 'Utilisateur', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Rôle', key: 'roles' },
  { title: 'Inscription', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const roleOptions = ['admin', 'manager', 'viewer']

const roleColor = role => {
  const map = { admin: 'error', manager: 'warning', viewer: 'info' }
  return map[role] ?? 'secondary'
}

const formatDate = date => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

// Dialog état
const dialog = ref(false)
const dialogDelete = ref(false)
const editMode = ref(false)
const selectedUser = ref(null)
const form = ref({ name: '', email: '', password: '', role: '' })
const formRef = ref()
const saving = ref(false)
const errorMsg = ref('')

const openCreate = () => {
  editMode.value = false
  form.value = { name: '', email: '', password: '', role: '' }
  errorMsg.value = ''
  dialog.value = true
}

const openEdit = user => {
  editMode.value = true
  selectedUser.value = user
  form.value = { name: user.name, email: user.email, password: '', role: user.roles?.[0] ?? '' }
  errorMsg.value = ''
  dialog.value = true
}

const openDelete = user => {
  selectedUser.value = user
  dialogDelete.value = true
}

const saveUser = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  errorMsg.value = ''

  try {
    if (editMode.value) {
      const body = { name: form.value.name, email: form.value.email }
      if (form.value.password) body.password = form.value.password
      await $api(`/users/${selectedUser.value.id}`, { method: 'PUT', body })
      if (form.value.role) {
        await $api(`/users/${selectedUser.value.id}/role`, { method: 'PATCH', body: { role: form.value.role } })
      }
    }
    else {
      const res = await $api('/users', {
        method: 'POST',
        body: {
          name: form.value.name,
          email: form.value.email,
          password: form.value.password,
          role: form.value.role,
        },
      })
      if (form.value.role && res?.data?.id) {
        await $api(`/users/${res.data.id}/role`, { method: 'PATCH', body: { role: form.value.role } })
      }
    }
    dialog.value = false
    refresh()
  }
  catch (err) {
    errorMsg.value = err?.response?._data?.message ?? 'Une erreur est survenue'
  }
  finally {
    saving.value = false
  }
}

const deleteUser = async () => {
  try {
    await $api(`/users/${selectedUser.value.id}`, { method: 'DELETE' })
    dialogDelete.value = false
    refresh()
  }
  catch {
    dialogDelete.value = false
  }
}

watch([search, roleFilter], () => { page.value = 1 })
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4 flex-wrap gap-2">
        <span class="text-h6">Gestion des Utilisateurs</span>
        <VBtn
          prepend-icon="tabler-plus"
          color="primary"
          @click="openCreate"
        >
          Ajouter
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VRow class="mb-2">
          <VCol
            cols="12"
            sm="6"
            md="4"
          >
            <AppTextField
              v-model="search"
              placeholder="Rechercher (nom, email)..."
              prepend-inner-icon="tabler-search"
              density="compact"
              clearable
            />
          </VCol>
          <VCol
            cols="12"
            sm="4"
            md="3"
          >
            <AppSelect
              v-model="roleFilter"
              :items="[{ title: 'Tous les rôles', value: '' }, ...roleOptions.map(r => ({ title: r, value: r }))]"
              density="compact"
              placeholder="Filtrer par rôle"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="users"
        :loading="isFetching"
        hide-default-footer
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3">
            <VAvatar
              size="34"
              color="primary"
              variant="tonal"
            >
              <span class="text-sm font-weight-medium">
                {{ item.name?.charAt(0)?.toUpperCase() ?? '?' }}
              </span>
            </VAvatar>
            <span>{{ item.name ?? '-' }}</span>
          </div>
        </template>

        <template #item.roles="{ item }">
          <div class="d-flex gap-1 flex-wrap">
            <VChip
              v-for="role in (item.roles ?? [])"
              :key="role"
              :color="roleColor(role)"
              size="small"
              variant="tonal"
            >
              {{ role }}
            </VChip>
            <span
              v-if="!item.roles?.length"
              class="text-medium-emphasis"
            >–</span>
          </div>
        </template>

        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <VBtn
              icon
              size="small"
              variant="text"
              @click="openEdit(item)"
            >
              <VIcon
                size="18"
                icon="tabler-edit"
              />
            </VBtn>
            <VBtn
              icon
              size="small"
              variant="text"
              color="error"
              @click="openDelete(item)"
            >
              <VIcon
                size="18"
                icon="tabler-trash"
              />
            </VBtn>
          </div>
        </template>
      </VDataTable>

      <!-- Pagination -->
      <VCardText class="d-flex align-center justify-space-between flex-wrap gap-2 py-3">
        <span class="text-body-2 text-medium-emphasis">
          {{ total }} utilisateur(s) au total
        </span>
        <VPagination
          v-model="page"
          :length="lastPage"
          :total-visible="5"
          rounded
        />
      </VCardText>
    </VCard>

    <!-- Dialog création/édition -->
    <VDialog
      v-model="dialog"
      max-width="500"
    >
      <VCard :title="editMode ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'">
        <VCardText>
          <VForm ref="formRef">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="form.name"
                  label="Nom complet"
                  :rules="[requiredValidator]"
                  placeholder="Jean Dupont"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="form.email"
                  label="Email"
                  type="email"
                  :rules="[requiredValidator, emailValidator]"
                  placeholder="jean@evame.com"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="form.password"
                  label="Mot de passe"
                  type="password"
                  :rules="editMode ? [] : [requiredValidator]"
                  :placeholder="editMode ? 'Laisser vide pour ne pas modifier' : '••••••••'"
                />
              </VCol>
              <VCol cols="12">
                <AppSelect
                  v-model="form.role"
                  label="Rôle"
                  :items="roleOptions.map(r => ({ title: r, value: r }))"
                  placeholder="Choisir un rôle"
                />
              </VCol>
              <VCol
                v-if="errorMsg"
                cols="12"
              >
                <VAlert
                  type="error"
                  variant="tonal"
                >
                  {{ errorMsg }}
                </VAlert>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn
            variant="tonal"
            @click="dialog = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="primary"
            :loading="saving"
            @click="saveUser"
          >
            {{ editMode ? 'Enregistrer' : 'Créer' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog suppression -->
    <VDialog
      v-model="dialogDelete"
      max-width="400"
    >
      <VCard title="Supprimer l'utilisateur">
        <VCardText>
          Êtes-vous sûr de vouloir supprimer <strong>{{ selectedUser?.name }}</strong> ?
          Cette action est irréversible.
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn
            variant="tonal"
            @click="dialogDelete = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="error"
            @click="deleteUser"
          >
            Supprimer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
