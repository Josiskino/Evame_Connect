<script setup>
import { $api, toMediaUrl } from '@/utils/api'

const search = ref('')
const isDialogOpen = ref(false)
const editingCategory = ref(null)
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
  slug: '',
  is_active: true,
})

const headers = [
  { title: 'Photo', key: 'photo', sortable: false },
  { title: 'Nom', key: 'name' },
  { title: 'Slug', key: 'slug' },
  { title: 'Statut', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const { data: categoriesData, execute: fetchCategories } = await useApi('/service-categories')

const categories = computed(() => {
  const data = categoriesData.value?.data ?? []
  console.log('[categories] data:', JSON.stringify(data.map(c => ({ id: c.id, name: c.name, photo_url: c.photo_url }))))
  return data
})

const filteredCategories = computed(() => {
  if (!search.value) return categories.value
  const q = search.value.toLowerCase()

  return categories.value.filter(c =>
    c.name.toLowerCase().includes(q) ||
    c.slug.toLowerCase().includes(q),
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
  form.slug = ''
  form.is_active = true
  editingCategory.value = null
  removePhoto()
}

const openCreateDialog = () => {
  resetForm()
  isDialogOpen.value = true
}

const openEditDialog = category => {
  editingCategory.value = category
  form.name = category.name
  form.slug = category.slug
  form.is_active = Boolean(category.is_active)
  photoFile.value = null
  photoPreview.value = category.photo_url ?? null
  if (photoInputRef.value) photoInputRef.value.value = ''
  isDialogOpen.value = true
}

watch(() => form.name, val => {
  if (!editingCategory.value) {
    form.slug = val.toLowerCase().trim().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')
  }
})

const saveCategory = async () => {
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

    let response
    if (editingCategory.value) {
      formData.append('_method', 'PUT')
      response = await $api(`/service-categories/${editingCategory.value.id}`, { method: 'POST', body: formData })
    } else {
      response = await $api('/service-categories', { method: 'POST', body: formData })
    }
    console.log('[saveCategory] response:', JSON.stringify(response))

    await fetchCategories()
    isDialogOpen.value = false
    showNotification(editingCategory.value ? 'Catégorie mise à jour avec succès' : 'Catégorie créée avec succès')
  } catch {
    showNotification('Une erreur est survenue. Veuillez réessayer.', 'error')
  } finally {
    isLoading.value = false
  }
}

const deleteCategory = async id => {
  try {
    await useApi(`/service-categories/${id}`).delete().json()
    await fetchCategories()
    showNotification('Catégorie supprimée avec succès')
  } catch {
    showNotification('Erreur lors de la suppression.', 'error')
  }
}
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span class="text-h6">Gestion des Catégories</span>
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          @click="openCreateDialog"
        >
          Ajouter une catégorie
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <VTextField
          v-model="search"
          placeholder="Rechercher une catégorie..."
          prepend-inner-icon="tabler-search"
          density="compact"
          class="mb-4"
          style="max-width: 300px"
        />
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="filteredCategories"
        :items-per-page="10"
        class="text-no-wrap"
      >
        <template #item.photo="{ item }">
          <VAvatar
            :key="item.photo_url"
            size="40"
            rounded
            :image="toMediaUrl(item.photo_url) ?? undefined"
            :icon="item.photo_url ? undefined : 'tabler-category'"
            color="primary"
            variant="tonal"
          />
        </template>

        <template #item.slug="{ item }">
          <code class="text-body-2">{{ item.slug }}</code>
        </template>

        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'error'"
            size="small"
            variant="tonal"
          >
            {{ item.is_active ? 'Actif' : 'Inactif' }}
          </VChip>
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
            @click="deleteCategory(item.id)"
          >
            <VIcon icon="tabler-trash" />
          </VBtn>
        </template>
      </VDataTable>
    </VCard>

    <!-- Dialog Ajout / Édition -->
    <VDialog
      v-model="isDialogOpen"
      max-width="480"
    >
      <VCard :title="editingCategory ? 'Modifier la catégorie' : 'Ajouter une catégorie'">
        <VCardText class="pt-4">
          <VRow>
            <!-- Photo upload -->
            <VCol cols="12">
              <div class="d-flex align-center gap-4">
                <VAvatar
                  size="80"
                  rounded
                  :image="toMediaUrl(photoPreview) ?? undefined"
                  :icon="photoPreview ? undefined : 'tabler-category'"
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
                label="Nom"
                required
              />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="form.slug"
                label="Slug"
                hint="Généré automatiquement depuis le nom"
                persistent-hint
              />
            </VCol>
            <VCol cols="12">
              <VSwitch
                v-model="form.is_active"
                label="Actif"
                color="success"
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
            @click="saveCategory"
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
