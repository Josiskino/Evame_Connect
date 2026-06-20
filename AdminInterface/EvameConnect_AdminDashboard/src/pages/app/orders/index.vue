<script setup>
import { $api } from '@/utils/api'
import { useNotificationsStore } from '@/stores/notifications'

definePage({ meta: { layout: 'default' } })

const search = ref('')
const statusFilter = ref('')
const currentPage = ref(1)
const isDetailDialogOpen = ref(false)
const isAssignDialogOpen = ref(false)
const selectedOrder = ref(null)
const selectedNurseId = ref(null)
const isSubmitting = ref(false)

// ── Snackbar standard ─────────────────────────────────────────────────────────
const snackbar = reactive({
  show: false,
  message: '',
  color: 'success',
})

// ── Snackbar nouvelle commande (temps réel) ───────────────────────────────────
const liveSnackbar = reactive({
  show: false,
  patient: '',
  service: '',
  amount: '',
})

const showLiveNotification = order => {
  liveSnackbar.patient = `${order.patient?.first_name ?? ''} ${order.patient?.last_name ?? ''}`.trim()
  liveSnackbar.service = order.service?.name ?? ''
  liveSnackbar.amount = formatAmount(order.total_amount)
  liveSnackbar.show = true
}

const showNotification = (message, color = 'success') => {
  snackbar.message = message
  snackbar.color = color
  snackbar.show = true
}

// ── Config statuts ────────────────────────────────────────────────────────────
const orderStatusConfig = {
  pending: { label: 'En attente', color: 'warning' },
  confirmed: { label: 'Confirmée', color: 'success' },
  cancelled: { label: 'Annulée', color: 'error' },
}

const missionStatusConfig = {
  pending: { label: 'En attente', color: 'warning' },
  assigned: { label: 'Assignée', color: 'info' },
  accepted: { label: 'Acceptée', color: 'teal' },
  refused: { label: 'Refusée', color: 'error' },
  en_route: { label: 'En route', color: 'purple' },
  in_progress: { label: 'En cours', color: 'indigo' },
  completed: { label: 'Terminée', color: 'success' },
  cancelled: { label: 'Annulée', color: 'grey' },
}

const statusOptions = [
  { title: 'Tous les statuts', value: '' },
  { title: 'En attente', value: 'pending' },
  { title: 'Confirmée', value: 'confirmed' },
  { title: 'Annulée', value: 'cancelled' },
]

