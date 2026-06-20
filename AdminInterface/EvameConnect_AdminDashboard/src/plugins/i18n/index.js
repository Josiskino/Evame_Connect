import { createI18n } from 'vue-i18n'
import { en as vuetifyEn, fr as vuetifyFr } from 'vuetify/locale'
import { cookieRef } from '@layouts/stores/config'
import { themeConfig } from '@themeConfig'

const appMessages = Object.fromEntries(Object.entries(import.meta.glob('./locales/*.json', { eager: true }))
  .map(([key, value]) => [key.slice(10, -5), value.default]))

const messages = {
  ...appMessages,
  en: { ...appMessages.en, $vuetify: vuetifyEn },
  fr: { ...appMessages.fr, $vuetify: vuetifyFr },
}

let _i18n = null
export const getI18n = () => {
  if (_i18n === null) {
    _i18n = createI18n({
      legacy: false,
      locale: cookieRef('language', themeConfig.app.i18n.defaultLocale).value,
      fallbackLocale: 'en',
      messages,
    })
  }

  return _i18n
}
export default function (app) {
  app.use(getI18n())
}
