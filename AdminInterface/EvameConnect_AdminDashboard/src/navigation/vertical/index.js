// Navigation latérale (drawer) — EVAME CONNECT
// Chaque entrée porte action/subject (CASL) : le drawer se filtre selon les
// droits de l'utilisateur (abilities renvoyées par l'API). Les subjects sont
// alignés sur les permissions backend (view.dashboard -> subject 'dashboard', etc.).
export default [
  {
    title: 'Tableau de bord',
    icon: { icon: 'tabler-layout-dashboard' },
    to: 'dashboard',
    action: 'read',
    subject: 'dashboard',
  },
  {
    title: 'Stats commerciales',
    icon: { icon: 'tabler-chart-line' },
    to: 'stats-commercial',
    action: 'read',
    subject: 'dashboard',
  },
  {
    title: 'Stats SAV',
    icon: { icon: 'tabler-chart-donut' },
    to: 'stats-sav',
    action: 'read',
    subject: 'dashboard',
  },

  { heading: 'Commercial' },
  {
    title: 'Catalogue motos',
    icon: { icon: 'tabler-motorbike' },
    to: 'motos',
    action: 'read',
    subject: 'catalogue',
  },
  {
    title: 'Ventes',
    icon: { icon: 'tabler-shopping-cart' },
    to: 'ventes',
    action: 'read',
    subject: 'ventes',
  },
  {
    title: 'Clients',
    icon: { icon: 'tabler-users' },
    to: 'clients',
    action: 'read',
    subject: 'clients',
  },

  { heading: 'Leasing' },
  {
    title: 'Contrats leasing',
    icon: { icon: 'tabler-file-dollar' },
    to: 'leasing',
    action: 'read',
    subject: 'leasing',
  },

  { heading: 'Service après-vente' },
  {
    title: 'Interventions',
    icon: { icon: 'tabler-tool' },
    to: 'interventions',
    action: 'read',
    subject: 'interventions',
  },

  { heading: 'Administration' },
  {
    title: 'Utilisateurs',
    icon: { icon: 'tabler-user-cog' },
    to: 'users',
    action: 'read',
    subject: 'admin',
  },
  {
    title: 'Rôles & permissions',
    icon: { icon: 'tabler-shield-lock' },
    to: 'roles',
    action: 'read',
    subject: 'admin',
  },
]
