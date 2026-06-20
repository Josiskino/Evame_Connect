import { defineStore } from 'pinia'
import { $api } from '@/utils/api'

/**
 * État de l'écran « Attestation d'importation » : liste des attestations
 * enregistrées et carnet d'adresses (importateurs / fournisseurs), persistés
 * côté backend et rattachés au compte. L'état du formulaire en cours d'édition
 * reste local à la page éditeur.
 */
export const useAttestationStore = defineStore('attestation', () => {
  const attestations = ref([])
  const contacts = ref([])
  const isLoadingList = ref(false)
  const isLoadingContacts = ref(false)

  const importers = computed(() => contacts.value.filter(c => c.type === 'importer'))
  const suppliers = computed(() => contacts.value.filter(c => c.type === 'supplier'))

  // ─── Attestations ────────────────────────────────────────────────────────
  const fetchList = async () => {
    isLoadingList.value = true
    try {
      const res = await $api('/attestations?per_page=100')

      attestations.value = res?.data ?? []
    }
    catch {
      attestations.value = []
    }
    finally {
      isLoadingList.value = false
    }
  }

  const create = async body => {
    const res = await $api('/attestations', { method: 'POST', body })
    const created = res?.data

    // Mise à jour locale immédiate plutôt qu'un second aller-retour réseau.
    if (created)
      attestations.value.unshift(created)

    return created
  }

  const update = async (id, body) => {
    const res = await $api(`/attestations/${id}`, { method: 'PUT', body })
    const updated = res?.data

    if (updated) {
      const i = attestations.value.findIndex(a => a.id === id)
      if (i !== -1)
        attestations.value[i] = updated
      else
        attestations.value.unshift(updated)
    }

    return updated
  }

  const load = async id => {
    const res = await $api(`/attestations/${id}`)

    return res?.data
  }

  const remove = async id => {
    await $api(`/attestations/${id}`, { method: 'DELETE' })
    attestations.value = attestations.value.filter(a => a.id !== id)
  }

  // ─── Carnet d'adresses ───────────────────────────────────────────────────
  const fetchContacts = async () => {
    isLoadingContacts.value = true
    try {
      const res = await $api('/attestation-contacts')

      contacts.value = res?.data ?? []
    }
    catch {
      contacts.value = []
    }
    finally {
      isLoadingContacts.value = false
    }
  }

  const addContact = async body => {
    const res = await $api('/attestation-contacts', { method: 'POST', body })
    const created = res?.data

    if (created)
      contacts.value.unshift(created)

    return created
  }

  const removeContact = async id => {
    await $api(`/attestation-contacts/${id}`, { method: 'DELETE' })
    contacts.value = contacts.value.filter(c => c.id !== id)
  }

  return {
    attestations,
    contacts,
    importers,
    suppliers,
    isLoadingList,
    isLoadingContacts,
    fetchList,
    create,
    update,
    load,
    remove,
    fetchContacts,
    addContact,
    removeContact,
  }
})
