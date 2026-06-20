<script setup>
import { $api, toMediaUrl } from '@/utils/api'

const search = ref('')
const isDialogOpen = ref(false)
const editingService = ref(null)
const photoFile = ref(null)
const photoPreview = ref(null)
const photoInputRef = ref(null)
const isLoading = ref(false)

const snackbar = reactive({
  show: false,
  message: '',
  color: 'success',
})

const showNotification = (message, color = 'success') => {
  snackbar.message = message
  snackbar.color = color
  snackbar.show = true
}

const form = reactive({
  name: '',
  service_category_id: '',
  description: '',
  base_price: '',
})

const headers = [
  { title: 'Photo', key: 'photo', sortable: false },
  { title: 'Nom', key: 'name' },
  { title: 'Catégorie', key: 'category' },
  { title: 'Description', key: 'description' },
  { title: 'Prix de base', key: 'base_price' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const { data: servicesData, execute: fetchServices } = await useApi('/services')
const { data: categoriesData } = await useApi('/service-categories')

const services = computed(() => servicesData.value?.data ?? [])
const categories = computed(() => categoriesData.value?.data ?? [])

const filteredServices = computed(() => {
  if (!search.value) return services.value
  const q = search.value.toLowerCase()

  return services.value.filter(s =>
    s.name.toLowerCase().includes(q) ||
    s.category?.name.toLowerCase().includes(q),
  )
})

const onPhotoChange = event => {
  const file = event.target.files[0]
  if (!file) return
  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

const removePhoto = () => {
  photoFile.value = null
  photoPreview.value = null
  if (photoInputRef.value) photoInputRef.value.value = ''
}

const resetForm = () => {
  form.name = ''
  form.service_category_id = ''
  form.description = ''
  form.base_price = ''
  editingService.value = null
  removePhoto()
}

const openCreateDialog = () => {
  resetForm()
  isDialogOpen.value = true
}

const openEditDialog = service => {
  editingService.value = service
  form.name = service.name
  form.service_category_id = service.category?.id ?? ''
  form.description = service.description ?? ''
  form.base_price = service.base_price ?? ''
  photoFile.value = null
  photoPreview.value = service.photo_url ?? null
  if (photoInputRef.value) photoInputRef.value.value = ''
  isDialogOpen.value = true
}

const saveService = async () => {
  isLoading.value = true

  try {
    const formData = new FormData()

    Object.entries(form).forEach(([key, val]) => {
      if (val !== '' && val !== null && val !== undefined) {
        formData.append(key, typeof val === 'boolean' ? (val ? '1' : '0') : String(val))
      }
    })

    if (photoFile.value) {
      formData.append('photo', photoFile.value)
    }

    if (editingService.value) {
      formData.append('_method', 'PUT')
      await $api(`/services/${editingService.value.id}`, { method: 'POST', body: formData })
    } else {
      await $api('/services', { method: 'POST', body: formData })
    }

    await fetchServices()
    isDialogOpen.value = false
    showNotification(editingService.value ? 'Service mis à jour avec succès' : 'Service créé avec succès')
  } catch {
    showNotification('Une erreur est survenue. Veuillez réessayer.', 'error')
  } finally {
    isLoading.value = false
  }
}

const deleteService = async id => {
  try {
    await useApi(`/services/${id}`).delete().json()
    await fetchServices()
    showNotification('Service supprimé avec succès')
  } catch {
    showNotification('Erreur lors de la suppression.', 'error')
  }
}
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span class="text-h6">Gestion des Services</span>
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          @click="openCreateDialog"
        >
          Ajouter un service
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VTextField
          v-model="search"
          placeholder="Rechercher un service..."
          prepend-inner-icon="tabler-search"
          density="compact"
          class="mb-4"
          style="max-width: 300px"
        />
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="filteredServices"
        :items-per-page="10"
        class="text-no-wrap"
      >
        <template #item.photo="{ item }">
          <VAvatar
            :key="item.photo_url"
            size="40"
            rounded
            :image="toMediaUrl(item.photo_url) ?? undefined"
            :icon="item.photo_url ? undefined : 'tabler-medical-cross'"
            color="primary"
            variant="tonal"
          />
        </template>

        <template #item.category="{ item }">
          <VChip
            v-if="item.category"
            size="small"
            color="primary"
            variant="tonal"
          >
            {{ item.category.name }}
          </VChip>
        </template>

        <template #item.base_price="{ item }">
          {{ Number(item.base_price).toLocaleString('fr-FR') }} FCFA
        </template>

        <template #item.actions="{ item }">
          <VBtn
            icon
            variant="text"
            size="small"
            color="primary"
            @click="openEditDialog(item)"
          >
            <VIcon icon="tabler-edit" />
          </VBtn>
          <VBtn
            icon
            variant="text"
            size="small"
            color="error"
            @click="deleteService(item.id)"
          >
            <VIcon icon="tabler-trash" />
          </VBtn>
        </template>
      </VDataTable>
    </VCard>

    <!-- Dialog Ajout / Édition -->
    <VDialog
      v-model="isDialogOpen"
      max-width="560"
    >
      <VCard :title="editingService ? 'Modifier le service' : 'Ajouter un service'">
        <VCardText class="pt-4">
          <VRow>
            <!-- Photo upload -->
            <VCol cols="12">
              <div class="d-flex align-center gap-4">
                <VAvatar
                  size="80"
                  rounded
                  :image="toMediaUrl(photoPreview) ?? undefined"
                  :icon="photoPreview ? undefined : 'tabler-medical-cross'"
                  color="primary"
                  variant="tonal"
                />
                <div class="d-flex flex-column gap-2">
                  <VBtn
                    variant="tonal"
                    color="primary"
                    size="small"
                    prepend-icon="tabler-upload"
                    @click="photoInputRef?.click()"
                  >
                    Choisir une photo
                  </VBtn>
                  <VBtn
                    v-if="photoPreview"
                    variant="text"
                    color="error"
                    size="small"
                    prepend-icon="tabler-trash"
                    @click="removePhoto"
                  >
                    Supprimer
                  </VBtn>
                  <input
                    ref="photoInputRef"
                    type="file"
                    accept="image/*"
                    class="d-none"
                    @change="onPhotoChange"
                  >
                </div>
              </div>
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="form.name"
                label="Nom du service"
                required
              />
            </VCol>
            <VCol cols="12">
              <VSelect
                v-model="form.service_category_id"
                :items="categories"
                item-title="name"
                item-value="id"
                label="Catégorie"
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="form.base_price"
                label="Prix de base (FCFA)"
                type="number"
              />
            </VCol>
            <VCol cols="12">
              <VTextarea
                v-model="form.description"
                label="Description"
                rows="3"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardActions class="justify-end pa-4">
          <VBtn
            variant="tonal"
            color="secondary"
            :disabled="isLoading"
            @click="isDialogOpen = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="primary"
            :loading="isLoading"
            @click="saveService"
          >
            Enregistrer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Notification -->
    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="3000"
      location="bottom end"
    >
      {{ snackbar.message }}
      <template #actions>
        <VBtn
          variant="text"
          @click="snackbar.show = false"
        >
          Fermer
        </VBtn>
      </template>
    </VSnackbar>
  </div>
</template>
