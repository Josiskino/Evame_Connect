<script setup>
definePage({ meta: { layout: 'default', action: 'read', subject: 'catalogue' } })

const route = useRoute()
const router = useRouter()

const { data, isFetching } = useApi(`/motos/${route.params.id}`)
const moto = computed(() => data.value?.data ?? null)

const fmtMoney = n => `${new Intl.NumberFormat('fr-FR').format(Number(n ?? 0))} FCFA`

const familleLabel = code => ({
  colonne_vertebrale: 'Colonne vertébrale',
  scooter: 'Scooter',
  sous_los: "Sous l'os",
}[code] ?? code)

const CONSTRUCTION_LABELS = {
  dimensions: 'Dimensions (L × l × H)',
  empattement: 'Empattement',
  masse: 'Masse à vide',
  garde_sol: 'Garde au sol',
  hauteur_assise: "Hauteur d'assise",
  reservoir: 'Réservoir',
  frein_av: 'Frein avant',
  frein_ar: 'Frein arrière',
  pneu_av: 'Pneu avant',
  pneu_ar: 'Pneu arrière',
}
const MOTEUR_LABELS = {
  type: 'Type',
  cylindree: 'Cylindrée',
  alesage_course: 'Alésage × course',
  taux_compression: 'Taux de compression',
  livraison_carburant: 'Alimentation',
  transmission: 'Transmission',
  starter: 'Démarrage',
  allumage: 'Allumage',
}

const rows = (specs, labels) => Object.entries(labels)
  .map(([key, label]) => ({ label, value: specs?.[key] || '—' }))
</script>

<template>
  <div>
    <VBtn variant="text" prepend-icon="tabler-arrow-left" class="mb-4" @click="router.push('/motos')">
      Retour au catalogue
    </VBtn>

    <div v-if="isFetching && !moto" class="d-flex justify-center py-12">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <template v-else-if="moto">
      <VRow>
        <!-- Image -->
        <VCol cols="12" md="6">
          <VCard>
            <VImg
              :src="moto.image_url || ''"
              height="360"
              contain
              class="bg-grey-lighten-4"
            >
              <template #placeholder>
                <div class="d-flex align-center justify-center h-100">
                  <VIcon icon="tabler-motorbike" size="64" class="text-disabled" />
                </div>
              </template>
            </VImg>
            <VCardText v-if="moto.images && moto.images.length > 1" class="d-flex gap-2 flex-wrap">
              <VAvatar
                v-for="(img, i) in moto.images"
                :key="i"
                rounded size="56"
                class="bg-grey-lighten-4 border"
              >
                <VImg :src="img" />
              </VAvatar>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Infos principales -->
        <VCol cols="12" md="6">
          <VCard class="h-100">
            <VCardItem>
              <VCardTitle class="text-h4 font-weight-bold">{{ moto.modele }}</VCardTitle>
              <div class="d-flex gap-2 mt-2">
                <VChip color="primary" label>{{ moto.classe_cc }}</VChip>
                <VChip label variant="tonal">{{ familleLabel(moto.famille) }}</VChip>
                <VChip v-if="!moto.disponible" color="error" label>Rupture</VChip>
                <VChip v-else-if="moto.stock_faible" color="warning" label>Stock faible</VChip>
                <VChip v-else color="success" label>Disponible</VChip>
              </div>
            </VCardItem>
            <VCardText>
              <div class="text-h4 text-primary font-weight-bold mb-4">{{ fmtMoney(moto.prix) }}</div>
              <VRow>
                <VCol cols="6"><div class="text-caption text-medium-emphasis">Stock</div><div class="font-weight-medium">{{ moto.stock }}</div></VCol>
                <VCol cols="6"><div class="text-caption text-medium-emphasis">Couleur</div><div class="font-weight-medium">{{ moto.couleur || '—' }}</div></VCol>
                <VCol cols="6"><div class="text-caption text-medium-emphasis">Puissance</div><div class="font-weight-medium">{{ moto.puissance || '—' }}</div></VCol>
                <VCol cols="6"><div class="text-caption text-medium-emphasis">Couple</div><div class="font-weight-medium">{{ moto.couple || '—' }}</div></VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Spécifications -->
      <VRow class="mt-1">
        <VCol cols="12" md="6">
          <VCard>
            <VCardItem><VCardTitle>Construction</VCardTitle></VCardItem>
            <VTable density="comfortable">
              <tbody>
                <tr v-for="r in rows(moto.specifications?.construction, CONSTRUCTION_LABELS)" :key="r.label">
                  <td class="text-medium-emphasis">{{ r.label }}</td>
                  <td class="font-weight-medium text-end">{{ r.value }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </VCol>
        <VCol cols="12" md="6">
          <VCard>
            <VCardItem><VCardTitle>Moteur</VCardTitle></VCardItem>
            <VTable density="comfortable">
              <tbody>
                <tr v-for="r in rows(moto.specifications?.moteur, MOTEUR_LABELS)" :key="r.label">
                  <td class="text-medium-emphasis">{{ r.label }}</td>
                  <td class="font-weight-medium text-end">{{ r.value }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </VCol>
      </VRow>
    </template>
  </div>
</template>
