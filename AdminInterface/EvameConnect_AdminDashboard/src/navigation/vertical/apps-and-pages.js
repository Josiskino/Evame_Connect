export default [
  { heading: 'Apps & Pages' },
  {
    title: 'Ecommerce',
    icon: { icon: 'tabler-shopping-cart' },
    children: [
      {
        title: 'Dashboard',
        to: 'template-dashboards-ecommerce',
      },
      {
        title: 'Product',
        children: [
          { title: 'List', to: 'template-apps-ecommerce-product-list' },
          { title: 'Add', to: 'template-apps-ecommerce-product-add' },
          { title: 'Category', to: 'template-apps-ecommerce-product-category-list' },
        ],
      },
      {
        title: 'Order',
        children: [
          { title: 'List', to: 'template-apps-ecommerce-order-list' },
          { title: 'Details', to: { name: 'template-apps-ecommerce-order-details-id', params: { id: '9042' } } },
        ],
      },
      {
        title: 'Customer',
        children: [
          { title: 'List', to: 'template-apps-ecommerce-customer-list' },
          { title: 'Details', to: { name: 'template-apps-ecommerce-customer-details-id', params: { id: 478426 } } },
        ],
      },
      {
        title: 'Manage Review',
        to: 'template-apps-ecommerce-manage-review',
      },
      {
        title: 'Referrals',
        to: 'template-apps-ecommerce-referrals',
      },
      {
        title: 'Settings',
        to: 'template-apps-ecommerce-settings',
      },
    ],
  },
  {
    title: 'Academy',
    icon: { icon: 'tabler-school' },
    children: [
      { title: 'Dashboard', to: 'template-apps-academy-dashboard' },
      { title: 'My Course', to: 'template-apps-academy-my-course' },
      { title: 'Course Details', to: 'template-apps-academy-course-details' },
    ],
  },
  {
    title: 'Logistics',
    icon: { icon: 'tabler-truck' },
    children: [
      { title: 'Dashboard', to: 'template-apps-logistics-dashboard' },
      { title: 'Fleet', to: 'template-apps-logistics-fleet' },
    ],
  },
  {
    title: 'Email',
    icon: { icon: 'tabler-mail' },
    to: 'template-apps-email',
  },
  {
    title: 'Chat',
    icon: { icon: 'tabler-message-circle-2' },
    to: 'template-apps-chat',
  },
  {
    title: 'Calendar',
    icon: { icon: 'tabler-calendar' },
    to: 'template-apps-calendar',
  },
  {
    title: 'Kanban',
    icon: { icon: 'tabler-layout-kanban' },
    to: 'template-apps-kanban',
  },
  {
    title: 'Invoice',
    icon: { icon: 'tabler-file-invoice' },
    children: [
      { title: 'List', to: 'template-apps-invoice-list' },
      { title: 'Preview', to: { name: 'template-apps-invoice-preview-id', params: { id: '5036' } } },
      { title: 'Edit', to: { name: 'template-apps-invoice-edit-id', params: { id: '5036' } } },
      { title: 'Add', to: 'template-apps-invoice-add' },
    ],
  },
  {
    title: 'User',
    icon: { icon: 'tabler-user' },
    children: [
      { title: 'List', to: 'template-apps-user-list' },
      { title: 'View', to: { name: 'template-apps-user-view-id', params: { id: 21 } } },
    ],
  },
  {
    title: 'Roles & Permissions',
    icon: { icon: 'tabler-lock' },
    children: [
      { title: 'Roles', to: 'template-apps-roles' },
      { title: 'Permissions', to: 'template-apps-permissions' },
    ],
  },
  {
    title: 'Pages',
    icon: { icon: 'tabler-file' },
    children: [
      { title: 'User Profile', to: { name: 'template-pages-user-profile-tab', params: { tab: 'profile' } } },
      { title: 'Account Settings', to: { name: 'template-pages-account-settings-tab', params: { tab: 'account' } } },
      { title: 'Pricing', to: 'template-pages-pricing' },
      { title: 'FAQ', to: 'template-pages-faq' },
      {
        title: 'Miscellaneous',
        children: [
          { title: 'Coming Soon', to: 'template-pages-misc-coming-soon', target: '_blank' },
          { title: 'Under Maintenance', to: 'template-pages-misc-under-maintenance', target: '_blank' },
          { title: 'Page Not Found - 404', to: { path: '/template/pages/misc/not-found' }, target: '_blank' },
          { title: 'Not Authorized - 401', to: { path: '/template/pages/misc/not-authorized' }, target: '_blank' },
        ],
      },
    ],
  },
  {
    title: 'Authentication',
    icon: { icon: 'tabler-shield-lock' },
    children: [
      {
        title: 'Login',
        children: [
          { title: 'Login v1', to: 'template-pages-authentication-login-v1', target: '_blank' },
          { title: 'Login v2', to: 'template-pages-authentication-login-v2', target: '_blank' },
        ],
      },
      {
        title: 'Register',
        children: [
          { title: 'Register v1', to: 'template-pages-authentication-register-v1', target: '_blank' },
          { title: 'Register v2', to: 'template-pages-authentication-register-v2', target: '_blank' },
          { title: 'Register Multi-Steps', to: 'template-pages-authentication-register-multi-steps', target: '_blank' },
        ],
      },
      {
        title: 'Verify Email',
        children: [
          { title: 'Verify Email v1', to: 'template-pages-authentication-verify-email-v1', target: '_blank' },
          { title: 'Verify Email v2', to: 'template-pages-authentication-verify-email-v2', target: '_blank' },
        ],
      },
      {
        title: 'Forgot Password',
        children: [
          { title: 'Forgot Password v1', to: 'template-pages-authentication-forgot-password-v1', target: '_blank' },
          { title: 'Forgot Password v2', to: 'template-pages-authentication-forgot-password-v2', target: '_blank' },
        ],
      },
      {
        title: 'Reset Password',
        children: [
          { title: 'Reset Password v1', to: 'template-pages-authentication-reset-password-v1', target: '_blank' },
          { title: 'Reset Password v2', to: 'template-pages-authentication-reset-password-v2', target: '_blank' },
        ],
      },
      {
        title: 'Two Steps',
        children: [
          { title: 'Two Steps v1', to: 'template-pages-authentication-two-steps-v1', target: '_blank' },
          { title: 'Two Steps v2', to: 'template-pages-authentication-two-steps-v2', target: '_blank' },
        ],
      },
    ],
  },
  {
    title: 'Wizard Examples',
    icon: { icon: 'tabler-dots' },
    children: [
      { title: 'Checkout', to: { name: 'template-wizard-examples-checkout' } },
      { title: 'Property Listing', to: { name: 'template-wizard-examples-property-listing' } },
      { title: 'Create Deal', to: { name: 'template-wizard-examples-create-deal' } },
    ],
  },
  {
    title: 'Dialog Examples',
    icon: { icon: 'tabler-square' },
    to: 'template-pages-dialog-examples',
  },
]
