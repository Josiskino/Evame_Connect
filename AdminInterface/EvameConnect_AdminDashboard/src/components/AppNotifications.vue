<script setup>
const { notifications, dismiss } = useNotifications()
</script>

<template>
  <div class="app-notifications">
    <TransitionGroup name="notif">
      <VCard
        v-for="n in notifications"
        :key="n.id"
        class="notif-card"
        elevation="8"
      >
        <div class="d-flex align-start gap-3 pa-3">
          <VIcon :icon="n.icon" :color="n.color" size="22" class="mt-1" />
          <div class="flex-grow-1">
            <div v-if="n.title" class="text-subtitle-2 font-weight-bold" :class="`text-${n.color}`">
              {{ n.title }}
            </div>
            <div class="text-body-2 text-medium-emphasis">{{ n.message }}</div>
          </div>
          <VBtn icon variant="text" size="x-small" @click="dismiss(n.id)">
            <VIcon icon="tabler-x" size="16" />
          </VBtn>
        </div>
        <!-- Barre de progression décroissante (timer) -->
        <div
          v-if="n.timeout"
          class="notif-timer"
          :class="`bg-${n.color}`"
          :style="{ animationDuration: `${n.timeout}ms` }"
        />
      </VCard>
    </TransitionGroup>
  </div>
</template>

<style lang="scss">
.app-notifications {
  position: fixed;
  z-index: 3000;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  inline-size: 360px;
  max-inline-size: calc(100vw - 2rem);
  inset-block-start: 1rem;
  inset-inline-end: 1rem;

  .notif-card {
    overflow: hidden;
    border-radius: 8px;
  }

  .notif-timer {
    block-size: 3px;
    inline-size: 100%;
    animation-name: notif-shrink;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
    transform-origin: inline-start;
  }
}

@keyframes notif-shrink {
  from { transform: scaleX(1); }
  to { transform: scaleX(0); }
}

// Animations d'entrée / sortie de la pile
.notif-enter-active,
.notif-leave-active {
  transition: all 0.35s ease;
}

.notif-enter-from {
  opacity: 0;
  transform: translateX(120%);
}

.notif-leave-to {
  opacity: 0;
  transform: translateX(120%);
}

.notif-move {
  transition: transform 0.35s ease;
}
</style>
