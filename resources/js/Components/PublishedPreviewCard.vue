<template>
  <!-- Card de "publicados" com preview directo de qualquer item (sem abrir a edição) -->
  <div ref="root" class="relative bg-card border border-border rounded-2xl p-5 transition-all hover:border-success/40 hover:shadow-sm">
    <Link :href="listHref" class="block group">
      <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mb-1.5 group-hover:text-foreground transition-colors">{{ label }}</p>
      <p class="text-3xl font-black text-success transition-all duration-300 group-hover:translate-x-0.5">{{ count }}</p>
    </Link>

    <!-- Botão que abre a lista de todos os itens publicados -->
    <button v-if="items.length" type="button" @click="toggle"
            :title="previewTitle" :aria-label="previewTitle" :aria-expanded="open"
            class="absolute top-3.5 right-3.5 inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold text-success bg-success/10 hover:bg-success hover:text-white transition-colors">
      <EyeIcon class="w-3.5 h-3.5" />
      <span class="hidden sm:inline">{{ previewLabel }}</span>
      <ChevronDownIcon class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" />
    </button>

    <!-- Dropdown: pesquisar + previsualizar qualquer item publicado -->
    <div v-if="open"
         class="absolute z-30 top-12 right-3.5 w-72 max-w-[calc(100vw-2rem)] bg-card border border-border rounded-xl shadow-lg p-2">
      <div v-if="items.length > 6" class="mb-2">
        <input v-model="filter" type="text" :placeholder="searchPlaceholder"
               class="w-full px-2.5 py-1.5 text-xs rounded-lg bg-muted border border-border text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary/50" />
      </div>
      <div class="max-h-64 overflow-y-auto space-y-0.5">
        <a v-for="it in filtered" :key="it.uuid"
           :href="previewUrl(it)" target="_blank" rel="noopener"
           class="flex items-center justify-between gap-2 px-2.5 py-2 rounded-lg hover:bg-success/10 group/item transition-colors">
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-foreground group-hover/item:text-success transition-colors truncate">{{ it.label }}</span>
            <span class="block text-[11px] text-muted-foreground truncate">{{ it.name }}</span>
          </span>
          <EyeIcon class="w-4 h-4 shrink-0 text-muted-foreground group-hover/item:text-success transition-colors" />
        </a>
        <p v-if="!filtered.length" class="text-xs text-muted-foreground text-center py-3">{{ noMatchText }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { EyeIcon, ChevronDownIcon } from 'lucide-vue-next';

const props = defineProps({
  label:             { type: String,  default: '' },
  count:             { type: [Number, String], default: 0 },
  items:             { type: Array,   default: () => [] },   // [{ uuid, label, name }]
  listHref:          { type: String,  default: '#' },
  previewUrl:        { type: Function, required: true },     // (item) => string
  previewTitle:      { type: String,  default: '' },
  previewLabel:      { type: String,  default: '' },
  searchPlaceholder: { type: String,  default: '' },
  noMatchText:       { type: String,  default: '' },
});

const open = ref(false);
const filter = ref('');
const root = ref(null);

const filtered = computed(() => {
  const q = filter.value.trim().toLowerCase();
  if (!q) return props.items;
  return props.items.filter(it =>
    (it.label || '').toLowerCase().includes(q) || (it.name || '').toLowerCase().includes(q));
});

function toggle() {
  open.value = !open.value;
  if (!open.value) filter.value = '';
}

function onDocClick(e) {
  if (open.value && root.value && !root.value.contains(e.target)) {
    open.value = false;
    filter.value = '';
  }
}

onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));
</script>
