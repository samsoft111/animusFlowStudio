<template>
  <AppLayout :title="'⚡ Analytics de Receitas'">
    <template #actions>
      <Link :href="'/recipes'"
        class="px-4 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors">
        Voltar à Lista
      </Link>
    </template>

    <div class="space-y-6">
      <!-- Top Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-card border border-border rounded-2xl p-6 relative overflow-hidden">
          <div class="absolute right-0 top-0 translate-x-3 -translate-y-3 w-24 h-24 bg-primary/5 rounded-full blur-xl"></div>
          <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total de Execuções</p>
          <p class="text-3xl font-black text-foreground mt-1">{{ stats.total_hits }}</p>
          <p class="text-[10px] text-muted-foreground mt-2">Hits acumulados em todas as receitas locais.</p>
        </div>
        
        <div class="bg-card border border-border rounded-2xl p-6 relative overflow-hidden">
          <div class="absolute right-0 top-0 translate-x-3 -translate-y-3 w-24 h-24 bg-success/5 rounded-full blur-xl"></div>
          <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Tokens Poupados Estimados</p>
          <p class="text-3xl font-black text-success mt-1">{{ formatTokens(stats.total_tokens_saved) }}</p>
          <p class="text-[10px] text-muted-foreground mt-2">Baseado em ~200 tokens por execução local bem-sucedida.</p>
        </div>

        <div class="bg-card border border-border rounded-2xl p-6 relative overflow-hidden">
          <div class="absolute right-0 top-0 translate-x-3 -translate-y-3 w-24 h-24 bg-warning/5 rounded-full blur-xl"></div>
          <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Eficiência da IA</p>
          <p class="text-3xl font-black text-foreground mt-1">{{ stats.active_count }} ativas</p>
          <p class="text-[10px] text-muted-foreground mt-2">Receitas ativas prontas para match e bypass de IA.</p>
        </div>
      </div>

      <!-- Main Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Top 10 Most Used Recipes -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h3 class="font-bold text-foreground text-sm flex items-center gap-2">🔥 Top 10 Receitas Mais Usadas</h3>
          
          <div v-if="top_recipes.length" class="space-y-3">
            <div v-for="(recipe, index) in top_recipes" :key="recipe.name" class="space-y-1">
              <div class="flex justify-between items-center text-xs">
                <span class="font-bold text-foreground truncate max-w-[250px]">{{ index + 1 }}. {{ recipe.name }}</span>
                <span class="font-mono text-muted-foreground">{{ recipe.hits }} hits ({{ formatTokens(recipe.tokens_saved) }} saved)</span>
              </div>
              <div class="w-full bg-muted rounded-full h-2 overflow-hidden">
                <div class="bg-primary h-full rounded-full transition-all duration-500" 
                     :style="{ width: getPercentage(recipe.hits) + '%' }"></div>
              </div>
            </div>
          </div>
          <p v-else class="text-xs text-muted-foreground py-6 text-center">Nenhuma receita foi executada ainda.</p>
        </div>

        <!-- Recent Activity Log -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h3 class="font-bold text-foreground text-sm flex items-center gap-2">⏱️ Atividade Recente</h3>
          
          <div v-if="recent_used.length" class="divide-y divide-border">
            <div v-for="log in recent_used" :key="log.name" class="py-3 first:pt-0 last:pb-0 flex justify-between items-center">
              <div>
                <p class="text-xs font-bold text-foreground">{{ log.name }}</p>
                <p class="text-[10px] text-muted-foreground">Executado {{ log.hits }} vezes</p>
              </div>
              <span class="text-[10px] text-muted-foreground font-mono">{{ formatDate(log.last_used_at) }}</span>
            </div>
          </div>
          <p v-else class="text-xs text-muted-foreground py-6 text-center">Nenhuma atividade recente.</p>
        </div>

      </div>

      <!-- Unused Recipes -->
      <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
        <h3 class="font-bold text-foreground text-sm flex items-center gap-2">💤 Receitas Não Utilizadas (Hits = 0)</h3>
        
        <div v-if="unused_recipes.length" class="overflow-x-auto">
          <table class="w-full border-collapse text-left text-xs">
            <thead>
              <tr class="border-b border-border bg-muted/40 text-muted-foreground uppercase font-bold tracking-wider">
                <th class="p-3">Nome</th>
                <th class="p-3">Tipo</th>
                <th class="p-3 text-center">Confiança</th>
                <th class="p-3 text-right">Ação</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="recipe in unused_recipes" :key="recipe.id" class="hover:bg-muted/10">
                <td class="p-3 font-bold text-foreground">{{ recipe.name }}</td>
                <td class="p-3">
                  <span class="px-2 py-0.5 rounded bg-muted text-muted-foreground uppercase text-[9px] font-semibold">
                    {{ recipe.recipe_type }}
                  </span>
                </td>
                <td class="p-3 text-center">
                  <span class="px-2 py-0.5 rounded font-bold text-[9px]"
                    :class="recipe.confidence_score >= 70 ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'">
                    {{ recipe.confidence_score }}%
                  </span>
                </td>
                <td class="p-3 text-right">
                  <Link :href="`/recipes/${recipe.id}/edit`" class="text-primary hover:underline font-semibold">Editar</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="text-xs text-success font-semibold py-4 text-center">Incrível! Todas as suas receitas registadas já foram utilizadas pelo menos uma vez.</p>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  stats: { type: Object, required: true },
  top_recipes: { type: Array, default: () => [] },
  unused_recipes: { type: Array, default: () => [] },
  recent_used: { type: Array, default: () => [] },
});

const maxHits = computed(() => {
  if (!props.top_recipes.length) return 1;
  return Math.max(...props.top_recipes.map(r => r.hits), 1);
});

function getPercentage(hits) {
  return (hits / maxHits.value) * 100;
}

function formatTokens(val) {
  if (!val) return '0 T';
  if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
  if (val >= 1000) return (val / 1000).toFixed(1) + 'k';
  return val + ' T';
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('pt-PT') + ' ' + date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
}
</script>

<style scoped>
@reference "../../../css/app.css";
</style>
