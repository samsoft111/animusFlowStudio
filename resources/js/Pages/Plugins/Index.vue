<template>
  <AppLayout :title="t('plugins.title')">
    <template #actions>
      <Link href="/plugins/create"
        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:opacity-90 transition-opacity">
        + {{ t('plugins.new') }}
      </Link>
    </template>

    <div v-if="plugins.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="plugin in plugins" :key="plugin.uuid"
        class="bg-card border border-border rounded-2xl p-5 flex flex-col gap-4 hover:border-primary/40 transition-colors">

        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <PuzzleIcon class="w-5 h-5 text-primary" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-foreground text-sm truncate">{{ plugin.label }}</h3>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0" :class="statusClass(plugin.status)">
                {{ t('plugins.status.' + plugin.status) }}
              </span>
            </div>
            <p class="text-xs text-muted-foreground">{{ plugin.name }}</p>
          </div>
        </div>

        <!-- Hooks chips -->
        <div v-if="plugin.hooks?.length" class="flex flex-wrap gap-1.5">
          <span v-for="hook in plugin.hooks" :key="hook"
            class="px-2 py-0.5 bg-muted text-muted-foreground rounded-md text-[10px] font-mono">
            {{ hook }}
          </span>
        </div>

        <div class="flex gap-2">
          <Link :href="`/plugins/${plugin.uuid}/edit`"
            class="flex-1 px-3 py-2 bg-muted text-foreground rounded-lg text-xs font-semibold text-center hover:bg-border transition-colors">
            {{ t('common.edit') }}
          </Link>
          <a :href="`/plugins/${plugin.uuid}/export`"
            class="px-3 py-2 bg-primary/10 text-primary rounded-lg text-xs font-semibold hover:bg-primary/20 transition-colors">
            {{ t('common.export') }}
          </a>
          <button @click="deletePlugin(plugin)"
            class="px-3 py-2 bg-destructive/10 text-destructive rounded-lg text-xs font-semibold hover:bg-destructive/20 transition-colors">
            {{ t('common.delete') }}
          </button>
        </div>
      </div>
    </div>

    <div v-else class="flex flex-col items-center justify-center py-20 text-center">
      <PuzzleIcon class="w-12 h-12 text-muted-foreground opacity-30 mb-4" />
      <h2 class="text-lg font-semibold text-foreground mb-2">{{ t('plugins.no_plugins') }}</h2>
      <p class="text-sm text-muted-foreground mb-6">{{ t('plugins.no_plugins_desc') }}</p>
      <Link href="/plugins/create" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold">
        {{ t('plugins.new') }}
      </Link>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PuzzleIcon } from 'lucide-vue-next';

const { t } = useI18n();

defineProps({ plugins: { type: Array, default: () => [] } });

function statusClass(status) {
  return {
    draft:     'bg-muted text-muted-foreground',
    ready:     'bg-warning/20 text-warning',
    published: 'bg-success/20 text-success',
  }[status] ?? 'bg-muted text-muted-foreground';
}

function deletePlugin(plugin) {
  if (!confirm(t('common.confirm_delete', { name: plugin.label }))) return;
  router.delete(`/plugins/${plugin.uuid}`);
}
</script>
