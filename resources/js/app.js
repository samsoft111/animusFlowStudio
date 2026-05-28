import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
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
/* Default is dark — only override if user explicitly chose light */
const savedTheme = localStorage.getItem('theme') ?? 'dark';
if (savedTheme !== 'light') {
    document.documentElement.setAttribute('data-theme', 'dark');
}

/* ── Sessão expirada (419) → recarrega a página para obter novo CSRF token ── */
router.on('httpException', (event) => {
    if (event.detail?.response?.status === 419) {
        event.preventDefault();
        window.location.reload();
    }
});

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
