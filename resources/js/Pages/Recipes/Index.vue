<template>
  <AppLayout :title="'⚡ Receitas IA'">
    <template #actions>
      <div class="flex items-center gap-2">
        <Link :href="'/recipes/analytics'"
          class="flex items-center gap-1.5 px-3 py-2 bg-card border border-border text-foreground rounded-lg text-sm font-semibold hover:bg-muted transition-colors">
          <BarChart3Icon class="w-4 h-4 text-primary" />
          Analytics
        </Link>
        <button @click="triggerImport"
          class="flex items-center gap-1.5 px-3 py-2 bg-card border border-border text-foreground rounded-lg text-sm font-semibold hover:bg-muted transition-colors">
          <UploadIcon class="w-4 h-4" />
          Importar (.afrecipes)
        </button>
        <button @click="exportSelected"
          class="flex items-center gap-1.5 px-3 py-2 bg-card border border-border text-foreground rounded-lg text-sm font-semibold hover:bg-muted transition-colors">
          <DownloadIcon class="w-4 h-4" />
          Exportar
        </button>
        <Link :href="'/recipes/create'"
          class="flex items-center gap-1.5 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:opacity-90 transition-opacity">
          + Nova Receita
        </Link>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Hidden file input for import -->
      <input type="file" ref="fileInput" @change="handleFileImport" accept=".afrecipes,application/json" class="hidden" />

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-card border border-border rounded-2xl p-5 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <ZapIcon class="w-6 h-6 text-primary" />
          </div>
          <div>
            <p class="text-xs text-muted-foreground font-semibold uppercase tracking-wider">Total de Hits</p>
            <p class="text-2xl font-black text-foreground mt-0.5">{{ stats.total_hits }}</p>
          </div>
        </div>
        <div class="bg-card border border-border rounded-2xl p-5 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
            <CpuIcon class="w-6 h-6 text-success" />
          </div>
          <div>
            <p class="text-xs text-muted-foreground font-semibold uppercase tracking-wider">Tokens Poupados (Est.)</p>
            <p class="text-2xl font-black text-foreground mt-0.5">{{ formatTokens(stats.total_tokens_saved) }}</p>
          </div>
        </div>
        <div class="bg-card border border-border rounded-2xl p-5 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
            <ToggleRightIcon class="w-6 h-6 text-warning" />
          </div>
          <div>
            <p class="text-xs text-muted-foreground font-semibold uppercase tracking-wider">Receitas Ativas</p>
            <p class="text-2xl font-black text-foreground mt-0.5">{{ stats.active_count }} / {{ recipes.length }}</p>
          </div>
        </div>
      </div>

      <!-- Filters & Bulk selection bar -->
      <div class="bg-card border border-border rounded-2xl p-4 flex flex-col md:flex-row gap-4 items-center justify-between">
        <!-- Search & Filter form -->
        <div class="flex flex-1 flex-col sm:flex-row gap-3 w-full">
          <div class="relative flex-1">
            <SearchIcon class="w-4 h-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2" />
            <input v-model="filterForm.q" @input="debouncedSearch" type="text"
              placeholder="Pesquisar por nome, prompt ou descrição..."
              class="w-full pl-9 pr-4 py-2 bg-muted border border-border rounded-xl text-sm focus:outline-none focus:border-primary" />
          </div>
          <select v-model="filterForm.type" @change="debouncedSearch"
            class="px-3 py-2 bg-muted border border-border rounded-xl text-sm focus:outline-none focus:border-primary shrink-0 min-w-[150px]">
            <option value="">Todos os tipos</option>
            <option value="theme">Tema (theme)</option>
            <option value="plugin">Plugin (plugin)</option>
          </select>
        </div>

        <!-- Selected indicator -->
        <div v-if="selectedIds.length" class="flex items-center gap-3 shrink-0">
          <span class="text-xs text-muted-foreground">{{ selectedIds.length }} selecionadas</span>
          <button @click="clearSelection" class="text-xs text-primary hover:underline">Limpar</button>
        </div>
      </div>

      <!-- Recipes list -->
      <div class="bg-card border border-border rounded-2xl overflow-hidden">
        <div v-if="recipes.length" class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr class="border-b border-border bg-muted/30 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                <th class="p-4 w-10">
                  <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="rounded border-border text-primary focus:ring-primary cursor-pointer" />
                </th>
                <th class="p-4">Receita</th>
                <th class="p-4">Padrão / Tipo</th>
                <th class="p-4 text-center">Confiança</th>
                <th class="p-4 text-center">Fuzzy Match</th>
                <th class="p-4 text-center">Hits / Tokens</th>
                <th class="p-4">Último Uso</th>
                <th class="p-4 text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border text-sm text-foreground">
              <tr v-for="recipe in recipes" :key="recipe.id" class="hover:bg-muted/10 transition-colors">
                <td class="p-4">
                  <input type="checkbox" :checked="selectedIds.includes(recipe.id)" @change="toggleSelect(recipe.id)" class="rounded border-border text-primary focus:ring-primary cursor-pointer" />
                </td>
                <td class="p-4 max-w-[200px]">
                  <div class="font-bold truncate text-foreground" :title="recipe.name">{{ recipe.name }}</div>
                  <div class="text-xs text-muted-foreground truncate" :title="recipe.description">{{ recipe.description || 'Sem descrição' }}</div>
                </td>
                <td class="p-4">
                  <div class="font-mono text-xs max-w-[300px] truncate bg-muted px-2 py-1 rounded border border-border" :title="recipe.prompt_pattern">
                    {{ recipe.prompt_pattern }}
                  </div>
                  <div class="flex items-center gap-1.5 mt-1.5">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                      :class="recipe.recipe_type === 'theme' ? 'bg-indigo-500/10 text-indigo-400' : 'bg-emerald-500/10 text-emerald-400'">
                      {{ recipe.recipe_type }}
                    </span>
                    <span v-if="Object.keys(recipe.placeholder_types || {}).length" class="px-1.5 py-0.5 bg-muted text-muted-foreground border border-border rounded text-[9px] font-mono">
                      {{ Object.keys(recipe.placeholder_types).length }} placeholders validados
                    </span>
                  </div>
                </td>
                <td class="p-4 text-center">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="confidenceClass(recipe.confidence_score)">
                    {{ recipe.confidence_score }}%
                  </span>
                </td>
                <td class="p-4 text-center">
                  <span class="text-xs font-semibold text-muted-foreground font-mono">
                    {{ recipe.fuzzy_threshold || 80 }}%
                  </span>
                </td>
                <td class="p-4 text-center">
                  <div class="font-bold">{{ recipe.hits }}</div>
                  <div class="text-[10px] text-muted-foreground font-semibold uppercase">{{ formatTokens(recipe.tokens_saved) }}</div>
                </td>
                <td class="p-4 text-xs text-muted-foreground">
                  {{ recipe.last_used_at ? formatDate(recipe.last_used_at) : 'Nunca usado' }}
                </td>
                <td class="p-4 text-right">
                  <div class="flex justify-end items-center gap-1.5">
                    <!-- Enable/Disable Toggle -->
                    <button @click="toggleEnabled(recipe.id)"
                      :title="recipe.is_enabled ? 'Desativar' : 'Ativar'"
                      class="p-1.5 rounded-lg hover:bg-muted text-muted-foreground transition-colors"
                      :class="{ 'text-success': recipe.is_enabled }">
                      <ToggleLeftIcon v-if="!recipe.is_enabled" class="w-5 h-5" />
                      <ToggleRightIcon v-else class="w-5 h-5 text-primary" />
                    </button>

                    <!-- Test Button -->
                    <button @click="openTestModal(recipe)"
                      title="Testar Prompt"
                      class="p-1.5 rounded-lg hover:bg-muted text-muted-foreground hover:text-foreground transition-colors">
                      <PlayIcon class="w-4 h-4" />
                    </button>

                    <!-- Edit -->
                    <Link :href="`/recipes/${recipe.id}/edit`"
                      title="Editar"
                      class="p-1.5 rounded-lg hover:bg-muted text-muted-foreground hover:text-foreground transition-colors">
                      <EditIcon class="w-4 h-4" />
                    </Link>

                    <!-- Delete -->
                    <button @click="deleteRecipe(recipe)"
                      title="Eliminar"
                      class="p-1.5 rounded-lg hover:bg-muted text-destructive hover:bg-destructive/10 transition-colors">
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-20 text-center">
          <ZapIcon class="w-12 h-12 text-muted-foreground opacity-30 mb-4" />
          <h2 class="text-lg font-semibold text-foreground mb-2">Nenhuma Receita Encontrada</h2>
          <p class="text-sm text-muted-foreground mb-6">Crie uma receita manualmente ou deixe que a IA as registe automaticamente.</p>
          <Link :href="'/recipes/create'" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold">
            Nova Receita
          </Link>
        </div>
      </div>
    </div>

    <!-- ── Test Modal ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showTestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showTestModal = false" />
          <div class="relative bg-card border border-border rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
            <h3 class="font-semibold text-foreground">Testar Receita: <span class="text-primary">{{ testingRecipe?.name }}</span></h3>
            <p class="text-xs text-muted-foreground">Insira um prompt de teste para ver se a receita faz match e extrai os valores corretamente.</p>

            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Padrão da Receita</label>
              <div class="p-2.5 bg-muted rounded-lg font-mono text-xs border border-border select-all">{{ testingRecipe?.prompt_pattern }}</div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Prompt de Teste</label>
              <input v-model="testPrompt" type="text"
                placeholder="ex: mudar a cor principal para #ff00ff"
                @keyup.enter="runTest"
                class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
            </div>

            <!-- Loader or Result -->
            <div v-if="testLoading" class="flex justify-center py-4">
              <div class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else-if="testResult" class="space-y-3">
              <div class="p-3 rounded-xl border text-xs font-semibold"
                :class="testResult.success ? 'bg-success/10 border-success/30 text-success' : 'bg-destructive/10 border-destructive/30 text-destructive'">
                <p v-if="testResult.success">
                  Match com sucesso!
                  <span v-if="testResult.fuzzy" class="ml-1 text-[10px] uppercase font-bold bg-success/20 px-1.5 py-0.5 rounded">Fuzzy (Similidade: {{ testResult.similarity }}%)</span>
                  <span v-else class="ml-1 text-[10px] uppercase font-bold bg-success/20 px-1.5 py-0.5 rounded">Exact Match</span>
                </p>
                <p v-else>Falha no match: {{ testResult.error }}</p>
              </div>

              <!-- Match details -->
              <div v-if="testResult.success" class="space-y-3">
                <div v-if="testResult.reply" class="bg-muted p-3 border border-border rounded-xl space-y-1">
                  <span class="text-[10px] text-muted-foreground font-semibold uppercase">Resposta Gerada</span>
                  <p class="text-sm font-medium">{{ testResult.reply }}</p>
                </div>
                <div v-if="testResult.updates && Object.keys(testResult.updates).length" class="bg-muted p-3 border border-border rounded-xl space-y-1">
                  <span class="text-[10px] text-muted-foreground font-semibold uppercase">Modificações de Código (JSON)</span>
                  <pre class="text-xs font-mono text-foreground p-1 max-h-40 overflow-y-auto">{{ JSON.stringify(testResult.updates, null, 2) }}</pre>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-border">
              <button @click="showTestModal = false" class="px-4 py-2 bg-muted rounded-lg text-sm font-semibold">Fechar</button>
              <button @click="runTest" :disabled="!testPrompt.trim() || testLoading"
                class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold disabled:opacity-50">
                Testar
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  ZapIcon,
  SearchIcon,
  DownloadIcon,
  UploadIcon,
  CpuIcon,
  ToggleLeftIcon,
  ToggleRightIcon,
  PlayIcon,
  EditIcon,
  TrashIcon,
  BarChart3Icon,
} from 'lucide-vue-next';

