import { createInertiaApp } from '@inertiajs/vue3';
import type { App as VueApp } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { formatPageTitle } from '@/lib/product';
import {
    initializeWorkspaceContext,
    workspaceHeaders,
} from '@/lib/workspaceContext';
import { createAppI18n, type LocaleCode } from '@/i18n';
import '@/pwa';

initializeTheme();

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
    defaults: {
        visitOptions: (_href, options) => ({
            headers: {
                ...options.headers,
                ...workspaceHeaders(),
            },
        }),
    },
    withApp(app: VueApp, { page }) {
        const locale = (page.props.locale ?? 'pt-BR') as LocaleCode;

        app.use(createAppI18n(locale));
        initializeWorkspaceContext(page);
    },
});

// This will listen for flash toast data from the server...
initializeFlashToast();
