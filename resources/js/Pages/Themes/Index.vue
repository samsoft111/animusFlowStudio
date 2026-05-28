<template>
  <AppLayout :title="t('themes.title')">
    <template #actions>
      <Link href="/themes/create"
        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:opacity-90 transition-opacity">
        + {{ t('themes.new') }}
      </Link>
    </template>

    <div v-if="themes.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="theme in themes" :key="theme.uuid"
        class="bg-card border border-border rounded-2xl p-5 flex flex-col gap-4 hover:border-primary/40 transition-colors">

        <!-- Preview -->
        <div class="w-full h-28 rounded-xl bg-muted overflow-hidden">
          <img v-if="theme.preview_url" :src="theme.preview_url" :alt="theme.label"
            class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground">
            <PaletteIcon class="w-8 h-8 opacity-30" />
          </div>
        </div>

        <div class="flex-1">
          <div class="flex items-center gap-2 mb-1">
            <h3 class="font-semibold text-foreground text-sm">{{ theme.label }}</h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold" :class="statusClass(theme.status)">
              {{ t('themes.status.' + theme.status) }}
            </span>
          </div>
          <p class="text-xs text-muted-foreground">{{ theme.name }} — v{{ theme.version }}</p>
        </div>

        <div class="flex gap-2">
          <Link :href="`/themes/${theme.uuid}/edit`"
            class="flex-1 px-3 py-2 bg-muted text-foreground rounded-lg text-xs font-semibold text-center hover:bg-border transition-colors">
            {{ t('common.edit') }}
          </Link>
          <a :href="`/themes/${theme.uuid}/export`"
            class="px-3 py-2 bg-primary/10 text-primary rounded-lg text-xs font-semibold hover:bg-primary/20 transition-colors">
            {{ t('common.export') }}
          </a>
          <button @click="deleteTheme(theme)"
            class="px-3 py-2 bg-destructive/10 text-destructive rounded-lg text-xs font-semibold hover:bg-destructive/20 transition-colors">
            {{ t('common.delete') }}
          </button>
        </div>
      </div>
    </div>

    <div v-else class="flex flex-col items-center justify-center py-20 text-center">
      <PaletteIcon class="w-12 h-12 text-muted-foreground opacity-30 mb-4" />
      <h2 class="text-lg font-semibold text-foreground mb-2">{{ t('themes.no_themes') }}</h2>
      <p class="text-sm text-muted-foreground mb-6">{{ t('themes.no_themes_desc') }}</p>
      <Link href="/themes/create" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold">
        {{ t('themes.new') }}
      </Link>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PaletteIcon } from 'lucide-vue-next';

const { t } = useI18n();

defineProps({ themes: { type: Array, default: () => [] } });

function statusClass(status) {
  return {
    draft:     'bg-muted text-muted-foreground',
    ready:     'bg-warning/20 text-warning',
    published: 'bg-success/20 text-success',
  }[status] ?? 'bg-muted text-muted-foreground';
}

function deleteTheme(theme) {
  if (!confirm(t('common.confirm_delete', { name: theme.label }))) return;
  router.delete(`/themes/${theme.uuid}`);
}
</script>
