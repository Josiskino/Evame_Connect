<script setup>
import mapStyle from '@/assets/map-style.json'

const mapRef = ref(null)
const mapInstance = ref(null)
const markers = ref([])
const selectedHospital = ref(null)
const isLoading = ref(true)

const { data: hospitalsData } = await useApi('/hospitals')
const hospitals = computed(() => hospitalsData.value?.data ?? [])

const initMap = () => {
  if (!mapRef.value || !window.google) return

  mapInstance.value = new window.google.maps.Map(mapRef.value, {
    center: { lat: 6.1375, lng: 1.2123 },
    zoom: 12,
    styles: mapStyle,
    disableDefaultUI: false,
    zoomControl: true,
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: true,
  })

  hospitals.value.forEach(hospital => {
    const lat = parseFloat(hospital.latitude)
    const lng = parseFloat(hospital.longitude)
    if (isNaN(lat) || isNaN(lng)) return

    const marker = new window.google.maps.Marker({
      position: { lat, lng },
      map: mapInstance.value,
      title: hospital.name,
      icon: {
        path: window.google.maps.SymbolPath.CIRCLE,
        fillColor: '#4CAF50',
        fillOpacity: 1,
        strokeColor: '#ffffff',
        strokeWeight: 2,
        scale: 10,
      },
    })

    marker.addListener('click', () => {
      selectedHospital.value = hospital
    })

    markers.value.push(marker)
  })
}

const loadGoogleMaps = () => {
  return new Promise(resolve => {
    if (window.google?.maps) { resolve(); return }

    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY}`
    script.async = true
    script.defer = true
    script.onload = resolve
    document.head.appendChild(script)
  })
}

onMounted(async () => {
  await loadGoogleMaps()
  isLoading.value = false
  await nextTick()
  initMap()
})

const flyTo = hospital => {
  selectedHospital.value = hospital
  if (!mapInstance.value) return
  mapInstance.value.panTo({
    lat: parseFloat(hospital.latitude),
    lng: parseFloat(hospital.longitude),
  })
  mapInstance.value.setZoom(15)
}
</script>

<template>
  <div>
    <VRow>
      <!-- Carte -->
      <VCol
        cols="12"
        md="8"
      >
        <VCard>
          <VCardTitle class="d-flex align-center justify-space-between pa-4">
            <span class="text-h6">Carte des Hôpitaux</span>
            <VChip
              color="primary"
              variant="tonal"
              size="small"
              prepend-icon="tabler-building-hospital"
            >
              {{ hospitals.length }} hôpitaux
            </VChip>
          </VCardTitle>
          <VDivider />
          <div style="position: relative; height: 560px;">
            <div
              v-if="isLoading"
              class="d-flex align-center justify-center"
              style="height: 100%;"
            >
              <VProgressCircular
                indeterminate
                color="primary"
                size="48"
              />
            </div>
            <div
              ref="mapRef"
              style="width: 100%; height: 100%;"
            />
          </div>
        </VCard>
      </VCol>

      <!-- Liste -->
      <VCol
        cols="12"
        md="4"
      >
        <VCard style="height: 632px; display: flex; flex-direction: column;">
          <VCardTitle class="pa-4 pb-2">
            <span class="text-h6">Liste des hôpitaux</span>
          </VCardTitle>
          <VDivider />
          <div style="overflow-y: auto; flex: 1;">
            <VList lines="two">
              <VListItem
                v-for="hospital in hospitals"
                :key="hospital.id"
                :active="selectedHospital?.id === hospital.id"
                active-color="primary"
                class="cursor-pointer"
                @click="flyTo(hospital)"
              >
                <template #prepend>
                  <VAvatar
                    color="primary"
                    variant="tonal"
                    size="36"
                  >
                    <VIcon
                      icon="tabler-building-hospital"
                      size="18"
                    />
                  </VAvatar>
                </template>

                <VListItemTitle class="font-weight-medium">
                  {{ hospital.name }}
                </VListItemTitle>
                <VListItemSubtitle class="text-caption">
                  {{ hospital.city }} · {{ hospital.address }}
                </VListItemSubtitle>
              </VListItem>
            </VList>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Info card hôpital sélectionné -->
    <VRow v-if="selectedHospital">
      <VCol cols="12">
        <VAlert
          color="primary"
          variant="tonal"
          closable
          @click:close="selectedHospital = null"
        >
          <template #prepend>
            <VIcon icon="tabler-building-hospital" />
          </template>
          <strong>{{ selectedHospital.name }}</strong> —
          {{ selectedHospital.address }}, {{ selectedHospital.city }}
          <span class="text-caption ml-2 text-medium-emphasis">
            ({{ selectedHospital.latitude }}, {{ selectedHospital.longitude }})
          </span>
        </VAlert>
      </VCol>
    </VRow>
  </div>
</template>
