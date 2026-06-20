<template>
  <AppLayout :title="t('themes.title')">
    <template #actions>
      <div class="flex gap-1 sm:gap-2 flex-wrap justify-end items-center">
        <button @click="showInspireModal = true"
          class="px-2 sm:px-4 py-2 bg-gradient-to-r from-violet-500 to-indigo-500 text-white rounded-lg text-xs sm:text-sm font-semibold hover:opacity-90 transition-opacity flex items-center gap-1 sm:gap-1.5">
          <SparklesIcon class="w-4 h-4 shrink-0" />
          <span class="hidden sm:inline">Inspiração por Categoria</span>
          <span class="sm:hidden">Inspirar</span>
        </button>
        <button @click="openCreate"
          class="px-2 sm:px-4 py-2 bg-primary text-primary-foreground rounded-lg text-xs sm:text-sm font-semibold hover:opacity-90 transition-opacity">
          + <span class="hidden sm:inline">{{ t('themes.new') }}</span><span class="sm:hidden">Novo</span>
        </button>
      </div>
    </template>

    <!-- Themes grid -->
    <div v-if="themes.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div v-for="theme in themes" :key="theme.uuid"
        class="group bg-card border border-border hover:border-primary/50 rounded-2xl p-5 flex flex-col justify-between shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 gap-4">

        <!-- Preview -->
        <div class="w-full h-32 rounded-xl bg-muted overflow-hidden relative border border-border/40 shrink-0">
          <img v-if="theme.preview_url" :src="theme.preview_url" :alt="theme.label"
            class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-500" />
          <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground">
            <PaletteIcon class="w-8 h-8 opacity-30 text-primary group-hover:scale-110 transition-transform duration-500" />
          </div>
        </div>

        <div class="flex-1">
          <div class="flex items-center justify-between gap-2 flex-wrap mb-1">
            <h3 class="font-bold text-foreground text-sm truncate" :title="theme.label">{{ theme.label }}</h3>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold shrink-0 capitalize shadow-sm" :class="statusClass(theme.status)">
              <span v-if="theme.status === 'published'" class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse" />
              <span v-else-if="theme.status === 'ready'" class="w-1 h-1 rounded-full bg-amber-500" />
              <span v-else class="w-1 h-1 rounded-full bg-muted-foreground" />
              {{ t('themes.status.' + theme.status) }}
            </span>
          </div>
          <p class="text-xs text-muted-foreground truncate">{{ theme.name }} — v{{ theme.version }}</p>
        </div>

        <div class="flex gap-2 border-t border-border/50 pt-3 mt-auto">
          <Link :href="`/themes/${theme.uuid}/edit`"
            class="flex-1 px-3 py-2 bg-muted hover:bg-border text-foreground rounded-lg text-xs font-semibold text-center transition-colors cursor-pointer">
            {{ t('common.edit') }}
          </Link>
          <a :href="`/themes/${theme.uuid}/export`"
            class="px-3 py-2 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-lg text-xs font-semibold transition-all duration-300 cursor-pointer">
            {{ t('common.export') }}
          </a>
          <button @click="deleteTheme(theme)"
            class="px-3 py-2 bg-destructive/10 text-destructive hover:bg-destructive hover:text-white rounded-lg text-xs font-semibold transition-all duration-300 cursor-pointer">
            {{ t('common.delete') }}
          </button>
        </div>
      </div>
    </div>

    <div v-else class="flex flex-col items-center justify-center py-20 text-center">
      <PaletteIcon class="w-12 h-12 text-muted-foreground opacity-30 mb-4" />
      <h2 class="text-lg font-semibold text-foreground mb-2">{{ t('themes.no_themes') }}</h2>
      <p class="text-sm text-muted-foreground mb-6">{{ t('themes.no_themes_desc') }}</p>
      <div class="flex gap-3">
        <button @click="showInspireModal = true"
          class="px-5 py-2.5 bg-gradient-to-r from-violet-500 to-indigo-500 text-white rounded-xl text-sm font-semibold flex items-center gap-2 cursor-pointer">
          <SparklesIcon class="w-4 h-4" /> Gerar por Categoria
        </button>
        <button @click="openCreate" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold cursor-pointer">
          {{ t('themes.new') }}
        </button>
      </div>
    </div>

    <!-- ── Create Modal ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCreateModal = false" />
          <div class="relative bg-card border border-border rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="font-semibold text-foreground mb-1">Novo Tema</h3>
            <p class="text-xs text-muted-foreground mb-4">Dá um nome ao teu tema. Podes alterá-lo depois nos detalhes.</p>
            <input ref="createInput" v-model="createForm.label" type="text"
              placeholder="ex: Restaurante Moderno"
              @keyup.enter="submitCreate"
              class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
            <p v-if="createForm.errors.label" class="text-xs text-destructive mt-1">{{ createForm.errors.label }}</p>
            <div class="flex justify-end gap-2 mt-5">
              <button @click="showCreateModal = false" class="px-4 py-2 bg-muted rounded-lg text-sm font-semibold cursor-pointer">Cancelar</button>
              <button @click="submitCreate" :disabled="createForm.processing || !createForm.label.trim()"
                class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold disabled:opacity-50 cursor-pointer">
                {{ createForm.processing ? 'A criar…' : 'Criar tema' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Inspire Modal ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showInspireModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <!-- Backdrop -->
          <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeInspireModal" />

          <!-- Panel -->
          <div class="relative w-full max-w-3xl bg-card border border-border rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-border bg-gradient-to-r from-violet-500/10 to-indigo-500/10">
              <div>
                <h2 class="text-lg font-bold text-foreground flex items-center gap-2">
                  <SparklesIcon class="w-5 h-5 text-violet-500" />
                  Inspiração por Categoria
                </h2>
                <p class="text-xs text-muted-foreground mt-0.5">Selecciona uma categoria e o estilo — a IA cria um tema completo como base</p>
              </div>
              <button @click="closeInspireModal" class="p-1.5 rounded-lg hover:bg-muted transition-colors text-muted-foreground">
                <XIcon class="w-5 h-5" />
              </button>
            </div>

            <!-- Body — Step 1: Select category + style -->
            <div v-if="inspireStep === 'select'" class="flex-1 overflow-y-auto p-6">
              <!-- Category grid -->
              <p class="text-sm font-semibold text-foreground mb-3">Categoria do site</p>
              <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 mb-6 max-h-64 overflow-y-auto pr-1">
                <button v-for="cat in categories" :key="cat.id"
                  @click="selectedCategory = cat.id"
                  :class="[
                    'flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition-all text-center cursor-pointer',
                    selectedCategory === cat.id
                      ? 'border-violet-500 bg-violet-500/10'
                      : 'border-border bg-muted/40 hover:border-border/80 hover:bg-muted/60'
                  ]">
                  <span class="text-2xl">{{ cat.emoji }}</span>
                  <span class="text-[11px] font-medium text-foreground leading-tight">{{ cat.label }}</span>
                </button>
              </div>

              <!-- Style selector -->
              <p class="text-sm font-semibold text-foreground mb-3">Estilo visual</p>
              <div class="flex flex-wrap gap-2 mb-6">
                <button v-for="s in styles" :key="s.id"
                  @click="selectedStyle = s.id"
                  :class="[
                    'px-4 py-2 rounded-xl border-2 text-sm font-medium transition-all',
                    selectedStyle === s.id
                      ? 'border-violet-500 bg-violet-500/10 text-violet-600'
                      : 'border-border bg-muted/40 text-muted-foreground hover:border-border/80'
                  ]">
                  {{ s.emoji }} {{ s.label }}
                </button>
              </div>

              <!-- Generate button -->
              <button @click="generateInspiration"
                :disabled="!selectedCategory"
                class="w-full py-3 bg-gradient-to-r from-violet-500 to-indigo-500 text-white rounded-xl font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
                <SparklesIcon class="w-4 h-4" />
                Gerar Inspiração ✨
              </button>
            </div>

            <!-- Body — Step 2: Loading -->
            <div v-else-if="inspireStep === 'loading'" class="flex-1 flex flex-col items-center justify-center py-16 gap-4">
              <div class="w-16 h-16 rounded-full bg-violet-500/10 flex items-center justify-center animate-pulse">
                <SparklesIcon class="w-8 h-8 text-violet-500" />
              </div>
              <p class="text-sm text-muted-foreground text-center">
                A IA está a pesquisar referências para <strong>{{ categoryLabel }}</strong>...<br />
                <span class="text-xs">Isto pode demorar alguns segundos</span>
              </p>
              <div class="flex gap-1 mt-2">
                <div v-for="i in 3" :key="i" class="w-2 h-2 rounded-full bg-violet-400 animate-bounce" :style="`animation-delay:${(i-1)*0.15}s`"></div>
              </div>
            </div>

            <!-- Body — Step 3: Result preview -->
            <div v-else-if="inspireStep === 'result'" class="flex-1 overflow-y-auto">
              <!-- Theme info bar -->
              <div class="px-6 py-4 border-b border-border bg-muted/30">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <h3 class="font-bold text-foreground">{{ inspireResult.label }}</h3>
                    <p class="text-xs text-muted-foreground mt-0.5 line-clamp-2">{{ inspireResult.inspiration }}</p>
                  </div>
                  <div class="flex gap-2 shrink-0">
                    <button @click="generateInspiration"
                      class="px-3 py-1.5 border border-border rounded-lg text-xs font-medium text-muted-foreground hover:bg-muted transition-colors flex items-center gap-1">
                      <RefreshCwIcon class="w-3 h-3" /> Gerar outra
                    </button>
                    <a :href="inspireResult.edit_url"
                      class="px-3 py-1.5 bg-gradient-to-r from-violet-500 to-indigo-500 text-white rounded-lg text-xs font-semibold hover:opacity-90 transition-opacity">
                      Usar como base →
                    </a>
                  </div>
                </div>
              </div>

              <!-- Preview iframe -->
              <div class="relative w-full" style="height:420px;">
                <iframe :src="inspireResult.preview_url" class="w-full h-full border-0" title="Preview do tema gerado" />
              </div>

              <!-- Color palette preview -->
              <div class="px-6 py-4 border-t border-border">
                <p class="text-xs font-semibold text-muted-foreground mb-2 uppercase tracking-wider">Paleta de Cores</p>
                <div class="flex gap-2 flex-wrap">
                  <div v-for="(val, key) in lightColors" :key="key" class="flex flex-col items-center gap-1">
                    <div class="w-8 h-8 rounded-lg border border-border shadow-sm" :style="`background:${val}`" :title="`${key}: ${val}`" />
                    <span class="text-[9px] text-muted-foreground">{{ key.replace('--color-', '') }}</span>
                  </div>
                </div>
              </div>

              <!-- Back button -->
              <div class="px-6 pb-4">
                <button @click="inspireStep = 'select'"
                  class="text-xs text-muted-foreground hover:text-foreground underline">
                  ← Voltar à selecção
                </button>
              </div>
            </div>

            <!-- Body — Step: Error -->
            <div v-else-if="inspireStep === 'error'" class="flex-1 flex flex-col items-center justify-center py-16 gap-4 px-6">
              <div class="w-14 h-14 rounded-full bg-destructive/10 flex items-center justify-center">
                <XIcon class="w-7 h-7 text-destructive" />
              </div>
              <p class="text-sm text-destructive font-medium text-center">{{ inspireError }}</p>
              <div class="flex gap-2">
                <button @click="inspireStep = 'select'" class="px-4 py-2 bg-muted rounded-lg text-sm">Voltar</button>
                <button @click="generateInspiration" class="px-4 py-2 bg-violet-500 text-white rounded-lg text-sm font-semibold">Tentar novamente</button>
              </div>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PaletteIcon, SparklesIcon, XIcon, RefreshCwIcon } from 'lucide-vue-next';
