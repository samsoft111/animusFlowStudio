<template>
  <AppLayout :title="theme ? `Edit: ${theme.label}` : 'New Theme'">
    <template #actions>
      <a v-if="theme" :href="`/themes/${theme.uuid}/export`"
        class="px-4 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors">
        Export ZIP
      </a>
    </template>

    <div class="max-w-2xl space-y-6">
      <!-- Create form (new theme) -->
      <form v-if="!theme" @submit.prevent="createTheme" class="bg-card border border-border rounded-2xl p-6 space-y-4">
        <h2 class="font-semibold text-foreground">Create New Theme</h2>

        <div>
          <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Slug (name)</label>
          <input v-model="createForm.name" placeholder="e.g. aurora-dark"
            class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
          <p class="text-xs text-muted-foreground mt-1">Lowercase letters, numbers, hyphens, underscores.</p>
        </div>
        <div>
          <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Label</label>
          <input v-model="createForm.label" placeholder="e.g. Aurora Dark"
            class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
        </div>
        <div>
          <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Description</label>
          <textarea v-model="createForm.description" rows="2" placeholder="Short description..."
            class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary resize-none" />
        </div>

        <button type="submit" :disabled="createForm.processing"
          class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          Create Theme
        </button>
      </form>

      <!-- Edit form (existing theme) -->
      <template v-if="theme">
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground">Theme Details</h2>
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
            <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Description</label>
            <textarea v-model="form.description" rows="2"
              class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary resize-none" />
          </div>
          <div>
            <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Preview URL</label>
            <input v-model="form.preview_url" placeholder="https://..."
              class="mt-1.5 w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
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
          <button @click="save" :disabled="form.processing"
            class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
            Save Changes
          </button>
        </div>

        <!-- Color tokens -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground">Color Tokens</h2>
          <p class="text-sm text-muted-foreground">Enter CSS values (e.g. <code class="bg-muted px-1 rounded">#6366f1</code> or <code class="bg-muted px-1 rounded">oklch(0.55 0.22 265)</code>).</p>

          <div class="space-y-3">
            <div v-for="token in colorTokens" :key="token.var" class="grid grid-cols-2 gap-3 items-center">
              <label class="text-xs font-mono text-muted-foreground">{{ token.var }}</label>
              <input v-model="form.colors.light[token.var]" :placeholder="token.default"
                class="px-3 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary font-mono" />
            </div>
          </div>
          <button @click="save" :disabled="form.processing"
            class="px-4 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors disabled:opacity-50">
            Save Colors
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

const props = defineProps({ theme: { type: Object, default: null } });

const createForm = useForm({ name: '', label: '', description: '', version: '1.0.0' });

function createTheme() {
  createForm.post('/themes');
}

const form = reactive({
  label:       props.theme?.label ?? '',
  description: props.theme?.description ?? '',
  version:     props.theme?.version ?? '1.0.0',
  preview_url: props.theme?.preview_url ?? '',
  status:      props.theme?.status ?? 'draft',
  colors:      { light: { ...(props.theme?.colors?.light ?? {}) } },
  processing:  false,
});

function save() {
  form.processing = true;
  router.put(`/themes/${props.theme.uuid}`, form, {
    onFinish: () => { form.processing = false; },
  });
}

const colorTokens = [
  { var: '--color-primary',          default: 'oklch(0.55 0.22 265)' },
  { var: '--color-background',       default: 'oklch(0.99 0.003 265)' },
  { var: '--color-foreground',       default: 'oklch(0.13 0.02 265)' },
  { var: '--color-card',             default: 'oklch(1 0 0)' },
  { var: '--color-muted',            default: 'oklch(0.96 0.005 265)' },
  { var: '--color-muted-foreground', default: 'oklch(0.50 0.02 265)' },
  { var: '--color-border',           default: 'oklch(0.91 0.005 265)' },
];
</script>
