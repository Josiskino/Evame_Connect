export default [
  { heading: 'Forms & Tables' },
  {
    title: 'Form Elements',
    icon: { icon: 'tabler-checkbox' },
    children: [
      { title: 'Autocomplete', to: 'template-forms-autocomplete' },
      { title: 'Checkbox', to: 'template-forms-checkbox' },
      { title: 'Combobox', to: 'template-forms-combobox' },
      { title: 'Date Time Picker', to: 'template-forms-date-time-picker' },
      { title: 'Editors', to: 'template-forms-editors' },
      { title: 'File Input', to: 'template-forms-file-input' },
      { title: 'Radio', to: 'template-forms-radio' },
      { title: 'Custom Input', to: 'template-forms-custom-input' },
      { title: 'Range Slider', to: 'template-forms-range-slider' },
      { title: 'Rating', to: 'template-forms-rating' },
      { title: 'Select', to: 'template-forms-select' },
      { title: 'Slider', to: 'template-forms-slider' },
      { title: 'Switch', to: 'template-forms-switch' },
      { title: 'Textarea', to: 'template-forms-textarea' },
      { title: 'Textfield', to: 'template-forms-textfield' },
    ],
  },
  {
    title: 'Form Layouts',
    icon: { icon: 'tabler-layout' },
    to: 'template-forms-form-layouts',
  },
  {
    title: 'Form Wizard',
    icon: { icon: 'tabler-git-merge' },
    children: [
      { title: 'Numbered', to: 'template-forms-form-wizard-numbered' },
      { title: 'Icons', to: 'template-forms-form-wizard-icons' },
    ],
  },
  {
    title: 'Form Validation',
    icon: { icon: 'tabler-checkup-list' },
    to: 'template-forms-form-validation',
  },
  {
    title: 'Tables',
    icon: { icon: 'tabler-table' },
    children: [
      { title: 'Simple Table', to: 'template-tables-simple-table' },
      { title: 'Data Table', to: 'template-tables-data-table' },
    ],
  },
]
