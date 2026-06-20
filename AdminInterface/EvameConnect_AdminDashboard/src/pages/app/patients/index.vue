<script setup>
definePage({ meta: { layout: 'default' } })

const search = ref('')
const currentPage = ref(1)
const isDetailDialogOpen = ref(false)
const selectedPatient = ref(null)
const activeTab = ref('info')

const headers = [
  { title: 'Patient', key: 'name' },
  { title: 'Téléphone', key: 'phone' },
  { title: 'Genre', key: 'gender' },
  { title: 'Date de naissance', key: 'birth_date' },
  { title: 'Adresse', key: 'address' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const vitalSignHeaders = [
  { title: 'Étape', key: 'recorded_at_step' },
  { title: 'Temp. (°C)', key: 'temperature' },
  { title: 'Tension', key: 'blood_pressure' },
  { title: 'Pouls (bpm)', key: 'heart_rate' },
  { title: 'SpO2 (%)', key: 'spo2' },
  { title: 'Glycémie', key: 'blood_sugar' },
  { title: 'Date', key: 'created_at' },
]

const apiUrl = computed(() => {
  const params = new URLSearchParams({ page: String(currentPage.value) })
  if (search.value) params.set('search', search.value)
  return `/patients?${params.toString()}`
})

watch(search, () => { currentPage.value = 1 })

const { data: patientsData, isFetching } = useApi(apiUrl)

const patients = computed(() => patientsData.value?.data?.data ?? [])
const total = computed(() => patientsData.value?.data?.meta?.total ?? 0)

const onTableOptions = ({ page }) => {
  if (page && page !== currentPage.value) currentPage.value = page
}

const formatDate = date => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

const genderLabel = g => ({ male: 'Homme', female: 'Femme' })[g] ?? '-'
const genderColor = g => ({ male: 'info', female: 'pink' })[g] ?? 'secondary'

const stepLabel = step => ({
  arrival: 'Arrivée',
  during: 'En cours',
  departure: 'Départ',
})[step] ?? step

const stepColor = step => ({
  arrival: 'info',
  during: 'warning',
  departure: 'success',
})[step] ?? 'grey'

// Signes vitaux pour le patient sélectionné
const vitalSignsUrl = computed(() =>
  selectedPatient.value ? `/patients/${selectedPatient.value.id}/vital-signs` : null,
)

const { data: vitalSignsData, isFetching: loadingVitals } = useApi(vitalSignsUrl)
const vitalSigns = computed(() => {
  const d = vitalSignsData.value?.data
  return Array.isArray(d) ? d : []
})

const openDetail = patient => {
  selectedPatient.value = patient
  activeTab.value = 'info'
  isDetailDialogOpen.value = true
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">
          Patients
        </h4>
        <p class="text-medium-emphasis mb-0">
          Liste des patients enregistrés
        </p>
      </div>
    </div>

    <VCard>
      <VCardText class="pa-4">
        <VTextField
          v-model="search"
          placeholder="Rechercher un patient..."
          prepend-inner-icon="tabler-search"
          density="compact"
          style="max-inline-size: 320px;"
          clearable
          hide-details
        />
      </VCardText>

      <VDivider />

      <VDataTableServer
        :headers="headers"
        :items="patients"
        :items-length="total"
        :loading="isFetching"
        :items-per-page="15"
        @update:options="onTableOptions"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3">
            <VAvatar
              size="36"
              :color="genderColor(item.gender)"
              variant="tonal"
            >
              <VIcon
                :icon="item.gender === 'female' ? 'tabler-user-heart' : 'tabler-user'"
                size="18"
              />
            </VAvatar>
            <div>
              <div class="font-weight-medium">
                {{ item.full_name }}
              </div>
            </div>
          </div>
        </template>

        <template #item.phone="{ item }">
          {{ item.phone ?? '-' }}
        </template>

        <template #item.gender="{ item }">
          <VChip
            :color="genderColor(item.gender)"
            size="small"
            variant="tonal"
          >
            {{ genderLabel(item.gender) }}
          </VChip>
        </template>

        <template #item.birth_date="{ item }">
          {{ formatDate(item.birth_date) }}
        </template>

        <template #item.address="{ item }">
          <span class="text-truncate d-block" style="max-width: 200px;">
            {{ item.address ?? '-' }}
          </span>
        </template>

        <template #item.actions="{ item }">
          <VBtn
            icon
            variant="text"
            size="small"
            color="info"
            @click="openDetail(item)"
          >
            <VIcon icon="tabler-eye" />
            <VTooltip activator="parent">Voir le dossier</VTooltip>
          </VBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Dialog Dossier Patient -->
    <VDialog
      v-model="isDetailDialogOpen"
      max-width="800"
      scrollable
    >
      <VCard v-if="selectedPatient">
        <VCardTitle class="d-flex align-center justify-space-between pa-5">
          <div class="d-flex align-center gap-3">
            <VAvatar
              :color="genderColor(selectedPatient.gender)"
              variant="tonal"
              size="44"
            >
              <VIcon :icon="selectedPatient.gender === 'female' ? 'tabler-user-heart' : 'tabler-user'" />
            </VAvatar>
            <div>
              <div>{{ selectedPatient.full_name }}</div>
              <small class="text-medium-emphasis font-weight-regular">{{ selectedPatient.phone }}</small>
            </div>
          </div>
          <VBtn
            icon
            variant="text"
            @click="isDetailDialogOpen = false"
          >
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VTabs
          v-model="activeTab"
          class="px-4"
        >
          <VTab value="info">
            <VIcon
              start
              icon="tabler-user"
            />
            Informations
          </VTab>
          <VTab value="vitals">
            <VIcon
              start
              icon="tabler-activity"
            />
            Signes vitaux
          </VTab>
        </VTabs>

        <VDivider />

        <VCardText class="pa-5">
          <!-- Onglet Info -->
          <VWindow v-model="activeTab">
            <VWindowItem value="info">
              <VRow>
                <VCol cols="6">
                  <div class="text-caption text-medium-emphasis mb-1">
                    Prénom
                  </div>
                  <div class="font-weight-medium">
                    {{ selectedPatient.first_name }}
                  </div>
                </VCol>
                <VCol cols="6">
                  <div class="text-caption text-medium-emphasis mb-1">
                    Nom
                  </div>
                  <div class="font-weight-medium">
                    {{ selectedPatient.last_name }}
                  </div>
                </VCol>
                <VCol cols="6">
                  <div class="text-caption text-medium-emphasis mb-1">
                    Téléphone
                  </div>
                  <div>{{ selectedPatient.phone ?? '-' }}</div>
                </VCol>
                <VCol cols="6">
                  <div class="text-caption text-medium-emphasis mb-1">
                    Genre
                  </div>
                  <VChip
                    :color="genderColor(selectedPatient.gender)"
                    size="small"
                    variant="tonal"
                  >
                    {{ genderLabel(selectedPatient.gender) }}
                  </VChip>
                </VCol>
                <VCol cols="6">
                  <div class="text-caption text-medium-emphasis mb-1">
                    Date de naissance
                  </div>
                  <div>{{ formatDate(selectedPatient.birth_date) }}</div>
                </VCol>
                <VCol cols="12">
                  <div class="text-caption text-medium-emphasis mb-1">
                    Adresse
                  </div>
                  <div>{{ selectedPatient.address ?? '-' }}</div>
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- Onglet Signes Vitaux -->
            <VWindowItem value="vitals">
              <div
                v-if="loadingVitals"
                class="text-center py-6"
              >
                <VProgressCircular
                  indeterminate
                  color="primary"
                />
              </div>
              <template v-else-if="vitalSigns.length">
                <VDataTable
                  :headers="vitalSignHeaders"
                  :items="vitalSigns"
                  :items-per-page="10"
                  density="compact"
                >
                  <template #item.recorded_at_step="{ item }">
                    <VChip
                      :color="stepColor(item.recorded_at_step)"
                      size="x-small"
                      variant="tonal"
                    >
                      {{ stepLabel(item.recorded_at_step) }}
                    </VChip>
                  </template>
                  <template #item.temperature="{ item }">
                    <span :class="item.temperature > 38 ? 'text-error font-weight-bold' : ''">
                      {{ item.temperature ?? '-' }}
                    </span>
                  </template>
                  <template #item.created_at="{ item }">
                    {{ formatDate(item.created_at) }}
                  </template>
                </VDataTable>
              </template>
              <div
                v-else
                class="text-center text-medium-emphasis py-8"
              >
                <VIcon
                  icon="tabler-activity-heartbeat"
                  size="48"
                  class="mb-3 d-block mx-auto opacity-30"
                />
                <div>Aucun signe vital enregistré pour ce patient</div>
              </div>
            </VWindowItem>
          </VWindow>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
