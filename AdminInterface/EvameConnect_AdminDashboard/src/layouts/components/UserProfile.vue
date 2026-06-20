<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const router = useRouter()
const ability = useAbility()

const userData = useCookie('userData')
const avatarRef = ref(null)

const logout = async () => {
  try {
    await $api('/logout', { method: 'POST' })
  }
  catch {}
  useCookie('accessToken').value = null
  userData.value = null
  useCookie('userAbilityRules').value = null
  ability.update([])
  await router.push({ name: 'login' })
}

const userProfileList = [
  { type: 'divider' },
  {
    type: 'navItem',
    icon: 'tabler-user',
    title: 'Profile',
    to: {
      name: 'template-apps-user-view-id',
      params: { id: 21 },
    },
  },
  {
    type: 'navItem',
    icon: 'tabler-settings',
    title: 'Settings',
    to: {
      name: 'template-pages-account-settings-tab',
      params: { tab: 'account' },
    },
  },
]
</script>

<template>
  <div v-if="userData">
    <VAvatar
      ref="avatarRef"
      size="38"
      class="cursor-pointer"
      color="primary"
      variant="tonal"
    >
      <VIcon icon="tabler-user" />
    </VAvatar>

    <VMenu
      :activator="avatarRef"
      width="240"
      location="bottom end"
      offset="12px"
    >
      <VList>
        <VListItem>
          <div class="d-flex gap-2 align-center">
            <VAvatar
              color="primary"
              variant="tonal"
            >
              <VIcon icon="tabler-user" />
            </VAvatar>
            <div>
              <h6 class="text-h6 font-weight-medium">
                {{ userData.full_name }}
              </h6>
              <VListItemSubtitle class="text-disabled">
                {{ userData.email }}
              </VListItemSubtitle>
            </div>
          </div>
        </VListItem>

        <PerfectScrollbar :options="{ wheelPropagation: false }">
          <template
            v-for="item in userProfileList"
            :key="item.title"
          >
            <VListItem
              v-if="item.type === 'navItem'"
              :to="item.to"
            >
              <template #prepend>
                <VIcon
                  :icon="item.icon"
                  size="22"
                />
              </template>
              <VListItemTitle>{{ item.title }}</VListItemTitle>
            </VListItem>

            <VDivider
              v-else
              class="my-2"
            />
          </template>

          <div class="px-4 py-2">
            <VBtn
              block
              size="small"
              color="error"
              append-icon="tabler-logout"
              @click="logout"
            >
              Logout
            </VBtn>
          </div>
        </PerfectScrollbar>
      </VList>
    </VMenu>
  </div>
</template>
