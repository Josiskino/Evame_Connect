<script setup>
import { useAttestationStore } from '@/stores/attestation'

definePage({ meta: { layout: 'default' } })

const store = useAttestationStore()
const router = useRouter()

const removingId = ref(null)

onMounted(() => store.fetchList())

const formatDate = value => (value ? new Date(value).toLocaleString('fr-FR') : '—')

const openEditor = id => router.push({ name: 'attestation', query: { id } })
const createNew = () => router.push({ name: 'attestation' })

const removeItem = async att => {
  removingId.value = att.id
  try {
    await store.remove(att.id)
  }
  finally {
    removingId.value = null
  }
}
</script>

<template>
  <div>
    <VCard>
      <VCardText class="d-flex align-center flex-wrap gap-y-3">
        <div class="me-auto pe-4">
          <h5 class="text-h5 mb-0">
            Mes attestations
          </h5>
          <span class="text-caption text-medium-emphasis">
            Retrouvez et rouvrez vos attestations enregistrées.
          </span>
        </div>
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          @click="createNew"
        >
          Nouvelle attestation
        </VBtn>
      </VCardText>

      <VDivider />

      <VProgressLinear
        v-if="store.isLoadingList"
        indeterminate
        color="primary"
      />

      <div
        v-else-if="!store.attestations.length"
        class="text-center text-medium-emphasis py-12"
      >
        <VIcon
          icon="tabler-file-off"
          size="40"
          class="mb-2"
        />
        <div>Aucune attestation enregistrée pour le moment.</div>
      </div>

      <VTable v-else>
        <thead>
          <tr>
            <th>Attestation</th>
            <th>Créé le</th>
            <th>Modifié le</th>
            <th class="text-end">
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="att in store.attestations"
            :key="att.id"
          >
            <td class="font-weight-medium">
              {{ att.label }}
            </td>
            <td class="text-medium-emphasis">
              {{ formatDate(att.created_at) }}
            </td>
            <td class="text-medium-emphasis">
              {{ att.updated_at !== att.created_at ? formatDate(att.updated_at) : '—' }}
            </td>
            <td class="text-end text-no-wrap">
              <VBtn
                size="small"
                variant="tonal"
                color="primary"
                class="me-2"
                @click="openEditor(att.id)"
              >
                Ouvrir
              </VBtn>
              <VBtn
                icon="tabler-trash"
                size="small"
                variant="text"
                color="error"
                :loading="removingId === att.id"
                @click="removeItem(att)"
              />
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>
  </div>
</template>