import axios from 'axios';

const { t } = useI18n();

defineProps({ themes: { type: Array, default: () => [] } });

// ── Create theme (name prompt) ──
const showCreateModal = ref(false);
const createInput     = ref(null);
const createForm      = useForm({ label: '' });

function openCreate() {
  createForm.reset();
  createForm.clearErrors();
  showCreateModal.value = true;
  nextTick(() => createInput.value?.focus());
}

function submitCreate() {
  if (!createForm.label.trim() || createForm.processing) return;
  createForm.post('/themes', { onSuccess: () => { showCreateModal.value = false; } });
}

// ── Categories & styles ──────────────────────────────────────────
const categories = [
  { id: 'E-commerce',        emoji: '🛒', label: 'E-commerce'    },
  { id: 'Restaurante',       emoji: '🍽️', label: 'Restaurante'   },
  { id: 'Agência',           emoji: '💼', label: 'Agência'       },
  { id: 'Portfolio',         emoji: '🎨', label: 'Portfolio'     },
  { id: 'Blog',              emoji: '📝', label: 'Blog'          },
  { id: 'Hotel',             emoji: '🏨', label: 'Hotel'         },
  { id: 'SaaS',              emoji: '🚀', label: 'SaaS'          },
  { id: 'Fitness',           emoji: '💪', label: 'Fitness'       },
  { id: 'Clínica',           emoji: '🏥', label: 'Clínica'       },
  { id: 'Música',            emoji: '🎵', label: 'Música/Eventos' },
  { id: 'Imobiliário',       emoji: '🏠', label: 'Imobiliário'   },
  { id: 'Educação',          emoji: '🎓', label: 'Educação'      },
  { id: 'Fotografia',        emoji: '📸', label: 'Fotografia'    },
  { id: 'Gaming',            emoji: '🎮', label: 'Gaming'        },
  { id: 'Beleza',            emoji: '💄', label: 'Beleza/Moda'   },
  { id: 'Tecnologia',        emoji: '💻', label: 'Tecnologia'    },
  { id: 'Seguros',           emoji: '🛡️', label: 'Seguros'       },
  { id: 'Jurídico',          emoji: '⚖️', label: 'Jurídico'      },
  { id: 'Consultoria',       emoji: '📊', label: 'Consultoria'   },
  { id: 'Construção',        emoji: '🏗️', label: 'Construção'    },
  { id: 'Transporte',        emoji: '🚚', label: 'Transporte'    },
  { id: 'Viagens',           emoji: '✈️', label: 'Viagens'       },
  { id: 'ONG',               emoji: '🤝', label: 'ONG/Social'    },
  { id: 'Moda',              emoji: '👗', label: 'Moda'          },
  { id: 'Gastronomia',       emoji: '🍳', label: 'Gastronomia'   },
];

