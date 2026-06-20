export const db = {
  searchItems: [
    {
      title: 'Dashboards',
      category: 'dashboards',
      children: [
        {
          url: { name: 'template-dashboards-analytics' },
          icon: 'tabler-timeline',
          title: 'Analytics Dashboard',
        },
        {
          url: { name: 'template-dashboards-crm' },
          icon: 'tabler-file-analytics',
          title: 'CRM Dashboard',
        },
        {
          url: { name: 'template-dashboards-ecommerce' },
          icon: 'tabler-shopping-cart',
          title: 'ECommerce Dashboard',
        },
        {
          url: { name: 'template-dashboards-academy' },
          icon: 'tabler-book',
          title: 'Academy Dashboard',
        },
        {
          url: { name: 'template-dashboards-logistics' },
          icon: 'tabler-truck',
          title: 'Logistics Dashboard',
        },
      ],
    },
    {
      title: 'Front Pages',
      category: 'frontPages',
      children: [
        {
          url: { name: 'template-front-pages-landing-page' },
          icon: 'tabler-file-description',
          title: 'Landing Front',
        },
        {
          url: { name: 'template-front-pages-pricing' },
          icon: 'tabler-file-description',
          title: 'Pricing Front',
        },
        {
          url: { name: 'template-front-pages-payment' },
          icon: 'tabler-file-description',
          title: 'Payment Front',
        },
        {
          url: { name: 'template-front-pages-checkout' },
          icon: 'tabler-file-description',
          title: 'Checkout Front',
        },
        {
          url: { name: 'template-front-pages-help-center' },
          icon: 'tabler-file-description',
          title: 'Help Center Front',
        },
      ],
    },
    {
      title: 'Apps & Pages',
      category: 'appsPages',
      children: [
        {
          url: { name: 'template-apps-email' },
          icon: 'tabler-mail',
          title: 'Email',
        },
        {
          url: { name: 'template-apps-chat' },
          icon: 'tabler-message',
          title: 'Chat',
        },
        {
          url: { name: 'template-apps-calendar' },
          icon: 'tabler-calendar',
          title: 'Calendar',
        },
        {
          title: 'Kanban',
          icon: 'tabler-layout-kanban',
          url: { name: 'template-apps-kanban' },
        },
        {
          url: { name: 'template-apps-ecommerce-dashboard' },
          icon: 'tabler-shopping-cart',
          title: 'ECommerce Dashboard',
        },
        {
          url: { name: 'template-apps-ecommerce-product-list' },
          icon: 'tabler-list',
          title: 'Ecommerce - Product List',
        },
        {
          url: { name: 'template-apps-ecommerce-product-add' },
          icon: 'tabler-circle-plus',
          title: 'Ecommerce - Add Product',
        },
        {
          url: { name: 'template-apps-ecommerce-product-category-list' },
          icon: 'tabler-list',
          title: 'Ecommerce - Category List',
        },
        {
          url: { name: 'template-apps-ecommerce-order-list' },
          icon: 'tabler-list',
          title: 'Ecommerce - Order List',
        },
        {
          url: { name: 'template-apps-ecommerce-order-details-id', params: { id: '9042' } },
          icon: 'tabler-list-check',
          title: 'Ecommerce - Order Details',
        },
        {
          url: { name: 'template-apps-ecommerce-customer-list' },
          icon: 'tabler-user',
          title: 'Ecommerce - Customer List',
        },
        {
          url: { name: 'template-apps-ecommerce-customer-details-id', params: { id: '478426', tab: 'security' } },
          icon: 'tabler-list',
          title: 'Ecommerce - Customer Details',
        },
        {
          url: { name: 'template-apps-ecommerce-manage-review' },
          icon: 'tabler-quote',
          title: 'Ecommerce - Manage Review',
        },
        {
          url: { name: 'template-apps-ecommerce-referrals' },
          icon: 'tabler-users-group',
          title: 'Ecommerce - Referrals',
        },
        {
          url: { name: 'template-apps-ecommerce-settings' },
          icon: 'tabler-settings',
          title: 'Ecommerce - Settings',
        },
        {
          url: { name: 'template-apps-academy-dashboard' },
          icon: 'tabler-book',
          title: 'Academy - Dashboard',
        },
        {
          url: { name: 'template-apps-academy-my-course' },
          icon: 'tabler-list',
          title: 'Academy - My Courses',
        },
        {
          url: { name: 'template-apps-academy-course-details' },
          icon: 'tabler-list',
          title: 'Academy - Course Details',
        },
        {
          url: { name: 'template-apps-logistics-dashboard' },
          icon: 'tabler-book',
          title: 'Logistics - Dashboard',
        },
        {
          url: { name: 'template-apps-logistics-fleet' },
          icon: 'tabler-car',
          title: 'Logistics - fleet',
        },
        {
          url: { name: 'template-apps-invoice-list' },
          icon: 'tabler-list',
          title: 'Invoice List',
        },
        {
          url: { name: 'template-apps-invoice-preview-id', params: { id: '5036' } },
          icon: 'tabler-file-description',
          title: 'Invoice Preview',
        },
        {
          url: { name: 'template-apps-invoice-edit-id', params: { id: '5036' } },
          icon: 'tabler-file-pencil',
          title: 'Invoice Edit',
        },
        {
            url: { name: 'template-apps-invoice-add' },
          icon: 'tabler-file-plus',
          title: 'Invoice Add',
        },
        {
          url: { name: 'template-apps-user-list' },
          icon: 'tabler-users-group',
          title: 'User List',
        },
        {
          url: { name: 'template-apps-user-view-id', params: { id: 21 } },
          icon: 'tabler-eye',
          title: 'User View',
        },
        {
          url: { name: 'template-pages-user-profile-tab', params: { tab: 'profile' } },
          icon: 'tabler-user-circle',
          title: 'User Profile - Profile',
        },
        {
          url: { name: 'template-pages-account-settings-tab', params: { tab: 'account' } },
          icon: 'tabler-user-circle',
          title: 'Account Settings - Account',
        },
        {
          url: { name: 'template-pages-account-settings-tab', params: { tab: 'security' } },
          icon: 'tabler-lock-open',
          title: 'Account Settings - Security',
        },
        {
          url: { name: 'template-pages-account-settings-tab', params: { tab: 'billing-plans' } },
          icon: 'tabler-currency-dollar',
          title: 'Account Settings - Billing',
        },
        {
          url: { name: 'template-pages-account-settings-tab', params: { tab: 'notification' } },
          icon: 'tabler-bell',
          title: 'Account Settings - Notifications',
        },
        {
          url: { name: 'template-pages-account-settings-tab', params: { tab: 'connection' } },
          icon: 'tabler-link',
          title: 'Account Settings - Connections',
        },
        {
          url: { name: 'template-pages-pricing' },
          icon: 'tabler-currency-dollar',
          title: 'Pricing',
        },
        {
          url: { name: 'template-pages-faq' },
          icon: 'tabler-help-circle',
          title: 'FAQ',
        },
        {
          url: { name: 'template-pages-misc-coming-soon' },
          icon: 'tabler-clock',
          title: 'Coming Soon',
        },
        {
          url: { name: 'template-pages-misc-under-maintenance' },
          icon: 'tabler-settings',
          title: 'Under Maintenance',
        },
        {
          url: { path: '/pages/misc/page-not-found' },
          icon: 'tabler-alert-circle',
          title: 'Page Not Found - 404',
        },
        {
          url: { path: '/pages/misc/not-authorized' },
          icon: 'tabler-user-x',
          title: 'Not Authorized - 401',
        },
        {
          url: { name: 'template-pages-authentication-login-v1' },
          icon: 'tabler-login',
          title: 'Login V1',
        },
        {
          url: { name: 'template-pages-authentication-login-v2' },
          icon: 'tabler-login',
          title: 'Login V2',
        },
        {
          url: { name: 'template-pages-authentication-register-v1' },
          icon: 'tabler-user-plus',
          title: 'Register V1',
        },
        {
          url: { name: 'template-pages-authentication-register-v2' },
          icon: 'tabler-user-plus',
          title: 'Register V2',
        },
        {
          icon: 'tabler-mail',
          title: 'Verify Email V1',
          url: { name: 'template-pages-authentication-verify-email-v1' },
        },
        {
          icon: 'tabler-mail',
          title: 'Verify Email V2',
          url: { name: 'template-pages-authentication-verify-email-v2' },
        },
        {
          url: { name: 'template-pages-authentication-forgot-password-v1' },
          icon: 'tabler-lock-exclamation',
          title: 'Forgot Password V1',
        },
        {
          url: { name: 'template-pages-authentication-forgot-password-v2' },
          icon: 'tabler-lock-exclamation',
          title: 'Forgot Password V2',
        },
        {
          url: { name: 'template-pages-authentication-reset-password-v1' },
          icon: 'tabler-help-circle',
          title: 'Reset Password V1',
        },
        {
          url: { name: 'template-pages-authentication-reset-password-v2' },
          icon: 'tabler-help-circle',
          title: 'Reset Password V2',
        },
        {
          icon: 'tabler-devices',
          title: 'Two Steps V1',
          url: { name: 'template-pages-authentication-two-steps-v1' },
        },
        {
          icon: 'tabler-devices',
          title: 'Two Steps V2',
          url: { name: 'template-pages-authentication-two-steps-v2' },
        },
        {
          url: { name: 'template-pages-dialog-examples' },
          icon: 'tabler-square',
          title: 'Dialog Examples',
        },
        {
          url: { name: 'template-pages-authentication-register-multi-steps' },
          icon: 'tabler-user-plus',
          title: 'Register Multi-Steps',
        },
        {
          url: { name: 'template-wizard-examples-checkout' },
          icon: 'tabler-shopping-cart',
          title: 'Wizard - Checkout',
        },
        {
          url: { name: 'template-wizard-examples-create-deal' },
          icon: 'tabler-gift',
          title: 'Wizard - create deal',
        },
        {
          url: { name: 'template-wizard-examples-property-listing' },
          icon: 'tabler-home',
          title: 'Wizard - Property Listing',
        },
        {
          url: { name: 'template-apps-roles' },
          icon: 'tabler-shield-checkered',
          title: 'Roles',
        },
        {
          url: { name: 'template-apps-permissions' },
          icon: 'tabler-shield-checkered',
          title: 'Permissions',
        },
      ],
    },
    {
      title: 'User Interface',
      category: 'userInterface',
      children: [
        {
          url: { name: 'template-pages-typography' },
          icon: 'tabler-letter-case',
          title: 'Typography',
        },
        {
          url: { name: 'template-pages-icons' },
          icon: 'tabler-icons',
          title: 'Icons',
        },
        {
          url: { name: 'template-pages-cards-card-basic' },
          icon: 'tabler-id',
          title: 'Card Basic',
        },
        {
          url: { name: 'template-pages-cards-card-advance' },
          icon: 'tabler-square-plus',
          title: 'Card Advance',
        },
        {
          url: { name: 'template-pages-cards-card-statistics' },
          icon: 'tabler-chart-bar',
          title: 'Card Statistics',
        },
        {
          url: { name: 'template-pages-cards-card-widgets' },
          icon: 'tabler-chart-bar',
          title: 'Card widgets',
        },
        {
          url: { name: 'template-pages-cards-card-actions' },
          icon: 'tabler-square-plus',
          title: 'Card Actions',
        },
        {
          url: { name: 'template-components-alert' },
          icon: 'tabler-alert-triangle',
          title: 'Alerts',
        },
        {
          url: { name: 'template-components-avatar' },
          icon: 'tabler-user-circle',
          title: 'Avatars',
        },
        {
          url: { name: 'template-components-badge' },
          icon: 'tabler-badge',
          title: 'Badges',
        },
        {
          url: { name: 'template-components-button' },
          icon: 'tabler-circle-plus',
          title: 'Buttons',
        },
        {
          url: { name: 'template-components-chip' },
          icon: 'tabler-square',
          title: 'Chips',
        },
        {
          url: { name: 'template-components-dialog' },
          icon: 'tabler-square',
          title: 'Dialogs',
        },
        {
          url: { name: 'template-components-list' },
          icon: 'tabler-list',
          title: 'List',
        },
        {
          url: { name: 'template-components-menu' },
          icon: 'tabler-menu-2',
          title: 'Menu',
        },
        {
          url: { name: 'template-components-pagination' },
          icon: 'tabler-skip-forward',
          title: 'Pagination',
        },
        {
          url: { name: 'template-components-progress-circular' },
          icon: 'tabler-progress',
          title: 'Progress Circular',
        },
        {
          url: { name: 'template-components-progress-linear' },
          icon: 'tabler-progress',
          title: 'Progress Linear',
        },
        {
          url: { name: 'template-components-expansion-panel' },
          icon: 'tabler-align-center',
          title: 'Expansion Panel',
        },
        {
          url: { name: 'template-components-snackbar' },
          icon: 'tabler-message-dots',
          title: 'Snackbar',
        },
        {
          url: { name: 'template-components-tabs' },
          icon: 'tabler-app-window',
          title: 'Tabs',
        },
        {
          url: { name: 'template-components-timeline' },
          icon: 'tabler-timeline',
          title: 'Timeline',
        },
        {
          url: { name: 'template-components-tooltip' },
          icon: 'tabler-message-2',
          title: 'Tooltip',
        },
        {
          url: { name: 'template-extensions-tour' },
          icon: 'tabler-box',
          title: 'Tour',
        },
        {
          url: { name: 'template-extensions-swiper' },
          icon: 'tabler-photo',
          title: 'Swiper',
        },
      ],
    },
    {
      title: 'Forms & Tables',
      category: 'formsTables',
      children: [
        {
          url: { name: 'template-forms-textfield' },
          icon: 'tabler-text-caption',
          title: 'TextField',
        },
        {
          url: { name: 'template-forms-select' },
          icon: 'tabler-list-check',
          title: 'Select',
        },
        {
          url: { name: 'template-forms-checkbox' },
          icon: 'tabler-square-check',
          title: 'Checkbox',
        },
        {
          url: { name: 'template-forms-radio' },
          icon: 'tabler-circle-dot',
          title: 'Radio',
        },
        {
          url: { name: 'template-forms-combobox' },
          icon: 'tabler-square-check',
          title: 'Combobox',
        },
        {
          url: { name: 'template-forms-date-time-picker' },
          icon: 'tabler-calendar',
          title: 'Date Time picker',
        },
        {
          url: { name: 'template-forms-textarea' },
          icon: 'tabler-notes',
          title: 'Textarea',
        },
        {
          url: { name: 'template-forms-switch' },
          icon: 'tabler-toggle-right',
          title: 'Switch',
        },
        {
          url: { name: 'template-forms-file-input' },
          icon: 'tabler-upload',
          title: 'File Input',
        },
        {
          url: { name: 'template-forms-editors' },
          icon: 'tabler-file-pencil',
          title: 'Editors',
        },
        {
          url: { name: 'template-forms-rating' },
          icon: 'tabler-star',
          title: 'Form Rating',
        },
        {
          url: { name: 'template-forms-slider' },
          icon: 'tabler-hand-move',
          title: 'Slider',
        },
        {
          url: { name: 'template-forms-range-slider' },
          icon: 'tabler-adjustments-horizontal',
          title: 'Range Slider',
        },
        {
          url: { name: 'template-forms-form-layouts' },
          icon: 'tabler-box',
          title: 'Form Layouts',
        },
        {
          url: { name: 'template-forms-form-validation' },
          icon: 'tabler-circle-check',
          title: 'Form Validation',
        },
        {
          url: { name: 'template-forms-custom-input' },
          icon: 'tabler-list-details',
          title: 'Custom Input',
        },
        {
          url: { name: 'template-forms-autocomplete' },
          icon: 'tabler-align-left',
          title: 'Autocomplete',
        },
        {
          url: { name: 'tables-data-table' },
          icon: 'tabler-table',
          title: 'Data Table',
        },
        {
          url: { name: 'tables-simple-table' },
          icon: 'tabler-table',
          title: 'Simple Table',
        },
        {
          url: { name: 'template-forms-form-wizard-numbered' },
          icon: 'tabler-align-center',
          title: 'Form Wizard Numbered',
        },
        {
          url: { name: 'template-forms-form-wizard-icons' },
          icon: 'tabler-align-center',
          title: 'Form Wizard Icons',
        },
      ],
    },
    {
      title: 'Chart & Misc',
      category: 'chartsMisc',
      children: [
        {
          url: { name: 'template-charts-apex-chart' },
          icon: 'tabler-chart-area-line',
          title: 'Apex Charts',
        },
        {
          url: { name: 'template-charts-chartjs' },
          icon: 'tabler-chart-histogram',
          title: 'ChartJS',
        },
        {
          url: { name: 'template-access-control' },
          icon: 'tabler-shield',
          title: 'Access Control (ACL)',
        },
      ],
    },
  ],
}
