<template>
  <AppLayout :title="recipe.id ? 'Editar Receita' : 'Nova Receita'">
    <template #actions>
      <Link :href="'/recipes'"
        class="px-4 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors">
        Cancelar
      </Link>
    </template>

    <div class="space-y-6">
      <form @submit.prevent="submit" class="space-y-6">
        
        <!-- General info card -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground flex items-center gap-2">⚡ Identidade da Receita</h2>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="field-label">Tipo de Receita</label>
              <select v-model="form.recipe_type" class="field-input">
                <option value="theme">Tema (theme)</option>
                <option value="plugin">Plugin (plugin)</option>
              </select>
            </div>
            <div>
              <label class="field-label">Nome Único (Slug)</label>
              <input v-model="form.name" placeholder="ex: mudar-cor-principal" class="field-input font-mono" required />
              <p v-if="form.errors.name" class="text-xs text-destructive mt-1">{{ form.errors.name }}</p>
            </div>
          </div>

          <div>
            <label class="field-label">Descrição</label>
            <input v-model="form.description" placeholder="Descreva brevemente o que esta receita faz..." class="field-input" />
            <p v-if="form.errors.description" class="text-xs text-destructive mt-1">{{ form.errors.description }}</p>
          </div>
        </div>

        <!-- Pattern matcher card -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground flex items-center gap-2">🔍 Correspondência de Prompt</h2>
          
          <div>
            <label class="field-label">Padrão de Prompt (Use {placeholder})</label>
            <textarea v-model="form.prompt_pattern" rows="2"
              placeholder="ex: mudar a cor principal para {cor}"
              class="field-input font-mono" required />
            <p class="field-hint">Defina o padrão textual com variáveis entre chavetas. Exemplo: <code>mudar o título para {titulo}</code>.</p>
            <p v-if="form.errors.prompt_pattern" class="text-xs text-destructive mt-1">{{ form.errors.prompt_pattern }}</p>
          </div>

          <!-- Detected placeholders settings -->
          <div v-if="detectedPlaceholders.length" class="p-4 bg-muted/40 border border-border rounded-xl space-y-3">
            <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Configuração de Variáveis Detetadas</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div v-for="name in detectedPlaceholders" :key="name" class="flex flex-col gap-1 p-2.5 bg-card border rounded-lg">
                <span class="text-xs font-mono font-bold text-primary">{{ '{' + name + '}' }}</span>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-[10px] text-muted-foreground shrink-0">Tipo:</span>
                  <select v-model="form.placeholder_types[name]" class="text-xs bg-muted border border-border rounded p-1 flex-1">
                    <option value="text">Texto livre</option>
                    <option value="color">Cor Hex (#fff ou #ffffff)</option>
                    <option value="url">URL Válida</option>
                    <option value="number">Número</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Fuzzy threshold and confidence score -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
            <div>
              <label class="field-label flex justify-between">
                <span>Limite de Fuzzy Match</span>
                <span class="text-primary font-mono font-bold">{{ form.fuzzy_threshold }}%</span>
              </label>
              <input v-model="form.fuzzy_threshold" type="range" min="50" max="100" step="5" class="w-full accent-primary mt-1" />
              <p class="text-[10px] text-muted-foreground mt-1">Percentagem mínima de similaridade (PHP similar_text) para fazer match fuzzy.</p>
            </div>

            <div>
              <label class="field-label flex justify-between">
                <span>Pontuação de Confiança</span>
                <span class="text-primary font-mono font-bold">{{ form.confidence_score }}%</span>
              </label>
              <input v-model="form.confidence_score" type="range" min="0" max="100" class="w-full accent-primary mt-1" />
              <p class="text-[10px] text-muted-foreground mt-1">Nível de certeza atribuído. Abaixo de 70%, a receita não será auto-executada.</p>
            </div>
          </div>
        </div>

        <!-- Templates card -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground flex items-center gap-2">🛠️ Resposta & Código</h2>

          <div>
            <label class="field-label">Template de Resposta do Assistente</label>
            <textarea v-model="form.reply_template" rows="2"
              placeholder="ex: Mudei a cor principal do tema para {{cor}} com sucesso!"
              class="field-input" required />
            <p class="field-hint">A resposta que a IA enviará de volta ao utilizador. Use <code>{{variavel}}</code> para injetar valores extraídos.</p>
            <p v-if="form.errors.reply_template" class="text-xs text-destructive mt-1">{{ form.errors.reply_template }}</p>
          </div>

          <div>
            <label class="field-label flex justify-between">
              <span>Modificações de Código (Templates JSON)</span>
              <span v-if="jsonError" class="text-destructive font-mono text-[10px]">{{ jsonError }}</span>
              <span v-else class="text-success font-semibold text-[10px]">✓ JSON Válido</span>
            </label>
            <textarea v-model="codeTemplatesJson" rows="8"
              class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-xs font-mono focus:outline-none focus:border-primary"
              placeholder='{
  "colors": {
    "light": {
      "--color-primary": "{{cor}}"
    }
  }
}' />
            <p class="field-hint">Os campos e valores que serão mesclados no tema ou plugin. Use a sintaxe <code>{{variavel}}</code>.</p>
            <p v-if="form.errors.code_templates" class="text-xs text-destructive mt-1">{{ form.errors.code_templates }}</p>
          </div>
        </div>

        <!-- Controls card -->
        <div class="bg-card border border-border rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h3 class="font-semibold text-foreground">Estado Ativo</h3>
            <p class="text-xs text-muted-foreground">Desative esta receita para impedir que ela seja selecionada nos matches da IA.</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer shrink-0">
            <input type="checkbox" v-model="form.is_enabled" class="sr-only peer" />
            <div class="w-11 h-6 bg-border peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
          </label>
        </div>

        <!-- Buttons -->
        <div class="flex items-center gap-3">
          <button type="submit" :disabled="form.processing || !!jsonError"
            class="flex-1 py-3 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50 transition-opacity flex items-center justify-center gap-2">
            <div v-if="form.processing" class="w-4 h-4 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
            Salvar Receita
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  recipe: { type: Object, required: true }
});

