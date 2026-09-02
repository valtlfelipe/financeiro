import { createI18n } from 'vue-i18n';
import auth from './i18n/locales/pt-BR/auth.json';
import common from './i18n/locales/pt-BR/common.json';
import finance from './i18n/locales/pt-BR/finance.json';
import settings from './i18n/locales/pt-BR/settings.json';

export type LocaleCode = 'pt-BR';

const messages = {
    'pt-BR': {
        ...auth,
        ...common,
        ...finance,
        ...settings,
    },
};

export function createAppI18n(locale: LocaleCode) {
    return createI18n({
        legacy: false,
        locale,
        fallbackLocale: 'pt-BR',
        messages,
        missingWarn: import.meta.env.DEV,
        fallbackWarn: import.meta.env.DEV,
    });
}
