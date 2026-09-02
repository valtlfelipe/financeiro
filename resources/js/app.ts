import { createInertiaApp } from '@inertiajs/vue3';
import type { App as VueApp } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { formatPageTitle } from '@/lib/product';
import { createAppI18n, type LocaleCode } from '@/i18n';
import '@/pwa';

void createInertiaApp({
    title: formatPageTitle,
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/') ||
                name === 'Setup' ||
                name.startsWith('Invitations/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#148A62',
    },
    withApp(app: VueApp, { page }) {
        const locale = (page.props.locale ?? 'pt-BR') as LocaleCode;

        app.use(createAppI18n(locale));
    },
});

// This will listen for flash toast data from the server...
initializeFlashToast();
