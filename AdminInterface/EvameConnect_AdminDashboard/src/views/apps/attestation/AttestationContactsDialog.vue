<script setup>
import { useAttestationStore } from '@/stores/attestation'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'useContact'])

const store = useAttestationStore()

const isOpen = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const blankImporter = () => ({ type: 'importer', name: '', address: '', phone: '', nif: '', city: '' })
const blankSupplier = () => ({ type: 'supplier', name: '', phone: '', address: '', country: '' })

const newImporter = reactive(blankImporter())
const newSupplier = reactive(blankSupplier())
const saving = ref(false)

watch(isOpen, open => {
  if (open)
    store.fetchContacts()
})

const buildDetails = parts => parts.filter(Boolean).join(' | ')

const addImporter = async () => {
  if (!newImporter.name.trim())
    return
  saving.value = true
  try {
    await store.addContact({
      ...newImporter,
      details: buildDetails([newImporter.nif, newImporter.phone, newImporter.city]),
    })
    Object.assign(newImporter, blankImporter())
  }
  finally { saving.value = false }
}

const addSupplier = async () => {
  if (!newSupplier.name.trim())
    return
  saving.value = true
  try {
    await store.addContact({
      ...newSupplier,
      details: buildDetails([newSupplier.address, newSupplier.country]),
    })
    Object.assign(newSupplier, blankSupplier())
  }
  finally { saving.value = false }
}

const use = contact => {
  emit('useContact', contact)
  isOpen.value = false
}
</script>

<template>
  <VDialog
    v-model="isOpen"
    max-width="760"
    scrollable
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>Carnet d'adresses</span>
        <VBtn
          icon="tabler-x"
          variant="text"
          size="small"
          @click="isOpen = false"
        />
      </VCardTitle>
      <VDivider />
      <VCardText style="max-height: 70vh;">
        <VRow>
          <!-- IMPORTATEURS -->
          <VCol
            cols="12"
            md="6"
          >
            <div class="text-subtitle-2 font-weight-bold mb-2">
              Importateurs
            </div>
            <VList
              v-if="store.importers.length"
              density="compact"
              class="mb-2"
            >
              <VListItem
                v-for="c in store.importers"
                :key="c.id"
                :title="c.name"
                :subtitle="c.details || c.nif"
              >
                <template #append>
                  <VBtn
                    size="x-small"
                    variant="tonal"
                    color="primary"
                    class="me-1"
                    @click="use(c)"
                  >
                    Utiliser
                  </VBtn>
                  <VBtn
                    icon="tabler-trash"
                    size="x-small"
                    variant="text"
                    color="error"
                    @click="store.removeContact(c.id)"
                  />
                </template>
              </VListItem>
            </VList>
            <div
              v-else
              class="text-caption text-medium-emphasis mb-2"
            >
              Aucun importateur enregistré.
            </div>
            <VTextField
              v-model="newImporter.name"
              label="Nom"
              density="compact"
              class="mb-2"
              hide-details
            />
            <VTextField
              v-model="newImporter.nif"
              label="NIF"
              density="compact"
              class="mb-2"
              hide-details
            />
            <div class="d-flex gap-2 mb-2">
              <VTextField
                v-model="newImporter.phone"
                label="Tél"
                density="compact"
                hide-details
              />
              <VTextField
                v-model="newImporter.city"
                label="Ville / Pays"
                density="compact"
                hide-details
              />
            </div>
            <VBtn
              size="small"
              color="primary"
              :loading="saving"
              :disabled="!newImporter.name.trim()"
              @click="addImporter"
            >
              + Ajouter
            </VBtn>
          </VCol>

          <!-- FOURNISSEURS -->
          <VCol
            cols="12"
            md="6"
          >
            <div class="text-subtitle-2 font-weight-bold mb-2">
              Fournisseurs
            </div>
            <VList
              v-if="store.suppliers.length"
              density="compact"
              class="mb-2"
            >
              <VListItem
                v-for="c in store.suppliers"
                :key="c.id"
                :title="c.name"
                :subtitle="c.details || c.country"
              >
                <template #append>
                  <VBtn
                    size="x-small"
                    variant="tonal"
                    color="primary"
                    class="me-1"
                    @click="use(c)"
                  >
                    Utiliser
                  </VBtn>
                  <VBtn
                    icon="tabler-trash"
                    size="x-small"
                    variant="text"
                    color="error"
                    @click="store.removeContact(c.id)"
                  />
                </template>
              </VListItem>
            </VList>
            <div
              v-else
              class="text-caption text-medium-emphasis mb-2"
            >
              Aucun fournisseur enregistré.
            </div>
            <VTextField
              v-model="newSupplier.name"
              label="Nom"
              density="compact"
              class="mb-2"
              hide-details
            />
            <VTextField
              v-model="newSupplier.country"
              label="Pays"
              density="compact"
              class="mb-2"
              hide-details
            />
            <div class="d-flex gap-2 mb-2">
              <VTextField
                v-model="newSupplier.phone"
                label="Tél"
                density="compact"
                hide-details
              />
              <VTextField
                v-model="newSupplier.address"
                label="Adresse"
                density="compact"
                hide-details
              />
            </div>
            <VBtn
              size="small"
              color="primary"
              :loading="saving"
              :disabled="!newSupplier.name.trim()"
              @click="addSupplier"
            >
              + Ajouter
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </VDialog>
</template>
