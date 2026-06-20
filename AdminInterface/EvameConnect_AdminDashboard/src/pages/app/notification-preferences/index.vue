<script setup>
definePage({ meta: { layout: 'default' } })

const { data: prefsData, isFetching, execute: refresh } = await useApi('/me/notification-preferences')

const channels = computed(() => prefsData.value?.data?.channels ?? [])
const events = computed(() => prefsData.value?.data?.events ?? [])

// matrix.value = { event_id: { channel_id: boolean } }
const matrix = ref({})

watchEffect(() => {
  const m = {}
  for (const e of events.value) {
    m[e.event_id] = {}
    for (const c of e.channels) m[e.event_id][c.channel_id] = !!c.is_subscribed
  }
  matrix.value = m
})

// Group events by category for table grouping
const groupedEvents = computed(() => {
  const groups = new Map()
  for (const e of events.value) {
    const key = e.event_category || 'Autres'
    if (!groups.has(key)) groups.set(key, [])
    groups.get(key).push(e)
  }
  return [...groups.entries()].map(([category, items]) => ({ category, items }))
})

const saving = ref(false)
const snackbar = ref(false)
const snackbarColor = ref('success')
const snackbarText = ref('')

const showSnack = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

const save = async () => {
  saving.value = true
  try {
    const preferences = []
    for (const e of events.value) {
      for (const c of e.channels) {
        preferences.push({
          event_id: e.event_id,
          channel_id: c.channel_id,
          is_subscribed: !!matrix.value[e.event_id]?.[c.channel_id],
        })
      }
    }
    await $api('/me/notification-preferences', { method: 'PUT', body: { preferences } })
    showSnack('Préférences enregistrées')
    refresh()
  }
  catch (err) {
    const msg = err?.response?._data?.message ?? 'Une erreur est survenue lors de l\'enregistrement'
    showSnack(msg, 'error')
  }
  finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-4">
      <h2 class="text-h5 mb-1">
        Préférences de notifications
      </h2>
      <p class="text-body-2 text-medium-emphasis mb-0">
        Choisissez par canal et par événement les notifications que vous souhaitez recevoir.
      </p>
    </div>

    <VAlert
      type="info"
      variant="tonal"
      class="mb-4"
    >
      Par défaut, vous recevez toutes les notifications. Désactivez celles qui ne vous intéressent pas.
    </VAlert>

    <VCard>
      <VCardText class="pa-0">
        <div
          v-if="isFetching"
          class="pa-6 text-center"
        >
          <VProgressCircular
            indeterminate
            color="primary"
          />
        </div>

        <div
          v-else-if="!events.length || !channels.length"
          class="pa-6 text-center text-medium-emphasis"
        >
          Aucun événement ou canal disponible.
        </div>

        <VTable
          v-else
          class="prefs-table"
        >
          <thead>
            <tr>
              <th class="event-col">
                Événement
              </th>
              <th
                v-for="c in channels"
                :key="c.id"
                class="text-center channel-col"
              >
                <div class="font-weight-medium">
                  {{ c.label }}
                </div>
                <div
                  v-if="c.description"
                  class="text-caption text-medium-emphasis"
                >
                  {{ c.description }}
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <template
              v-for="group in groupedEvents"
              :key="group.category"
            >
              <tr class="category-row">
                <td
                  :colspan="channels.length + 1"
                  class="text-uppercase text-caption text-medium-emphasis font-weight-bold py-2"
                >
                  {{ group.category }}
                </td>
              </tr>
              <tr
                v-for="ev in group.items"
                :key="ev.event_id"
              >
                <td class="event-col">
                  <div class="font-weight-medium">
                    {{ ev.event_label }}
                  </div>
                  <div
                    v-if="ev.event_slug"
                    class="text-caption text-medium-emphasis"
                  >
                    {{ ev.event_slug }}
                  </div>
                </td>
                <td
                  v-for="c in channels"
                  :key="c.id"
                  class="text-center channel-col"
                >
                  <VSwitch
                    v-if="matrix[ev.event_id] && c.id in matrix[ev.event_id]"
                    v-model="matrix[ev.event_id][c.id]"
                    color="primary"
                    density="compact"
                    hide-details
                    class="d-inline-flex"
                  />
                  <span
                    v-else
                    class="text-medium-emphasis"
                  >–</span>
                </td>
              </tr>
            </template>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>

    <div class="d-flex justify-end mt-4 sticky-save">
      <VBtn
        color="primary"
        :loading="saving"
        :disabled="isFetching || !events.length"
        prepend-icon="tabler-device-floppy"
        @click="save"
      >
        Enregistrer
      </VBtn>
    </div>

    <VSnackbar
      v-model="snackbar"
      :color="snackbarColor"
      location="bottom right"
      :timeout="3000"
    >
      {{ snackbarText }}
    </VSnackbar>
  </div>
</template>

<style scoped>
.prefs-table .event-col {
  min-inline-size: 240px;
}

.prefs-table .channel-col {
  min-inline-size: 140px;
}

.prefs-table .category-row td {
  background-color: rgba(var(--v-theme-on-surface), 0.04);
}

.sticky-save {
  position: sticky;
  inset-block-end: 16px;
}
</style>
