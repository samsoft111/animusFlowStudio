<template>
  <AppLayout :title="plugin ? plugin.label : t('plugins.create_title')">
    <template #actions>
      <template v-if="plugin">
        <a :href="`/plugins/${plugin.uuid}/export`"
          class="px-3 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors flex items-center gap-1.5">
          <DownloadIcon class="w-3.5 h-3.5" />
          {{ t('common.export') }}
        </a>
        <button @click="publishPlugin" :disabled="publishing"
          class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5 disabled:opacity-50"
          :class="plugin.is_published ? 'bg-success/10 text-success hover:bg-success/20' : 'bg-primary text-primary-foreground hover:opacity-90'">
          <UploadIcon class="w-3.5 h-3.5" />
          {{ publishing ? t('common.loading') : (plugin.is_published ? t('plugins.republish') : t('plugins.publish')) }}
        </button>
      </template>
    </template>

    <!-- Create form -->
    <div v-if="!plugin" class="max-w-lg">
      <form @submit.prevent="createPlugin" class="bg-card border border-border rounded-2xl p-6 space-y-4">
        <h2 class="font-semibold text-foreground">{{ t('plugins.create_title') }}</h2>
        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('plugins.slug') }}</label>
          <input v-model="createForm.name" placeholder="e.g. af-hello-bar" autofocus
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('common.label') }}</label>
          <input v-model="createForm.label" placeholder="e.g. Hello Bar"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">{{ t('common.description') }}</label>
          <textarea v-model="createForm.description" rows="2"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary resize-none" />
        </div>
        <button type="submit" :disabled="createForm.processing"
          class="w-full py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ createForm.processing ? t('common.loading') : t('plugins.create_title') }}
        </button>
      </form>
    </div>

    <!-- Edit tabs -->
    <div v-else class="space-y-4">

      <!-- Feedback messages -->
      <div v-if="aiError" class="flex items-center gap-2 px-4 py-3 bg-destructive/10 text-destructive border border-destructive/20 rounded-xl text-sm font-medium">
        <XCircleIcon class="w-4 h-4 shrink-0" />{{ aiError }}
        <button @click="aiError=''" class="ml-auto">✕</button>
      </div>
      <div v-if="aiSuccess" class="flex items-center gap-2 px-4 py-3 bg-success/10 text-success border border-success/20 rounded-xl text-sm font-medium">
        <CheckCircleIcon class="w-4 h-4 shrink-0" />{{ aiSuccess }}
        <button @click="aiSuccess=''" class="ml-auto">✕</button>
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
          <h2 class="font-semibold text-foreground">{{ t('plugins.details') }}</h2>
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
              <option value="draft">{{ t('plugins.status.draft') }}</option>
              <option value="ready">{{ t('plugins.status.ready') }}</option>
              <option value="published">{{ t('plugins.status.published') }}</option>
            </select>
          </div>

          <!-- Hooks -->
          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">{{ t('plugins.hooks') }}</label>
            <div class="space-y-2">
              <label v-for="h in availableHooks" :key="h"
                class="flex items-start gap-3 p-3 bg-muted rounded-xl cursor-pointer hover:bg-border/50 transition-colors">
                <input type="checkbox" :value="h" v-model="form.hooks" class="mt-0.5" />
                <div>
                  <span class="text-sm font-mono font-semibold text-foreground">{{ h }}</span>
                  <p class="text-xs text-muted-foreground mt-0.5">{{ hookDescriptions[h] }}</p>
                </div>
              </label>
            </div>
          </div>

          <button @click="save" :disabled="saving"
            class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
            {{ saving ? t('common.loading') : t('common.save') }}
          </button>
        </div>
      </div>

      <!-- ── Tab: AI Generator ── -->
      <div v-show="activeTab === 'ai'" class="max-w-2xl">
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
              <SparklesIcon class="w-4 h-4 text-primary" />
            </div>
            <div>
              <h2 class="font-semibold text-foreground">{{ t('plugins.ai_generator') }}</h2>
              <p class="text-xs text-muted-foreground">{{ t('plugins.ai_generator_hint') }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">
              {{ t('plugins.ai_prompt_label') }}
            </label>
            <textarea v-model="aiPrompt" rows="4"
              :placeholder="t('plugins.ai_prompt_placeholder')"
              :disabled="aiLoading"
              class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary resize-none disabled:opacity-50" />
          </div>

          <button @click="generateAi" :disabled="aiLoading || !aiPrompt.trim()"
            class="w-full py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50 flex items-center justify-center gap-2">
            <div v-if="aiLoading" class="w-4 h-4 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
            <SparklesIcon v-else class="w-4 h-4" />
            {{ aiLoading ? t('plugins.generating') : t('plugins.generate') }}
          </button>

          <div v-if="aiLoading" class="bg-muted rounded-xl p-4 text-sm text-muted-foreground text-center">
            {{ t('plugins.ai_waiting') }}
          </div>
        </div>
      </div>

      <!-- ── Tab: Code ── -->
      <div v-show="activeTab === 'code'" class="max-w-4xl space-y-4">

        <!-- Plugin.php -->
        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <span class="text-sm font-semibold text-foreground">Plugin.php</span>
            <span class="text-xs text-muted-foreground font-mono">{{ (form.plugin_php || '').length }} chars</span>
          </div>
          <textarea v-model="form.plugin_php" rows="16" spellcheck="false"
            :placeholder="phpPlaceholder"
            class="w-full px-4 py-3 bg-muted/30 text-xs font-mono focus:outline-none resize-y border-0" />
        </div>

        <!-- Widget Blade -->
        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <span class="text-sm font-semibold text-foreground">
              widget.blade.php
              <span class="text-xs text-muted-foreground font-normal ml-2">({{ t('plugins.widget_blade_hint') }})</span>
            </span>
            <span class="text-xs text-muted-foreground font-mono">{{ (form.widget_blade || '').length }} chars</span>
          </div>
          <textarea v-model="form.widget_blade" rows="8" spellcheck="false"
            placeholder="{{-- Blade rendered before </body> via page.render hook --}}"
            class="w-full px-4 py-3 bg-muted/30 text-xs font-mono focus:outline-none resize-y border-0" />
        </div>

        <!-- Widget JS -->
        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <span class="text-sm font-semibold text-foreground">
              widget.js
              <span class="text-xs text-muted-foreground font-normal ml-2">({{ t('plugins.widget_js_hint') }})</span>
            </span>
            <span class="text-xs text-muted-foreground font-mono">{{ (form.widget_js || '').length }} chars</span>
          </div>
          <textarea v-model="form.widget_js" rows="8" spellcheck="false"
            placeholder="// JavaScript for your widget"
            class="w-full px-4 py-3 bg-muted/30 text-xs font-mono focus:outline-none resize-y border-0" />
        </div>

        <button @click="save" :disabled="saving"
          class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ saving ? t('common.loading') : t('plugins.save_code') }}
        </button>
      </div>

      <!-- ── Tab: Settings Schema ── -->
      <div v-show="activeTab === 'schema'" class="max-w-3xl space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="font-semibold text-foreground">{{ t('plugins.schema_title') }}</h2>
            <p class="text-xs text-muted-foreground mt-0.5">{{ t('plugins.schema_hint') }}</p>
          </div>
          <button @click="addSchemaField"
            class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border transition-colors flex items-center gap-1">
            <PlusIcon class="w-3.5 h-3.5" />
            {{ t('plugins.add_field') }}
          </button>
        </div>

        <div v-if="!form.settings_schema.length"
          class="bg-card border border-border border-dashed rounded-2xl p-10 text-center">
          <SlidersIcon class="w-8 h-8 text-muted-foreground opacity-30 mx-auto mb-3" />
          <p class="text-sm text-muted-foreground">{{ t('plugins.no_fields') }}</p>
        </div>

        <div v-for="(field, index) in form.settings_schema" :key="index"
          class="bg-card border border-border rounded-2xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              {{ t('plugins.field') }} {{ index + 1 }}
            </span>
            <button @click="removeSchemaField(index)"
              class="text-xs text-destructive/60 hover:text-destructive px-2 py-0.5 rounded hover:bg-destructive/10">
              {{ t('common.delete') }}
            </button>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Key</label>
              <input v-model="field.key" placeholder="setting_key"
                class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary font-mono" />
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Label</label>
              <input v-model="field.label" placeholder="Setting Name"
                class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary" />
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Type</label>
              <select v-model="field.type"
                class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary">
                <option value="text">text</option>
                <option value="textarea">textarea</option>
                <option value="color">color</option>
                <option value="select">select</option>
                <option value="toggle">toggle</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Default</label>
              <input v-model="field.default" placeholder="default value"
                class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary" />
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Placeholder</label>
              <input v-model="field.placeholder" placeholder="placeholder text"
                class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary" />
            </div>
          </div>

          <div>
            <label class="block text-xs text-muted-foreground mb-1">Hint</label>
            <input v-model="field.hint" placeholder="Help text for this field"
              class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary" />
          </div>

          <!-- Select options -->
          <div v-if="field.type === 'select'" class="space-y-1">
            <label class="block text-xs text-muted-foreground">Options (value: Label — one per line)</label>
            <textarea
              :value="selectOptionsText(field.options)"
              @input="e => field.options = parseSelectOptions(e.target.value)"
              rows="3" placeholder="value1: Label One&#10;value2: Label Two"
              class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary font-mono resize-none" />
          </div>

          <!-- Toggle label -->
          <div v-if="field.type === 'toggle'">
            <label class="block text-xs text-muted-foreground mb-1">Toggle label</label>
            <input v-model="field.toggle_label" placeholder="Enable feature"
              class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary" />
          </div>
        </div>

        <button v-if="form.settings_schema.length" @click="save" :disabled="saving"
          class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ saving ? t('common.loading') : t('plugins.save_schema') }}
        </button>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  DownloadIcon, UploadIcon, SparklesIcon,
  CheckCircleIcon, XCircleIcon, PlusIcon, SlidersIcon,
} from 'lucide-vue-next';

