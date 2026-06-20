<script setup>
definePage({ meta: { layout: 'default' } })
</script>

<template>
  <VCard>
    <VCardText class="d-flex flex-column align-center text-center py-12">
      <VIcon
        icon="tabler-shield-lock"
        size="64"
        color="primary"
        class="mb-4"
      />
      <h4 class="text-h4 mb-2">
        Rôles & permissions
      </h4>
      <p class="text-medium-emphasis mb-0">
        Gestion des rôles et permissions (réservé au Super Admin). Module en cours de développement.
      </p>
    </VCardText>
  </VCard>
</template>
