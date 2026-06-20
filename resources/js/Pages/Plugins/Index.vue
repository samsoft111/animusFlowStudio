<template>
  <AppLayout :title="t('plugins.title')">
    <template #actions>
      <button @click="openCreate"
        class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:opacity-90 transition-opacity">
        + {{ t('plugins.new') }}
      </button>
    </template>

    <div v-if="plugins.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div v-for="plugin in plugins" :key="plugin.uuid"
        class="group bg-card border border-border hover:border-primary/50 rounded-2xl p-5 flex flex-col justify-between shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 gap-4">

        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-300">
            <PuzzleIcon class="w-6 h-6" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2 flex-wrap mb-1">
              <h3 class="font-bold text-foreground text-sm truncate" :title="plugin.label">{{ plugin.label }}</h3>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold shrink-0 capitalize" :class="statusClass(plugin.status)">
                <span v-if="plugin.status === 'published'" class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse" />
                <span v-else-if="plugin.status === 'ready'" class="w-1 h-1 rounded-full bg-amber-500" />
                <span v-else class="w-1 h-1 rounded-full bg-muted-foreground" />
                {{ t('plugins.status.' + plugin.status) }}
              </span>
            </div>
            <p class="text-xs text-muted-foreground truncate">{{ plugin.name }}</p>
          </div>
        </div>

        <!-- Hooks chips -->
        <div v-if="plugin.hooks?.length" class="flex flex-wrap gap-1">
          <span v-for="hook in plugin.hooks" :key="hook"
            class="px-2 py-0.5 bg-muted/60 text-muted-foreground rounded-md text-[9px] font-mono border border-border/40 select-all">
            {{ hook }}
          </span>
        </div>

        <div class="flex gap-2 border-t border-border/50 pt-3 mt-auto">
          <Link :href="`/plugins/${plugin.uuid}/edit`"
            class="flex-1 px-3 py-2 bg-muted hover:bg-border text-foreground rounded-lg text-xs font-semibold text-center transition-colors cursor-pointer">
            {{ t('common.edit') }}
          </Link>
          <a :href="`/plugins/${plugin.uuid}/export`"
            class="px-3 py-2 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-lg text-xs font-semibold transition-all duration-300 cursor-pointer">
            {{ t('common.export') }}
          </a>
          <button @click="deletePlugin(plugin)"
            class="px-3 py-2 bg-destructive/10 text-destructive hover:bg-destructive hover:text-white rounded-lg text-xs font-semibold transition-all duration-300 cursor-pointer">
            {{ t('common.delete') }}
          </button>
        </div>
      </div>
    </div>

    <div v-else class="flex flex-col items-center justify-center py-20 text-center">
      <PuzzleIcon class="w-12 h-12 text-muted-foreground opacity-30 mb-4" />
      <h2 class="text-lg font-semibold text-foreground mb-2">{{ t('plugins.no_plugins') }}</h2>
      <p class="text-sm text-muted-foreground mb-6">{{ t('plugins.no_plugins_desc') }}</p>
      <button @click="openCreate" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold cursor-pointer">
        {{ t('plugins.new') }}
      </button>
    </div>

    <!-- ── Create Modal ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCreateModal = false" />
          <div class="relative bg-card border border-border rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="font-semibold text-foreground mb-1">Novo Plugin</h3>
            <p class="text-xs text-muted-foreground mb-4">Dá um nome ao teu plugin. Podes alterá-lo depois nos detalhes.</p>
            <input ref="createInput" v-model="createForm.label" type="text"
              placeholder="ex: Barra de Anúncio"
              @keyup.enter="submitCreate"
              class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
            <p v-if="createForm.errors.label" class="text-xs text-destructive mt-1">{{ createForm.errors.label }}</p>
            <div class="flex justify-end gap-2 mt-5">
              <button @click="showCreateModal = false" class="px-4 py-2 bg-muted rounded-lg text-sm font-semibold cursor-pointer">Cancelar</button>
              <button @click="submitCreate" :disabled="createForm.processing || !createForm.label.trim()"
                class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold disabled:opacity-50 cursor-pointer">
                {{ createForm.processing ? 'A criar…' : 'Criar plugin' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PuzzleIcon } from 'lucide-vue-next';

const { t } = useI18n();

defineProps({ plugins: { type: Array, default: () => [] } });

// ── Create plugin (name prompt) ──
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
  createForm.post('/plugins', { onSuccess: () => { showCreateModal.value = false; } });
}

function statusClass(status) {
  return {
    draft:     'bg-secondary/40 text-muted-foreground border border-border/80',
    ready:     'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20',
    published: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20',
  }[status] ?? 'bg-muted text-muted-foreground';
}

function deletePlugin(plugin) {
  if (!confirm(t('common.confirm_delete', { name: plugin.label }))) return;
  router.delete(`/plugins/${plugin.uuid}`);
}
</script>
