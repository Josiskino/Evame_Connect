<script setup>
definePage({ meta: { layout: 'default' } })

const userData = useCookie('userData')
const ability = useAbility()
const router = useRouter()

const logout = async () => {
  try {
    await $api('/logout', { method: 'POST' })
  }
  catch {
    // ignore
  }
  finally {
    useCookie('userData').value = null
    useCookie('accessToken').value = null
    useCookie('userAbilityRules').value = null
    ability.update([])
    router.push({ name: 'login' })
  }
}

const settingsSections = [
  {
    title: 'Compte',
    icon: 'tabler-user-circle',
    color: 'primary',
    items: [
      { label: 'Nom', value: userData.value?.name ?? '–' },
      { label: 'Email', value: userData.value?.email ?? '–' },
      { label: 'Rôle', value: userData.value?.roles?.join(', ') ?? userData.value?.role ?? '–' },
    ],
  },
  {
    title: 'Application',
    icon: 'tabler-settings',
    color: 'secondary',
    items: [
      { label: 'Version API', value: 'v1' },
      { label: 'Environnement', value: import.meta.env.MODE },
    ],
  },
]
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <h4 class="text-h4 font-weight-bold">
        Paramètres
      </h4>
    </div>

    <VRow>
      <VCol
        v-for="section in settingsSections"
        :key="section.title"
        cols="12"
        md="6"
      >
        <VCard>
          <VCardTitle class="d-flex align-center gap-3 pa-5 pb-3">
            <VAvatar
              :color="section.color"
              variant="tonal"
              size="40"
              rounded
            >
              <VIcon :icon="section.icon" />
            </VAvatar>
            {{ section.title }}
          </VCardTitle>
          <VDivider />
          <VList lines="one">
            <template
              v-for="(item, idx) in section.items"
              :key="item.label"
            >
              <VListItem>
                <template #prepend>
                  <span class="text-body-2 text-medium-emphasis mr-4">{{ item.label }}</span>
                </template>
                <template #title>
                  <span class="font-weight-medium">{{ item.value }}</span>
                </template>
              </VListItem>
              <VDivider v-if="idx < section.items.length - 1" />
            </template>
          </VList>
        </VCard>
      </VCol>

      <VCol cols="12">
        <VCard>
          <VCardTitle class="d-flex align-center gap-3 pa-5 pb-3">
            <VAvatar
              color="error"
              variant="tonal"
              size="40"
              rounded
            >
              <VIcon icon="tabler-logout" />
            </VAvatar>
            Session
          </VCardTitle>
          <VDivider />
          <VCardText>
            <p class="text-body-2 text-medium-emphasis mb-4">
              Vous êtes connecté en tant que <strong>{{ userData?.name }}</strong>.
              Cliquez sur le bouton ci-dessous pour vous déconnecter.
            </p>
            <VBtn
              color="error"
              variant="tonal"
              prepend-icon="tabler-logout"
              @click="logout"
            >
              Se déconnecter
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
