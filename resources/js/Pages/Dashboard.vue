<template>
  <AppLayout :title="t('dashboard.title')">
    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <StatCard :label="t('dashboard.themes')"    :value="stats.themes"           icon="palette" href="/themes" />
      <StatCard :label="t('dashboard.plugins')"   :value="stats.plugins"          icon="puzzle" href="/plugins" />
      <!-- Temas publicados — preview directo de QUALQUER tema (sem abrir a edição) -->
      <div ref="publishedCard" class="relative bg-card border border-border rounded-2xl p-5 transition-all hover:border-success/40 hover:shadow-sm">
        <Link href="/themes?status=published" class="block group">
          <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mb-1.5 group-hover:text-foreground transition-colors">
            {{ t('themes.status.published') + ' ' + t('nav.themes') }}
          </p>
          <p class="text-3xl font-black text-success transition-all duration-300 group-hover:translate-x-0.5">{{ stats.published_themes }}</p>
        </Link>

        <!-- Botão que abre a lista de todos os temas publicados -->
        <button v-if="publishedThemes.length" type="button" @click="togglePublished"
                :title="t('dashboard.preview_theme')" :aria-label="t('dashboard.preview_theme')"
                :aria-expanded="showPublished"
                class="absolute top-3.5 right-3.5 inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold text-success bg-success/10 hover:bg-success hover:text-white transition-colors">
          <EyeIcon class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">{{ t('dashboard.preview') }}</span>
          <ChevronDownIcon class="w-3 h-3 transition-transform" :class="showPublished ? 'rotate-180' : ''" />
        </button>

        <!-- Dropdown: pesquisar + previsualizar qualquer tema publicado -->
        <div v-if="showPublished"
             class="absolute z-30 top-12 right-3.5 w-72 max-w-[calc(100vw-2rem)] bg-card border border-border rounded-xl shadow-lg p-2">
          <div v-if="publishedThemes.length > 6" class="mb-2">
            <input v-model="publishedFilter" type="text" :placeholder="t('dashboard.search_theme')"
                   class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-muted border border-border text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary/50" />
          </div>
          <div class="max-h-64 overflow-y-auto space-y-0.5">
            <a v-for="th in filteredPublishedThemes" :key="th.uuid"
               :href="`/preview/theme/${th.uuid}`" target="_blank" rel="noopener"
               class="flex items-center justify-between gap-2 px-2.5 py-2 rounded-lg hover:bg-success/10 group/item transition-colors">
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-medium text-foreground group-hover/item:text-success transition-colors truncate">{{ th.label }}</span>
                <span class="block text-[11px] text-muted-foreground truncate">{{ th.name }}</span>
              </span>
              <EyeIcon class="w-4 h-4 shrink-0 text-muted-foreground group-hover/item:text-success transition-colors" />
            </a>
            <p v-if="!filteredPublishedThemes.length" class="text-xs text-muted-foreground text-center py-3">
              {{ t('dashboard.no_match') }}
            </p>
          </div>
        </div>
      </div>
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
          <div v-for="th in recentThemes" :key="th.uuid"
            class="flex items-center justify-between gap-3 p-3 hover:bg-muted rounded-xl transition-colors group">
            <Link :href="`/themes/${th.uuid}/edit`" :title="t('dashboard.edit_theme')"
              class="min-w-0 flex-1 cursor-pointer">
              <p class="text-sm font-medium text-foreground group-hover:text-primary transition-colors truncate">{{ th.label }}</p>
              <p class="text-xs text-muted-foreground truncate">{{ th.name }}</p>
            </Link>
            <div class="flex items-center gap-2 shrink-0">
              <a :href="`/preview/theme/${th.uuid}`" target="_blank" rel="noopener"
                :title="t('dashboard.preview_theme')" :aria-label="t('dashboard.preview_theme')"
                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-muted-foreground hover:text-primary hover:bg-primary/10 transition-colors">
                <EyeIcon class="w-4 h-4" />
              </a>
              <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold transition-colors"
                :class="statusClass(th.status)">{{ t('themes.status.' + th.status) }}</span>
            </div>
          </div>
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import { PaletteIcon, PuzzleIcon, ArrowRightIcon, EyeIcon, ChevronDownIcon } from 'lucide-vue-next';

const { t } = useI18n();

const props = defineProps({
  stats:           { type: Object, default: () => ({}) },
  recentThemes:    { type: Array,  default: () => [] },
  recentPlugins:   { type: Array,  default: () => [] },
  publishedThemes: { type: Array,  default: () => [] },
});

// Dropdown de preview de qualquer tema publicado
const showPublished = ref(false);
const publishedFilter = ref('');
const publishedCard = ref(null);

const filteredPublishedThemes = computed(() => {
  const q = publishedFilter.value.trim().toLowerCase();
  if (!q) return props.publishedThemes;
  return props.publishedThemes.filter(th =>
    (th.label || '').toLowerCase().includes(q) || (th.name || '').toLowerCase().includes(q));
});

function togglePublished() {
  showPublished.value = !showPublished.value;
  if (!showPublished.value) publishedFilter.value = '';
}

function onDocClick(e) {
  if (showPublished.value && publishedCard.value && !publishedCard.value.contains(e.target)) {
    showPublished.value = false;
    publishedFilter.value = '';
  }
}

onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));

function statusClass(status) {
  return {
    draft:     'bg-muted text-muted-foreground',
    ready:     'bg-warning/20 text-warning',
    published: 'bg-success/20 text-success',
  }[status] ?? 'bg-muted text-muted-foreground';
}
</script>
