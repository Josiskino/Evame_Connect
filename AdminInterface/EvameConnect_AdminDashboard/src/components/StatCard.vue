<script setup>
defineProps({
  title: { type: String, default: '' },
  value: { type: [String, Number], default: '' },
  icon: { type: String, default: 'tabler-chart-bar' },
  color: { type: String, default: 'primary' },
})
</script>

<template>
  <VCard>
    <VCardText class="d-flex align-center gap-4">
      <VAvatar :color="color" variant="tonal" rounded size="44">
        <VIcon :icon="icon" size="24" />
      </VAvatar>
      <div>
        <div class="text-h6 font-weight-bold">{{ value }}</div>
        <div class="text-caption text-medium-emphasis">{{ title }}</div>
      </div>
    </VCardText>
  </VCard>
</template>
