// Navigation latérale (drawer) — EVAME CONNECT
// Aligné sur les modules métier et les rôles (Direction, Commercial, SAV, Admin).
export default [
  {
    title: 'Tableau de bord',
    icon: { icon: 'tabler-layout-dashboard' },
    to: 'dashboard',
  },

  { heading: 'Commercial' },
  {
    title: 'Catalogue motos',
    icon: { icon: 'tabler-motorbike' },
    to: 'motos',
  },
  {
    title: 'Ventes',
    icon: { icon: 'tabler-shopping-cart' },
    to: 'ventes',
  },
  {
    title: 'Clients',
    icon: { icon: 'tabler-users' },
    to: 'clients',
  },

  { heading: 'Leasing' },
  {
    title: 'Contrats leasing',
    icon: { icon: 'tabler-file-dollar' },
    to: 'leasing',
  },

  { heading: 'Service après-vente' },
  {
    title: 'Interventions',
    icon: { icon: 'tabler-tool' },
    to: 'interventions',
  },

  { heading: 'Administration' },
  {
    title: 'Utilisateurs',
    icon: { icon: 'tabler-user-cog' },
    to: 'users',
  },
  {
    title: 'Rôles & permissions',
    icon: { icon: 'tabler-shield-lock' },
    to: 'roles',
  },
]
