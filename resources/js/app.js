import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createI18n } from 'vue-i18n';
import pt from './locales/pt.json';
import en from './locales/en.json';

const savedLocale = localStorage.getItem('locale') ?? 'pt';

const i18n = createI18n({
    legacy: false,
    locale: savedLocale,
    fallbackLocale: 'en',
    messages: { pt, en },
});

/* Apply saved theme on page load before Vue mounts (avoids flash) */
const savedTheme = localStorage.getItem('theme') ?? 'light';
if (savedTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
}

createInertiaApp({
    title: (title) => title ? `${title} — AnimusFlowStudio` : 'AnimusFlowStudio',
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')
    ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#6366f1',
    },
});
