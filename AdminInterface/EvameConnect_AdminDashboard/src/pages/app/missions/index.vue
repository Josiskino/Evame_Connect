<script setup>
import { $api } from '@/utils/api'

const search = ref('')
const statusFilter = ref('')
const isAssignDialogOpen = ref(false)
const isDetailDialogOpen = ref(false)
const selectedMission = ref(null)
const selectedNurseId = ref(null)
const nurseSearch = ref('')
const isLoading = ref(false)
const missions = ref([])
const nurses = ref([])
let refreshInterval = null

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

const statusConfig = {
  pending: { label: 'En attente', color: 'warning' },
  assigned: { label: 'Assignee', color: 'info' },
  accepted: { label: 'Acceptee', color: 'teal' },
  refused: { label: 'Refusee', color: 'error' },
  en_route: { label: 'En route', color: 'purple' },
  in_progress: { label: 'En cours', color: 'indigo' },
  completed: { label: 'Terminee', color: 'success' },
  cancelled: { label: 'Annulee', color: 'grey' },
}

const statusOptions = [
  { title: 'Tous', value: '' },
  ...Object.entries(statusConfig).map(([value, { label }]) => ({ title: label, value })),
]

const headers = [
  { title: '#', key: 'id', width: '60px' },
  { title: 'Patient', key: 'patient' },
  { title: 'Service', key: 'service' },
  { title: 'Infirmier', key: 'nurse' },
  { title: 'Statut', key: 'status' },
  { title: 'Date', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchMissions = async () => {
  try {
    const response = await $api('/missions')
    const payload = response.data ?? response

    missions.value = Array.isArray(payload) ? payload : (payload.data ?? [])
  } catch {
    // silently fail for polling
  }
}

const fetchNurses = async () => {
  try {
    const response = await $api('/nurses')
    const payload = response.data ?? response

    nurses.value = Array.isArray(payload) ? payload : (payload.data ?? [])
  } catch {
    showNotification('Erreur lors du chargement des infirmiers.', 'error')
  }
}

const filteredMissions = computed(() => {
  let result = missions.value

  if (statusFilter.value) {
    result = result.filter(m => m.status === statusFilter.value)
  }

  if (search.value) {
    const q = search.value.toLowerCase()

    result = result.filter(m =>
      (m.patient?.first_name + ' ' + m.patient?.last_name).toLowerCase().includes(q)
      || m.service?.name?.toLowerCase().includes(q)
      || (m.nurse?.first_name + ' ' + m.nurse?.last_name).toLowerCase().includes(q),
    )
  }

  return result
})

const pendingCount = computed(() => missions.value.filter(m => m.status === 'pending' || m.status === 'refused').length)

const openAssignDialog = mission => {
  selectedMission.value = mission
  selectedNurseId.value = null
  nurseSearch.value = ''
  isAssignDialogOpen.value = true
  fetchNurses()
}

const openDetailDialog = mission => {
  selectedMission.value = mission
  isDetailDialogOpen.value = true
}

const assignNurse = async () => {
  if (!selectedNurseId.value || !selectedMission.value) return
  isLoading.value = true

  try {
    await $api(`/missions/${selectedMission.value.id}/assign`, {
      method: 'PATCH',
      body: { nurse_id: selectedNurseId.value },
    })

    await fetchMissions()
    isAssignDialogOpen.value = false
    showNotification('Infirmier assigne avec succes')
  } catch {
    showNotification('Erreur lors de l\'assignation.', 'error')
  } finally {
    isLoading.value = false
  }
}

const formatDate = dateStr => {
  if (!dateStr) return '-'

  return new Date(dateStr).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatPatientName = patient => {
  if (!patient) return '-'

  return `${patient.first_name} ${patient.last_name}`
}

const formatNurseName = nurse => {
  if (!nurse) return 'Non assigne'

  return `${nurse.first_name} ${nurse.last_name}`
}

const timelineEvents = computed(() => {
  if (!selectedMission.value) return []

  const m = selectedMission.value
  const events = []

  events.push({ label: 'Demande creee', date: m.created_at, icon: 'tabler-plus', color: 'primary' })

  if (m.assigned_at) events.push({ label: `Assigne a ${formatNurseName(m.nurse)}`, date: m.assigned_at, icon: 'tabler-user-check', color: 'info' })
  if (m.accepted_at) events.push({ label: 'Acceptee par l\'infirmier', date: m.accepted_at, icon: 'tabler-check', color: 'teal' })
  if (m.refused_at) events.push({ label: `Refusee: ${m.refusal_reason || '-'}`, date: m.refused_at, icon: 'tabler-x', color: 'error' })
  if (m.en_route_at) events.push({ label: 'Infirmier en route', date: m.en_route_at, icon: 'tabler-car', color: 'purple' })
  if (m.started_at) events.push({ label: 'Soins en cours', date: m.started_at, icon: 'tabler-heartbeat', color: 'indigo' })
  if (m.completed_at) events.push({ label: 'Mission terminee', date: m.completed_at, icon: 'tabler-circle-check', color: 'success' })
  if (m.cancelled_at) events.push({ label: 'Mission annulee', date: m.cancelled_at, icon: 'tabler-ban', color: 'grey' })

  return events
})

// Auto-refresh every 10 seconds
onMounted(async () => {
  await fetchMissions()
  refreshInterval = setInterval(fetchMissions, 10000)
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <div class="d-flex align-center gap-2">
          <span class="text-h6">Gestion des Missions</span>
          <VChip
            v-if="pendingCount > 0"
            color="warning"
            size="small"
          >
            {{ pendingCount }} en attente
          </VChip>
        </div>
      </VCardTitle>

      <VDivider />

      <VCardText>
        <div class="d-flex gap-4 mb-4">
          <VTextField
            v-model="search"
            placeholder="Rechercher..."
            prepend-inner-icon="tabler-search"
            density="compact"
            style="max-width: 300px"
          />
          <VSelect
            v-model="statusFilter"
            :items="statusOptions"
            label="Filtrer par statut"
            density="compact"
            style="max-width: 200px"
            clearable
          />
        </div>
      </VCardText>

      <VDataTable
        :headers="headers"
        :items="filteredMissions"
        :items-per-page="10"
        class="text-no-wrap"
      >
        <template #item.patient="{ item }">
          {{ formatPatientName(item.patient) }}
        </template>

        <template #item.service="{ item }">
          <div>
            <div>{{ item.service?.name ?? '-' }}</div>
            <small class="text-medium-emphasis">{{ item.service ? `${Number(item.service.base_price).toLocaleString('fr-FR')} FCFA` : '' }}</small>
          </div>
        </template>

        <template #item.nurse="{ item }">
          {{ formatNurseName(item.nurse) }}
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="statusConfig[item.status]?.color ?? 'grey'"
            size="small"
            variant="tonal"
          >
            {{ statusConfig[item.status]?.label ?? item.status }}
          </VChip>
        </template>

        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #item.actions="{ item }">
          <VBtn
            v-if="item.status === 'pending' || item.status === 'refused'"
            variant="tonal"
            color="primary"
            size="small"
            prepend-icon="tabler-user-plus"
            class="me-2"
            @click="openAssignDialog(item)"
          >
            Assigner
          </VBtn>
          <VBtn
            icon
            variant="text"
            size="small"
            color="info"
            @click="openDetailDialog(item)"
          >
            <VIcon icon="tabler-eye" />
          </VBtn>
        </template>
      </VDataTable>
    </VCard>

    <!-- Dialog Assignation -->
    <VDialog
      v-model="isAssignDialogOpen"
      max-width="500"
    >
      <VCard title="Assigner un infirmier">
        <VCardText class="pt-4">
          <p class="mb-4">
            Mission #{{ selectedMission?.id }} — {{ selectedMission?.service?.name }}
            pour {{ formatPatientName(selectedMission?.patient) }}
          </p>
          <VAutocomplete
            v-model="selectedNurseId"
            v-model:search="nurseSearch"
            :items="nurses"
            item-title="full_name"
            item-value="id"
            label="Rechercher un infirmier"
            prepend-inner-icon="tabler-search"
            no-data-text="Aucun infirmier trouve"
          >
            <template #item="{ item: nurseItem, props: itemProps }">
              <VListItem v-bind="itemProps">
                <template #subtitle>
                  {{ nurseItem.raw.specialization }} — {{ nurseItem.raw.neighborhood }}
                </template>
              </VListItem>
            </template>
          </VAutocomplete>
        </VCardText>
        <VCardActions class="justify-end pa-4">
          <VBtn
            variant="tonal"
            color="secondary"
            :disabled="isLoading"
            @click="isAssignDialogOpen = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="primary"
            :loading="isLoading"
            :disabled="!selectedNurseId"
            @click="assignNurse"
          >
            Assigner
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Detail Mission -->
    <VDialog
      v-model="isDetailDialogOpen"
      max-width="600"
    >
      <VCard title="Detail de la mission">
        <VCardText class="pt-4">
          <template v-if="selectedMission">
            <div class="d-flex justify-space-between mb-4">
              <div>
                <div class="text-h6">
                  Mission #{{ selectedMission.id }}
                </div>
                <VChip
                  :color="statusConfig[selectedMission.status]?.color ?? 'grey'"
                  size="small"
                  variant="tonal"
                  class="mt-1"
                >
                  {{ statusConfig[selectedMission.status]?.label ?? selectedMission.status }}
                </VChip>
              </div>
            </div>

            <VDivider class="mb-4" />

            <VRow class="mb-4">
              <VCol
                cols="12"
                sm="6"
              >
                <div class="text-caption text-medium-emphasis">
                  Patient
                </div>
                <div class="font-weight-medium">
                  {{ formatPatientName(selectedMission.patient) }}
                </div>
              </VCol>
              <VCol
                cols="12"
                sm="6"
              >
                <div class="text-caption text-medium-emphasis">
                  Service
                </div>
                <div class="font-weight-medium">
                  {{ selectedMission.service?.name ?? '-' }}
                </div>
                <small class="text-medium-emphasis">{{ selectedMission.service ? `${Number(selectedMission.service.base_price).toLocaleString('fr-FR')} FCFA` : '' }}</small>
              </VCol>
              <VCol
                cols="12"
                sm="6"
              >
                <div class="text-caption text-medium-emphasis">
                  Infirmier
                </div>
                <div class="font-weight-medium">
                  {{ formatNurseName(selectedMission.nurse) }}
                </div>
              </VCol>
              <VCol
                v-if="selectedMission.notes"
                cols="12"
              >
                <div class="text-caption text-medium-emphasis">
                  Notes
                </div>
                <div>{{ selectedMission.notes }}</div>
              </VCol>
            </VRow>

            <VDivider class="mb-4" />

            <div class="text-subtitle-2 mb-3">
              Historique
            </div>
            <VTimeline
              density="compact"
              side="end"
            >
              <VTimelineItem
                v-for="(event, i) in timelineEvents"
                :key="i"
                :dot-color="event.color"
                size="small"
              >
                <div class="d-flex justify-space-between">
                  <span class="font-weight-medium">{{ event.label }}</span>
                  <small class="text-medium-emphasis">{{ formatDate(event.date) }}</small>
                </div>
              </VTimelineItem>
            </VTimeline>
          </template>
        </VCardText>
        <VCardActions class="justify-end pa-4">
          <VBtn
            variant="tonal"
            @click="isDetailDialogOpen = false"
          >
            Fermer
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
