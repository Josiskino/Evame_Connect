export default [
  { heading: 'UI Elements' },
  {
    title: 'Typography',
    icon: { icon: 'tabler-typography' },
    to: 'template-pages-typography',
  },
  {
    title: 'Icons',
    icon: { icon: 'tabler-brand-tabler' },
    to: 'template-pages-icons',
  },
  {
    title: 'Cards',
    icon: { icon: 'tabler-id' },
    children: [
      { title: 'Basic', to: 'template-pages-cards-card-basic' },
      { title: 'Advance', to: 'template-pages-cards-card-advance' },
      { title: 'Statistics', to: 'template-pages-cards-card-statistics' },
      { title: 'Widgets', to: 'template-pages-cards-card-widgets' },
      { title: 'Actions', to: 'template-pages-cards-card-actions' },
    ],
  },
  {
    title: 'Components',
    icon: { icon: 'tabler-atom' },
    children: [
      { title: 'Alert', to: 'template-components-alert' },
      { title: 'Avatar', to: 'template-components-avatar' },
      { title: 'Badge', to: 'template-components-badge' },
      { title: 'Button', to: 'template-components-button' },
      { title: 'Chip', to: 'template-components-chip' },
      { title: 'Dialog', to: 'template-components-dialog' },
      { title: 'Expansion Panel', to: 'template-components-expansion-panel' },
      { title: 'List', to: 'template-components-list' },
      { title: 'Menu', to: 'template-components-menu' },
      { title: 'Pagination', to: 'template-components-pagination' },
      { title: 'Progress Circular', to: 'template-components-progress-circular' },
      { title: 'Progress Linear', to: 'template-components-progress-linear' },
      { title: 'Snackbar', to: 'template-components-snackbar' },
      { title: 'Tabs', to: 'template-components-tabs' },
      { title: 'Timeline', to: 'template-components-timeline' },
      { title: 'Tooltip', to: 'template-components-tooltip' },
    ],
  },
  {
    title: 'Extensions',
    icon: { icon: 'tabler-box' },
    children: [
      { title: 'Tour', to: 'template-extensions-tour' },
      { title: 'Swiper', to: 'template-extensions-swiper' },
    ],
  },
]
