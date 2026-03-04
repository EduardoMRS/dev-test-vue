import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme } from './composables/useAppearance';

import InstallGuideIOS from './components/InstallGuideIOS.vue';
import { Toaster } from 'vue-sonner'
import Notify from '@/utils/notify'
import { initPWAInstaller } from './utils/installPWAHelper'
import i18n from '@/plugins/i18n'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.component('InstallGuideIOS', InstallGuideIOS);
        app.component('Toaster', Toaster);
        // register notify plugin correctly and initialize PWA installer
        app.use(Notify)
        app.use(i18n)
        initPWAInstaller()
        app.use(plugin).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