const form = useForm({
  recipe_type: props.recipe.recipe_type || 'theme',
  name: props.recipe.name || '',
  description: props.recipe.description || '',
  prompt_pattern: props.recipe.prompt_pattern || '',
  code_templates: props.recipe.code_templates || {},
  reply_template: props.recipe.reply_template || '',
  confidence_score: props.recipe.confidence_score !== undefined ? props.recipe.confidence_score : 100,
  is_enabled: props.recipe.is_enabled !== undefined ? props.recipe.is_enabled : true,
  fuzzy_threshold: props.recipe.fuzzy_threshold !== undefined ? props.recipe.fuzzy_threshold : 80,
  placeholder_types: props.recipe.placeholder_types || {}
});

// JSON raw textarea state
const codeTemplatesJson = ref(JSON.stringify(form.code_templates, null, 2));
const jsonError = ref('');

watch(codeTemplatesJson, (val) => {
  if (!val.trim()) {
    form.code_templates = {};
    jsonError.value = '';
    return;
  }
  try {
    const parsed = JSON.parse(val);
    if (parsed && typeof parsed === 'object') {
      form.code_templates = parsed;
      jsonError.value = '';
    } else {
      jsonError.value = 'O JSON deve ser um objeto ou array.';
    }
  } catch (e) {
    jsonError.value = 'JSON Inválido: ' + e.message;
  }
}, { immediate: true });

// Live placeholder extraction
const detectedPlaceholders = computed(() => {
  const pattern = form.prompt_pattern || '';
  const regex = /\{([a-zA-Z0-9_]+)\}/g;
  const matches = [];
  let match;
  while ((match = regex.exec(pattern)) !== null) {
    if (!matches.includes(match[1])) {
      matches.push(match[1]);
    }
  }
  return matches;
});

// Sync placeholders list with configuration map
watch(detectedPlaceholders, (newList) => {
  const types = { ...form.placeholder_types };
  let modified = false;

  // Add new ones
  newList.forEach(name => {
    if (!types[name]) {
      types[name] = 'text';
      modified = true;
    }
  });

  // Remove missing ones
  Object.keys(types).forEach(name => {
    if (!newList.includes(name)) {
      delete types[name];
      modified = true;
    }
  });

  if (modified) {
    form.placeholder_types = types;
  }
}, { deep: true, immediate: true });

function submit() {
  if (jsonError.value) return;

  if (props.recipe.id) {
    form.put(`/recipes/${props.recipe.id}`);
  } else {
    form.post('/recipes');
  }
}
</script>

<style scoped>
@reference "../../../css/app.css";

.field-label {
  @apply block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5;
}
.field-input {
  @apply w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary;
}
.field-hint {
  @apply text-xs text-muted-foreground mt-1;
}
</style>