const props = defineProps({
  recipes: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({ q: '', type: '' }) },
  stats: { type: Object, default: () => ({ total_hits: 0, total_tokens_saved: 0, active_count: 0 }) }
});

// ── Search & Filter ──
const filterForm = ref({
  q: props.filters.q || '',
  type: props.filters.type || ''
});

let debounceTimer = null;
function debouncedSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    router.get('/recipes', filterForm.value, {
      preserveState: true,
      preserveScroll: true,
      replace: true
    });
  }, 300);
}

// ── Bulk Selection ──
const selectedIds = ref([]);
const isAllSelected = computed(() => {
  return props.recipes.length > 0 && selectedIds.value.length === props.recipes.length;
});

function toggleSelectAll(e) {
  if (e.target.checked) {
    selectedIds.value = props.recipes.map(r => r.id);
  } else {
    selectedIds.value = [];
  }
}

function toggleSelect(id) {
  const index = selectedIds.value.indexOf(id);
  if (index > -1) {
    selectedIds.value.splice(index, 1);
  } else {
    selectedIds.value.push(id);
  }
}

function clearSelection() {
  selectedIds.value = [];
}

// ── Actions ──
function toggleEnabled(id) {
  router.post(`/recipes/${id}/toggle`, {}, {
    preserveScroll: true
  });
}

