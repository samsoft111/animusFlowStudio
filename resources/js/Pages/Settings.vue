<template>
  <AppLayout :title="t('settings.title')">
    <div class="max-w-2xl space-y-6">

      <form @submit.prevent="save" class="bg-card border border-border rounded-2xl p-6 space-y-5">
        <h2 class="font-semibold text-foreground">{{ t('settings.studio_config') }}</h2>

        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">
            {{ t('settings.studio_name') }}
          </label>
          <input v-model="form.studio_name"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>

        <hr class="border-border" />
        <h3 class="text-sm font-semibold text-foreground">{{ t('settings.marketplace') }}</h3>

        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">
            {{ t('settings.animus_api_url') }}
          </label>
          <input v-model="form.animus_api_url" placeholder="https://animus.kwantoe.com"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">
            {{ t('settings.animusflow_api_key') }}
          </label>
          <input v-model="form.animusflow_api_key" type="password" placeholder="af_..."
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
          <p class="text-xs text-muted-foreground mt-1">{{ t('settings.animusflow_api_key_hint') }}</p>
        </div>

        <hr class="border-border" />
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold text-foreground">{{ t('settings.ai_provider') }}</h3>
          <span class="flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
            :class="props.settings.has_ai_key
              ? 'bg-success/10 text-success'
              : 'bg-warning/10 text-warning'">
            <span class="w-1.5 h-1.5 rounded-full"
              :class="props.settings.has_ai_key ? 'bg-success' : 'bg-warning'"></span>
            {{ props.settings.has_ai_key ? t('settings.key_configured') : t('settings.key_missing') }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">
              {{ t('settings.provider') }}
            </label>
            <select v-model="form.ai_provider"
              class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary">
              <option value="claude">{{ t('settings.providers.claude') }}</option>
              <option value="openai">{{ t('settings.providers.openai') }}</option>
              <option value="gemini">{{ t('settings.providers.gemini') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">
              {{ t('settings.model') }}
            </label>
            <input v-model="form.ai_model" :placeholder="modelPlaceholder"
              class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">
            {{ t('settings.api_key') }}
            <span class="text-muted-foreground font-normal normal-case tracking-normal ml-1">
              ({{ props.settings.has_ai_key ? t('settings.key_update_hint') : t('settings.key_set_hint') }})
            </span>
          </label>
          <input v-model="form.ai_api_key" type="password"
            :placeholder="props.settings.has_ai_key ? '••••••••••••••••' : 'sk-... / sk-ant-...'"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>

        <button type="submit" :disabled="form.processing"
          class="w-full py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ form.processing ? t('common.loading') : t('settings.save') }}
        </button>
      </form>

    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
});

const form = useForm({
  studio_name:        props.settings.studio_name        ?? 'AnimusFlowStudio',
  animus_api_url:     props.settings.animus_api_url     ?? 'https://animus.kwantoe.com',
  animusflow_api_key: props.settings.animusflow_api_key ?? '',
  ai_provider:        props.settings.ai_provider        ?? 'claude',
  ai_model:           props.settings.ai_model           ?? '',
  ai_api_key:         '', // never pre-filled — only sent when user types a new key
});

const modelPlaceholder = computed(() => ({
  claude: 'claude-sonnet-4-5',
  openai: 'gpt-4o',
  gemini: 'gemini-1.5-flash',
}[form.ai_provider] ?? ''));

function save() { form.put('/settings'); }
</script>
