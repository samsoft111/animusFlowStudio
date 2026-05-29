<template>
  <AppLayout :title="t('settings.title')">
    <div class="max-w-3xl space-y-5">

      <!-- Tab bar -->
      <div class="flex flex-wrap gap-1 bg-muted p-1 rounded-xl w-fit">
        <button v-for="tab in tabs" :key="tab.id"
          @click="activeTab = tab.id"
          class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors"
          :class="activeTab === tab.id
            ? 'bg-card text-foreground shadow-sm'
            : 'text-muted-foreground hover:text-foreground'">
          {{ tab.icon }} {{ tab.label }}
        </button>
      </div>

      <form @submit.prevent="save">

        <!-- ══════════════════════════════════════
             TAB: Studio
        ══════════════════════════════════════ -->
        <div v-show="activeTab === 'studio'" class="space-y-5">
          <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-foreground flex items-center gap-2">
              🏢 {{ t('settings.studio_identity') }}
            </h2>
            <p class="text-xs text-muted-foreground -mt-2">{{ t('settings.studio_identity_hint') }}</p>

            <div>
              <label class="field-label">{{ t('settings.studio_name') }}</label>
              <input v-model="form.studio_name" placeholder="AnimusFlowStudio" class="field-input" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="field-label">{{ t('settings.studio_author') }}</label>
                <input v-model="form.studio_author" placeholder="Your name" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ t('settings.studio_author_email') }}</label>
                <input v-model="form.studio_author_email" type="email" placeholder="you@example.com" class="field-input" />
              </div>
            </div>
            <div>
              <label class="field-label">{{ t('settings.studio_author_url') }}</label>
              <input v-model="form.studio_author_url" placeholder="https://yourwebsite.com" class="field-input" />
              <p class="field-hint">{{ t('settings.studio_author_url_hint') }}</p>
            </div>
          </div>
        </div>

        <!-- ══════════════════════════════════════
             TAB: IA
        ══════════════════════════════════════ -->
        <div v-show="activeTab === 'ai'" class="space-y-5">

          <!-- Provider & Key -->
          <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="font-semibold text-foreground flex items-center gap-2">🤖 {{ t('settings.ai_provider') }}</h2>
              <span class="flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                :class="props.settings.has_ai_key ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'">
                <span class="w-1.5 h-1.5 rounded-full" :class="props.settings.has_ai_key ? 'bg-success' : 'bg-warning'"></span>
                {{ props.settings.has_ai_key ? t('settings.key_configured') : t('settings.key_missing') }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="field-label">{{ t('settings.provider') }}</label>
                <select v-model="form.ai_provider" class="field-input">
                  <option value="claude">Claude (Anthropic)</option>
                  <option value="openai">OpenAI</option>
                  <option value="gemini">Google Gemini</option>
                </select>
              </div>
              <div>
                <label class="field-label">{{ t('settings.model') }}</label>
                <input v-model="form.ai_model" :placeholder="modelPlaceholder" class="field-input" />
              </div>
            </div>

            <div>
              <label class="field-label">
                {{ t('settings.api_key') }}
                <span class="text-muted-foreground font-normal normal-case tracking-normal ml-1">
                  ({{ props.settings.has_ai_key ? t('settings.key_update_hint') : t('settings.key_set_hint') }})
                </span>
              </label>
              <input v-model="form.ai_api_key" type="password"
                :placeholder="props.settings.has_ai_key ? '••••••••••••••••' : 'sk-... / sk-ant-...'"
                class="field-input" />
            </div>
          </div>

          <!-- Behaviour -->
          <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-foreground">{{ t('settings.ai_behaviour') }}</h2>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="field-label">
                  {{ t('settings.ai_temperature') }}
                  <span class="ml-1 text-primary font-mono font-bold">{{ form.ai_temperature }}</span>
                </label>
                <input v-model="form.ai_temperature" type="range" min="0" max="1" step="0.05"
                  class="w-full accent-primary mt-1" />
                <div class="flex justify-between text-[10px] text-muted-foreground mt-0.5">
                  <span>{{ t('settings.temperature_precise') }}</span>
                  <span>{{ t('settings.temperature_creative') }}</span>
                </div>
              </div>
              <div>
                <label class="field-label">{{ t('settings.ai_max_tokens') }}</label>
                <select v-model="form.ai_max_tokens" class="field-input">
                  <option value="1024">1 024 — Rápido</option>
                  <option value="2048">2 048 — Normal</option>
                  <option value="4096">4 096 — Detalhado (recomendado)</option>
                  <option value="8192">8 192 — Muito longo</option>
                  <option value="16000">16 000 — Máximo</option>
                </select>
              </div>
            </div>

            <div>
              <label class="field-label">{{ t('settings.ai_custom_instructions') }}</label>
              <textarea v-model="form.ai_custom_instructions" rows="4"
                :placeholder="t('settings.ai_custom_instructions_placeholder')"
                class="field-input resize-none" />
              <p class="field-hint">{{ t('settings.ai_custom_instructions_hint') }}</p>
            </div>
          </div>
        </div>

        <!-- ══════════════════════════════════════
             TAB: Defaults de Tema
        ══════════════════════════════════════ -->
        <div v-show="activeTab === 'theme'" class="space-y-5">

          <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-foreground flex items-center gap-2">🎨 {{ t('settings.theme_defaults') }}</h2>
            <p class="text-xs text-muted-foreground -mt-2">{{ t('settings.theme_defaults_hint') }}</p>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="field-label">{{ t('settings.theme_default_primary') }}</label>
                <div class="flex gap-2 items-center">
                  <input type="color" v-model="form.theme_default_primary"
                    class="w-10 h-9 rounded-lg border border-border cursor-pointer bg-transparent p-0.5" />
                  <input v-model="form.theme_default_primary" placeholder="#6366f1"
                    class="flex-1 px-3 py-2 bg-muted border border-border rounded-lg text-sm font-mono focus:outline-none focus:border-primary" />
                </div>
              </div>
              <div>
                <label class="field-label">{{ t('settings.theme_default_font_heading') }}</label>
                <select v-model="form.theme_default_font_heading" class="field-input">
                  <option value="">System default</option>
                  <option v-for="f in googleFonts" :key="f" :value="f">{{ f }}</option>
                </select>
              </div>
              <div>
                <label class="field-label">{{ t('settings.theme_default_font_body') }}</label>
                <select v-model="form.theme_default_font_body" class="field-input">
                  <option value="">System default</option>
                  <option v-for="f in googleFonts" :key="f" :value="f">{{ f }}</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="field-label">{{ t('settings.theme_default_version') }}</label>
                <input v-model="form.theme_default_version" placeholder="1.0.0" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ t('settings.theme_dark_mode') }}</label>
                <select v-model="form.theme_dark_mode" class="field-input">
                  <option value="always">Sempre (light + dark)</option>
                  <option value="optional">Opcional (só light)</option>
                  <option value="none">Não gerar dark mode</option>
                </select>
              </div>
              <div>
                <label class="field-label">{{ t('settings.theme_border_radius') }}</label>
                <select v-model="form.theme_border_radius" class="field-input">
                  <option value="sharp">Sharp (sem arredondamento)</option>
                  <option value="normal">Normal (padrão)</option>
                  <option value="rounded">Rounded (muito arredondado)</option>
                </select>
              </div>
            </div>

            <div>
              <label class="field-label">{{ t('settings.theme_default_sections') }}</label>
              <div class="flex flex-wrap gap-2 mt-1.5">
                <label v-for="sec in allSectionTypes" :key="sec"
                  class="flex items-center gap-1.5 px-3 py-1.5 bg-muted border rounded-lg cursor-pointer text-xs font-mono transition-colors"
                  :class="selectedSections.includes(sec)
                    ? 'border-primary bg-primary/10 text-primary'
                    : 'border-border text-muted-foreground hover:border-primary/50'">
                  <input type="checkbox" :value="sec" v-model="selectedSections" class="hidden" />
                  {{ sec }}
                </label>
              </div>
              <p class="field-hint">{{ t('settings.theme_default_sections_hint') }}</p>
            </div>
          </div>

          <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-foreground">{{ t('settings.theme_animusflow_path') }}</h2>
            <div>
              <label class="field-label">{{ t('settings.theme_animusflow_path_label') }}</label>
              <input v-model="form.theme_animusflow_path" placeholder="../animusFlow/core" class="field-input font-mono text-xs" />
              <p class="field-hint">{{ t('settings.theme_animusflow_path_hint') }}</p>
            </div>
          </div>
        </div>

        <!-- ══════════════════════════════════════
             TAB: Defaults de Plugin
        ══════════════════════════════════════ -->
        <div v-show="activeTab === 'plugin'" class="space-y-5">
          <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-foreground flex items-center gap-2">🔌 {{ t('settings.plugin_defaults') }}</h2>
            <p class="text-xs text-muted-foreground -mt-2">{{ t('settings.plugin_defaults_hint') }}</p>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="field-label">{{ t('settings.plugin_default_version') }}</label>
                <input v-model="form.plugin_default_version" placeholder="1.0.0" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ t('settings.plugin_namespace') }}</label>
                <input v-model="form.plugin_namespace" placeholder="MyStudio\\Plugins" class="field-input font-mono text-xs" />
                <p class="field-hint">{{ t('settings.plugin_namespace_hint') }}</p>
              </div>
            </div>

            <div>
              <label class="field-label">{{ t('settings.plugin_default_hooks') }}</label>
              <div class="space-y-2 mt-1.5">
                <label v-for="h in availableHooks" :key="h"
                  class="flex items-start gap-3 p-3 bg-muted rounded-xl cursor-pointer hover:bg-border/50 transition-colors">
                  <input type="checkbox" :value="h" v-model="selectedHooks" class="mt-0.5" />
                  <div>
                    <span class="text-sm font-mono font-semibold text-foreground">{{ h }}</span>
                    <p class="text-xs text-muted-foreground mt-0.5">{{ hookDescriptions[h] }}</p>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- ══════════════════════════════════════
             TAB: Marketplace
        ══════════════════════════════════════ -->
        <div v-show="activeTab === 'marketplace'" class="space-y-5">
          <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="font-semibold text-foreground flex items-center gap-2">🛒 {{ t('settings.marketplace') }}</h2>
              <span class="flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                :class="props.settings.has_animusflow_api_key ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'">
                <span class="w-1.5 h-1.5 rounded-full"
                  :class="props.settings.has_animusflow_api_key ? 'bg-success' : 'bg-warning'"></span>
                {{ props.settings.has_animusflow_api_key ? t('settings.key_configured') : t('settings.key_missing') }}
              </span>
            </div>

            <div>
              <label class="field-label">{{ t('settings.animus_api_url') }}</label>
              <input v-model="form.animus_api_url" placeholder="https://animus.kwantoe.com" class="field-input" />
            </div>

            <div>
              <label class="field-label">
                {{ t('settings.animusflow_api_key') }}
                <span class="text-muted-foreground font-normal normal-case tracking-normal ml-1">
                  ({{ props.settings.has_animusflow_api_key ? t('settings.key_update_hint') : t('settings.key_set_hint') }})
                </span>
              </label>
              <input v-model="form.animusflow_api_key" type="password"
                :placeholder="props.settings.has_animusflow_api_key ? '••••••••••••••••' : 'af_...'"
                class="field-input" />
              <p class="field-hint">{{ t('settings.animusflow_api_key_hint') }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="field-label">{{ t('settings.marketplace_publisher_name') }}</label>
                <input v-model="form.marketplace_publisher_name" placeholder="Your Studio Name" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ t('settings.marketplace_publisher_url') }}</label>
                <input v-model="form.marketplace_publisher_url" placeholder="https://..." class="field-input" />
              </div>
            </div>

            <label class="flex items-center gap-3 p-3 bg-muted rounded-xl cursor-pointer hover:bg-border/50 transition-colors">
              <div class="relative">
                <input type="checkbox" v-model="form.marketplace_auto_publish" :true-value="'1'" :false-value="'0'" class="sr-only" />
                <div class="w-10 h-5 rounded-full transition-colors"
                  :class="form.marketplace_auto_publish === '1' ? 'bg-primary' : 'bg-border'">
                  <div class="w-4 h-4 bg-white rounded-full shadow absolute top-0.5 transition-transform"
                    :class="form.marketplace_auto_publish === '1' ? 'translate-x-5' : 'translate-x-0.5'"></div>
                </div>
              </div>
              <div>
                <p class="text-sm font-semibold text-foreground">{{ t('settings.marketplace_auto_publish') }}</p>
                <p class="text-xs text-muted-foreground">{{ t('settings.marketplace_auto_publish_hint') }}</p>
              </div>
            </label>
          </div>
        </div>

        <!-- ══════════════════════════════════════
             TAB: Exportação
        ══════════════════════════════════════ -->
        <div v-show="activeTab === 'export'" class="space-y-5">
          <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-foreground flex items-center gap-2">📦 {{ t('settings.export_title') }}</h2>
            <p class="text-xs text-muted-foreground -mt-2">{{ t('settings.export_hint') }}</p>

            <div>
              <label class="field-label">{{ t('settings.export_animusflow_min_ver') }}</label>
              <input v-model="form.export_animusflow_min_ver" placeholder="1.0.0" class="field-input" />
              <p class="field-hint">{{ t('settings.export_animusflow_min_ver_hint') }}</p>
            </div>

            <label class="flex items-center gap-3 p-3 bg-muted rounded-xl cursor-pointer hover:bg-border/50 transition-colors">
              <div class="relative">
                <input type="checkbox" v-model="form.export_include_readme" :true-value="'1'" :false-value="'0'" class="sr-only" />
                <div class="w-10 h-5 rounded-full transition-colors"
                  :class="form.export_include_readme === '1' ? 'bg-primary' : 'bg-border'">
                  <div class="w-4 h-4 bg-white rounded-full shadow absolute top-0.5 transition-transform"
                    :class="form.export_include_readme === '1' ? 'translate-x-5' : 'translate-x-0.5'"></div>
                </div>
              </div>
              <div>
                <p class="text-sm font-semibold text-foreground">{{ t('settings.export_include_readme') }}</p>
                <p class="text-xs text-muted-foreground">{{ t('settings.export_include_readme_hint') }}</p>
              </div>
            </label>

            <label class="flex items-center gap-3 p-3 bg-muted rounded-xl cursor-pointer hover:bg-border/50 transition-colors">
              <div class="relative">
                <input type="checkbox" v-model="form.export_minify_html" :true-value="'1'" :false-value="'0'" class="sr-only" />
                <div class="w-10 h-5 rounded-full transition-colors"
                  :class="form.export_minify_html === '1' ? 'bg-primary' : 'bg-border'">
                  <div class="w-4 h-4 bg-white rounded-full shadow absolute top-0.5 transition-transform"
                    :class="form.export_minify_html === '1' ? 'translate-x-5' : 'translate-x-0.5'"></div>
                </div>
              </div>
              <div>
                <p class="text-sm font-semibold text-foreground">{{ t('settings.export_minify_html') }}</p>
                <p class="text-xs text-muted-foreground">{{ t('settings.export_minify_html_hint') }}</p>
              </div>
            </label>
          </div>
        </div>

        <!-- Save button (all tabs) -->
        <button type="submit" :disabled="form.processing"
          class="w-full py-3 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50 transition-opacity flex items-center justify-center gap-2">
          <div v-if="form.processing" class="w-4 h-4 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
          {{ form.processing ? t('common.loading') : t('settings.save') }}
        </button>

      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

