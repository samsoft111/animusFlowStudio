<template>
  <AppLayout :title="plugin ? `Edit: ${plugin.label}` : 'New Plugin'">
    <template #actions>
      <a v-if="plugin" :href="`/plugins/${plugin.uuid}/export`"
        class="px-4 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors">
        Export ZIP
      </a>
    </template>

    <div class="max-w-3xl space-y-6">
      <!-- Create form -->
      <form v-if="!plugin" @submit.prevent="createPlugin" class="bg-card border border-border rounded-2xl p-6 space-y-4">
        <h2 class="font-semibold text-foreground">Create New Plugin</h2>
        <div>
          <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Slug (name)</label>
          <input v-model="createForm.name" placeholder="e.g. af-hello-bar"
            class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>
        <div>
          <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Label</label>
          <input v-model="createForm.label" placeholder="e.g. Hello Bar"
            class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>
        <div>
          <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Description</label>
          <textarea v-model="createForm.description" rows="2"
            class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary resize-none" />
        </div>
        <button type="submit" :disabled="createForm.processing"
          class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          Create Plugin
        </button>
      </form>

      <!-- Edit form -->
      <template v-if="plugin">
        <!-- Metadata -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground">Plugin Details</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Label</label>
              <input v-model="form.label"
                class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
            </div>
            <div>
              <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Version</label>
              <input v-model="form.version"
                class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
            </div>
          </div>
          <div>
            <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Status</label>
            <select v-model="form.status"
              class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary">
              <option value="draft">Draft</option>
              <option value="ready">Ready</option>
              <option value="published">Published</option>
            </select>
          </div>
          <!-- Hooks -->
          <div>
            <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 block">Hooks</label>
            <div class="flex flex-wrap gap-2">
              <label v-for="h in availableHooks" :key="h" class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" :value="h" v-model="form.hooks" class="rounded" />
                <span class="text-xs font-mono text-foreground">{{ h }}</span>
              </label>
            </div>
          </div>
          <button @click="save" :disabled="form.processing"
            class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
            Save Changes
          </button>
        </div>

        <!-- Plugin.php -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-3">
          <h2 class="font-semibold text-foreground">Plugin.php</h2>
          <textarea v-model="form.plugin_php" rows="14" spellcheck="false"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-xs font-mono focus:outline-none focus:border-primary resize-y" />
          <button @click="save" :disabled="form.processing"
            class="px-4 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors disabled:opacity-50">
            Save Code
          </button>
        </div>

        <!-- Widget Blade (optional) -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-3">
          <h2 class="font-semibold text-foreground">Widget Blade <span class="text-xs text-muted-foreground font-normal">(optional — for page.render hook)</span></h2>
          <textarea v-model="form.widget_blade" rows="8" spellcheck="false" placeholder="<!-- Blade template rendered in page footer -->"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-xs font-mono focus:outline-none focus:border-primary resize-y" />
          <button @click="save" :disabled="form.processing"
            class="px-4 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors disabled:opacity-50">
            Save Blade
          </button>
        </div>

        <!-- Widget JS (optional) -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-3">
          <h2 class="font-semibold text-foreground">Widget JavaScript <span class="text-xs text-muted-foreground font-normal">(optional)</span></h2>
          <textarea v-model="form.widget_js" rows="8" spellcheck="false" placeholder="// JavaScript for your widget"
            class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-xs font-mono focus:outline-none focus:border-primary resize-y" />
          <button @click="save" :disabled="form.processing"
            class="px-4 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors disabled:opacity-50">
            Save JS
          </button>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ plugin: { type: Object, default: null } });

const createForm = useForm({ name: '', label: '', description: '', version: '1.0.0' });
function createPlugin() { createForm.post('/plugins'); }

const availableHooks = ['page.render', 'content.publish', 'admin.sidebar'];

const form = reactive({
  label:       props.plugin?.label ?? '',
  description: props.plugin?.description ?? '',
  version:     props.plugin?.version ?? '1.0.0',
  status:      props.plugin?.status ?? 'draft',
  hooks:       props.plugin?.hooks ?? [],
  plugin_php:  props.plugin?.plugin_php ?? '',
  widget_blade:props.plugin?.widget_blade ?? '',
  widget_js:   props.plugin?.widget_js ?? '',
  processing:  false,
});

function save() {
  form.processing = true;
  router.put(`/plugins/${props.plugin.uuid}`, form, {
    onFinish: () => { form.processing = false; },
  });
}
</script>
