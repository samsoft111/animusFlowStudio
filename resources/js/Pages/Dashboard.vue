<template>
  <AppLayout :title="t('dashboard.title')">
    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <StatCard :label="t('dashboard.themes')"    :value="stats.themes"           icon="palette" />
      <StatCard :label="t('dashboard.plugins')"   :value="stats.plugins"          icon="puzzle" />
      <StatCard :label="t('themes.status.published') + ' ' + t('nav.themes')"
                :value="stats.published_themes"   icon="upload" color="success" />
      <StatCard :label="t('themes.status.published') + ' ' + t('nav.plugins')"
                :value="stats.published_plugins"  icon="upload" color="success" />
    </div>

    <!-- Recent items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent themes -->
      <div class="bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-foreground">{{ t('dashboard.recent_themes') }}</h2>
          <Link href="/themes/create" class="text-xs text-primary hover:underline font-medium">
            + {{ t('common.new') }}
          </Link>
        </div>
        <div v-if="recentThemes.length" class="space-y-2">
          <div v-for="th in recentThemes" :key="th.uuid"
            class="flex items-center justify-between p-3 hover:bg-muted rounded-xl transition-colors">
            <div>
              <p class="text-sm font-medium text-foreground">{{ th.label }}</p>
              <p class="text-xs text-muted-foreground">{{ th.name }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
              :class="statusClass(th.status)">{{ t('themes.status.' + th.status) }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-muted-foreground py-4 text-center">{{ t('dashboard.no_themes') }}</p>
      </div>

      <!-- Recent plugins -->
      <div class="bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-foreground">{{ t('dashboard.recent_plugins') }}</h2>
          <Link href="/plugins/create" class="text-xs text-primary hover:underline font-medium">
            + {{ t('common.new') }}
          </Link>
        </div>
        <div v-if="recentPlugins.length" class="space-y-2">
          <div v-for="pl in recentPlugins" :key="pl.uuid"
            class="flex items-center justify-between p-3 hover:bg-muted rounded-xl transition-colors">
            <div>
              <p class="text-sm font-medium text-foreground">{{ pl.label }}</p>
              <p class="text-xs text-muted-foreground">{{ pl.name }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
              :class="statusClass(pl.status)">{{ t('plugins.status.' + pl.status) }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-muted-foreground py-4 text-center">{{ t('dashboard.no_plugins') }}</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';

const { t } = useI18n();

defineProps({
  stats:         { type: Object, default: () => ({}) },
  recentThemes:  { type: Array,  default: () => [] },
  recentPlugins: { type: Array,  default: () => [] },
});

function statusClass(status) {
  return {
    draft:     'bg-muted text-muted-foreground',
    ready:     'bg-warning/20 text-warning',
    published: 'bg-success/20 text-success',
  }[status] ?? 'bg-muted text-muted-foreground';
}
</script>
