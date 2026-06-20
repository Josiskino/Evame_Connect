export default [
  {
    title: 'Apps',
    icon: { icon: 'tabler-layout-grid-add' },
    children: [
      {
        title: 'Ecommerce',
        icon: { icon: 'tabler-shopping-cart-plus' },
        children: [
          {
            title: 'Dashboard',
            to: 'template-apps-ecommerce-dashboard',
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
        icon: { icon: 'tabler-book' },
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
        icon: { icon: 'tabler-message-circle' },
        to: 'template-apps-chat',
      },
      {
        title: 'Calendar',
        to: 'template-apps-calendar',
        icon: { icon: 'tabler-calendar' },
      },
      {
        title: 'Kanban',
        icon: { icon: 'tabler-layout-kanban' },
        to: 'template-apps-kanban',
      },
      {
        title: 'Invoice',
        icon: { icon: 'tabler-file-dollar' },
        children: [
           { title: 'List', to: 'template-apps-invoice-list' },
           { title: 'Preview', to: { name: 'template-apps-invoice-preview-id', params: { id: '5036' } } },
           { title: 'Edit', to: { name: 'template-apps-invoice-edit-id', params: { id: '5036' } } },
           { title: 'Add', to: 'template-apps-invoice-add' },
        ],
      },
      {
        title: 'User',
        icon: { icon: 'tabler-users' },
        children: [
          { title: 'List', to: 'template-apps-user-list' },
          { title: 'View', to: { name: 'template-apps-user-view-id', params: { id: 21 } } },
        ],
      },
      {
        title: 'Roles & Permissions',
        icon: { icon: 'tabler-settings' },
        children: [
          { title: 'Roles', to: 'template-apps-roles' },
          { title: 'Permissions', to: 'template-apps-permissions' },
        ],
      },
    ],
  },
]
