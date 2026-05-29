<template>
  <AppLayout :title="theme ? `${theme.label}` : t('themes.create_title')">
    <template #actions>
      <template v-if="theme">
        <!-- Preview -->
        <a :href="`/preview/theme/${theme.uuid}`" target="_blank"
          class="px-3 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors flex items-center gap-1.5">
          <EyeIcon class="w-3.5 h-3.5" />
          {{ t('themes.preview') }}
        </a>
        <!-- Export -->
        <a :href="`/themes/${theme.uuid}/export`"
          class="px-3 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors flex items-center gap-1.5">
          <DownloadIcon class="w-3.5 h-3.5" />
          {{ t('common.export') }}
        </a>
        <!-- Publish -->
        <button @click="publishTheme" :disabled="publishing"
          class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5 disabled:opacity-50"
          :class="theme.is_published ? 'bg-success/10 text-success hover:bg-success/20' : 'bg-primary text-primary-foreground hover:opacity-90'">
          <UploadIcon class="w-3.5 h-3.5" />
          {{ publishing ? t('common.loading') : (theme.is_published ? t('themes.republish') : t('themes.publish')) }}
        </button>
      </template>
    </template>

    <!-- Create form -->
    <div v-if="!theme" class="max-w-lg">
      <form @submit.prevent="createTheme" class="bg-card border border-border rounded-2xl p-6 space-y-4">
        <h2 class="font-semibold text-foreground">{{ t('themes.create_title') }}</h2>
        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('themes.slug') }}</label>
          <input v-model="createForm.name" placeholder="e.g. aurora-dark" autofocus
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
          <p class="text-xs text-muted-foreground mt-1">{{ t('themes.slug_hint') }}</p>
        </div>
        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('common.label') }}</label>
          <input v-model="createForm.label" placeholder="e.g. Aurora Dark"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('common.description') }}</label>
          <textarea v-model="createForm.description" rows="2" placeholder="Short description…"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary resize-none" />
        </div>
        <button type="submit" :disabled="createForm.processing"
          class="w-full py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ createForm.processing ? t('common.loading') : t('themes.create_title') }}
        </button>
      </form>
    </div>

    <!-- Edit tabs -->
    <div v-else class="space-y-4">

      <!-- Error / success for AI -->
      <div v-if="aiError" class="flex items-center gap-2 px-4 py-3 bg-destructive/10 text-destructive border border-destructive/20 rounded-xl text-sm font-medium">
        <XCircleIcon class="w-4 h-4 shrink-0" />{{ aiError }}
        <button @click="aiError=''" class="ml-auto text-destructive/60 hover:text-destructive">✕</button>
      </div>
      <div v-if="aiSuccess" class="flex items-center gap-2 px-4 py-3 bg-success/10 text-success border border-success/20 rounded-xl text-sm font-medium">
        <CheckCircleIcon class="w-4 h-4 shrink-0" />{{ aiSuccess }}
        <button @click="aiSuccess=''" class="ml-auto text-success/60 hover:text-success">✕</button>
      </div>

      <!-- Tab bar -->
      <div class="flex gap-1 bg-muted p-1 rounded-xl w-fit">
        <button v-for="tab in tabs" :key="tab.id"
          @click="activeTab = tab.id"
          class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors"
          :class="activeTab === tab.id
            ? 'bg-card text-foreground shadow-sm'
            : 'text-muted-foreground hover:text-foreground'">
          {{ tab.label }}
        </button>
      </div>

      <!-- ── Tab: Details ── -->
      <div v-show="activeTab === 'details'" class="max-w-xl">
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground">{{ t('themes.details') }}</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('common.label') }}</label>
              <input v-model="form.label"
                class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('common.version') }}</label>
              <input v-model="form.version"
                class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('common.description') }}</label>
            <textarea v-model="form.description" rows="2"
              class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary resize-none" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('common.status') }}</label>
            <select v-model="form.status"
              class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary">
              <option value="draft">{{ t('themes.status.draft') }}</option>
              <option value="ready">{{ t('themes.status.ready') }}</option>
              <option value="published">{{ t('themes.status.published') }}</option>
            </select>
          </div>
          <button @click="save" :disabled="saving"
            class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
            {{ saving ? t('common.loading') : t('common.save') }}
          </button>
        </div>
      </div>

      <!-- ── Tab: Design ── -->
      <div v-show="activeTab === 'design'" class="max-w-3xl space-y-6">

        <!-- AI Generator -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
              <SparklesIcon class="w-4 h-4 text-primary" />
            </div>
            <div>
              <h2 class="font-semibold text-foreground">{{ t('themes.ai_generator') }}</h2>
              <p class="text-xs text-muted-foreground">{{ t('themes.ai_generator_hint') }}</p>
            </div>
          </div>
          <div class="flex gap-2">
            <input v-model="aiPrompt" @keyup.enter="generateAi"
              :placeholder="t('themes.ai_prompt_placeholder')"
              :disabled="aiLoading"
              class="flex-1 px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary disabled:opacity-50" />
            <button @click="generateAi" :disabled="aiLoading || !aiPrompt.trim()"
              class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold disabled:opacity-50 flex items-center gap-1.5 whitespace-nowrap">
              <SparklesIcon v-if="!aiLoading" class="w-3.5 h-3.5" />
              <div v-else class="w-3.5 h-3.5 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
              {{ aiLoading ? t('themes.generating') : t('themes.generate') }}
            </button>
          </div>
        </div>

        <!-- Font picker -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground">{{ t('themes.fonts') }}</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('themes.font_heading') }}</label>
              <select v-model="form.fonts.heading"
                class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary">
                <option value="">System default</option>
                <option v-for="f in googleFonts" :key="f" :value="f">{{ f }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('themes.font_body') }}</label>
              <select v-model="form.fonts.body"
                class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary">
                <option value="">System default</option>
                <option v-for="f in googleFonts" :key="f" :value="f">{{ f }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Color tokens -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-foreground">{{ t('themes.colors') }}</h2>
            <div class="flex gap-1 bg-muted p-0.5 rounded-lg">
              <button @click="colorMode = 'light'"
                class="px-3 py-1 rounded-md text-xs font-semibold transition-colors"
                :class="colorMode === 'light' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'">
                ☀️ Light
              </button>
              <button @click="colorMode = 'dark'"
                class="px-3 py-1 rounded-md text-xs font-semibold transition-colors"
                :class="colorMode === 'dark' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'">
                🌙 Dark
              </button>
            </div>
          </div>
          <p class="text-xs text-muted-foreground">{{ t('themes.colors_hint') }}</p>

          <div class="space-y-2">
            <div v-for="token in colorTokens" :key="token.var"
              class="grid grid-cols-[1fr_auto_1fr] gap-3 items-center">
              <span class="text-xs font-mono text-muted-foreground truncate">{{ token.var }}</span>
              <!-- Live color swatch -->
              <div class="w-6 h-6 rounded border border-border flex-shrink-0 overflow-hidden">
                <input type="color"
                  :value="hexColorValue(currentColors[token.var] || token.default)"
                  @input="e => setColorHex(token.var, e.target.value)"
                  class="w-8 h-8 -m-1 cursor-pointer border-0 bg-transparent" />
              </div>
              <input v-model="currentColors[token.var]" :placeholder="token.default"
                class="px-3 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary font-mono" />
            </div>
          </div>

          <button @click="save" :disabled="saving"
            class="px-4 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors disabled:opacity-50">
            {{ saving ? t('common.loading') : t('themes.save_design') }}
          </button>
        </div>
      </div>

      <!-- ── Tab: Sections ── -->
      <div v-show="activeTab === 'sections'" class="max-w-4xl space-y-4">

        <div class="flex items-center justify-between">
          <p class="text-sm text-muted-foreground">{{ t('themes.sections_hint') }}</p>
          <button @click="addSection"
            class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border transition-colors flex items-center gap-1">
            <PlusIcon class="w-3.5 h-3.5" />
            {{ t('themes.add_section') }}
          </button>
        </div>

        <div v-if="Object.keys(form.sections).length === 0"
          class="bg-card border border-border border-dashed rounded-2xl p-12 text-center">
          <CodeIcon class="w-10 h-10 text-muted-foreground opacity-30 mx-auto mb-3" />
          <p class="text-sm text-muted-foreground">{{ t('themes.no_sections') }}</p>
          <p class="text-xs text-muted-foreground mt-1">{{ t('themes.no_sections_hint') }}</p>
        </div>

        <div v-for="(blade, sectionType) in form.sections" :key="sectionType"
          class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <span class="text-sm font-semibold font-mono text-foreground">{{ sectionType }}</span>
            <div class="flex gap-2">
              <span class="text-xs text-muted-foreground">{{ (blade || '').length }} chars</span>
              <button @click="removeSection(sectionType)"
                class="text-xs text-destructive/60 hover:text-destructive px-2 py-0.5 rounded hover:bg-destructive/10 transition-colors">
                {{ t('common.delete') }}
              </button>
            </div>
          </div>
          <textarea v-model="form.sections[sectionType]" rows="10" spellcheck="false"
            class="w-full px-4 py-3 bg-muted/30 text-xs font-mono focus:outline-none resize-y border-0" />
        </div>

        <button v-if="Object.keys(form.sections).length > 0" @click="save" :disabled="saving"
          class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ saving ? t('common.loading') : t('themes.save_sections') }}
        </button>
      </div>

      <!-- ── Tab: Preview ── -->
      <div v-show="activeTab === 'preview'" class="space-y-4">
        <div class="flex items-center gap-3">
          <p class="text-sm text-muted-foreground flex-1">{{ t('themes.preview_hint') }}</p>
          <button @click="reloadPreview"
            class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border transition-colors flex items-center gap-1">
            <RefreshCwIcon class="w-3.5 h-3.5" />
            {{ t('themes.reload_preview') }}
          </button>
          <a :href="`/preview/theme/${theme.uuid}`" target="_blank"
            class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border transition-colors flex items-center gap-1">
            <ExternalLinkIcon class="w-3.5 h-3.5" />
            {{ t('themes.open_preview') }}
          </a>
        </div>
        <div class="bg-muted rounded-2xl overflow-hidden border border-border" style="height: 70vh;">
          <iframe :key="previewKey" :src="`/preview/theme/${theme.uuid}`"
            class="w-full h-full border-0" />
        </div>
      </div>

    </div>

    <!-- Add section modal -->
    <div v-if="showAddSection"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showAddSection = false">
      <div class="bg-card border border-border rounded-2xl p-6 w-full max-w-sm space-y-4">
        <h3 class="font-semibold text-foreground">{{ t('themes.add_section') }}</h3>
        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">
            {{ t('themes.section_type') }}
          </label>
          <select v-model="newSectionType"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary">
            <option v-for="t in availableSectionTypes" :key="t" :value="t">{{ t }}</option>
            <option value="__custom__">Custom type…</option>
          </select>
          <input v-if="newSectionType === '__custom__'" v-model="customSectionType"
            placeholder="e.g. my_section"
            class="mt-2 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>
        <div class="flex gap-2">
          <button @click="confirmAddSection"
            class="flex-1 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold">
            Add
          </button>
          <button @click="showAddSection = false"
            class="flex-1 py-2.5 bg-muted text-foreground rounded-xl text-sm font-semibold hover:bg-border">
            {{ t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  EyeIcon, DownloadIcon, UploadIcon, SparklesIcon,
  CheckCircleIcon, XCircleIcon, PlusIcon, CodeIcon,
  RefreshCwIcon, ExternalLinkIcon,
} from 'lucide-vue-next';

const { t } = useI18n();

const props = defineProps({ theme: { type: Object, default: null } });

// ── Tabs ──
const activeTab = ref('details');
const tabs = computed(() => {
  if (!props.theme) return [];
  return [
    { id: 'details',  label: t('themes.tab_details')  },
    { id: 'design',   label: t('themes.tab_design')   },
    { id: 'sections', label: t('themes.tab_sections') },
    { id: 'preview',  label: t('themes.tab_preview')  },
  ];
});

// ── Create form ──
const createForm = useForm({ name: '', label: '', description: '', version: '1.0.0' });
function createTheme() { createForm.post('/themes'); }

// ── Edit form ──
const form = reactive({
  label:       props.theme?.label       ?? '',
  description: props.theme?.description ?? '',
  version:     props.theme?.version     ?? '1.0.0',
  status:      props.theme?.status      ?? 'draft',
  preview_url: props.theme?.preview_url ?? '',
  fonts: {
    heading: props.theme?.fonts?.heading ?? '',
    body:    props.theme?.fonts?.body    ?? '',
  },
  colors: {
    light: { ...(props.theme?.colors?.light ?? {}) },
    dark:  { ...(props.theme?.colors?.dark  ?? {}) },
  },
  sections: { ...(props.theme?.sections ?? {}) },
});

const saving = ref(false);
function save() {
  saving.value = true;
  router.put(`/themes/${props.theme.uuid}`, form, {
    onFinish: () => { saving.value = false; },
    onSuccess: () => { aiSuccess.value = t('themes.saved'); },
  });
}

// ── Color mode ──
const colorMode = ref('light');
const currentColors = computed(() =>
  colorMode.value === 'light' ? form.colors.light : form.colors.dark
);

const colorTokens = [
  { var: '--color-primary',            default: 'oklch(0.55 0.22 265)' },
  { var: '--color-primary-foreground', default: 'oklch(1 0 0)' },
  { var: '--color-background',         default: 'oklch(0.99 0.003 265)' },
  { var: '--color-foreground',         default: 'oklch(0.13 0.02 265)' },
  { var: '--color-card',               default: 'oklch(1 0 0)' },
  { var: '--color-muted',              default: 'oklch(0.96 0.005 265)' },
  { var: '--color-muted-foreground',   default: 'oklch(0.50 0.02 265)' },
  { var: '--color-border',             default: 'oklch(0.91 0.005 265)' },
  { var: '--color-success',            default: 'oklch(0.65 0.20 150)' },
  { var: '--color-warning',            default: 'oklch(0.75 0.18 80)' },
  { var: '--color-destructive',        default: 'oklch(0.60 0.22 25)' },
];

// Convert oklch/hex to hex for the color input
function hexColorValue(value) {
  if (!value) return '#6366f1';
  if (value.startsWith('#')) return value;
  return '#6366f1'; // fallback for oklch (browser can't parse in color input)
}

function setColorHex(variable, hexValue) {
  currentColors.value[variable] = hexValue;
}

// ── Google Fonts list ──
const googleFonts = [
  'Inter', 'Poppins', 'DM Sans', 'Outfit', 'Plus Jakarta Sans',
  'Sora', 'Nunito', 'Raleway', 'Lato', 'Montserrat',
  'Playfair Display', 'Merriweather', 'Lora', 'Fraunces',
  'Space Grotesk', 'Geist', 'IBM Plex Sans', 'Source Sans 3',
];

// ── AI Generator ──
const aiPrompt  = ref('');
const aiLoading = ref(false);
const aiError   = ref('');
const aiSuccess = ref('');

async function generateAi() {
  if (!aiPrompt.value.trim() || aiLoading.value) return;
  aiLoading.value = true;
  aiError.value   = '';
  aiSuccess.value = '';

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const res  = await fetch(`/themes/${props.theme.uuid}/generate-ai`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body:    JSON.stringify({ prompt: aiPrompt.value }),
    });
    const data = await res.json();

    if (!res.ok || data.error) {
      aiError.value = data.error ?? 'AI generation failed.';
      return;
    }

    // Update local form with AI results
    if (data.colors?.light) Object.assign(form.colors.light, data.colors.light);
    if (data.colors?.dark)  Object.assign(form.colors.dark,  data.colors.dark);
    if (data.fonts?.heading) form.fonts.heading = data.fonts.heading;
    if (data.fonts?.body)    form.fonts.body    = data.fonts.body;
    if (data.sections)       Object.assign(form.sections, data.sections);

    aiSuccess.value = t('themes.ai_success');
    aiPrompt.value  = '';
    activeTab.value = 'design'; // Switch to Design tab to see result
  } catch (e) {
    aiError.value = e.message;
  } finally {
    aiLoading.value = false;
  }
}

