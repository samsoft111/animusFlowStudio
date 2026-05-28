<template>
  <AppLayout :title="t('settings.title')">
    <div class="max-w-xl">
      <form @submit.prevent="save" class="bg-card border border-border rounded-2xl p-6 space-y-5">
        <h2 class="font-semibold text-foreground">{{ t('settings.studio_config') }}</h2>

        <div>
          <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">{{ t('settings.studio_name') }}</label>
          <input v-model="form.studio_name"
            class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>

        <hr class="border-border" />
        <h3 class="text-sm font-semibold text-foreground">{{ t('settings.marketplace') }}</h3>

        <div>
          <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">{{ t('settings.animus_api_url') }}</label>
          <input v-model="form.animus_api_url" placeholder="https://animus.kwantoe.com"
            class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>

        <hr class="border-border" />
        <h3 class="text-sm font-semibold text-foreground">{{ t('settings.ai_provider') }}</h3>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">{{ t('settings.provider') }}</label>
            <select v-model="form.ai_provider"
              class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary">
              <option value="claude">{{ t('settings.providers.claude') }}</option>
              <option value="openai">{{ t('settings.providers.openai') }}</option>
              <option value="gemini">{{ t('settings.providers.gemini') }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">{{ t('settings.model') }}</label>
            <input v-model="form.ai_model" placeholder="e.g. claude-sonnet-4-6"
              class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
          </div>
        </div>
        <div>
          <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">{{ t('settings.api_key') }}</label>
          <input v-model="form.ai_api_key" type="password" placeholder="sk-..."
            class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>

        <button type="submit" :disabled="form.processing"
          class="w-full py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ t('settings.save') }}
        </button>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

const props = defineProps({ settings: { type: Object, default: () => ({}) } });

const form = useForm({
  studio_name:    props.settings.studio_name    ?? 'AnimusFlowStudio',
  animus_api_url: props.settings.animus_api_url ?? 'https://animus.kwantoe.com',
  ai_provider:    props.settings.ai_provider    ?? 'claude',
  ai_model:       props.settings.ai_model       ?? '',
  ai_api_key:     props.settings.ai_api_key     ?? '',
});

function save() { form.put('/settings'); }
</script>
