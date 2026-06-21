<script setup>
const props = defineProps({
  from: { type: String, default: '' },
  to: { type: String, default: '' },
})

const emit = defineEmits(['update:from', 'update:to'])

const preset = ref(null)
const customRange = ref('')

const presetOptions = [
  { title: 'Toutes les dates', value: null },
  { title: "Aujourd'hui", value: 'today' },
  { title: 'Cette semaine', value: 'week' },
  { title: 'Ce mois', value: 'month' },
  { title: 'Plage personnalisée', value: 'custom' },
]

// Format local AAAA-MM-JJ (sans décalage de fuseau)
const fmt = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

const setRange = (f, t) => {
  emit('update:from', f)
  emit('update:to', t)
}

watch(preset, p => {
  const today = new Date()

  if (p === null) {
    customRange.value = ''
    setRange('', '')
  }
  else if (p === 'today') {
    customRange.value = ''
    setRange(fmt(today), fmt(today))
  }
  else if (p === 'week') {
    customRange.value = ''
    const day = (today.getDay() + 6) % 7 // 0 = lundi
    const monday = new Date(today); monday.setDate(today.getDate() - day)
    const sunday = new Date(monday); sunday.setDate(monday.getDate() + 6)
    setRange(fmt(monday), fmt(sunday))
  }
  else if (p === 'month') {
    customRange.value = ''
    const first = new Date(today.getFullYear(), today.getMonth(), 1)
    const last = new Date(today.getFullYear(), today.getMonth() + 1, 0)
    setRange(fmt(first), fmt(last))
  }
  else if (p === 'custom') {
    // En attente de la sélection au calendrier
    setRange('', '')
  }
})

// Plage personnalisée : flatpickr renvoie "AAAA-MM-JJ to AAAA-MM-JJ"
watch(customRange, val => {
  if (!val) return
  const parts = String(val).split(' to ')
  setRange(parts[0] || '', parts[1] || parts[0] || '')
})
</script>

<template>
  <div class="d-flex gap-3" style="inline-size: 100%;">
    <AppSelect
      v-model="preset"
      :items="presetOptions"
      placeholder="Période"
      style="min-inline-size: 160px;"
      :style="preset === 'custom' ? 'flex: 0 0 180px;' : 'flex: 1 1 auto;'"
    />
    <AppDateTimePicker
      v-if="preset === 'custom'"
      v-model="customRange"
      placeholder="Du … au …"
      :config="{ mode: 'range', dateFormat: 'Y-m-d' }"
      style="flex: 1 1 auto;"
    />
  </div>
</template>