const { t } = useI18n();
const props = defineProps({ plugin: { type: Object, default: null } });

// ── Tabs ──
const activeTab = ref('details');
const tabs = [
  { id: 'details', label: t('plugins.tab_details') },
  { id: 'ai',      label: t('plugins.tab_ai')      },
  { id: 'code',    label: t('plugins.tab_code')    },
  { id: 'schema',  label: t('plugins.tab_schema')  },
];

// ── Create form ──
const createForm = useForm({ name: '', label: '', description: '', version: '1.0.0' });
function createPlugin() { createForm.post('/plugins'); }

// ── Edit form ──
const form = reactive({
  label:           props.plugin?.label           ?? '',
  description:     props.plugin?.description     ?? '',
  version:         props.plugin?.version         ?? '1.0.0',
  status:          props.plugin?.status          ?? 'draft',
  hooks:           [...(props.plugin?.hooks      ?? [])],
  plugin_php:      props.plugin?.plugin_php      ?? '',
  widget_blade:    props.plugin?.widget_blade    ?? '',
  widget_js:       props.plugin?.widget_js       ?? '',
  settings_schema: JSON.parse(JSON.stringify(props.plugin?.settings_schema ?? [])),
});

const saving = ref(false);
function save() {
  saving.value = true;
  router.put(`/plugins/${props.plugin.uuid}`, form, {
    onFinish:  () => { saving.value = false; },
    onSuccess: () => { aiSuccess.value = t('plugins.saved'); },
  });
}

