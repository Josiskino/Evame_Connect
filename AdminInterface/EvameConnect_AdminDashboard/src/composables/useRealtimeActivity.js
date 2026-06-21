import { ref } from 'vue'
import { getEcho } from '@/utils/echo'

// Dernier évènement d'activité reçu (les écrans le surveillent pour se rafraîchir)
const lastActivity = ref(null)
let subscribed = false

const ACTION_LABEL = {
  created: 'ajouté(e)',
  updated: 'modifié(e)',
  deleted: 'supprimé(e)',
}

const RESOURCE_LABEL = {
  client: 'Client',
  vente: 'Vente',
  leasing: 'Contrat leasing',
  paiement: 'Paiement',
  intervention: 'Intervention',
}

/**
 * Abonnement temps réel à l'activité partagée (canal `evame.activity`).
 * Quand un AUTRE utilisateur crée/modifie/supprime une ressource :
 *  - une notification apparaît (haut-droite, avec timer) ;
 *  - `lastActivity` est mis à jour -> les listes concernées se rafraîchissent.
 * L'auteur de l'action est ignoré (il a déjà son retour local).
 */
export const useRealtimeActivity = () => {
  const { notify } = useNotifications()
  const userData = useCookie('userData')

  const subscribe = () => {
    if (subscribed)
      return

    getEcho().private('evame.activity')
      .listen('.resource.changed', payload => {
        // Ignore mes propres actions (déjà traitées localement)
        if (payload?.by?.id && Number(payload.by.id) === Number(userData.value?.id))
          return

        const resLabel = RESOURCE_LABEL[payload.resource] ?? payload.resource
        const actLabel = ACTION_LABEL[payload.action] ?? payload.action
        const auteur = payload?.by?.name ? ` par ${payload.by.name}` : ''

        notify({
          color: payload.action === 'deleted' ? 'warning' : 'info',
          title: `${resLabel} ${actLabel}`,
          message: `${payload.label}${auteur}`,
        })

        // Notifie les écrans pour rafraîchissement
        lastActivity.value = { ...payload, ts: Date.now() }
      })

    subscribed = true
  }

  return { subscribe, lastActivity }
}
