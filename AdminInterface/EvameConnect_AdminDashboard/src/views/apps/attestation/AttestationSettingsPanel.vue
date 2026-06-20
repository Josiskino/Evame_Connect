<script setup>
import { settingsTotalIsValid } from '@/composables/useAttestationCalc'

const settings = defineModel('settings', { type: Object, required: true })

const totalOk = computed(() => settingsTotalIsValid(settings.value))

const total = computed(() =>
  (Number(settings.value.pct_fob) || 0)
  + (Number(settings.value.pct_fret) || 0)
  + (Number(settings.value.pct_ass) || 0),
)
</script>

<template>
  <VCard
    variant="outlined"
    rounded="lg"
    class="mb-4"
  >
    <VCardText>
      <div class="d-flex align-center flex-wrap gap-4">
        <div class="text-body-2 font-weight-medium text-medium-emphasis me-2">
          <VIcon
            icon="tabler-adjustments"
            size="18"
            class="me-1"
          />
          Répartition de la valeur CAF
        </div>
        <VTextField
          v-model.number="settings.pct_fob"
          label="% FOB"
          type="number"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 104px;"
        />
        <VTextField
          v-model.number="settings.pct_fret"
          label="% Fret"
          type="number"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 104px;"
        />
        <VTextField
          v-model.number="settings.pct_ass"
          label="% Assurance"
          type="number"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 116px;"
        />
        <VChip
          :color="totalOk ? 'success' : 'error'"
          size="small"
          label
        >
          Total&nbsp;: {{ total.toFixed(2) }} %
        </VChip>
        <VDivider
          vertical
          class="mx-1 d-none d-sm-block"
          style="height: 32px;"
        />
        <VTextField
          v-model.number="settings.taux_change"
          label="Taux 1 EUR → FCFA"
          type="number"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 184px;"
        />
      </div>
      <div class="text-caption text-medium-emphasis mt-3">
        Le total des trois pourcentages doit faire 100 %. Les champs en lecture seule du document sont calculés automatiquement.
      </div>
    </VCardText>
  </VCard>
</template>