// ── Sections ──
const showAddSection  = ref(false);
const newSectionType  = ref('hero');
const customSectionType = ref('');

const availableSectionTypes = [
  'hero', 'features', 'text', 'cta', 'testimonials', 'pricing',
  'gallery', 'faq', 'contact', 'newsletter', 'columns', 'stats',
  'team', 'steps', 'timeline', 'cards', 'quote', 'banner',
];

function addSection() { showAddSection.value = true; }

function confirmAddSection() {
  const type = newSectionType.value === '__custom__'
    ? customSectionType.value.trim()
    : newSectionType.value;

  if (!type) return;
  if (!form.sections[type]) {
    form.sections[type] = `{{-- ${type} section --}}\n<section class="af-${type}" style="padding: 5rem 2rem;">\n  <div style="max-width: 1100px; margin: 0 auto;">\n    <h2>{{ \$content['heading'] ?? '${type}' }}</h2>\n  </div>\n</section>\n`;
  }
  showAddSection.value    = false;
  newSectionType.value    = 'hero';
  customSectionType.value = '';
  activeTab.value = 'sections';
}

function removeSection(type) {
  if (!confirm(`Remove section "${type}"?`)) return;
  delete form.sections[type];
}

// ── Preview ──
const previewKey = ref(0);
function reloadPreview() { previewKey.value++; }

// ── Publish ──
const publishing = ref(false);
async function publishTheme() {
  if (!confirm(t('themes.publish_confirm'))) return;
  publishing.value = true;
  aiError.value    = '';
  aiSuccess.value  = '';

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const res  = await fetch(`/themes/${props.theme.uuid}/publish`, {
      method: 'POST', headers: { 'X-CSRF-TOKEN': csrf },
    });
    const data = await res.json();

    if (!res.ok || data.error) {
      aiError.value = data.error ?? 'Publish failed.';
    } else {
      aiSuccess.value = t('themes.publish_success');
      // Reload page to update is_published flag
      setTimeout(() => router.reload(), 1500);
    }
  } catch (e) {
    aiError.value = e.message;
  } finally {
    publishing.value = false;
  }
}
</script>