const props = defineProps({ settings: { type: Object, default: () => ({}) } });

// ── Tabs ──
const activeTab = ref('studio');
const tabs = [
  { id: 'studio',      icon: '🏢', label: 'Studio'      },
  { id: 'ai',          icon: '🤖', label: 'IA'           },
  { id: 'theme',       icon: '🎨', label: 'Temas'        },
  { id: 'plugin',      icon: '🔌', label: 'Plugins'      },
  { id: 'marketplace', icon: '🛒', label: 'Marketplace'  },
  { id: 'export',      icon: '📦', label: 'Exportação'   },
];

// ── Form ──
const form = useForm({
  // Studio
  studio_name:         props.settings.studio_name         ?? 'AnimusFlowStudio',
  studio_author:       props.settings.studio_author       ?? '',
  studio_author_email: props.settings.studio_author_email ?? '',
  studio_author_url:   props.settings.studio_author_url   ?? '',
  // AI
  ai_provider:             props.settings.ai_provider             ?? 'claude',
  ai_model:                props.settings.ai_model                ?? '',
  ai_api_key:              '',
  ai_temperature:          props.settings.ai_temperature          ?? '0.7',
  ai_max_tokens:           props.settings.ai_max_tokens           ?? '4096',
  ai_custom_instructions:  props.settings.ai_custom_instructions  ?? '',
  // Theme defaults
  theme_default_primary:      props.settings.theme_default_primary      ?? '#6366f1',
  theme_default_font_heading: props.settings.theme_default_font_heading ?? 'Inter',
  theme_default_font_body:    props.settings.theme_default_font_body    ?? 'Inter',
  theme_default_version:      props.settings.theme_default_version      ?? '1.0.0',
  theme_default_sections:     props.settings.theme_default_sections     ?? 'hero,features,cta,testimonials,pricing',
  theme_dark_mode:            props.settings.theme_dark_mode            ?? 'always',
  theme_border_radius:        props.settings.theme_border_radius        ?? 'normal',
  theme_animusflow_path:      props.settings.theme_animusflow_path      ?? '../animusFlow/core',
  // Plugin defaults
  plugin_default_version: props.settings.plugin_default_version ?? '1.0.0',
  plugin_default_hooks:   props.settings.plugin_default_hooks   ?? 'page.render',
  plugin_namespace:       props.settings.plugin_namespace        ?? '',
  // Marketplace
  animus_api_url:              props.settings.animus_api_url              ?? 'https://animus.kwantoe.com',
  animusflow_api_key:          '',
  marketplace_publisher_name:  props.settings.marketplace_publisher_name  ?? '',
  marketplace_publisher_url:   props.settings.marketplace_publisher_url   ?? '',
  marketplace_auto_publish:    props.settings.marketplace_auto_publish     ?? '0',
  // Export
  export_minify_html:        props.settings.export_minify_html        ?? '0',
  export_include_readme:     props.settings.export_include_readme     ?? '1',
  export_animusflow_min_ver: props.settings.export_animusflow_min_ver ?? '1.0.0',
});

