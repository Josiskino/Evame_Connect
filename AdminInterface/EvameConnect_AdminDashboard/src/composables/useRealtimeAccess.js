import { getEcho } from '@/utils/echo'

/**
 * Abonnement temps réel aux changements d'accès de l'utilisateur.
 * Écoute le canal privé `user.{id}` (Pusher) et met à jour, EN DIRECT :
 *  - les règles CASL (ability) -> le drawer et les écrans se filtrent aussitôt
 *  - les vues / permissions / rôles stockés dans userData
 *
 * Cas d'usage : le Super Admin retire une vue à un utilisateur -> elle disparaît
 * immédiatement chez lui, sans rechargement.
 */
export const useRealtimeAccess = () => {
  const ability = useAbility()
  const userData = useCookie('userData')

  let subscribedId = null

  const subscribe = () => {
    const user = userData.value
    if (!user?.id || subscribedId)
      return

    const echo = getEcho()

    echo.private(`user.${user.id}`)
      .listen('.access.updated', payload => {
        // 1) Droits CASL à jour -> réactivité immédiate du menu / des écrans
        if (Array.isArray(payload.abilities)) {
          useCookie('userAbilityRules').value = payload.abilities
          ability.update(payload.abilities)
        }

        // 2) Mise à jour de userData (vues, permissions, rôles)
        userData.value = {
          ...userData.value,
          ...(payload.views ? { views: payload.views } : {}),
          ...(payload.permissions ? { permissions: payload.permissions } : {}),
          ...(payload.roles ? { roles: payload.roles } : {}),
        }
      })

    subscribedId = user.id
  }

  const unsubscribe = () => {
    if (subscribedId) {
      getEcho().leave(`user.${subscribedId}`)
      subscribedId = null
    }
  }

  return { subscribe, unsubscribe }
}
