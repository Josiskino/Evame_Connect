<script setup>
import { refDebounced } from '@vueuse/core'

definePage({ meta: { layout: 'default', action: 'read', subject: 'admin' } })

const page = ref(1)
const perPage = ref(15)
const searchRaw = ref('')
const search = refDebounced(searchRaw, 400)

watch([search, perPage], () => { page.value = 1 })

const queryUrl = computed(() => {
  const p = new URLSearchParams()
  p.set('page', String(page.value))
  p.set('per_page', String(perPage.value))
  if (search.value) p.set('search', search.value)

  return `/admin/users?${p.toString()}`
})

const { data, isFetching } = useApi(queryUrl)

const users = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta ?? { last_page: 1, total: 0, from: 0, to: 0 })

const headers = [
  { title: 'Utilisateur', key: 'name' },
  { title: 'E-mail', key: 'email' },
  { title: 'Téléphone', key: 'telephone' },
  { title: 'Rôles', key: 'roles' },
  { title: 'Inscription', key: 'created_at' },
]

const roleLabel = { 'super-admin': 'Super Admin', manager: 'Manager', commercial: 'Commercial', sav: 'Technicien SAV' }
const roleColor = r => ({ 'super-admin': 'error', manager: 'warning', commercial: 'info', sav: 'success' }[r] ?? 'secondary')

const fmtDate = d => (d ? new Intl.DateTimeFormat('fr-FR').format(new Date(d)) : '—')
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-space-between align-center gap-4 mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">Utilisateurs</h4>
        <p class="text-medium-emphasis mb-0">{{ meta.total }} utilisateur(s)</p>
      </div>
    </div>

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="5">
            <AppTextField
              v-model="searchRaw"
              placeholder="Rechercher par nom ou e-mail…"
              prepend-inner-icon="tabler-search"
              clearable
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTable
        :headers="headers"
        :items="users"
        :loading="isFetching"
        hide-default-footer
        :items-per-page="perPage"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-2">
            <VAvatar size="34" color="primary" variant="tonal">
              <span class="text-caption">{{ (item.name || '?').charAt(0).toUpperCase() }}</span>
            </VAvatar>
            <span class="font-weight-medium">{{ item.name }}</span>
          </div>
        </template>
        <template #item.telephone="{ item }">{{ item.telephone || '—' }}</template>
        <template #item.roles="{ item }">
          <div class="d-flex flex-wrap gap-1">
            <VChip v-for="r in item.roles" :key="r" size="small" label :color="roleColor(r)">
              {{ roleLabel[r] || r }}
            </VChip>
            <span v-if="!item.roles?.length" class="text-medium-emphasis">—</span>
          </div>
        </template>
        <template #item.created_at="{ item }">{{ fmtDate(item.created_at) }}</template>
        <template #no-data>
          <div class="text-center text-medium-emphasis py-8">Aucun utilisateur.</div>
        </template>
      </VDataTable>
    </VCard>

    <div v-if="users.length" class="d-flex flex-wrap align-center justify-space-between gap-4 mt-6">
      <span class="text-body-2 text-medium-emphasis">{{ meta.from }}–{{ meta.to }} sur {{ meta.total }}</span>
      <VPagination v-model="page" :length="meta.last_page" :total-visible="5" rounded="circle" />
    </div>
  </div>
</template>
