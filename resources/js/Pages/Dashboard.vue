<template>
  <AppLayout :title="t('dashboard.title')">
    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <StatCard :label="t('dashboard.themes')"    :value="stats.themes"           icon="palette" href="/themes" />
      <StatCard :label="t('dashboard.plugins')"   :value="stats.plugins"          icon="puzzle" href="/plugins" />
      <StatCard :label="t('themes.status.published') + ' ' + t('nav.themes')"
                :value="stats.published_themes"   icon="upload" color="success" href="/themes?status=published" />
      <StatCard :label="t('themes.status.published') + ' ' + t('nav.plugins')"
                :value="stats.published_plugins"  icon="upload" color="success" href="/plugins?status=published" />
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <!-- Card Criar Tema -->
      <Link href="/themes/create" class="group relative overflow-hidden bg-card hover:bg-muted/30 border border-border hover:border-primary/50 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between gap-4 cursor-pointer">
        <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full translate-x-8 -translate-y-8 group-hover:scale-110 transition-transform duration-300" />
        <div class="relative flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-300">
            <PaletteIcon class="w-6 h-6" />
          </div>
          <div>
            <h3 class="font-bold text-lg text-foreground group-hover:text-primary transition-colors flex items-center gap-1.5">
              Criar Novo Tema
            </h3>
            <p class="text-sm text-muted-foreground mt-1.5 leading-relaxed">
              Crie um novo tema visual exclusivo. Use o assistente de IA ou configure do zero layouts, cores, tipografia, seções e animações de entrada.
            </p>
          </div>
        </div>
        <div class="flex items-center text-xs font-semibold text-primary group-hover:translate-x-1.5 transition-transform duration-300 self-end mt-2">
          Começar a desenhar <ArrowRightIcon class="w-4 h-4 ml-1" />
        </div>
      </Link>

      <!-- Card Criar Plugin -->
      <Link href="/plugins/create" class="group relative overflow-hidden bg-card hover:bg-muted/30 border border-border hover:border-primary/50 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between gap-4 cursor-pointer">
        <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/5 rounded-bl-full translate-x-8 -translate-y-8 group-hover:scale-110 transition-transform duration-300" />
        <div class="relative flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center shrink-0 text-indigo-500 group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300">
            <PuzzleIcon class="w-6 h-6" />
          </div>
          <div>
            <h3 class="font-bold text-lg text-foreground group-hover:text-indigo-500 transition-colors flex items-center gap-1.5">
              Criar Novo Plugin
            </h3>
            <p class="text-sm text-muted-foreground mt-1.5 leading-relaxed">
              Desenvolva novas funcionalidades, extensões de PHP/JS, widgets interativos de conteúdo e integrações com APIs e serviços externos.
            </p>
          </div>
        </div>
        <div class="flex items-center text-xs font-semibold text-indigo-500 group-hover:translate-x-1.5 transition-transform duration-300 self-end mt-2">
          Começar a programar <ArrowRightIcon class="w-4 h-4 ml-1" />
        </div>
      </Link>
    </div>

    <!-- Recent items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent themes -->
      <div class="bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-foreground">{{ t('dashboard.recent_themes') }}</h2>
        </div>
        <div v-if="recentThemes.length" class="space-y-2">
          <Link v-for="th in recentThemes" :key="th.uuid" :href="`/themes/${th.uuid}/edit`"
            class="flex items-center justify-between p-3 hover:bg-muted rounded-xl transition-colors group cursor-pointer">
            <div>
              <p class="text-sm font-medium text-foreground group-hover:text-primary transition-colors">{{ th.label }}</p>
              <p class="text-xs text-muted-foreground">{{ th.name }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold transition-colors"
              :class="statusClass(th.status)">{{ t('themes.status.' + th.status) }}</span>
          </Link>
        </div>
        <p v-else class="text-sm text-muted-foreground py-4 text-center">{{ t('dashboard.no_themes') }}</p>
      </div>

      <!-- Recent plugins -->
      <div class="bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-foreground">{{ t('dashboard.recent_plugins') }}</h2>
        </div>
        <div v-if="recentPlugins.length" class="space-y-2">
          <Link v-for="pl in recentPlugins" :key="pl.uuid" :href="`/plugins/${pl.uuid}/edit`"
            class="flex items-center justify-between p-3 hover:bg-muted rounded-xl transition-colors group cursor-pointer">
            <div>
              <p class="text-sm font-medium text-foreground group-hover:text-indigo-500 transition-colors">{{ pl.label }}</p>
              <p class="text-xs text-muted-foreground">{{ pl.name }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold transition-colors"
              :class="statusClass(pl.status)">{{ t('plugins.status.' + pl.status) }}</span>
          </Link>
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
import { PaletteIcon, PuzzleIcon, ArrowRightIcon } from 'lucide-vue-next';

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
