import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import vuetify from './plugins/vuetify';
import '@mdi/font/css/materialdesignicons.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent<DefineComponent>(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(vuetify)
            .mount(el);
    },
    layout: (name: string) => {
        switch (true) {
            // Welcome et Rooms partagent le même nav/footer Vuetify public,
            // désormais centralisés dans PublicLayout.vue (ni l'un ni l'autre
            // ne contient plus son propre <v-app> directement). Toute future
            // page publique du même genre devrait aussi être ajoutée ici.
            case name === 'Welcome':
            case name === 'Rooms':
            case name.startsWith('staff/'):
                return PublicLayout;
            case name === 'auth/Login':
                return null;
            case name === 'auth/Register':
                return null;
            case name === 'Dashboard':
            return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
initializeFlashToast();