const styles = [
  { id: 'minimalista', emoji: '⬜', label: 'Minimalista' },
  { id: 'moderno',     emoji: '✦',  label: 'Moderno'     },
  { id: 'elegante',    emoji: '💎', label: 'Elegante'    },
  { id: 'arrojado',    emoji: '⚡', label: 'Arrojado'    },
  { id: 'colorido',    emoji: '🌈', label: 'Colorido'    },
];

// ── State ────────────────────────────────────────────────────────
const showInspireModal  = ref(false);
const inspireStep       = ref('select'); // select | loading | result | error
const selectedCategory  = ref(null);
const selectedStyle     = ref('moderno');
const inspireResult     = ref(null);
const inspireError      = ref('');

const categoryLabel = computed(() =>
  categories.find(c => c.id === selectedCategory.value)?.label ?? selectedCategory.value
);

const lightColors = computed(() => {
  const colors = inspireResult.value?.colors?.light ?? {};
  // Show only the main palette entries (skip success/warning/destructive)
  return Object.fromEntries(
    Object.entries(colors).filter(([k]) =>
      ['--color-primary', '--color-secondary', '--color-accent', '--color-background', '--color-card', '--color-foreground', '--color-muted', '--color-border'].includes(k)
    )
  );
});

// ── Actions ──────────────────────────────────────────────────────
function closeInspireModal() {
  showInspireModal.value = false;
  // Reset to select step after a delay so exit animation plays
  setTimeout(() => {
    if (!showInspireModal.value) {
      inspireStep.value     = 'select';
      inspireResult.value   = null;
      inspireError.value    = '';
    }
  }, 300);
}

async function generateInspiration() {
  if (!selectedCategory.value) return;
  inspireStep.value   = 'loading';
  inspireError.value  = '';
  inspireResult.value = null;

  try {
    const res = await axios.post('/themes/inspire', {
      category: selectedCategory.value,
      style: selectedStyle.value,
    });
    if (res.data.success) {
      inspireResult.value = res.data;
      inspireStep.value   = 'result';
    } else {
      throw new Error(res.data.error ?? 'Erro desconhecido');
    }
  } catch (e) {
    inspireError.value = e.response?.data?.error ?? e.message ?? 'Erro ao gerar tema. Verifica as configurações de IA.';
    inspireStep.value  = 'error';
  }
}

// ── Theme CRUD ───────────────────────────────────────────────────
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

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-6px); }
}
.animate-bounce { animation: bounce 0.8s infinite; }
</style>