const headers = [
  { title: '#', key: 'id', width: '60px' },
  { title: 'Patient', key: 'patient' },
  { title: 'Service', key: 'service' },
  { title: 'Montant', key: 'total_amount' },
  { title: 'Statut commande', key: 'status' },
  { title: 'Statut mission', key: 'mission_status' },
  { title: 'Date', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

// ── Données API ───────────────────────────────────────────────────────────────
const apiUrl = computed(() => {
  const params = new URLSearchParams({ page: String(currentPage.value) })
  if (statusFilter.value) params.set('status', statusFilter.value)
  if (search.value) params.set('search', search.value)
  return `/orders?${params.toString()}`
})

watch([search, statusFilter], () => { currentPage.value = 1 })

const { data: ordersData, isFetching, execute: fetchOrders } = useApi(apiUrl)
const { data: nursesData } = await useApi('/nurses')

const orders = computed(() => ordersData.value?.data?.data ?? [])
const total = computed(() => ordersData.value?.data?.meta?.total ?? 0)
const nurses = computed(() => nursesData.value?.data?.data ?? nursesData.value?.data ?? [])

const onTableOptions = ({ page }) => {
  if (page && page !== currentPage.value) currentPage.value = page
}

// ── Formatters ────────────────────────────────────────────────────────────────
const formatDate = date => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatAmount = amount => {
  return Number(amount).toLocaleString('fr-FR') + ' FCFA'
}

const formatName = obj => {
  if (!obj) return '-'
  return `${obj.first_name ?? ''} ${obj.last_name ?? ''}`.trim() || '-'
}

// ── Actions ───────────────────────────────────────────────────────────────────
const openDetail = order => {
  selectedOrder.value = order
  isDetailDialogOpen.value = true
}

const openAssign = order => {
  selectedOrder.value = order
  selectedNurseId.value = order.mission?.nurse?.id ?? null
  isAssignDialogOpen.value = true
}

const assignNurse = async () => {
  if (!selectedNurseId.value || !selectedOrder.value?.mission?.id) return
  isSubmitting.value = true
  try {
    await $api(`/missions/${selectedOrder.value.mission.id}/assign`, {
      method: 'PATCH',
      body: { nurse_id: selectedNurseId.value },
    })
    showNotification('Infirmier assigné avec succès.')
    isAssignDialogOpen.value = false
    fetchOrders()
  } catch {
    showNotification('Erreur lors de l\'assignation.', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const cancelOrder = async order => {
  try {
    await $api(`/orders/${order.id}/status`, {
      method: 'PATCH',
      body: { status: 'cancelled' },
    })
    showNotification('Commande annulée.')
    fetchOrders()
  } catch {
    showNotification('Erreur lors de l\'annulation.', 'error')
  }
}

const canAssign = order => {
  const ms = order.mission?.status
  return ms === 'pending' || ms === 'refused'
}

// ── Réaction au store notifications (Echo géré globalement par le store) ──────
const notifStore = useNotificationsStore()

watch(() => notifStore.lastNewOrder, order => {
  if (!order) return
  fetchOrders()
  showLiveNotification(order)
})

</script>

<template>
  <div>
    <!-- ── En-tête ───────────────────────────────────────────────────────────── -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">
          Commandes
        </h4>
        <p class="text-medium-emphasis mb-0">
          Gestion des demandes de soins
        </p>
      </div>
    </div>

    <!-- ── Tableau ───────────────────────────────────────────────────────────── -->
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4 pa-4">
        <VTextField
          v-model="search"
          placeholder="Rechercher par patient ou service..."
          prepend-inner-icon="tabler-search"
          density="compact"
          style="max-inline-size: 300px;"
          clearable
          hide-details
        />
        <VSelect
          v-model="statusFilter"
          :items="statusOptions"
          item-title="title"
          item-value="value"
          placeholder="Statut"
          density="compact"
          style="max-inline-size: 200px;"
          hide-details
        />
      </VCardText>

      <VDivider />

      <VDataTableServer
        :headers="headers"
        :items="orders"
        :items-length="total"
        :loading="isFetching"
        :items-per-page="15"
        @update:options="onTableOptions"
      >
        <template #item.patient="{ item }">
          <div class="font-weight-medium">
            {{ formatName(item.patient) }}
          </div>
          <small class="text-medium-emphasis">{{ item.patient?.phone ?? '-' }}</small>
        </template>

        <template #item.service="{ item }">
          {{ item.service?.name ?? '-' }}
        </template>

        <template #item.total_amount="{ item }">
          <span class="font-weight-medium">{{ formatAmount(item.total_amount) }}</span>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="orderStatusConfig[item.status]?.color ?? 'grey'"
            size="small"
            variant="tonal"
          >
            {{ orderStatusConfig[item.status]?.label ?? item.status }}
          </VChip>
        </template>

        <template #item.mission_status="{ item }">
          <VChip
            v-if="item.mission"
            :color="missionStatusConfig[item.mission.status]?.color ?? 'grey'"
            size="small"
            variant="tonal"
          >
            {{ missionStatusConfig[item.mission.status]?.label ?? item.mission.status }}
          </VChip>
          <span
            v-else
            class="text-medium-emphasis"
          >—</span>
        </template>

        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <VBtn
              icon
              variant="text"
              size="small"
              color="info"
              @click="openDetail(item)"
            >
              <VIcon icon="tabler-eye" />
              <VTooltip activator="parent">Voir le détail</VTooltip>
            </VBtn>
            <VBtn
              v-if="canAssign(item)"
              icon
              variant="text"
              size="small"
              color="primary"
              @click="openAssign(item)"
            >
              <VIcon icon="tabler-user-plus" />
              <VTooltip activator="parent">Assigner un infirmier</VTooltip>
            </VBtn>
            <VBtn
              v-if="item.status === 'pending'"
              icon
              variant="text"
              size="small"
              color="error"
              @click="cancelOrder(item)"
            >
              <VIcon icon="tabler-x" />
              <VTooltip activator="parent">Annuler la commande</VTooltip>
            </VBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- ── Dialog Détail ─────────────────────────────────────────────────────── -->
    <VDialog
      v-model="isDetailDialogOpen"
      max-width="640"
      scrollable
    >
      <VCard v-if="selectedOrder">
        <VCardTitle class="d-flex align-center justify-space-between pa-5">
          <span>Commande #{{ selectedOrder.id }}</span>
          <VBtn
            icon
            variant="text"
            @click="isDetailDialogOpen = false"
          >
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          <VRow>
            <VCol cols="6">
              <div class="text-caption text-medium-emphasis mb-1">
                Statut commande
              </div>
              <VChip
                :color="orderStatusConfig[selectedOrder.status]?.color ?? 'grey'"
                size="small"
                variant="tonal"
              >
                {{ orderStatusConfig[selectedOrder.status]?.label ?? selectedOrder.status }}
              </VChip>
            </VCol>
            <VCol cols="6">
              <div class="text-caption text-medium-emphasis mb-1">
                Montant
              </div>
              <div class="font-weight-bold text-primary">
                {{ formatAmount(selectedOrder.total_amount) }}
              </div>
            </VCol>
            <VCol cols="6">
              <div class="text-caption text-medium-emphasis mb-1">
                Patient
              </div>
              <div class="font-weight-medium">
                {{ formatName(selectedOrder.patient) }}
              </div>
              <small class="text-medium-emphasis">{{ selectedOrder.patient?.phone }}</small>
            </VCol>
            <VCol cols="6">
              <div class="text-caption text-medium-emphasis mb-1">
                Service
              </div>
              <div class="font-weight-medium">
                {{ selectedOrder.service?.name ?? '-' }}
              </div>
            </VCol>
            <VCol cols="6">
              <div class="text-caption text-medium-emphasis mb-1">
                Demandeur
              </div>
              <div>{{ formatName(selectedOrder.care_actor) }}</div>
              <small class="text-medium-emphasis">{{ selectedOrder.care_actor?.phone }}</small>
            </VCol>
            <VCol cols="6">
              <div class="text-caption text-medium-emphasis mb-1">
                Date de commande
              </div>
              <div>{{ formatDate(selectedOrder.created_at) }}</div>
            </VCol>
            <VCol
              v-if="selectedOrder.notes"
              cols="12"
            >
              <div class="text-caption text-medium-emphasis mb-1">
                Notes
              </div>
              <div class="text-body-2">
                {{ selectedOrder.notes }}
              </div>
            </VCol>
          </VRow>

          <template v-if="selectedOrder.mission">
            <VDivider class="my-4" />
            <div class="text-subtitle-1 font-weight-bold mb-3">
              Mission associée
            </div>
            <VRow>
              <VCol cols="6">
                <div class="text-caption text-medium-emphasis mb-1">
                  Statut mission
                </div>
                <VChip
                  :color="missionStatusConfig[selectedOrder.mission.status]?.color ?? 'grey'"
                  size="small"
                  variant="tonal"
                >
                  {{ missionStatusConfig[selectedOrder.mission.status]?.label ?? selectedOrder.mission.status }}
                </VChip>
              </VCol>
              <VCol cols="6">
                <div class="text-caption text-medium-emphasis mb-1">
                  Infirmier assigné
                </div>
                <div>{{ formatName(selectedOrder.mission.nurse) || 'Non assigné' }}</div>
              </VCol>
              <VCol
                v-if="selectedOrder.mission.accepted_at"
                cols="6"
              >
                <div class="text-caption text-medium-emphasis mb-1">
                  Acceptée le
                </div>
                <div>{{ formatDate(selectedOrder.mission.accepted_at) }}</div>
              </VCol>
              <VCol
                v-if="selectedOrder.mission.completed_at"
                cols="6"
              >
                <div class="text-caption text-medium-emphasis mb-1">
                  Terminée le
                </div>
                <div>{{ formatDate(selectedOrder.mission.completed_at) }}</div>
              </VCol>
            </VRow>
          </template>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-4 justify-end gap-2">
          <VBtn
            v-if="canAssign(selectedOrder)"
            color="primary"
            prepend-icon="tabler-user-plus"
            @click="isDetailDialogOpen = false; openAssign(selectedOrder)"
          >
            Assigner un infirmier
          </VBtn>
          <VBtn
            variant="tonal"
            @click="isDetailDialogOpen = false"
          >
            Fermer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Dialog Assignation ─────────────────────────────────────────────────── -->
    <VDialog
      v-model="isAssignDialogOpen"
      max-width="480"
    >
      <VCard>
        <VCardTitle class="pa-5">
          Assigner un infirmier
        </VCardTitle>
        <VCardText class="pt-0 pa-5">
          <p class="text-body-2 text-medium-emphasis mb-4">
            Commande #{{ selectedOrder?.id }} — {{ selectedOrder?.service?.name }}
            pour {{ formatName(selectedOrder?.patient) }}
          </p>
          <VAutocomplete
            v-model="selectedNurseId"
            :items="nurses"
            item-title="full_name"
            item-value="id"
            label="Choisir un infirmier"
            prepend-inner-icon="tabler-search"
            no-data-text="Aucun infirmier trouvé"
          >
            <template #item="{ item: n, props: p }">
              <VListItem v-bind="p">
                <template #subtitle>
                  {{ n.raw.specialization }} — {{ n.raw.neighborhood }}
                </template>
              </VListItem>
            </template>
          </VAutocomplete>
        </VCardText>
        <VCardActions class="justify-end pa-4 gap-2">
          <VBtn
            variant="tonal"
            :disabled="isSubmitting"
            @click="isAssignDialogOpen = false"
          >
            Annuler
          </VBtn>
          <VBtn
            color="primary"
            :loading="isSubmitting"
            :disabled="!selectedNurseId"
            @click="assignNurse"
          >
            Confirmer l'assignation
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ── Snackbar standard ──────────────────────────────────────────────────── -->
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

    <!-- ── Snackbar nouvelle commande (temps réel) ──────────────────────────── -->
    <VSnackbar
      v-model="liveSnackbar.show"
      :timeout="7000"
      location="top end"
    >
      <div class="d-flex align-center gap-3">
        <VIcon
          icon="tabler-shopping-cart"
          color="success"
          size="24"
        />
        <div>
          <div class="d-flex align-center gap-2 mb-1">
            <span class="font-weight-bold">Nouvelle commande</span>
            <VChip
              color="success"
              size="x-small"
              variant="tonal"
            >
              En attente
            </VChip>
          </div>
          <div class="text-body-2">
            {{ liveSnackbar.patient }} — {{ liveSnackbar.service }}
          </div>
          <div class="text-caption text-medium-emphasis">
            {{ liveSnackbar.amount }}
          </div>
        </div>
      </div>
      <template #actions>
        <VBtn
          variant="text"
          @click="liveSnackbar.show = false"
        >
          Fermer
        </VBtn>
      </template>
    </VSnackbar>
  </div>
</template>