// ── Hooks ──
const availableHooks = ['page.render', 'content.publish', 'admin.sidebar'];
const hookDescriptions = {
  'page.render':      'Returns HTML injected before </body> on every page',
  'content.publish':  'Fired when a page is published',
  'admin.sidebar':    'Adds a link to the admin sidebar',
};

// ── PHP placeholder ──
const phpPlaceholder = props.plugin
  ? `<?php\n\ndeclare(strict_types=1);\n\nclass ${props.plugin.name.replace(/[^a-zA-Z0-9]/g, '')}Plugin\n{\n    public function onPageRender($page): string\n    {\n        return '';\n    }\n}`
  : '';

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
    const res  = await fetch(`/plugins/${props.plugin.uuid}/generate-ai`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body:    JSON.stringify({ prompt: aiPrompt.value }),
    });
    const data = await res.json();

    if (!res.ok || data.error) {
      aiError.value = data.error ?? 'AI generation failed.';
      return;
    }

    if (data.plugin_php)   form.plugin_php   = data.plugin_php;
    if (data.widget_blade) form.widget_blade = data.widget_blade;
    if (data.widget_js)    form.widget_js    = data.widget_js;
    if (data.settings_schema?.length) {
      form.settings_schema.splice(0, form.settings_schema.length, ...data.settings_schema);
    }

    aiSuccess.value = t('plugins.ai_success');
    aiPrompt.value  = '';
    activeTab.value = 'code';
  } catch (e) {
    aiError.value = e.message;
  } finally {
    aiLoading.value = false;
  }
}

// ── Settings Schema builder ──
function addSchemaField() {
  form.settings_schema.push({
    key: '', label: '', type: 'text', default: '',
    placeholder: '', hint: '', options: {}, toggle_label: '',
  });
}

function removeSchemaField(index) {
  form.settings_schema.splice(index, 1);
}

function selectOptionsText(options) {
  if (!options || typeof options !== 'object') return '';
  return Object.entries(options).map(([v, l]) => `${v}: ${l}`).join('\n');
}

function parseSelectOptions(text) {
  const obj = {};
  for (const line of text.split('\n')) {
    const idx = line.indexOf(':');
    if (idx > 0) {
      const key = line.slice(0, idx).trim();
      const val = line.slice(idx + 1).trim();
      if (key) obj[key] = val;
    }
  }
  return obj;
}

// ── Publish ──
const publishing = ref(false);
async function publishPlugin() {
  if (!confirm(t('plugins.publish_confirm'))) return;
  publishing.value = true;
  aiError.value    = '';
  aiSuccess.value  = '';

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const res  = await fetch(`/plugins/${props.plugin.uuid}/publish`, {
      method: 'POST', headers: { 'X-CSRF-TOKEN': csrf },
    });
    const data = await res.json();

    if (!res.ok || data.error) {
      aiError.value = data.error ?? 'Publish failed.';
    } else {
      aiSuccess.value = t('plugins.publish_success');
      setTimeout(() => router.reload(), 1500);
    }
  } catch (e) {
    aiError.value = e.message;
  } finally {
    publishing.value = false;
  }
}
</script>
