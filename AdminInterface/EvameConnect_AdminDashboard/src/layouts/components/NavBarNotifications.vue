<script setup>
import { useRouter } from 'vue-router'
import { useNotificationsStore } from '@/stores/notifications'

const router = useRouter()
const store = useNotificationsStore()

onMounted(() => {
  store.fetchInitial()
  const userData = useCookie('userData').value
  const userId = userData?.id ?? userData?.user?.id
  if (userId) store.startEchoListener(userId)
})

const onNotificationClick = n => {
  if (!n.isSeen) store.markRead([n.id])
  if (n.cta_url) router.push(n.cta_url)
}
</script>

<template>
  <Notifications
    :notifications="store.notifications"
    @remove="store.remove"
    @read="store.markRead"
    @unread="store.markUnread"
    @click:notification="onNotificationClick"
  />
</template>
