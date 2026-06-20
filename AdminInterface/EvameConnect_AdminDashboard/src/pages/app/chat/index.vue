<script setup>
import { $api } from '@/utils/api'
import { getEcho } from '@/utils/echo'

definePage({ meta: { layout: 'default' } })

const userData = useCookie('userData')
const currentUserId = computed(() => userData.value?.id)

const conversations = ref([])
const messages = ref([])
const selectedUserId = ref(null)
const selectedUserName = ref('')
const newMessage = ref('')
const messagesContainer = ref(null)
const isSending = ref(false)
const isLoadingMessages = ref(false)
let echoChannel = null

const fetchConversations = async () => {
  try {
    const res = await $api('/conversations')
    conversations.value = res.data ?? []
  } catch {
    // silently fail
  }
}

const fetchMessages = async userId => {
  if (!userId) return
  isLoadingMessages.value = true
  try {
    const res = await $api(`/messages?with_user_id=${userId}`)
    messages.value = res.data?.data ?? []
    await nextTick(() => scrollToBottom())
  } catch {
    messages.value = []
  } finally {
    isLoadingMessages.value = false
  }
}

const selectConversation = async (userId, userName) => {
  selectedUserId.value = userId
  selectedUserName.value = userName
  await fetchMessages(userId)
  subscribeToChannel(userId)
}

const subscribeToChannel = userId => {
  if (!currentUserId.value) return

  if (echoChannel) {
    echoChannel.stopListening('.message.sent')
  }

  const ids = [currentUserId.value, userId].sort((a, b) => a - b)
  const channelKey = `conversation.${ids[0]}.${ids[1]}`

  const echo = getEcho()
  echoChannel = echo.private(channelKey)
  echoChannel.listen('.message.sent', event => {
    if (
      (event.sender?.id === selectedUserId.value || event.receiver?.id === selectedUserId.value)
    ) {
      messages.value.push(event)
      nextTick(() => scrollToBottom())
    }
    fetchConversations()
  })
}

const sendMessage = async () => {
  const content = newMessage.value.trim()
  if (!content || !selectedUserId.value || isSending.value) return

  isSending.value = true
  const optimistic = {
    id: Date.now(),
    content,
    sender: { id: currentUserId.value },
    receiver: { id: selectedUserId.value },
    created_at: new Date().toISOString(),
    is_read: false,
    optimistic: true,
  }
  messages.value.push(optimistic)
  newMessage.value = ''
  await nextTick(() => scrollToBottom())

  try {
    const res = await $api('/messages', {
      method: 'POST',
      body: {
        receiver_id: selectedUserId.value,
        content,
      },
    })
    const idx = messages.value.findIndex(m => m.id === optimistic.id)
    if (idx !== -1) messages.value[idx] = res.data
    fetchConversations()
  } catch {
    messages.value = messages.value.filter(m => m.id !== optimistic.id)
  } finally {
    isSending.value = false
  }
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const formatTime = date => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

const formatDate = date => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

const getInterlocuteur = msg => {
  if (!msg) return null
  return msg.sender?.id === currentUserId.value ? msg.receiver : msg.sender
}

const getInitials = name => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const handleKeydown = e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    sendMessage()
  }
}

const isNewConvDialogOpen = ref(false)
const careActors = ref([])
const careActorSearch = ref('')
const isLoadingCareActors = ref(false)

const filteredCareActors = computed(() => {
  if (!careActorSearch.value) return careActors.value
  const q = careActorSearch.value.toLowerCase()
  return careActors.value.filter(u =>
    `${u.first_name} ${u.last_name}`.toLowerCase().includes(q) ||
    u.phone?.toLowerCase().includes(q)
  )
})

const openNewConvDialog = async () => {
  isNewConvDialogOpen.value = true
  isLoadingCareActors.value = true
  try {
    const res = await $api('/users')
    careActors.value = res.data ?? []
  } catch {
    careActors.value = []
  } finally {
    isLoadingCareActors.value = false
  }
}

