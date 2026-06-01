<template>
  <AppLayout :title="plugin ? plugin.label : t('plugins.create_title')">
    <template #actions>
      <template v-if="plugin">
        <a :href="`/plugins/${plugin.uuid}/export`"
          class="px-3 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors flex items-center gap-1.5">
          <DownloadIcon class="w-3.5 h-3.5" /> {{ t('common.export') }}
        </a>
        <button @click="showPromptModal = true"
          class="px-3 py-2 bg-violet-500/10 text-violet-600 dark:text-violet-400 border border-violet-500/20 rounded-lg text-sm font-semibold hover:bg-violet-500/20 transition-colors flex items-center gap-1.5">
          <SparklesIcon class="w-3.5 h-3.5" /> .afprompt
        </button>
        <button @click="installInCms" :disabled="installingCms"
          class="px-3 py-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-lg text-sm font-semibold hover:bg-emerald-500/20 transition-colors flex items-center gap-1.5 disabled:opacity-50">
          <template v-if="installingCms"><span class="w-3.5 h-3.5 border-2 border-emerald-500/30 border-t-emerald-500 rounded-full animate-spin inline-block"></span></template>
          <template v-else>⚡</template>
          {{ installingCms ? 'A instalar…' : 'Instalar no CMS' }}
        </button>
        <button @click="publishPlugin" :disabled="publishing"
          class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5 disabled:opacity-50"
          :class="plugin.is_published ? 'bg-success/10 text-success hover:bg-success/20' : 'bg-primary text-primary-foreground hover:opacity-90'">
          <UploadIcon class="w-3.5 h-3.5" />
          {{ publishing ? t('common.loading') : (plugin.is_published ? t('plugins.republish') : t('plugins.publish')) }}
        </button>
      </template>
    </template>

    <!-- Edit tabs -->
    <div class="space-y-4">

      <!-- Feedback -->
      <div v-if="feedback.error" class="flex items-center gap-2 px-4 py-3 bg-destructive/10 text-destructive border border-destructive/20 rounded-xl text-sm">
        <XCircleIcon class="w-4 h-4 shrink-0" />{{ feedback.error }}
        <button @click="feedback.error=''" class="ml-auto">✕</button>
      </div>
      <div v-if="feedback.success" class="flex items-center gap-2 px-4 py-3 bg-success/10 text-success border border-success/20 rounded-xl text-sm">
        <CheckCircleIcon class="w-4 h-4 shrink-0" />{{ feedback.success }}
        <button @click="feedback.success=''" class="ml-auto">✕</button>
      </div>

      <!-- Tab bar -->
      <div class="flex flex-wrap gap-1 bg-muted p-1 rounded-xl w-fit">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
          class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition-colors"
          :class="activeTab === tab.id ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
          {{ tab.label }}
        </button>
      </div>

      <!-- ══════════ Tab: Detalhes ══════════ -->
      <div v-show="activeTab === 'details'" class="max-w-2xl space-y-4">
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground text-sm">Informações gerais</h2>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Label</label>
              <input v-model="form.label" :class="inp" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Versão</label>
              <input v-model="form.version" :class="inp" placeholder="1.0.0" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Descrição</label>
            <textarea v-model="form.description" rows="2" :class="inp + ' resize-none'" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Autor</label>
              <input v-model="form.author" :class="inp" placeholder="Nome do autor" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">URL do Autor</label>
              <input v-model="form.author_url" :class="inp" placeholder="https://..." />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Categoria</label>
              <select v-model="form.category" :class="inp">
                <option value="">— Sem categoria —</option>
                <option value="seo">SEO</option>
                <option value="analytics">Analytics</option>
                <option value="ecommerce">E-commerce</option>
                <option value="social">Social Media</option>
                <option value="forms">Forms</option>
                <option value="ai">AI / Chatbot</option>
                <option value="design">Design</option>
                <option value="marketing">Marketing</option>
                <option value="utilities">Utilities</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Licença</label>
              <select v-model="form.license" :class="inp">
                <option value="MIT">MIT</option>
                <option value="GPL-2.0">GPL-2.0</option>
                <option value="GPL-3.0">GPL-3.0</option>
                <option value="Apache-2.0">Apache-2.0</option>
                <option value="Commercial">Commercial</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">AnimusFlow mínimo</label>
              <input v-model="form.min_animusflow_version" :class="inp" placeholder="1.0.0" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Estado</label>
              <select v-model="form.status" :class="inp">
                <option value="draft">Draft</option>
                <option value="ready">Ready</option>
                <option value="published">Published</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Tags</label>
            <input :value="(form.tags ?? []).join(', ')" @input="e => form.tags = e.target.value.split(',').map(s => s.trim()).filter(Boolean)"
              :class="inp" placeholder="chatbot, ai, widget" />
            <p class="text-xs text-muted-foreground mt-1">Separadas por vírgula</p>
          </div>

          <button @click="save" :disabled="saving" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50 flex items-center gap-2">
            <div v-if="saving" class="w-3.5 h-3.5 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
            {{ saving ? t('common.loading') : t('common.save') }}
          </button>
        </div>

        <!-- Plugin info card -->
        <div class="bg-muted/40 border rounded-xl p-4 text-xs space-y-1 text-muted-foreground font-mono">
          <div>Slug: <span class="text-foreground">{{ plugin.name }}</span></div>
          <div>UUID: <span class="text-foreground">{{ plugin.uuid }}</span></div>
        </div>
      </div>

      <!-- ══════════ Tab: Hooks ══════════ -->
      <div v-show="activeTab === 'hooks'" class="max-w-2xl">
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <div>
            <h2 class="font-semibold text-foreground text-sm">Hooks activos</h2>
            <p class="text-xs text-muted-foreground mt-0.5">Selecciona os eventos do CMS a que este plugin responde.</p>
          </div>
          <div class="space-y-3">
            <label v-for="h in availableHooks" :key="h"
              class="flex items-start gap-3 p-4 bg-muted rounded-xl cursor-pointer hover:bg-border/50 transition-colors border"
              :class="(form.hooks ?? []).includes(h) ? 'border-primary/30 bg-primary/5' : 'border-transparent'">
              <input type="checkbox" :value="h" v-model="form.hooks" class="mt-0.5 accent-primary" />
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-mono font-semibold text-foreground">{{ h }}</span>
                  <span class="text-xs px-2 py-0.5 bg-muted-foreground/10 rounded-full text-muted-foreground">{{ hookMethods[h] }}</span>
                </div>
                <p class="text-xs text-muted-foreground mt-1">{{ hookDescriptions[h] }}</p>
              </div>
            </label>
          </div>
          <button @click="save" :disabled="saving" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
            {{ saving ? t('common.loading') : t('common.save') }}
          </button>
        </div>
      </div>

      <!-- ══════════ Tab: PHP ══════════ -->
      <div v-show="activeTab === 'php'" class="max-w-4xl space-y-4">
        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <div>
              <span class="text-sm font-semibold text-foreground">Plugin.php</span>
              <span class="text-xs text-muted-foreground ml-2">Classe principal do plugin</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-xs text-muted-foreground font-mono">{{ (form.plugin_php || '').length }} chars</span>
              <button @click="injectPhpScaffold" class="text-xs text-primary hover:underline">Gerar scaffold</button>
            </div>
          </div>
          <textarea v-model="form.plugin_php" rows="24" spellcheck="false"
            :placeholder="phpPlaceholder"
            class="w-full px-4 py-3 bg-[#1e1e2e] text-[#cdd6f4] text-xs font-mono focus:outline-none resize-y border-0" />
        </div>
        <button @click="save" :disabled="saving" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ saving ? t('common.loading') : 'Guardar PHP' }}
        </button>
      </div>

      <!-- ══════════ Tab: Widget ══════════ -->
      <div v-show="activeTab === 'widget'" class="max-w-4xl space-y-4">
        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <div>
              <span class="text-sm font-semibold text-foreground">widget.blade.php</span>
              <span class="text-xs text-muted-foreground ml-2">HTML injectado via hook page.render</span>
            </div>
            <span class="text-xs text-muted-foreground font-mono">{{ (form.widget_blade || '').length }} chars</span>
          </div>
          <textarea v-model="form.widget_blade" rows="14" spellcheck="false"
            placeholder="{{-- Blade template rendered before </body> --}}"
            class="w-full px-4 py-3 bg-[#1e1e2e] text-[#cdd6f4] text-xs font-mono focus:outline-none resize-y border-0" />
        </div>
        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <div>
              <span class="text-sm font-semibold text-foreground">widget.js</span>
              <span class="text-xs text-muted-foreground ml-2">JavaScript do widget (carregado automaticamente)</span>
            </div>
            <span class="text-xs text-muted-foreground font-mono">{{ (form.widget_js || '').length }} chars</span>
          </div>
          <textarea v-model="form.widget_js" rows="10" spellcheck="false"
            placeholder="// JavaScript do widget"
            class="w-full px-4 py-3 bg-[#1e1e2e] text-[#cdd6f4] text-xs font-mono focus:outline-none resize-y border-0" />
        </div>
        <button @click="save" :disabled="saving" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ saving ? t('common.loading') : 'Guardar Widget' }}
        </button>
      </div>

      <!-- ══════════ Tab: CSS ══════════ -->
      <div v-show="activeTab === 'css'" class="max-w-4xl space-y-4">
        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <div>
              <span class="text-sm font-semibold text-foreground">plugin.css</span>
              <span class="text-xs text-muted-foreground ml-2">Estilos personalizados do plugin</span>
            </div>
            <span class="text-xs text-muted-foreground font-mono">{{ (form.custom_css || '').length }} chars</span>
          </div>
          <textarea v-model="form.custom_css" rows="20" spellcheck="false"
            placeholder="/* CSS do plugin */&#10;.af-plugin { }"
            class="w-full px-4 py-3 bg-[#1e1e2e] text-[#cdd6f4] text-xs font-mono focus:outline-none resize-y border-0" />
        </div>
        <button @click="save" :disabled="saving" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ saving ? t('common.loading') : 'Guardar CSS' }}
        </button>
      </div>

      <!-- ══════════ Tab: Configurações ══════════ -->
      <div v-show="activeTab === 'schema'" class="max-w-3xl space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="font-semibold text-foreground">{{ t('plugins.schema_title') }}</h2>
            <p class="text-xs text-muted-foreground mt-0.5">{{ t('plugins.schema_hint') }}</p>
          </div>
          <button @click="addSchemaField"
            class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border transition-colors flex items-center gap-1">
            <PlusIcon class="w-3.5 h-3.5" /> {{ t('plugins.add_field') }}
          </button>
        </div>

        <div v-if="!form.settings_schema.length"
          class="bg-card border border-border border-dashed rounded-2xl p-10 text-center">
          <SlidersIcon class="w-8 h-8 text-muted-foreground opacity-30 mx-auto mb-3" />
          <p class="text-sm text-muted-foreground">{{ t('plugins.no_fields') }}</p>
        </div>

        <div v-for="(field, index) in form.settings_schema" :key="index"
          class="bg-card border border-border rounded-2xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Campo {{ index + 1 }}</span>
            <button @click="removeSchemaField(index)" class="text-xs text-destructive/60 hover:text-destructive px-2 py-0.5 rounded hover:bg-destructive/10">Remover</button>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Key</label>
              <input v-model="field.key" placeholder="setting_key" class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary font-mono" />
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Label</label>
              <input v-model="field.label" placeholder="Nome da configuração" class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary" />
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Tipo</label>
              <select v-model="field.type" class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary">
                <option value="text">text</option>
                <option value="textarea">textarea</option>
                <option value="color">color</option>
                <option value="select">select</option>
                <option value="toggle">toggle</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Default</label>
              <input v-model="field.default" placeholder="valor padrão" class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary" />
            </div>
            <div>
              <label class="block text-xs text-muted-foreground mb-1">Placeholder</label>
              <input v-model="field.placeholder" placeholder="texto de ajuda" class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary" />
            </div>
          </div>
          <div>
            <label class="block text-xs text-muted-foreground mb-1">Hint</label>
            <input v-model="field.hint" placeholder="Descrição curta para o utilizador" class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary" />
          </div>
          <div v-if="field.type === 'select'" class="space-y-1">
            <label class="block text-xs text-muted-foreground">Opções (valor: Label — uma por linha)</label>
            <textarea :value="selectOptionsText(field.options)" @input="e => field.options = parseSelectOptions(e.target.value)"
              rows="3" placeholder="value1: Label Um&#10;value2: Label Dois"
              class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary font-mono resize-none" />
          </div>
          <div v-if="field.type === 'toggle'">
            <label class="block text-xs text-muted-foreground mb-1">Toggle label</label>
            <input v-model="field.toggle_label" placeholder="Activar funcionalidade" class="w-full px-2.5 py-1.5 bg-muted border border-border rounded-lg text-xs focus:outline-none focus:border-primary" />
          </div>
        </div>

        <button v-if="form.settings_schema.length" @click="save" :disabled="saving"
          class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ saving ? t('common.loading') : t('plugins.save_schema') }}
        </button>
      </div>

      <!-- ══════════ Tab: Documentação ══════════ -->
      <div v-show="activeTab === 'docs'" class="max-w-4xl space-y-4">
        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <div>
              <span class="text-sm font-semibold text-foreground">README.md</span>
              <span class="text-xs text-muted-foreground ml-2">Documentação incluída no ZIP</span>
            </div>
            <span class="text-xs text-muted-foreground font-mono">{{ (form.readme || '').length }} chars</span>
          </div>
          <textarea v-model="form.readme" rows="20" spellcheck="false"
            placeholder="# Nome do Plugin&#10;&#10;Descrição do plugin...&#10;&#10;## Instalação&#10;&#10;## Utilização"
            class="w-full px-4 py-3 bg-muted/30 text-foreground text-sm font-mono focus:outline-none resize-y border-0" />
        </div>
        <button @click="save" :disabled="saving" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
          {{ saving ? t('common.loading') : 'Guardar README' }}
        </button>
      </div>

      <!-- ══════════ Tab: IA ══════════ -->
      <div v-show="activeTab === 'ai'" class="max-w-2xl">
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
              <SparklesIcon class="w-4 h-4 text-primary" />
            </div>
            <div>
              <h2 class="font-semibold text-foreground">{{ t('plugins.ai_generator') }}</h2>
              <p class="text-xs text-muted-foreground">{{ t('plugins.ai_generator_hint') }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Prompt</label>
            <textarea v-model="aiPrompt" rows="5"
              :placeholder="t('plugins.ai_prompt_placeholder')"
              :disabled="aiLoading"
              class="w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary resize-none disabled:opacity-50" />
          </div>

          <button @click="generateAi" :disabled="aiLoading || !aiPrompt.trim()"
            class="w-full py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50 flex items-center justify-center gap-2">
            <div v-if="aiLoading" class="w-4 h-4 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
            <SparklesIcon v-else class="w-4 h-4" />
            {{ aiLoading ? t('plugins.generating') : t('plugins.generate') }}
          </button>

          <div v-if="aiLoading" class="bg-muted rounded-xl p-4 text-sm text-muted-foreground text-center">
            {{ t('plugins.ai_waiting') }}
          </div>
        </div>
      </div>

      <!-- ══════════ Tab: Exportar ══════════ -->
      <div v-show="activeTab === 'export'" class="max-w-2xl space-y-4">
        <div class="bg-card border border-border rounded-2xl p-6 space-y-5">
          <h2 class="font-semibold text-foreground text-sm">Exportar Plugin</h2>

          <!-- Status -->
          <div class="grid grid-cols-3 gap-3">
            <div class="bg-muted rounded-xl p-3 text-center">
              <div class="text-lg font-bold text-foreground capitalize">{{ plugin.status }}</div>
              <div class="text-xs text-muted-foreground mt-0.5">Estado</div>
            </div>
            <div class="bg-muted rounded-xl p-3 text-center">
              <div class="text-lg font-bold" :class="plugin.is_published ? 'text-green-500' : 'text-muted-foreground'">
                {{ plugin.is_published ? '✓' : '—' }}
              </div>
              <div class="text-xs text-muted-foreground mt-0.5">Publicado</div>
            </div>
            <div class="bg-muted rounded-xl p-3 text-center">
              <div class="text-lg font-bold text-foreground">{{ plugin.version }}</div>
              <div class="text-xs text-muted-foreground mt-0.5">Versão</div>
            </div>
          </div>

          <!-- Checklist -->
          <div class="space-y-2">
            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Checklist</p>
            <div v-for="item in checklist" :key="item.label" class="flex items-center gap-3 text-sm"
              :class="item.ok ? 'text-foreground' : 'text-muted-foreground'">
              <span :class="item.ok ? 'text-green-500' : 'text-muted-foreground/40'">{{ item.ok ? '✓' : '○' }}</span>
              {{ item.label }}
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col gap-3 pt-2 border-t border-border">
            <a :href="`/plugins/${plugin.uuid}/export`"
              class="flex items-center justify-center gap-2 px-4 py-3 bg-muted text-foreground rounded-xl text-sm font-semibold hover:bg-border transition-colors">
              <DownloadIcon class="w-4 h-4" /> Descarregar ZIP
            </a>
            <button @click="showPromptModal = true"
              class="flex items-center justify-center gap-2 px-4 py-3 bg-violet-500/10 text-violet-600 dark:text-violet-400 border border-violet-500/20 rounded-xl text-sm font-semibold hover:bg-violet-500/20 transition-colors">
              <SparklesIcon class="w-4 h-4" /> Exportar Plugin Prompt (.afprompt)
            </button>
            <button @click="installInCms" :disabled="installingCms"
              class="flex items-center justify-center gap-2 px-4 py-3 bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 rounded-xl text-sm font-semibold hover:bg-emerald-500/20 disabled:opacity-50">
              <template v-if="installingCms"><span class="w-4 h-4 border-2 border-emerald-500/30 border-t-emerald-500 rounded-full animate-spin inline-block"></span></template>
              <template v-else>⚡</template>
              {{ installingCms ? 'A instalar…' : 'Instalar directamente no CMS' }}
            </button>
            <button @click="publishPlugin" :disabled="publishing"
              class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-semibold disabled:opacity-50"
              :class="plugin.is_published ? 'bg-success/10 text-success border border-success/20 hover:bg-success/20' : 'bg-primary text-primary-foreground hover:bg-primary/90'">
              <UploadIcon class="w-4 h-4" />
              {{ publishing ? 'A publicar…' : (plugin.is_published ? 'Re-publicar no Marketplace' : 'Publicar no Marketplace') }}
            </button>
          </div>
        </div>
      </div>

      <!-- ══════════ Tab: Chat IA ══════════ -->
      <div v-show="activeTab === 'chat'" class="flex flex-col gap-4">

        <!-- Header info -->
        <div class="flex items-center gap-3 px-4 py-3 bg-violet-500/8 border border-violet-500/20 rounded-xl">
          <span class="text-lg">💬</span>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-foreground">Assistente de Desenvolvimento IA</p>
            <p class="text-xs text-muted-foreground">Descreve o que queres em linguagem natural. Podes anexar imagens e documentos para inspiração.</p>
          </div>
          <button @click="chatMessages = []; chatPendingUpdates = null"
            class="text-xs text-muted-foreground hover:text-foreground px-2 py-1 rounded-lg hover:bg-muted transition-colors shrink-0">
            🗑 Limpar
          </button>
        </div>

        <!-- Chat messages -->
        <div ref="chatScrollEl"
          class="flex flex-col gap-3 overflow-y-auto pr-1"
          style="max-height: 55vh; min-height: 200px;">

          <!-- Empty state -->
          <div v-if="chatMessages.length === 0" class="flex flex-col items-center justify-center py-16 gap-3 text-center">
            <div class="w-14 h-14 rounded-2xl bg-violet-500/10 flex items-center justify-center text-2xl">🔌</div>
            <p class="text-sm font-semibold text-foreground">Começa uma conversa</p>
            <p class="text-xs text-muted-foreground max-w-xs">Pergunta algo como "Cria um plugin de anúncio" ou "Adiciona um campo de cor" e descreve o comportamento pretendido.</p>
            <div class="flex flex-wrap gap-2 justify-center mt-2">
              <button v-for="qp in chatQuickPrompts" :key="qp"
                @click="chatInput = qp"
                class="px-3 py-1.5 bg-muted hover:bg-border border border-border rounded-full text-xs text-foreground transition-colors">
                {{ qp }}
              </button>
            </div>
          </div>

          <!-- Messages -->
          <template v-for="(msg, i) in chatMessages" :key="i">

            <!-- User message -->
            <div v-if="msg.role === 'user'" class="flex justify-end gap-2 items-end">
              <div class="max-w-[78%]">
                <div v-if="msg.attachmentPreviews?.length" class="flex flex-wrap gap-1.5 mb-1.5 justify-end">
                  <template v-for="(att, ai) in msg.attachmentPreviews" :key="ai">
                    <img v-if="att.type === 'image'" :src="att.url" class="h-16 w-16 object-cover rounded-lg border border-border" />
                    <div v-else class="h-16 px-3 flex flex-col items-center justify-center bg-muted border border-border rounded-lg gap-1">
                      <span class="text-xl">{{ att.icon }}</span>
                      <span class="text-[9px] text-muted-foreground truncate max-w-[60px]">{{ att.name }}</span>
                    </div>
                  </template>
                </div>
                <div class="bg-primary text-primary-foreground rounded-2xl rounded-br-sm px-4 py-2.5 text-sm leading-relaxed whitespace-pre-wrap">{{ msg.content }}</div>
              </div>
              <div class="w-7 h-7 rounded-full bg-primary/20 flex items-center justify-center text-xs shrink-0 mb-0.5">👤</div>
            </div>

            <!-- Assistant message -->
            <div v-else class="flex gap-2 items-end">
              <div class="w-7 h-7 rounded-full bg-violet-500/15 flex items-center justify-center text-xs shrink-0 mb-0.5">✦</div>
              <div class="max-w-[82%]">
                <div class="bg-card border border-border rounded-2xl rounded-bl-sm px-4 py-2.5 text-sm leading-relaxed whitespace-pre-wrap text-foreground">{{ msg.content }}</div>
                <div v-if="msg.applied" class="mt-1.5 flex items-center gap-1.5">
                  <span class="text-[10px] text-success font-semibold flex items-center gap-1">✓ Alterações aplicadas ao plugin</span>
                  <button @click="save()" class="text-[10px] px-2 py-0.5 bg-success/10 text-success rounded-full hover:bg-success/20 transition-colors">Guardar agora</button>
                </div>
                <div v-else-if="msg.updates && !msg.applied" class="mt-1.5 flex items-center gap-2">
                  <button @click="applyChatUpdates(msg.updates, i)"
                    class="text-[10px] px-2.5 py-1 bg-primary text-primary-foreground rounded-full font-semibold hover:opacity-90 transition-opacity">
                    ✦ Aplicar alterações
                  </button>
                  <span class="text-[10px] text-muted-foreground">A IA sugeriu mudanças ao plugin</span>
                </div>
              </div>
            </div>

          </template>

          <!-- Typing indicator -->
          <div v-if="chatLoading" class="flex gap-2 items-end">
            <div class="w-7 h-7 rounded-full bg-violet-500/15 flex items-center justify-center text-xs shrink-0">✦</div>
            <div class="bg-card border border-border rounded-2xl rounded-bl-sm px-4 py-3 flex items-center gap-1">
              <span class="w-1.5 h-1.5 bg-muted-foreground rounded-full animate-bounce" style="animation-delay:0ms"></span>
              <span class="w-1.5 h-1.5 bg-muted-foreground rounded-full animate-bounce" style="animation-delay:150ms"></span>
              <span class="w-1.5 h-1.5 bg-muted-foreground rounded-full animate-bounce" style="animation-delay:300ms"></span>
            </div>
          </div>

        </div>

        <!-- File attachment previews -->
        <div v-if="chatAttachments.length" class="flex flex-wrap gap-2 px-1">
          <div v-for="(att, i) in chatAttachments" :key="i"
            class="relative group flex items-center gap-2 px-2 py-1.5 bg-muted border border-border rounded-xl">
            <img v-if="att.type === 'image'" :src="att.url" class="w-8 h-8 object-cover rounded-md" />
            <span v-else class="text-lg">{{ att.icon }}</span>
            <div class="min-w-0">
              <p class="text-[10px] font-semibold text-foreground truncate max-w-[100px]">{{ att.name }}</p>
              <p class="text-[9px] text-muted-foreground">{{ att.sizeLabel }}</p>
            </div>
            <button @click="chatAttachments.splice(i, 1)"
              class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-destructive text-white rounded-full text-[8px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
          </div>
        </div>

        <!-- Drag & drop zone -->
        <div v-if="chatDragging"
          class="border-2 border-dashed border-primary rounded-2xl p-8 text-center text-primary text-sm font-semibold"
          @dragover.prevent @dragleave="chatDragging = false"
          @drop.prevent="onChatDrop($event)">
          📎 Solta os ficheiros aqui
        </div>

        <!-- Input row -->
        <div v-else class="flex gap-2 items-end" @dragover.prevent="chatDragging = true">
          <button @click="$refs.chatFileInput.click()"
            class="w-9 h-9 rounded-xl bg-muted border border-border flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-border transition-colors shrink-0 mb-0.5"
            title="Anexar ficheiro">📎</button>
          <input ref="chatFileInput" type="file" class="hidden" multiple
            accept="image/*,video/*,audio/*,.pdf,.txt,.md,.csv"
            @change="onChatFileSelect($event)" />

          <div class="flex-1 relative">
            <textarea v-model="chatInput"
              ref="chatTextarea"
              rows="1"
              placeholder="Escreve uma instrução ou pergunta… (Enter para enviar)"
              class="w-full px-4 py-2.5 bg-muted border border-border rounded-2xl text-sm resize-none focus:outline-none focus:border-primary transition-colors leading-relaxed"
              style="max-height: 120px; overflow-y: auto;"
              @keydown.enter.exact.prevent="sendChatMessage"
              @keydown.enter.shift.exact="chatInput += '\n'"
              @input="autoResizeChatTextarea"
            ></textarea>
          </div>

          <button @click="sendChatMessage" :disabled="chatLoading || !chatInput.trim()"
            class="w-9 h-9 rounded-xl bg-primary text-primary-foreground flex items-center justify-center shrink-0 mb-0.5 disabled:opacity-40 hover:opacity-90 transition-opacity"
            title="Enviar (Enter)">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
            </svg>
          </button>
        </div>

        <p class="text-[10px] text-muted-foreground text-center">
          Suporta imagens (JPG, PNG, GIF, WebP), PDFs e documentos de texto · Enter envia · Shift+Enter nova linha
        </p>

      </div>

    </div>

  <!-- ════════════════════ MODAL: Exportar Plugin Prompt ════════════════════ -->
  <transition name="fade">
    <div v-if="showPromptModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="showPromptModal = false">

      <div class="bg-card border border-border rounded-2xl w-full max-w-2xl shadow-2xl flex flex-col max-h-[90vh]">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-violet-500/15 flex items-center justify-center">
              <SparklesIcon class="w-4 h-4 text-violet-500" />
            </div>
            <div>
              <h2 class="font-bold text-foreground text-sm">Exportar Plugin Prompt</h2>
              <p class="text-[10px] text-muted-foreground">Formato <code>.afprompt</code> — lido pelo AnimusFlow para instalar o plugin</p>
            </div>
          </div>
          <button @click="showPromptModal = false" class="w-7 h-7 rounded-lg bg-muted hover:bg-border flex items-center justify-center text-muted-foreground transition-colors">✕</button>
        </div>

        <!-- Conteúdo -->
        <div class="overflow-y-auto flex-1 p-6 space-y-5">

          <!-- O que é -->
          <div class="bg-violet-500/10 border border-violet-500/20 rounded-xl px-4 py-3 text-xs text-violet-600 dark:text-violet-400 space-y-2">
            <p class="font-semibold">✦ O que é um Plugin Prompt?</p>
            <p>É um ficheiro de texto estruturado (<code>.afprompt</code>) que contém <strong>todo o plugin num único bloco</strong> — manifest, PHP, Blade, JavaScript, CSS e schema de configurações. O AnimusFlow lê este ficheiro e instala o plugin automaticamente.</p>
          </div>

          <!-- Resumo do plugin -->
          <div class="bg-muted rounded-xl p-4 space-y-3">
            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">📦 Conteúdo que será exportado</p>
            <div class="grid grid-cols-2 gap-2">
              <div v-for="item in promptSummary" :key="item.label"
                class="flex items-center gap-2 bg-card rounded-lg px-3 py-2 border border-border">
                <span class="text-base">{{ item.icon }}</span>
                <div>
                  <p class="text-[10px] font-semibold text-foreground">{{ item.label }}</p>
                  <p class="text-[9px] text-muted-foreground">{{ item.value }}</p>
                </div>
                <span class="ml-auto text-xs" :class="item.ok ? 'text-success' : 'text-muted-foreground'">
                  {{ item.ok ? '✓' : '—' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Fluxo de instalação -->
          <div class="space-y-2">
            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">🔄 Como instalar no AnimusFlow</p>
            <div class="flex flex-col gap-1.5">
              <div v-for="(step, i) in installSteps" :key="i"
                class="flex items-start gap-3 p-3 bg-muted rounded-xl">
                <div class="w-5 h-5 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">{{ i + 1 }}</div>
                <p class="text-xs text-foreground">{{ step }}</p>
              </div>
            </div>
          </div>

          <!-- Pré-visualização do formato -->
          <div class="bg-muted rounded-xl overflow-hidden">
            <div class="flex items-center justify-between px-4 py-2 border-b border-border">
              <span class="text-xs font-semibold text-muted-foreground">Pré-visualização do formato</span>
              <span class="text-[10px] text-muted-foreground font-mono">{{ plugin.name }}.afprompt</span>
            </div>
            <pre class="text-[10px] text-muted-foreground p-4 overflow-x-auto leading-relaxed font-mono">{{ promptPreview }}</pre>
          </div>

        </div>

        <!-- Acções -->
        <div class="flex items-center gap-3 px-6 py-4 border-t border-border shrink-0 bg-muted/50">
          <div class="flex-1 min-w-0">
            <p class="text-xs text-muted-foreground truncate">
              Checksum SHA-256 gerado automaticamente para verificação de integridade.
            </p>
          </div>
          <button @click="showPromptModal = false"
            class="px-4 py-2 bg-muted text-foreground rounded-xl text-sm font-semibold hover:bg-border transition-colors">
            Cancelar
          </button>
          <button @click="copyPromptToClipboard"
            class="px-4 py-2 bg-muted border border-border text-foreground rounded-xl text-sm font-semibold hover:bg-border transition-colors flex items-center gap-2">
            <span>📋</span> Copiar
          </button>
          <a :href="`/plugins/${plugin.uuid}/export-prompt`"
            class="px-5 py-2 bg-violet-600 text-white rounded-xl text-sm font-bold hover:bg-violet-700 transition-colors flex items-center gap-2">
            <SparklesIcon class="w-4 h-4" /> Descarregar .afprompt
          </a>
        </div>

      </div>
    </div>
  </transition>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  DownloadIcon, UploadIcon, SparklesIcon,
  CheckCircleIcon, XCircleIcon, PlusIcon, SlidersIcon,
} from 'lucide-vue-next';

const { t } = useI18n();
const props = defineProps({ plugin: { type: Object, default: null } });

const inp = 'w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary';

// ── Tabs ──
const activeTab = ref('details');
const tabs = [
  { id: 'details', label: 'Detalhes' },
  { id: 'hooks',   label: 'Hooks'    },
  { id: 'php',     label: 'PHP'      },
  { id: 'widget',  label: 'Widget'   },
  { id: 'css',     label: 'CSS'      },
  { id: 'schema',  label: 'Configurações' },
  { id: 'docs',    label: 'Docs'     },
  { id: 'ai',      label: '✨ IA'    },
  { id: 'chat',    label: '💬 Chat IA' },
  { id: 'export',  label: 'Exportar' },
];

const feedback = reactive({ error: '', success: '' });

// ── Edit form ──
const form = reactive({
  label:                   props.plugin?.label                   ?? '',
  description:             props.plugin?.description             ?? '',
  version:                 props.plugin?.version                 ?? '1.0.0',
  author:                  props.plugin?.author                  ?? '',
  author_url:              props.plugin?.author_url              ?? '',
  category:                props.plugin?.category                ?? '',
  tags:                    [...(props.plugin?.tags               ?? [])],
  license:                 props.plugin?.license                 ?? 'MIT',
  min_animusflow_version:  props.plugin?.min_animusflow_version  ?? '1.0.0',
  homepage_url:            props.plugin?.homepage_url            ?? '',
  status:                  props.plugin?.status                  ?? 'draft',
  hooks:                   [...(props.plugin?.hooks              ?? [])],
  plugin_php:              props.plugin?.plugin_php              ?? '',
  widget_blade:            props.plugin?.widget_blade            ?? '',
  widget_js:               props.plugin?.widget_js               ?? '',
  custom_css:              props.plugin?.custom_css              ?? '',
  readme:                  props.plugin?.readme                  ?? '',
  settings_schema:         JSON.parse(JSON.stringify(props.plugin?.settings_schema ?? [])),
});

const saving = ref(false);
function save() {
  saving.value = true;
  feedback.error = ''; feedback.success = '';
  router.put(`/plugins/${props.plugin.uuid}`, form, {
    onFinish:  () => { saving.value = false; },
    onSuccess: () => { feedback.success = 'Guardado com sucesso!'; },
    onError:   (e) => { feedback.error = Object.values(e)[0] ?? 'Erro ao guardar.'; },
  });
}

// ── Hooks ──
const availableHooks = ['page.render', 'content.publish', 'admin.sidebar'];
const hookDescriptions = {
  'page.render':     'Injeta HTML antes de </body> em todas as páginas renderizadas.',
  'content.publish': 'Disparado quando uma página é publicada.',
  'admin.sidebar':   'Adiciona um link ao sidebar do painel de administração.',
};
const hookMethods = {
  'page.render':     'onPageRender($page): string',
  'content.publish': 'onContentPublish($page): void',
  'admin.sidebar':   'onAdminSidebar(): array',
};

// ── PHP scaffold ──
const phpPlaceholder = props.plugin
  ? `<?php\n\ndeclare(strict_types=1);\n\nclass ${props.plugin.name.replace(/[^a-zA-Z0-9]/g, '')}Plugin\n{\n    public function onPageRender($page): string\n    {\n        return '';\n    }\n}`
  : '';

function injectPhpScaffold() {
  if (form.plugin_php && !confirm('Substituir o PHP actual pelo scaffold?')) return;
  const cls = props.plugin.name.replace(/[^a-zA-Z0-9]/g, '');
  const hooks = form.hooks ?? [];
  const methods = hooks.map(h => {
    if (h === 'page.render')     return `\n    public function onPageRender($page): string\n    {\n        return view('${props.plugin.name}::widget')->render();\n    }`;
    if (h === 'content.publish') return `\n    public function onContentPublish($page): void\n    {\n        // ...\n    }`;
    if (h === 'admin.sidebar')   return `\n    public function onAdminSidebar(): array\n    {\n        return ['label' => '${form.label}', 'icon' => '🔌', 'url' => '/admin/plugins'];\n    }`;
    return '';
  }).join('');
  form.plugin_php = `<?php\n\ndeclare(strict_types=1);\n\nclass ${cls}Plugin\n{${methods}\n}\n`;
}

// ── Checklist ──
const checklist = computed(() => [
  { label: 'Label preenchido',          ok: !!form.label },
  { label: 'Descrição preenchida',      ok: !!form.description },
  { label: 'Hooks seleccionados',       ok: (form.hooks ?? []).length > 0 },
  { label: 'Plugin.php com código',     ok: form.plugin_php.length > 50 },
  { label: 'Estado: ready ou published', ok: ['ready','published'].includes(form.status) },
]);

// ── AI Generator ──
const aiPrompt  = ref('');
const aiLoading = ref(false);

async function generateAi() {
  if (!aiPrompt.value.trim() || aiLoading.value) return;
  aiLoading.value = true;
  feedback.error = ''; feedback.success = '';

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const res  = await fetch(`/plugins/${props.plugin.uuid}/generate-ai`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body:    JSON.stringify({ prompt: aiPrompt.value }),
    });
    const data = await res.json();

    if (!res.ok || data.error) { feedback.error = data.error ?? 'AI generation failed.'; return; }

    if (data.plugin_php)   form.plugin_php   = data.plugin_php;
    if (data.widget_blade) form.widget_blade = data.widget_blade;
    if (data.widget_js)    form.widget_js    = data.widget_js;
    if (data.settings_schema?.length) {
      form.settings_schema.splice(0, form.settings_schema.length, ...data.settings_schema);
    }

    feedback.success = 'Código gerado pela IA! Verifica os tabs PHP e Widget.';
    aiPrompt.value   = '';
    activeTab.value  = 'php';
  } catch (e) {
    feedback.error = e.message;
  } finally {
    aiLoading.value = false;
  }
}

// ── Settings Schema builder ──
function addSchemaField() {
  form.settings_schema.push({ key: '', label: '', type: 'text', default: '', placeholder: '', hint: '', options: {}, toggle_label: '' });
}
function removeSchemaField(index) { form.settings_schema.splice(index, 1); }
function selectOptionsText(options) {
  if (!options || typeof options !== 'object') return '';
  return Object.entries(options).map(([v, l]) => `${v}: ${l}`).join('\n');
}
function parseSelectOptions(text) {
  const obj = {};
  for (const line of text.split('\n')) {
    const idx = line.indexOf(':');
    if (idx > 0) { const k = line.slice(0, idx).trim(); const v = line.slice(idx + 1).trim(); if (k) obj[k] = v; }
  }
  return obj;
}

// ── Chat IA ──
const chatMessages       = ref([]);
const chatInput          = ref('');
const chatLoading        = ref(false);
const chatAttachments    = ref([]);
const chatDragging       = ref(false);
const chatPendingUpdates = ref(null);
const chatScrollEl       = ref(null);
const chatTextarea       = ref(null);
const chatFileInput      = ref(null);

const chatQuickPrompts = [
  'Cria um plugin de barra de anúncio',
  'Adiciona suporte a dark mode no widget',
  'Gera um PHP scaffold para page.render',
  'Que campos de configuração devo usar?',
  'Optimiza o CSS do widget',
];

function autoResizeChatTextarea() {
  const el = chatTextarea.value;
  if (!el) return;
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function chatScrollToBottom() {
  nextTick(() => {
    const el = chatScrollEl.value;
    if (el) el.scrollTop = el.scrollHeight;
  });
}

function fileToAttachment(file) {
  const mime = file.type;
  const sizeLabel = file.size > 1024 * 1024
    ? (file.size / 1024 / 1024).toFixed(1) + ' MB'
    : Math.round(file.size / 1024) + ' KB';

  if (mime.startsWith('image/'))      return { file, type: 'image',    name: file.name, icon: '🖼️', url: URL.createObjectURL(file), sizeLabel };
  if (mime === 'application/pdf')     return { file, type: 'document', name: file.name, icon: '📄', url: null, sizeLabel };
  if (mime.startsWith('audio/'))      return { file, type: 'audio',    name: file.name, icon: '🎵', url: null, sizeLabel };
  if (mime.startsWith('video/'))      return { file, type: 'video',    name: file.name, icon: '🎬', url: null, sizeLabel };
  return { file, type: 'document', name: file.name, icon: '📎', url: null, sizeLabel };
}

function onChatFileSelect(event) {
  Array.from(event.target.files ?? []).forEach(f => chatAttachments.value.push(fileToAttachment(f)));
  event.target.value = '';
}

function onChatDrop(event) {
  chatDragging.value = false;
  Array.from(event.dataTransfer.files ?? []).forEach(f => chatAttachments.value.push(fileToAttachment(f)));
}

async function sendChatMessage() {
  const text = chatInput.value.trim();
  if (!text || chatLoading.value) return;

  const attachPreviews = chatAttachments.value.map(a => ({
    type: a.type === 'image' ? 'image' : 'other',
    url: a.url, icon: a.icon, name: a.name,
  }));

  chatMessages.value.push({ role: 'user', content: text, attachmentPreviews: attachPreviews });
  chatScrollToBottom();

  const filesToSend = [...chatAttachments.value];
  chatInput.value = '';
  chatAttachments.value = [];
  chatLoading.value = true;

  const history = chatMessages.value
    .slice(-20)
    .map(m => ({ role: m.role, content: m.content }));

  try {
    const formData = new FormData();
    formData.append('message', text);
    history.slice(0, -1).forEach((m, i) => {
      formData.append(`history[${i}][role]`, m.role);
      formData.append(`history[${i}][content]`, m.content);
    });
    filesToSend.forEach((att, i) => formData.append(`files[${i}]`, att.file));
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

    const res  = await fetch(`/plugins/${props.plugin.uuid}/chat`, { method: 'POST', body: formData });
    const data = await res.json();

    if (!res.ok || data.error) {
      chatMessages.value.push({ role: 'assistant', content: '⚠️ ' + (data.error ?? 'Erro desconhecido.') });
    } else {
      chatMessages.value.push({
        role:    'assistant',
        content: data.reply,
        updates: data.updates ?? null,
        applied: data.applied ?? false,
      });

      if (data.applied && data.plugin) {
        const p = data.plugin;
        if (p.plugin_php    !== undefined) form.plugin_php    = p.plugin_php;
        if (p.widget_blade  !== undefined) form.widget_blade  = p.widget_blade;
        if (p.widget_js     !== undefined) form.widget_js     = p.widget_js;
        if (p.custom_css    !== undefined) form.custom_css    = p.custom_css;
        if (p.settings_schema)             form.settings_schema.splice(0, form.settings_schema.length, ...p.settings_schema);
        if (p.hooks)                       form.hooks         = [...p.hooks];
        if (p.label)                       form.label         = p.label;
        if (p.description   !== undefined) form.description   = p.description;
        if (p.version)                     form.version       = p.version;
        if (p.status)                      form.status        = p.status;
      }
    }
  } catch (e) {
    chatMessages.value.push({ role: 'assistant', content: '⚠️ ' + e.message });
  } finally {
    chatLoading.value = false;
    chatScrollToBottom();
    nextTick(autoResizeChatTextarea);
  }
}

function applyChatUpdates(updates, msgIdx) {
  if (!updates) return;
  if (updates.plugin_php    !== undefined) form.plugin_php    = updates.plugin_php;
  if (updates.widget_blade  !== undefined) form.widget_blade  = updates.widget_blade;
  if (updates.widget_js     !== undefined) form.widget_js     = updates.widget_js;
  if (updates.custom_css    !== undefined) form.custom_css    = updates.custom_css;
  if (updates.settings_schema)             form.settings_schema.splice(0, form.settings_schema.length, ...updates.settings_schema);
  if (updates.hooks)                       form.hooks         = [...updates.hooks];
  if (updates.label)                       form.label         = updates.label;
  if (updates.description   !== undefined) form.description   = updates.description;
  if (updates.version)                     form.version       = updates.version;
  if (updates.status)                      form.status        = updates.status;
  chatMessages.value[msgIdx].applied = true;
}

// ── Plugin Prompt Modal ──────────────────────────────────────────────
const showPromptModal = ref(false);

const promptSummary = computed(() => {
  const f = form;
  const hooksCount = (f.hooks ?? []).length;
  return [
    { icon: '📋', label: 'Metadados',       value: `${f.label} v${f.version}`,                                            ok: !!f.label },
    { icon: '🪝', label: 'Hooks',           value: hooksCount ? (f.hooks ?? []).join(', ') : 'Nenhum',                   ok: hooksCount > 0 },
    { icon: '🐘', label: 'Plugin.php',      value: f.plugin_php?.trim() ? `${f.plugin_php.split('\n').length} linhas` : 'Vazio', ok: !!f.plugin_php?.trim() },
    { icon: '🌐', label: 'Widget Blade',    value: f.widget_blade?.trim() ? `${f.widget_blade.split('\n').length} linhas` : 'Vazio', ok: !!f.widget_blade?.trim() },
    { icon: '⚡', label: 'Widget JS',       value: f.widget_js?.trim() ? `${f.widget_js.split('\n').length} linhas` : 'Vazio',     ok: !!f.widget_js?.trim() },
    { icon: '🎨', label: 'CSS Custom',      value: f.custom_css?.trim() ? `${f.custom_css.split('\n').length} linhas` : 'Vazio',   ok: !!f.custom_css?.trim() },
    { icon: '⚙️',  label: 'Configurações',  value: `${(f.settings_schema ?? []).length} campo(s)`,                                ok: (f.settings_schema ?? []).length > 0 },
    { icon: '📄', label: 'Manifest',        value: `animusflow-plugin.json`,                                                      ok: true },
  ];
});

const installSteps = [
  'Descarrega o ficheiro .afprompt ou copia o conteúdo para o clipboard.',
  'Abre o AnimusFlow Admin → Extensões → Plugins → Importar Prompt.',
  'Cola o bloco completo (incluindo as marcações [AF:PLUGIN:BEGIN] e [AF:PLUGIN:END]).',
  'Clica em "Instalar Plugin" — o AnimusFlow valida o checksum e instala tudo automaticamente.',
  'Activa o plugin em AnimusFlow Admin → Extensões → Plugins.',
];

const promptPreview = computed(() => {
  const divider = '━'.repeat(50);
  const hooks = (form.hooks ?? []).join(', ') || '—';
  const schema = (form.settings_schema ?? []).length;
  return `${divider}
 ANIMUSFLOW PLUGIN PROMPT  v1.0
 Plugin: ${form.label}  (${plugin.name})
 Versão: ${form.version}
${divider}

[AF:PLUGIN:BEGIN]
{
  "af_prompt_version": "1.0",
  "type": "plugin",
  "meta": { "name": "${plugin.name}", "label": "${form.label}", ... },
  "code": {
    "plugin_php":   "<?php ...",
    "widget_blade": "<div class=\\"af-widget\\">...</div>",
    "widget_js":    "// widget JS",
    "custom_css":   "/* CSS */"
  },
  "settings_schema": [ /* ${schema} campo(s) */ ],
  "af_install": {
    "manifest": { "hooks": ["${hooks}"], ... },
    ...
  }
}
[AF:PLUGIN:END]
${divider}
CHECKSUM: sha256:<gerado no servidor>
${divider}`;
});

async function copyPromptToClipboard() {
  try {
    const res  = await fetch(`/plugins/${props.plugin.uuid}/export-prompt`);
    const text = await res.text();
    await navigator.clipboard.writeText(text);
    feedback.success = '📋 Plugin Prompt copiado para o clipboard!';
    setTimeout(() => { feedback.success = ''; }, 3000);
    showPromptModal.value = false;
  } catch {
    feedback.error = 'Não foi possível copiar. Usa o botão Descarregar.';
  }
}

// ── Install in CMS ──
const installingCms = ref(false);
async function installInCms() {
  if (!confirm('Instalar este plugin directamente no CMS local?')) return;
  installingCms.value = true; feedback.error = ''; feedback.success = '';
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const res  = await fetch(`/plugins/${props.plugin.uuid}/install-in-cms`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } });
    const data = await res.json();
    if (!res.ok || data.error) { feedback.error = data.error ?? 'Instalação falhou.'; }
    else { feedback.success = data.message ?? 'Plugin instalado no CMS!'; }
  } catch (e) { feedback.error = e.message; }
  finally { installingCms.value = false; }
}

// ── Publish ──
const publishing = ref(false);
async function publishPlugin() {
  if (!confirm(t('plugins.publish_confirm'))) return;
  publishing.value = true; feedback.error = ''; feedback.success = '';
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const res  = await fetch(`/plugins/${props.plugin.uuid}/publish`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } });
    const data = await res.json();
    if (!res.ok || data.error) { feedback.error = data.error ?? 'Publish failed.'; }
    else { feedback.success = t('plugins.publish_success'); setTimeout(() => router.reload(), 1500); }
  } catch (e) { feedback.error = e.message; }
  finally { publishing.value = false; }
}
</script>