function deleteRecipe(recipe) {
  if (!confirm(`Tem a certeza que deseja eliminar a receita "${recipe.name}"?`)) return;
  router.delete(`/recipes/${recipe.id}`);
}

function exportSelected() {
  const params = selectedIds.value.length ? `?ids[]=${selectedIds.value.join('&ids[]=')}` : '';
  window.open(`/recipes/export${params}`, '_blank');
}

const fileInput = ref(null);
function triggerImport() {
  fileInput.value?.click();
}

function handleFileImport(event) {
  const file = event.target.files?.[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('file', file);

  router.post('/recipes/import', formData, {
    forceFormData: true,
    onFinish: () => {
      if (fileInput.value) fileInput.value.value = '';
    }
  });
}

// ── Test Recipe Modal ──
const showTestModal = ref(false);
const testingRecipe = ref(null);
const testPrompt = ref('');
const testLoading = ref(false);
const testResult = ref(null);

function openTestModal(recipe) {
  testingRecipe.value = recipe;
  testPrompt.value = '';
  testResult.value = null;
  showTestModal.value = true;
}

async function runTest() {
  if (!testPrompt.value.trim() || testLoading.value) return;
  try {
    testLoading.value = true;
    testResult.value = null;

    const response = await fetch(`/recipes/${testingRecipe.value.id}/test`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ prompt: testPrompt.value }),
    });

    if (!response.ok) throw new Error('Falha na resposta do servidor.');

    testResult.value = await response.json();
  } catch (e) {
    testResult.value = { success: false, error: 'Ocorreu um erro no teste: ' + e.message };
  } finally {
    testLoading.value = false;
  }
}

// ── Helpers ──
function formatTokens(val) {
  if (!val) return '0 T';
  if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
  if (val >= 1000) return (val / 1000).toFixed(1) + 'k';
  return val + ' T';
}

function confidenceClass(score) {
  if (score >= 90) return 'bg-success/15 text-success';
  if (score >= 70) return 'bg-warning/15 text-warning';
  return 'bg-destructive/15 text-destructive';
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('pt-PT') + ' ' + date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
}
</script>

<style scoped>
@reference "../../../css/app.css";

/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