const startConversation = user => {
  selectConversation(user.id, `${user.first_name} ${user.last_name}`)
  isNewConvDialogOpen.value = false
}

onMounted(() => {
  fetchConversations()
})

onUnmounted(() => {
  if (echoChannel) echoChannel.stopListening('.message.sent')
})
</script>

<template>
  <div>
    <div class="mb-6">
      <h4 class="text-h4 font-weight-bold">
        Messagerie
      </h4>
      <p class="text-medium-emphasis mb-0">
        Communication en temps réel
      </p>
    </div>

    <VCard style="height: calc(100vh - 240px); min-height: 500px;">
      <div class="d-flex" style="height: 100%;">
        <!-- Liste conversations -->
        <div
          class="border-e"
          style="width: 300px; flex-shrink: 0; display: flex; flex-direction: column; overflow: hidden;"
        >
          <div class="pa-3 border-b" style="flex-shrink: 0;">
            <div class="text-subtitle-2 font-weight-bold">
              Conversations
            </div>
          </div>

          <div style="flex: 1; overflow-y: auto; position: relative;">
            <div v-if="!conversations.length" class="text-center text-medium-emphasis pa-6">
              <VIcon icon="tabler-message-off" size="40" class="mb-2 opacity-40 d-block mx-auto" />
              <small>Aucune conversation</small>
            </div>

            <VList
              v-else
              density="compact"
              nav
            >
              <VListItem
                v-for="msg in conversations"
                :key="getInterlocuteur(msg)?.id"
                :active="selectedUserId === getInterlocuteur(msg)?.id"
                rounded="lg"
                class="mb-1"
                @click="selectConversation(getInterlocuteur(msg)?.id, `${getInterlocuteur(msg)?.first_name} ${getInterlocuteur(msg)?.last_name}`)"
              >
                <template #prepend>
                  <VAvatar
                    color="primary"
                    variant="tonal"
                    size="36"
                  >
                    {{ getInitials(`${getInterlocuteur(msg)?.first_name} ${getInterlocuteur(msg)?.last_name}`) }}
                  </VAvatar>
                </template>
                <VListItemTitle class="text-body-2 font-weight-medium">
                  {{ getInterlocuteur(msg)?.first_name }} {{ getInterlocuteur(msg)?.last_name }}
                </VListItemTitle>
                <VListItemSubtitle class="text-truncate text-caption">
                  {{ msg.content }}
                </VListItemSubtitle>
                <template #append>
                  <small class="text-medium-emphasis text-caption">{{ formatDate(msg.created_at) }}</small>
                </template>
              </VListItem>
            </VList>

            <!-- FAB nouvelle conversation -->
            <VTooltip text="Nouvelle conversation" location="top">
              <template #activator="{ props: tooltipProps }">
                <VBtn
                  v-bind="tooltipProps"
                  icon="tabler-plus"
                  color="primary"
                  size="small"
                  style="position: sticky; bottom: 12px; left: calc(100% - 48px); display: block;"
                  @click="openNewConvDialog"
                />
              </template>
            </VTooltip>
          </div>
        </div>

        <!-- Dialogue nouvelle conversation -->
        <VDialog
          v-model="isNewConvDialogOpen"
          max-width="440"
          scrollable
        >
          <VCard>
            <VCardTitle class="pa-4 pb-2 text-subtitle-1 font-weight-bold">
              Nouvelle conversation
            </VCardTitle>

            <div class="px-4 pb-2">
              <VTextField
                v-model="careActorSearch"
                placeholder="Rechercher par nom ou téléphone…"
                prepend-inner-icon="tabler-search"
                density="compact"
                variant="outlined"
                hide-details
                clearable
              />
            </div>

            <VCardText class="pa-0" style="max-height: 380px;">
              <div
                v-if="isLoadingCareActors"
                class="d-flex justify-center align-center pa-6"
              >
                <VProgressCircular indeterminate color="primary" />
              </div>

              <div
                v-else-if="!filteredCareActors.length"
                class="text-center text-medium-emphasis pa-6"
              >
                <VIcon icon="tabler-users-off" size="36" class="mb-2 opacity-40 d-block mx-auto" />
                <small>Aucun contact trouvé</small>
              </div>

              <VList
                v-else
                density="compact"
                nav
              >
                <VListItem
                  v-for="user in filteredCareActors"
                  :key="user.id"
                  rounded="lg"
                  class="mb-1"
                  @click="startConversation(user)"
                >
                  <template #prepend>
                    <VAvatar
                      color="primary"
                      variant="tonal"
                      size="36"
                    >
                      {{ getInitials(`${user.first_name} ${user.last_name}`) }}
                    </VAvatar>
                  </template>
                  <VListItemTitle class="text-body-2 font-weight-medium">
                    {{ user.first_name }} {{ user.last_name }}
                  </VListItemTitle>
                  <VListItemSubtitle class="text-caption">
                    {{ user.phone }}
                  </VListItemSubtitle>
                </VListItem>
              </VList>
            </VCardText>

            <VCardActions class="pa-3 pt-1">
              <VSpacer />
              <VBtn
                variant="text"
                @click="isNewConvDialogOpen = false"
              >
                Annuler
              </VBtn>
            </VCardActions>
          </VCard>
        </VDialog>

        <!-- Zone de messages -->
        <div class="d-flex flex-column flex-grow-1" style="min-width: 0;">
          <!-- Header conversation active -->
          <div
            v-if="selectedUserId"
            class="d-flex align-center gap-3 pa-4 border-b"
          >
            <VAvatar
              color="primary"
              variant="tonal"
              size="40"
            >
              {{ getInitials(selectedUserName) }}
            </VAvatar>
            <div class="font-weight-medium">
              {{ selectedUserName }}
            </div>
          </div>

          <!-- Messages -->
          <div
            ref="messagesContainer"
            class="flex-grow-1 pa-4"
            style="overflow-y: auto;"
          >
            <div
              v-if="!selectedUserId"
              class="d-flex align-center justify-center h-100 text-medium-emphasis"
            >
              <div class="text-center">
                <VIcon icon="tabler-message" size="56" class="mb-3 opacity-30 d-block mx-auto" />
                <div>Sélectionnez une conversation pour commencer</div>
              </div>
            </div>

            <div
              v-else-if="isLoadingMessages"
              class="d-flex align-center justify-center h-100"
            >
              <VProgressCircular indeterminate color="primary" />
            </div>

            <template v-else>
              <div
                v-for="msg in messages"
                :key="msg.id"
                class="mb-3"
                :class="msg.sender?.id === currentUserId ? 'd-flex justify-end' : 'd-flex justify-start'"
              >
                <div
                  :class="[
                    'px-4 py-2 rounded-lg',
                    msg.sender?.id === currentUserId
                      ? 'bg-primary text-white'
                      : 'bg-grey-lighten-4',
                  ]"
                  style="max-width: 60%;"
                >
                  <div class="text-body-2">
                    {{ msg.content }}
                  </div>
                  <div
                    class="text-caption mt-1 opacity-70 text-right"
                  >
                    {{ formatTime(msg.created_at) }}
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Zone de saisie -->
          <div
            v-if="selectedUserId"
            class="pa-3 border-t"
          >
            <div class="d-flex gap-2 align-end">
              <VTextarea
                v-model="newMessage"
                placeholder="Écrire un message... (Entrée pour envoyer)"
                rows="1"
                auto-grow
                max-rows="4"
                density="compact"
                hide-details
                variant="outlined"
                class="flex-grow-1"
                @keydown="handleKeydown"
              />
              <VBtn
                color="primary"
                icon="tabler-send"
                :loading="isSending"
                :disabled="!newMessage.trim()"
                @click="sendMessage"
              />
            </div>
          </div>
        </div>
      </div>
    </VCard>
  </div>
</template>
