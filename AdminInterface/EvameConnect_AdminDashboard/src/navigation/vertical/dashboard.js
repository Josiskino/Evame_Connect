export default [
  {
    title: 'Dashboards',
    icon: { icon: 'tabler-smart-home' },
    children: [
      {
        title: 'Analytics',
        to: 'template-dashboards-analytics',
      },
      {
        title: 'CRM',
        to: 'template-dashboards-crm',
      },
      {
        title: 'Ecommerce',
        to: 'template-dashboards-ecommerce',
      },
      {
        title: 'Academy',
        to: 'template-apps-academy-dashboard',
      },
      {
        title: 'Logistics',
        to: 'template-apps-logistics-dashboard',
      },
    ],
    badgeContent: '5',
    badgeClass: 'bg-error',
  },
  {
    title: 'Shop',
    icon: { icon: 'tabler-shopping-cart' },
    to: 'shop',  // <- Route générée automatiquement
  },
  {
    title: 'Front Pages',
    icon: { icon: 'tabler-files' },
    children: [
      {
        title: 'Landing',
        to: 'template-front-pages-landing-page',
        target: '_blank',
      },
      {
        title: 'Pricing',
        to: 'template-front-pages-pricing',
        target: '_blank',
      },
      {
        title: 'Payment',
        to: 'template-front-pages-payment',
        target: '_blank',
      },
      {
        title: 'Checkout',
        to: 'template-front-pages-checkout',
        target: '_blank',
      },
      {
        title: 'Help Center',
        to: 'template-front-pages-help-center',
        target: '_blank',
      },
    ],
  },
]
