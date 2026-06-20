<script setup>
import { VForm } from 'vuetify/components/VForm'
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw'
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const isPasswordVisible = ref(false)
const route = useRoute()
const router = useRouter()
const ability = useAbility()

const refVForm = ref()
const credentials = ref({ email: '', password: '' })
const isLoading = ref(false)

// --- Notification animée (haut de l'écran) + barre de chargement dégressive ---
const NOTIF_DURATION = 4000
const notif = ref({ show: false, message: '', color: 'error', progress: 100 })
let notifTimer = null

const notify = (message, color = 'error') => {
  if (notifTimer)
    clearInterval(notifTimer)

  notif.value = { show: true, message, color, progress: 100 }

  const stepMs = 30
  const decrement = 100 / (NOTIF_DURATION / stepMs)

  notifTimer = setInterval(() => {
    notif.value.progress -= decrement
    if (notif.value.progress <= 0) {
      clearInterval(notifTimer)
      notif.value.show = false
    }
  }, stepMs)
}

onBeforeUnmount(() => {
  if (notifTimer)
    clearInterval(notifTimer)
})

const login = async () => {
  isLoading.value = true
  try {
    const res = await $api('/login', {
      method: 'POST',
      body: {
        email: credentials.value.email,
        password: credentials.value.password,
      },
    })

    // Format API uniforme : { status, message, data: { token, user } }
    const token = res?.data?.token
    const user = res?.data?.user
    const rules = user?.abilities ?? []

    useCookie('userAbilityRules').value = rules
    ability.update(rules)
    useCookie('userData').value = user
    useCookie('accessToken').value = token

    notify('Connexion réussie. Redirection…', 'success')

    await nextTick(() => {
      router.replace(route.query.to ? String(route.query.to) : '/dashboard')
    })
  }
  catch (err) {
    const data = err?.response?._data
    let message = data?.message || 'Connexion impossible. Veuillez réessayer.'

    // Erreurs de validation -> premier message
    if (data?.errors) {
      const first = Object.values(data.errors)[0]
      if (Array.isArray(first) && first[0])
        message = first[0]
    }

    notify(message, 'error')
  }
  finally {
    isLoading.value = false
  }
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid)
      login()
  })
}
</script>

<template>
  <!-- 👉 Notification animée en haut de l'écran -->
  <VSnackbar
    v-model="notif.show"
    location="top"
    :timeout="-1"
    :color="notif.color"
    transition="slide-y-reverse-transition"
    class="evame-notif"
  >
    <div class="d-flex align-center pe-2">
      <VIcon
        :icon="notif.color === 'success' ? 'tabler-circle-check' : 'tabler-alert-triangle'"
        size="22"
        class="me-2"
      />
      <span class="font-weight-medium">{{ notif.message }}</span>
    </div>

    <!-- Barre de chargement qui diminue puis fait disparaître la notif -->
    <VProgressLinear
      :model-value="notif.progress"
      color="white"
      height="3"
      bg-opacity="0.25"
      class="evame-notif__progress"
    />
  </VSnackbar>

  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <div class="position-relative my-sm-16">
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1TopShape })"
        class="text-primary auth-v1-top-shape d-none d-sm-block"
      />
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1BottomShape })"
        class="text-primary auth-v1-bottom-shape d-none d-sm-block"
      />

      <VCard
        class="auth-card"
        max-width="460"
        :class="$vuetify.display.smAndUp ? 'pa-6' : 'pa-0'"
      >
        <VCardItem class="justify-center">
          <VCardTitle>
            <RouterLink to="/">
              <div class="d-flex justify-center">
                <img
                  src="/logo-evame.png"
                  alt="Evame Connect"
                  height="84"
                >
              </div>
            </RouterLink>
          </VCardTitle>
        </VCardItem>

        <VCardText>
          <h4 class="text-h4 mb-1">
            Bienvenue sur <span class="text-capitalize">{{ themeConfig.app.title }}</span>
          </h4>
          <p class="mb-0">
            Connectez-vous à votre compte
          </p>
        </VCardText>

        <VCardText>
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.email"
                  autofocus
                  label="Email"
                  type="email"
                  placeholder="admin@evame.com"
                  :rules="[requiredValidator, emailValidator]"
                />
              </VCol>

              <VCol cols="12">
                <AppTextField
                  v-model="credentials.password"
                  label="Mot de passe"
                  placeholder="············"
                  :rules="[requiredValidator]"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />

                <VBtn
                  block
                  type="submit"
                  class="mt-6"
                  :loading="isLoading"
                >
                  Se connecter
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth";

.evame-notif {
  .evame-notif__progress {
    position: absolute;
    inset-block-end: 0;
    inset-inline: 0;
  }
}
</style>
