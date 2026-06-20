<script setup>
import { $api } from '@/utils/api'

const search = ref('')
const currentPage = ref(1)
const isFormDialogOpen = ref(false)
const isVerifyDialogOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const editingNurse = ref(null)
const verifyingNurse = ref(null)
const deletingNurse = ref(null)
const isSubmitting = ref(false)
const photoFile = ref(null)
const photoPreview = ref(null)
const photoInputRef = ref(null)

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
  gender: '',
  license_number: '',
  specialization: '',
  neighborhood: '',
  id_card_number: '',
  id_card_issued_at: '',
  id_card_expires_at: '',
  cni_issued_by: '',
})

const verifyForm = reactive({
  verification_status: '',
})

const headers = [
  { title: 'Infirmier', key: 'name' },
  { title: 'Téléphone', key: 'phone' },
  { title: 'Spécialisation', key: 'specialization' },
  { title: 'Quartier', key: 'neighborhood' },
  { title: 'Vérification', key: 'verification_status' },
  { title: 'Actif', key: 'is_active' },
  { title: 'Inscription', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const specializationOptions = [
  'General',
  'Pediatrics',
  'Geriatrics',
  'Mental Health',
]

const genderOptions = [
  { title: 'Homme', value: 'male' },
  { title: 'Femme', value: 'female' },
]

const verificationOptions = [
  { title: 'Vérifié', value: 'verified' },
  { title: 'Rejeté', value: 'rejected' },
  { title: 'En attente', value: 'pending' },
]

const apiUrl = computed(() => {
  const params = new URLSearchParams({ page: String(currentPage.value) })
  if (search.value) params.set('search', search.value)
  return `/nurses?${params.toString()}`
})

watch(search, () => { currentPage.value = 1 })

const { data: nursesData, isFetching, execute: fetchNurses } = useApi(apiUrl)

const nurses = computed(() => nursesData.value?.data?.data ?? [])
const totalNurses = computed(() => nursesData.value?.data?.meta?.total ?? 0)

const onTableOptions = ({ page }) => {
  if (page && page !== currentPage.value) currentPage.value = page
}

const verificationColor = status => ({
  pending: 'warning',
  verified: 'success',
  rejected: 'error',
})[status] ?? 'secondary'

const verificationLabel = status => ({
  pending: 'En attente',
  verified: 'Vérifié',
  rejected: 'Rejeté',
})[status] ?? status

const formatDate = date => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

const resetForm = () => {
  form.first_name = ''
  form.last_name = ''
  form.email = ''
  form.phone = ''
  form.password = ''
  form.gender = ''
  form.license_number = ''
  form.specialization = ''
  form.neighborhood = ''
  form.id_card_number = ''
  form.id_card_issued_at = ''
  form.id_card_expires_at = ''
  form.cni_issued_by = ''
  photoFile.value = null
  photoPreview.value = null
  editingNurse.value = null
}

const onPhotoSelected = event => {
  const file = event.target.files[0]
  if (!file) return
  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

const resetPhoto = () => {
  photoFile.value = null
  photoPreview.value = null
}

const openCreateDialog = () => {
  resetForm()
  isFormDialogOpen.value = true
}

const openEditDialog = nurse => {
  editingNurse.value = nurse
  form.first_name = nurse.first_name ?? ''
  form.last_name = nurse.last_name ?? ''
  form.email = nurse.email ?? ''
  form.phone = nurse.phone ?? ''
  form.password = ''
  form.gender = nurse.gender ?? ''
  form.license_number = nurse.license_number ?? ''
  form.specialization = nurse.specialization ?? ''
  form.neighborhood = nurse.neighborhood ?? ''
  form.id_card_number = nurse.id_card_number ?? ''
  form.id_card_issued_at = nurse.id_card_issued_at ?? ''
  form.id_card_expires_at = nurse.id_card_expires_at ?? ''
  form.cni_issued_by = nurse.cni_issued_by ?? ''
  photoFile.value = null
  photoPreview.value = nurse.profile_photo_url ?? null
  isFormDialogOpen.value = true
}

const openVerifyDialog = nurse => {
  verifyingNurse.value = nurse
  verifyForm.verification_status = nurse.verification_status ?? 'pending'
  isVerifyDialogOpen.value = true
}

const openDeleteDialog = nurse => {
  deletingNurse.value = nurse
  isDeleteDialogOpen.value = true
}

const saveNurse = async () => {
  isSubmitting.value = true
  try {
    if (editingNurse.value) {
      const payload = { ...form }
      if (!payload.password) delete payload.password

      if (photoFile.value) {
        const formData = new FormData()
        Object.entries(payload).forEach(([key, val]) => {
          if (val !== '' && val !== null && val !== undefined) {
            formData.append(key, String(val))
          }
        })
        formData.append('profile_photo', photoFile.value)
        await $api(`/nurses/${editingNurse.value.id}`, { method: 'PUT', body: formData })
      } else {
        await useApi(`/nurses/${editingNurse.value.id}`).put(payload).json()
      }
    } else {
      if (photoFile.value) {
        const formData = new FormData()
        Object.entries(form).forEach(([key, val]) => {
          if (val !== '' && val !== null && val !== undefined) {
            formData.append(key, String(val))
          }
        })
        formData.append('profile_photo', photoFile.value)
        await $api('/nurses', { method: 'POST', body: formData })
      } else {
        await useApi('/nurses').post(form).json()
      }
    }
    isFormDialogOpen.value = false
    fetchNurses()
  } finally {
    isSubmitting.value = false
  }
}

const saveVerification = async () => {
  isSubmitting.value = true
  try {
    await useApi(`/nurses/${verifyingNurse.value.id}/verify`).patch(verifyForm).json()
    isVerifyDialogOpen.value = false
    fetchNurses()
  } finally {
    isSubmitting.value = false
  }
}

const confirmDelete = async () => {
  isSubmitting.value = true
  try {
    await useApi(`/nurses/${deletingNurse.value.id}`).delete().json()
    isDeleteDialogOpen.value = false
    fetchNurses()
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span class="text-h6">Gestion des Infirmiers</span>
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          @click="openCreateDialog"
        >
          Ajouter un infirmier
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VTextField
          v-model="search"
          placeholder="Rechercher par nom, téléphone, numéro de licence..."
          prepend-inner-icon="tabler-search"
          density="compact"
          class="mb-4"
          style="max-width: 360px"
        />
      </VCardText>

      <VDataTableServer
        :headers="headers"
        :items="nurses"
        :items-length="totalNurses"
        :items-per-page="15"
        :page="currentPage"
        :loading="isFetching"
        class="text-no-wrap"
        @update:options="onTableOptions"
      >
        <!-- Nom + avatar -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3">
            <VAvatar
              size="34"
              color="success"
              variant="tonal"
            >
              <span class="text-sm font-weight-medium">
                {{ item.first_name?.charAt(0)?.toUpperCase() ?? '?' }}
              </span>
            </VAvatar>
            <div>
              <div class="font-weight-medium">
                {{ item.full_name ?? '-' }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ item.email ?? '' }}
              </div>
            </div>
          </div>
        </template>

        <!-- Spécialisation -->
        <template #item.specialization="{ item }">
          <VChip
            v-if="item.specialization"
            size="small"
            color="primary"
            variant="tonal"
          >
            {{ item.specialization }}
          </VChip>
          <span
            v-else
            class="text-medium-emphasis"
          >-</span>
        </template>

        <!-- Quartier -->
        <template #item.neighborhood="{ item }">
          {{ item.neighborhood ?? '-' }}
        </template>

        <!-- Statut de vérification -->
        <template #item.verification_status="{ item }">
          <VChip
            :color="verificationColor(item.verification_status)"
            size="small"
            variant="tonal"
          >
            {{ verificationLabel(item.verification_status) }}
          </VChip>
        </template>

        <!-- Actif -->
        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'default'"
            size="small"
            variant="tonal"
          >
            {{ item.is_active ? 'Actif' : 'Inactif' }}
          </VChip>
        </template>

        <!-- Date inscription -->
        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <VTooltip text="Vérifier / Rejeter" location="top">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon
                variant="text"
                size="small"
                color="info"
                @click="openVerifyDialog(item)"
              >
                <VIcon icon="tabler-shield-check" />
              </VBtn>
            </template>
          </VTooltip>

          <VTooltip text="Modifier" location="top">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon
                variant="text"
                size="small"
                color="primary"
                @click="openEditDialog(item)"
              >
                <VIcon icon="tabler-edit" />
              </VBtn>
            </template>
          </VTooltip>

          <VTooltip text="Supprimer" location="top">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                icon
                variant="text"
                size="small"
                color="error"
                @click="openDeleteDialog(item)"
              >
                <VIcon icon="tabler-trash" />
              </VBtn>
            </template>
          </VTooltip>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- ─── Dialog Créer / Modifier ─────────────────────────────────────────── -->
    <VDialog
      v-model="isFormDialogOpen"
      max-width="640"
      scrollable
    >
      <VCard :title="editingNurse ? 'Modifier l\'infirmier' : 'Ajouter un infirmier'">
        <VCardText class="pt-4">
          <VRow>
            <!-- Photo -->
            <VCol
              cols="12"
              class="d-flex align-center gap-6"
            >
              <VAvatar
                rounded
                size="88"
                color="success"
                variant="tonal"
              >
                <VImg
                  v-if="photoPreview"
                  :src="photoPreview"
                  cover
                />
                <VIcon
                  v-else
                  icon="tabler-user"
                  size="40"
                />
              </VAvatar>

              <div class="d-flex flex-column gap-3">
                <div class="d-flex flex-wrap gap-3">
                  <VBtn
                    size="small"
                    prepend-icon="tabler-cloud-upload"
                    @click="photoInputRef?.click()"
                  >
                    Importer une photo
                  </VBtn>
                  <VBtn
                    v-if="photoPreview"
                    size="small"
                    color="secondary"
                    variant="tonal"
                    prepend-icon="tabler-refresh"
                    @click="resetPhoto"
                  >
                    Réinitialiser
                  </VBtn>
                </div>
                <p class="text-body-2 text-medium-emphasis mb-0">
                  JPG, PNG ou GIF · Max 2 Mo
                </p>
              </div>

              <input
                ref="photoInputRef"
                type="file"
                accept="image/*"
                class="d-none"
                @change="onPhotoSelected"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.first_name"
                label="Prénom"
                required
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.last_name"
                label="Nom"
                required
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.email"
                label="Email"
                type="email"
                required
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.phone"
                label="Téléphone (+228XXXXXXXX)"
                placeholder="+22890000000"
                required
              />
            </VCol>
            <VCol
              v-if="!editingNurse"
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.password"
                label="Mot de passe"
                type="password"
                required
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="form.gender"
                :items="genderOptions"
                item-title="title"
                item-value="value"
                label="Sexe"
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.license_number"
                label="Numéro de licence"
                required
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="form.specialization"
                :items="specializationOptions"
                label="Spécialisation"
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.neighborhood"
                label="Quartier"
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.id_card_number"
                label="Numéro de pièce d'identité"
                required
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.id_card_issued_at"
                label="Date d'émission de la pièce"
                type="date"
                required
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="form.id_card_expires_at"
                label="Date d'expiration de la pièce"
                type="date"
                required
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="form.cni_issued_by"
                label="Instance ayant délivré la CNI"
                placeholder="Ex: Préfecture du Golfe – Lomé"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions class="justify-end pa-4">
          <VBtn
            variant="tonal"
            color="secondary"
            @click="isFormDialogOpen = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="primary"
            :loading="isSubmitting"
            @click="saveNurse"
          >
            {{ editingNurse ? 'Enregistrer les modifications' : 'Créer l\'infirmier' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ─── Dialog Vérification ──────────────────────────────────────────────── -->
    <VDialog
      v-model="isVerifyDialogOpen"
      max-width="400"
    >
      <VCard title="Vérification du compte infirmier">
        <VCardText class="pt-4">
          <p class="mb-4 text-body-2">
            Infirmier :
            <strong>{{ verifyingNurse?.full_name }}</strong>
          </p>
          <VSelect
            v-model="verifyForm.verification_status"
            :items="verificationOptions"
            item-title="title"
            item-value="value"
            label="Statut de vérification"
          />
        </VCardText>

        <VCardActions class="justify-end pa-4">
          <VBtn
            variant="tonal"
            color="secondary"
            @click="isVerifyDialogOpen = false"
          >
            Annuler
          </VBtn>
          <VBtn
            :color="verifyForm.verification_status === 'verified' ? 'success' : verifyForm.verification_status === 'rejected' ? 'error' : 'warning'"
            :loading="isSubmitting"
            @click="saveVerification"
          >
            Confirmer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ─── Dialog Suppression ───────────────────────────────────────────────── -->
    <VDialog
      v-model="isDeleteDialogOpen"
      max-width="400"
    >
      <VCard title="Supprimer l'infirmier">
        <VCardText class="pt-4">
          <p class="text-body-2">
            Êtes-vous sûr de vouloir supprimer
            <strong>{{ deletingNurse?.full_name }}</strong> ?
            Cette action est irréversible.
          </p>
        </VCardText>

        <VCardActions class="justify-end pa-4">
          <VBtn
            variant="tonal"
            color="secondary"
            @click="isDeleteDialogOpen = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="error"
            :loading="isSubmitting"
            @click="confirmDelete"
          >
            Supprimer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
