<template>
  <AppLayout title="Dashboard">
    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <StatCard label="Themes" :value="stats.themes" icon="palette" />
      <StatCard label="Plugins" :value="stats.plugins" icon="puzzle" />
      <StatCard label="Published Themes" :value="stats.published_themes" icon="upload" color="success" />
      <StatCard label="Published Plugins" :value="stats.published_plugins" icon="upload" color="success" />
    </div>

    <!-- Recent items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent themes -->
      <div class="bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-foreground">Recent Themes</h2>
          <Link href="/themes/create" class="text-xs text-primary hover:underline font-medium">+ New</Link>
        </div>
        <div v-if="recentThemes.length" class="space-y-2">
          <div v-for="t in recentThemes" :key="t.uuid"
            class="flex items-center justify-between p-3 hover:bg-muted rounded-xl transition-colors">
            <div>
              <p class="text-sm font-medium text-foreground">{{ t.label }}</p>
              <p class="text-xs text-muted-foreground">{{ t.name }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
              :class="statusClass(t.status)">{{ t.status }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-muted-foreground py-4 text-center">No themes yet.</p>
      </div>

      <!-- Recent plugins -->
      <div class="bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-foreground">Recent Plugins</h2>
          <Link href="/plugins/create" class="text-xs text-primary hover:underline font-medium">+ New</Link>
        </div>
        <div v-if="recentPlugins.length" class="space-y-2">
          <div v-for="p in recentPlugins" :key="p.uuid"
            class="flex items-center justify-between p-3 hover:bg-muted rounded-xl transition-colors">
            <div>
              <p class="text-sm font-medium text-foreground">{{ p.label }}</p>
              <p class="text-xs text-muted-foreground">{{ p.name }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
              :class="statusClass(p.status)">{{ p.status }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-muted-foreground py-4 text-center">No plugins yet.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';

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
