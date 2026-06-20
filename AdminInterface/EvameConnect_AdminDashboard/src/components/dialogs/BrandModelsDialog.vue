<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  brand: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue'])

const dialog = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const search = ref('')

const queryUrl = computed(
  () => `/products?brand=${encodeURIComponent(props.brand?.name ?? '')}&per_page=200`,
)

const { data: productsData, isFetching } = useApi(queryUrl)

const models = computed(() => {
  const products = productsData.value?.data ?? []
  const seen = new Map()
  for (const p of products) {
    const name = p.model?.name
    if (!name) continue
    if (!seen.has(name)) seen.set(name, { name, count: 1 })
    else seen.get(name).count += 1
  }
  return [...seen.values()].sort((a, b) => a.name.localeCompare(b.name))
})

const filteredModels = computed(() => {
  const q = search.value?.toLowerCase().trim()
  if (!q) return models.value
  return models.value.filter(m => m.name.toLowerCase().includes(q))
})

watch(() => props.modelValue, v => { if (v) search.value = '' })
</script>

<template>
  <VDialog
    v-model="dialog"
    max-width="560"
    scrollable
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <div class="d-flex align-center gap-3">
          <VAvatar
            color="primary"
            variant="tonal"
            size="36"
          >
            <VIcon icon="tabler-tag" />
          </VAvatar>
          <div>
            <div class="text-h6">
              {{ brand?.name ?? '–' }}
            </div>
            <div class="text-caption text-medium-emphasis">
              Modèles enregistrés
            </div>
          </div>
        </div>
        <VBtn
          icon
          variant="text"
          size="small"
          @click="dialog = false"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-4">
        <AppTextField
          v-model="search"
          placeholder="Rechercher un modèle..."
          prepend-inner-icon="tabler-search"
          density="compact"
          clearable
          class="mb-4"
        />

        <div
          v-if="isFetching"
          class="d-flex justify-center align-center py-8"
        >
          <VProgressCircular
            indeterminate
            color="primary"
          />
        </div>

        <div
          v-else-if="filteredModels.length === 0"
          class="text-center py-8 text-medium-emphasis"
        >
          <VIcon
            icon="tabler-mood-empty"
            size="36"
            class="mb-2"
          />
          <div class="text-body-2">
            Aucun modèle enregistré pour cette marque
          </div>
        </div>

        <VList
          v-else
          density="compact"
          class="rounded border"
        >
          <VListItem
            v-for="m in filteredModels"
            :key="m.name"
          >
            <template #prepend>
              <VIcon
                icon="tabler-car"
                size="18"
                class="me-2"
              />
            </template>
            <VListItemTitle class="text-body-2">
              {{ m.name }}
            </VListItemTitle>
            <template #append>
              <VChip
                size="x-small"
                variant="tonal"
                color="primary"
              >
                {{ m.count }}
              </VChip>
            </template>
          </VListItem>
        </VList>
      </VCardText>

      <VDivider />

      <VCardActions class="justify-end pa-3">
        <VBtn
          variant="tonal"
          @click="dialog = false"
        >
          Fermer
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
