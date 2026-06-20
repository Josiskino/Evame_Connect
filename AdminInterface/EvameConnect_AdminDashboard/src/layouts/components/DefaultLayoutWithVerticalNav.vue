<script setup>
import staticNavItems from '@/navigation/vertical'
import { useSidebarCountsStore } from '@/stores/sidebar-counts'
import { themeConfig } from '@themeConfig'

// Components
import Footer from '@/layouts/components/Footer.vue'
import NavBarNotifications from '@/layouts/components/NavBarNotifications.vue'
import NavSearchBar from '@/layouts/components/NavSearchBar.vue'
import NavbarThemeSwitcher from '@/layouts/components/NavbarThemeSwitcher.vue'
import UserProfile from '@/layouts/components/UserProfile.vue'
import NavBarI18n from '@core/components/I18n.vue'

// @layouts plugin
import { VerticalNavLayout } from '@layouts'
import { useRealtimeAccess } from '@/composables/useRealtimeAccess'

// Temps réel : met à jour droits/menu en direct (retrait de vue par l'admin)
const { subscribe, unsubscribe } = useRealtimeAccess()
onMounted(() => subscribe())
onBeforeUnmount(() => unsubscribe())

const sidebarCounts = useSidebarCountsStore()

const BADGE_BY_ROUTE = {
  quotes: () => sidebarCounts.quotes,
  dossiers: () => sidebarCounts.dossiers,
}

// Inject live counts into the matching nav entries without mutating the
// shared module-level array. Items keep their original shape and just
// gain a reactive badgeContent string when relevant.
const navItems = computed(() => staticNavItems.map(item => {
  if (!item.to || !BADGE_BY_ROUTE[item.to]) return item
  const n = BADGE_BY_ROUTE[item.to]()
  if (!n) return item
  return {
    ...item,
    badgeContent: String(n),
    badgeClass: 'bg-primary',
  }
}))
</script>

<template>
  <VerticalNavLayout :nav-items="navItems"
  >
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">
        <IconBtn
          id="vertical-nav-toggle-btn"
          class="ms-n3 d-lg-none"
          @click="toggleVerticalOverlayNavActive(true)"
        >
          <VIcon
            size="26"
            icon="tabler-menu-2"
          />
        </IconBtn>

        <NavSearchBar class="ms-lg-n3" />

        <VSpacer />

        <NavBarI18n
          v-if="themeConfig.app.i18n.enable && themeConfig.app.i18n.langConfig?.length"
          :languages="themeConfig.app.i18n.langConfig"
        />
        <NavbarThemeSwitcher />
        <NavBarNotifications class="me-1" />
        <UserProfile />
      </div>
    </template>

    <!-- 👉 Pages -->
    <slot />

    <!-- 👉 Footer -->
    <template #footer>
      <Footer />
    </template>
  </VerticalNavLayout>
</template>