function save() { form.put('/settings'); }

// ── AI model placeholder ──
const modelPlaceholder = computed(() => ({
  claude: 'claude-sonnet-4-5',
  openai: 'gpt-4o',
  gemini: 'gemini-1.5-flash',
}[form.ai_provider] ?? ''));

// ── Theme sections multi-select ──
const allSectionTypes = [
  'hero', 'features', 'text', 'cta', 'testimonials', 'pricing',
  'gallery', 'faq', 'contact', 'newsletter', 'columns', 'stats',
  'team', 'steps', 'timeline', 'cards', 'quote', 'banner',
];

const selectedSections = ref(
  (props.settings.theme_default_sections ?? 'hero,features,cta,testimonials,pricing')
    .split(',').map(s => s.trim()).filter(Boolean)
);

watch(selectedSections, val => {
  form.theme_default_sections = val.join(',');
}, { deep: true });

// ── Plugin hooks multi-select ──
const availableHooks = ['page.render', 'content.publish', 'admin.sidebar'];
const hookDescriptions = {
  'page.render':      'HTML injected before </body> on every page',
  'content.publish':  'Fired when a page is published',
  'admin.sidebar':    'Adds a link to the admin sidebar',
};

const selectedHooks = ref(
  (props.settings.plugin_default_hooks ?? 'page.render')
    .split(',').map(h => h.trim()).filter(Boolean)
);

watch(selectedHooks, val => {
  form.plugin_default_hooks = val.join(',');
}, { deep: true });

// ── Google Fonts ──
const googleFonts = [
  'Inter', 'Poppins', 'DM Sans', 'Outfit', 'Plus Jakarta Sans',
  'Sora', 'Nunito', 'Raleway', 'Lato', 'Montserrat',
  'Playfair Display', 'Merriweather', 'Lora', 'Fraunces',
  'Space Grotesk', 'Geist', 'IBM Plex Sans', 'Source Sans 3',
];
</script>

<style scoped>
@reference "../../css/app.css";

.field-label {
  @apply block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5;
}
.field-input {
  @apply w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary;
}
.field-hint {
  @apply text-xs text-muted-foreground mt-1;
}
</style>
