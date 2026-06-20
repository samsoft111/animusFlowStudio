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

        <!-- ── Inspiration panel ────────────────────────────────── -->
        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-border bg-gradient-to-r from-amber-500/5 via-orange-500/5 to-rose-500/5">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-amber-500/10 rounded-lg flex items-center justify-center text-base">🔍</div>
              <div>
                <p class="text-sm font-semibold text-foreground">Procurar exemplos de plugins</p>
                <p class="text-xs text-muted-foreground mt-0.5">A IA pesquisa padrões reais da categoria e gera 3 exemplos funcionais prontos a usar</p>
              </div>
            </div>
            <button @click="fetchInspiration"
              :disabled="inspireLoading || !form.category"
              class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed"
              :class="form.category
                ? 'bg-amber-500 hover:bg-amber-400 text-white shadow-sm shadow-amber-500/20'
                : 'bg-muted text-muted-foreground'">
              <template v-if="inspireLoading">
                <span class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin inline-block"></span>
                A pesquisar…
              </template>
              <template v-else>
                <span>✨</span>
                {{ form.category ? 'Buscar exemplos para «' + categoryLabel + '»' : 'Selecciona uma categoria primeiro' }}
              </template>
            </button>
          </div>

          <!-- Results -->
          <div v-if="inspireExamples.length" class="p-5 space-y-4">
            <!-- Example selector tabs -->
            <div class="flex gap-2 flex-wrap">
              <button v-for="(ex, i) in inspireExamples" :key="i"
                @click="inspireActive = i"
                class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold border transition-all"
                :class="inspireActive === i
                  ? 'bg-amber-500 text-white border-amber-500 shadow-sm'
                  : 'bg-muted text-muted-foreground border-border hover:border-amber-400 hover:text-amber-600'">
                <span>{{ ['①','②','③'][i] }}</span>
                <span>{{ ex.title }}</span>
                <span class="opacity-60 text-[10px] font-normal px-1.5 py-0.5 rounded-full"
                  :class="ex.complexity === 'simples' ? 'bg-emerald-500/20 text-emerald-600'
                         : ex.complexity === 'avançado' ? 'bg-rose-500/20 text-rose-600'
                         : 'bg-blue-500/20 text-blue-600'">
                  {{ ex.complexity }}
                </span>
              </button>
            </div>

            <!-- Active example card -->
            <div v-if="inspireExamples[inspireActive]" class="border border-border rounded-xl overflow-hidden">
              <!-- Example header -->
              <div class="px-5 py-4 bg-muted/40 border-b border-border flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-foreground">{{ inspireExamples[inspireActive].title }}</p>
                  <p class="text-xs text-muted-foreground mt-1">{{ inspireExamples[inspireActive].description }}</p>
                  <p class="text-xs text-amber-600 dark:text-amber-400 mt-2 flex items-center gap-1">
                    <span>💡</span>
                    <span>{{ inspireExamples[inspireActive].inspiration_source }}</span>
                  </p>
                  <div class="flex flex-wrap gap-1.5 mt-2">
                    <span v-for="h in (inspireExamples[inspireActive].hooks ?? [])" :key="h"
                      class="text-[10px] px-2 py-0.5 bg-primary/10 text-primary rounded-full font-mono">{{ h }}</span>
                  </div>
                </div>
                <button @click="applyInspiration(inspireExamples[inspireActive])"
                  class="shrink-0 flex items-center gap-1.5 px-4 py-2 bg-primary text-primary-foreground rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity shadow-sm">
                  ✅ Usar esta base
                </button>
              </div>

              <!-- Code tabs inside example -->
              <div class="flex gap-0 border-b border-border bg-muted/20 text-xs">
                <button v-for="ct in inspireCodeTabs" :key="ct.id" @click="inspireCodeTab = ct.id"
                  class="px-4 py-2 font-medium transition-colors border-r border-border last:border-0"
                  :class="inspireCodeTab === ct.id ? 'bg-card text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground'">
                  {{ ct.label }}
                </button>
              </div>

              <!-- Code preview -->
              <div class="relative">
                <pre class="overflow-auto text-xs font-mono p-4 bg-[#1e1e2e] text-[#cdd6f4] max-h-72 leading-relaxed whitespace-pre-wrap break-words"><code>{{ inspireCodePreview }}</code></pre>
                <button @click="copyInspireCode"
                  class="absolute top-2 right-2 text-[10px] px-2 py-1 bg-white/10 hover:bg-white/20 text-white rounded transition-colors">
                  {{ inspireCopied ? '✓ Copiado' : 'Copiar' }}
                </button>
              </div>

              <!-- Schema preview if available -->
              <div v-if="(inspireExamples[inspireActive].settings_schema ?? []).length" class="px-4 py-3 border-t border-border bg-muted/20">
                <p class="text-xs font-semibold text-muted-foreground mb-2">{{ inspireExamples[inspireActive].settings_schema.length }} campo(s) de configuração incluídos</p>
                <div class="flex flex-wrap gap-2">
                  <span v-for="f in inspireExamples[inspireActive].settings_schema" :key="f.key"
                    class="text-[10px] px-2 py-1 bg-muted rounded font-mono text-muted-foreground border border-border">
                    {{ f.key }} <span class="opacity-50">{{ f.type }}</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Apply all button -->
            <div class="flex items-center gap-3 pt-1">
              <button @click="applyInspiration(inspireExamples[inspireActive])"
                class="flex items-center gap-2 px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity">
                ✅ Usar «{{ inspireExamples[inspireActive]?.title }}» como base do plugin
              </button>
              <span class="text-xs text-muted-foreground">Preenche PHP, Widget, JS, CSS e Configurações</span>
            </div>
          </div>

          <!-- Empty state -->
          <div v-else-if="!inspireLoading" class="px-5 py-8 text-center">
            <div class="text-3xl mb-3">🔍</div>
            <p class="text-sm text-muted-foreground">
              {{ form.category
                ? 'Clica em «Buscar exemplos» para a IA pesquisar padrões reais de plugins «' + categoryLabel + '» e gerar exemplos prontos a usar.'
                : 'Selecciona uma categoria no formulário acima para desbloquear a pesquisa de exemplos.' }}
            </p>
          </div>

          <!-- Loading skeleton -->
          <div v-if="inspireLoading" class="p-5 space-y-3">
            <div class="flex gap-2">
              <div v-for="i in 3" :key="i" class="h-7 w-28 bg-muted rounded-full animate-pulse"></div>
            </div>
            <div class="h-24 bg-muted rounded-xl animate-pulse"></div>
            <div class="h-48 bg-muted rounded-xl animate-pulse"></div>
          </div>
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

        <!-- AI generate banner -->
        <div class="flex items-center gap-4 px-5 py-4 bg-gradient-to-r from-violet-500/8 via-purple-500/5 to-transparent border border-violet-500/20 rounded-2xl">
          <div class="w-9 h-9 bg-violet-500/15 rounded-xl flex items-center justify-center text-lg shrink-0">📝</div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-foreground">Gerar Documentação Completa com IA</p>
            <p class="text-xs text-muted-foreground mt-0.5">A IA analisa o teu plugin (hooks, configurações, código) e gera um README.md profissional com instalação, referência de campos, exemplos e FAQ</p>
          </div>
          <button @click="generateDocs" :disabled="docsLoading"
            class="shrink-0 flex items-center gap-1.5 px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-50">
            <span v-if="docsLoading" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin inline-block"></span>
            <span v-else>✨</span>
            {{ docsLoading ? 'A gerar…' : 'Gerar com IA' }}
          </button>
        </div>

        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <div>
              <span class="text-sm font-semibold text-foreground">README.md</span>
              <span class="text-xs text-muted-foreground ml-2">Incluído no ZIP e na documentação HTML</span>
            </div>
            <span class="text-xs text-muted-foreground font-mono">{{ (form.readme || '').length }} chars</span>
          </div>
          <textarea v-model="form.readme" rows="22" spellcheck="false"
            placeholder="# Nome do Plugin&#10;&#10;Descrição do plugin...&#10;&#10;## Instalação&#10;&#10;## Utilização"
            class="w-full px-4 py-3 bg-muted/30 text-foreground text-sm font-mono focus:outline-none resize-y border-0" />
        </div>
        <div class="flex items-center gap-3">
          <button @click="save" :disabled="saving" class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50">
            {{ saving ? t('common.loading') : 'Guardar README' }}
          </button>
          <a :href="`/plugins/${plugin.uuid}/export-doc`"
            class="flex items-center gap-1.5 px-5 py-2.5 bg-muted text-foreground border border-border rounded-xl text-sm font-semibold hover:bg-border transition-colors">
            📄 Pré-visualizar Documentação HTML
          </a>
        </div>
      </div>

      <!-- ══════════ Tab: Preview ══════════ -->
      <div v-show="activeTab === 'preview'" class="space-y-3">

        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Device switcher -->
          <div class="flex bg-muted p-1 rounded-xl gap-1">
            <button v-for="d in previewDevices" :key="d.id" @click="previewDevice = d.id"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
              :class="previewDevice === d.id ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
              <span>{{ d.icon }}</span> {{ d.label }}
            </button>
          </div>

          <button @click="refreshPreview"
            class="flex items-center gap-1.5 px-3.5 py-2 bg-primary text-primary-foreground rounded-xl text-xs font-semibold hover:opacity-90 transition-opacity">
            🔄 Actualizar Preview
          </button>

          <a :href="`/plugins/${plugin.uuid}/preview-widget`" target="_blank"
            class="flex items-center gap-1.5 px-3.5 py-2 bg-muted text-foreground border border-border rounded-xl text-xs font-semibold hover:bg-border transition-colors">
            🔗 Abrir em nova janela
          </a>

          <!-- Notes -->
          <div class="ml-auto flex items-center gap-1.5 text-xs text-muted-foreground">
            <span class="text-amber-500">⚠️</span>
            Variáveis Blade <code class="bg-muted px-1 py-0.5 rounded text-xs">&#123;&#123; var &#125;&#125;</code> não são processadas no preview
          </div>
        </div>

        <!-- iframe wrapper with device frame -->
        <div class="flex justify-center">
          <div class="transition-all duration-300 overflow-hidden rounded-2xl shadow-xl border border-border bg-white"
            :style="previewWrapStyle">
            <!-- Device chrome for mobile/tablet -->
            <div v-if="previewDevice !== 'desktop'" class="bg-muted/80 px-4 py-2 border-b border-border flex items-center justify-between">
              <div class="flex gap-1.5">
                <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-yellow-400"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-green-400"></div>
              </div>
              <div class="text-xs text-muted-foreground font-mono">{{ previewDevices.find(d=>d.id===previewDevice)?.label }}</div>
              <div></div>
            </div>
            <iframe ref="previewFrame"
              :src="`/plugins/${plugin.uuid}/preview-widget`"
              :style="previewFrameStyle"
              class="w-full border-0 block"
              sandbox="allow-scripts allow-same-origin">
            </iframe>
          </div>
        </div>

        <!-- Info cards below preview -->
        <div class="grid grid-cols-3 gap-3 max-w-2xl">
          <div class="bg-card border border-border rounded-xl p-3 text-center">
            <div class="text-lg font-bold text-foreground">{{ (form.widget_blade || '').length }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">Widget HTML chars</div>
          </div>
          <div class="bg-card border border-border rounded-xl p-3 text-center">
            <div class="text-lg font-bold text-foreground">{{ (form.custom_css || '').length }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">CSS chars</div>
          </div>
          <div class="bg-card border border-border rounded-xl p-3 text-center">
            <div class="text-lg font-bold text-foreground">{{ (form.widget_js || '').length }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">JS chars</div>
          </div>
        </div>
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

      <!-- ══════════ Tab: Versões ══════════ -->
      <div v-show="activeTab === 'versions'" class="max-w-3xl space-y-4">

        <!-- Top bar: new version form + load button -->
        <div class="flex flex-col sm:flex-row gap-3">

          <!-- Create version card -->
          <div class="flex-1 bg-card border border-border rounded-2xl p-5 space-y-4">
            <div class="flex items-center gap-2">
              <span class="text-lg">🏷️</span>
              <div>
                <p class="text-sm font-semibold text-foreground">Guardar nova versão</p>
                <p class="text-xs text-muted-foreground">Cria um snapshot imutável do estado actual</p>
              </div>
            </div>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Número de versão</label>
                <div class="flex items-center gap-2">
                  <input v-model="newVersionNum" :class="inp" placeholder="ex: 1.1.0" class="flex-1" />
                  <div class="flex gap-1">
                    <button v-for="bump in ['patch','minor','major']" :key="bump"
                      @click="bumpVersion(bump)"
                      class="text-[10px] px-2 py-1 bg-muted hover:bg-border rounded font-mono text-muted-foreground transition-colors">
                      +{{ bump }}
                    </button>
                  </div>
                </div>
                <p class="text-[10px] text-muted-foreground mt-1">Formato semver: MAJOR.MINOR.PATCH (ex: 1.0.0, 2.1.3)</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5">Changelog</label>
                <textarea v-model="newVersionChangelog" rows="3" :class="inp + ' resize-none'"
                  placeholder="O que mudou nesta versão? (opcional)" />
              </div>
              <button @click="createVersion" :disabled="versionSaving || !newVersionNum.trim()"
                class="w-full flex items-center justify-center gap-2 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50 hover:opacity-90 transition-opacity">
                <span v-if="versionSaving" class="w-3.5 h-3.5 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin inline-block"></span>
                <span v-else>💾</span>
                {{ versionSaving ? 'A guardar…' : 'Guardar versão ' + (newVersionNum || '…') }}
              </button>
            </div>
          </div>

          <!-- Current state info -->
          <div class="sm:w-52 bg-gradient-to-b from-primary/5 to-transparent border border-primary/20 rounded-2xl p-4 space-y-3">
            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Estado actual</p>
            <div class="space-y-2 text-xs">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Versão</span>
                <span class="font-mono font-bold text-foreground">{{ form.version }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Estado</span>
                <span class="capitalize font-semibold" :class="form.status==='published'?'text-green-500':form.status==='ready'?'text-blue-500':'text-muted-foreground'">{{ form.status }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Hooks</span>
                <span class="font-mono text-foreground">{{ (form.hooks??[]).length }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Versões</span>
                <span class="font-mono font-bold text-primary">{{ versions.length }}</span>
              </div>
            </div>
            <button @click="loadVersions" :disabled="versionsLoading"
              class="w-full py-1.5 text-xs font-semibold bg-muted hover:bg-border rounded-lg transition-colors flex items-center justify-center gap-1.5">
              <span v-if="versionsLoading" class="w-3 h-3 border-2 border-muted-foreground/30 border-t-muted-foreground rounded-full animate-spin inline-block"></span>
              <span v-else>🔄</span> Actualizar lista
            </button>
          </div>
        </div>

        <!-- Compare bar (shown when 2 versions selected) -->
        <Transition name="slide-down">
          <div v-if="compareA && compareB"
            class="flex items-center gap-3 px-4 py-3 bg-amber-500/10 border border-amber-500/20 rounded-xl">
            <span class="text-base">⚖️</span>
            <span class="text-sm font-semibold text-foreground flex-1">
              Comparar <code class="text-amber-600">v{{ versions.find(v=>v.id===compareA)?.version }}</code>
              com <code class="text-amber-600">v{{ versions.find(v=>v.id===compareB)?.version }}</code>
            </span>
            <button @click="runCompare" :disabled="compareLoading"
              class="px-3.5 py-1.5 bg-amber-500 text-white rounded-lg text-xs font-semibold hover:bg-amber-400 transition-colors disabled:opacity-50">
              <span v-if="compareLoading" class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin inline-block mr-1"></span>
              Ver diferenças
            </button>
            <button @click="compareA=null;compareB=null;diffResult=null" class="text-muted-foreground hover:text-foreground text-xs px-2">✕ Cancelar</button>
          </div>
        </Transition>

        <!-- Diff result -->
        <div v-if="diffResult" class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-5 py-3 border-b border-border bg-muted/40">
            <div class="flex items-center gap-2">
              <span class="text-base">⚖️</span>
              <span class="text-sm font-semibold">
                v{{ diffResult.version_a.version }} → v{{ diffResult.version_b.version }}
              </span>
              <span class="text-xs px-2 py-0.5 bg-amber-500/15 text-amber-600 rounded-full font-semibold">{{ diffResult.changed }} campo(s) alterado(s)</span>
              <span class="text-xs text-muted-foreground">{{ diffResult.unchanged }} iguais</span>
            </div>
            <button @click="diffResult=null" class="text-muted-foreground hover:text-foreground text-xs">✕</button>
          </div>
          <div class="divide-y divide-border">
            <div v-for="d in diffResult.diff" :key="d.field" class="p-4">
              <div class="flex items-center gap-2 mb-2">
                <code class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded">{{ d.field }}</code>
                <span v-if="d.is_code" class="text-[10px] text-muted-foreground">{{ d.a_lines }} → {{ d.b_lines }} linhas</span>
              </div>
              <div v-if="!d.is_code" class="grid grid-cols-2 gap-3">
                <div class="bg-red-500/8 border border-red-500/20 rounded-lg px-3 py-2 text-xs font-mono text-red-700 dark:text-red-400 line-through opacity-70">{{ d.a ?? '(vazio)' }}</div>
                <div class="bg-green-500/8 border border-green-500/20 rounded-lg px-3 py-2 text-xs font-mono text-green-700 dark:text-green-400">{{ d.b ?? '(vazio)' }}</div>
              </div>
              <div v-else class="grid grid-cols-2 gap-3">
                <div class="bg-red-500/5 border border-red-500/20 rounded-lg overflow-hidden">
                  <div class="px-3 py-1 bg-red-500/10 text-[10px] text-red-600 font-semibold">v{{ diffResult.version_a.version }} — {{ d.a_lines }} linhas</div>
                  <pre class="p-3 text-[10px] font-mono text-muted-foreground overflow-auto max-h-48 whitespace-pre-wrap break-words">{{ (d.a ?? '').slice(0, 800) }}{{ (d.a ?? '').length > 800 ? '\n… (truncado)' : '' }}</pre>
                </div>
                <div class="bg-green-500/5 border border-green-500/20 rounded-lg overflow-hidden">
                  <div class="px-3 py-1 bg-green-500/10 text-[10px] text-green-600 font-semibold">v{{ diffResult.version_b.version }} — {{ d.b_lines }} linhas</div>
                  <pre class="p-3 text-[10px] font-mono text-muted-foreground overflow-auto max-h-48 whitespace-pre-wrap break-words">{{ (d.b ?? '').slice(0, 800) }}{{ (d.b ?? '').length > 800 ? '\n… (truncado)' : '' }}</pre>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Version list loading -->
        <div v-if="versionsLoading && !versions.length" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-20 bg-muted rounded-xl animate-pulse"></div>
        </div>

        <!-- Empty state -->
        <div v-if="!versionsLoading && !versions.length"
          class="bg-card border border-dashed border-border rounded-2xl p-10 text-center">
          <div class="text-3xl mb-3">📦</div>
          <p class="text-sm font-semibold text-foreground mb-1">Nenhuma versão guardada ainda</p>
          <p class="text-xs text-muted-foreground">Cria a tua primeira versão acima. Ao publicares no marketplace é criado um snapshot automático.</p>
        </div>

        <!-- Version timeline -->
        <div v-if="versions.length" class="space-y-3">
          <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-2">
            <span>📋 Histórico de versões</span>
            <span class="bg-muted px-2 py-0.5 rounded-full">{{ versions.length }}</span>
          </p>

          <div class="relative">
            <!-- Timeline line -->
            <div class="absolute left-[1.4rem] top-4 bottom-4 w-px bg-border"></div>

            <div class="space-y-3">
              <div v-for="(ver, idx) in versions" :key="ver.id"
                class="relative flex gap-4 items-start">

                <!-- Timeline dot -->
                <div class="relative z-10 shrink-0 mt-3">
                  <div class="w-7 h-7 rounded-full border-2 flex items-center justify-center text-[10px] font-bold"
                    :class="idx === 0
                      ? 'bg-primary border-primary text-primary-foreground'
                      : ver.is_published
                        ? 'bg-green-500 border-green-500 text-white'
                        : 'bg-card border-border text-muted-foreground'">
                    {{ idx === 0 ? '●' : ver.is_published ? '✓' : '○' }}
                  </div>
                </div>

                <!-- Version card -->
                <div class="flex-1 bg-card border border-border rounded-xl overflow-hidden hover:border-primary/30 transition-colors"
                  :class="(compareA === ver.id || compareB === ver.id) ? 'border-amber-400 ring-1 ring-amber-400/30' : ''">

                  <!-- Card header -->
                  <div class="flex items-start justify-between gap-3 px-4 py-3">
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 flex-wrap">
                        <code class="text-sm font-bold text-foreground font-mono">v{{ ver.version }}</code>
                        <span v-if="idx === 0" class="text-[10px] px-2 py-0.5 bg-primary/10 text-primary rounded-full font-semibold">actual</span>
                        <span v-if="ver.is_published" class="text-[10px] px-2 py-0.5 bg-green-500/10 text-green-600 rounded-full font-semibold">publicada</span>
                        <span class="text-xs text-muted-foreground">{{ ver.created_at_human }}</span>
                      </div>
                      <p v-if="ver.changelog" class="text-xs text-muted-foreground mt-1 line-clamp-2">{{ ver.changelog }}</p>
                    </div>
                    <!-- Actions -->
                    <div class="flex items-center gap-1.5 shrink-0">
                      <!-- Compare selector -->
                      <button @click="selectForCompare(ver.id)" title="Seleccionar para comparar"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition-colors"
                        :class="(compareA === ver.id || compareB === ver.id)
                          ? 'bg-amber-500/20 text-amber-600'
                          : 'bg-muted hover:bg-border text-muted-foreground'">
                        ⚖️
                      </button>
                      <!-- View snapshot -->
                      <button @click="viewSnapshot(ver)" title="Ver snapshot completo"
                        class="w-7 h-7 rounded-lg bg-muted hover:bg-border flex items-center justify-center text-xs text-muted-foreground transition-colors">
                        👁️
                      </button>
                      <!-- Restore -->
                      <button v-if="idx !== 0" @click="restoreToVersion(ver)"
                        class="flex items-center gap-1 px-2.5 py-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded-lg text-xs font-semibold transition-colors">
                        ↩️ Restaurar
                      </button>
                    </div>
                  </div>

                  <!-- Summary chips -->
                  <div class="flex flex-wrap gap-1.5 px-4 pb-3">
                    <span v-for="h in (ver.summary?.hooks ?? [])" :key="h"
                      class="text-[10px] px-2 py-0.5 bg-muted font-mono text-muted-foreground rounded-full">{{ h }}</span>
                    <span v-if="ver.summary?.has_php"    class="text-[10px] px-2 py-0.5 bg-blue-500/10 text-blue-600 rounded-full">PHP</span>
                    <span v-if="ver.summary?.has_widget" class="text-[10px] px-2 py-0.5 bg-teal-500/10 text-teal-600 rounded-full">Widget</span>
                    <span v-if="ver.summary?.has_js"     class="text-[10px] px-2 py-0.5 bg-yellow-500/10 text-yellow-600 rounded-full">JS</span>
                    <span v-if="ver.summary?.has_css"    class="text-[10px] px-2 py-0.5 bg-pink-500/10 text-pink-600 rounded-full">CSS</span>
                    <span v-if="ver.summary?.fields"     class="text-[10px] px-2 py-0.5 bg-purple-500/10 text-purple-600 rounded-full">{{ ver.summary.fields }} campo(s)</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Snapshot viewer modal -->
        <Transition name="fade">
          <div v-if="snapshotModal" class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @click.self="snapshotModal=null">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative bg-card border border-border rounded-2xl w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl">
              <div class="flex items-center justify-between px-5 py-4 border-b border-border shrink-0">
                <div>
                  <p class="text-sm font-bold text-foreground">📦 Snapshot v{{ snapshotModal.version }}</p>
                  <p class="text-xs text-muted-foreground mt-0.5">{{ snapshotModal.created_at_human }}</p>
                </div>
                <button @click="snapshotModal=null" class="w-7 h-7 rounded-lg bg-muted hover:bg-border flex items-center justify-center text-muted-foreground">✕</button>
              </div>

              <!-- Snapshot code tabs -->
              <div class="flex border-b border-border bg-muted/30 text-xs shrink-0">
                <button v-for="ct in inspireCodeTabs" :key="ct.id" @click="snapshotCodeTab=ct.id"
                  class="px-4 py-2 font-medium transition-colors border-r border-border last:border-0"
                  :class="snapshotCodeTab===ct.id?'bg-card text-foreground font-semibold':'text-muted-foreground hover:text-foreground'">
                  {{ ct.label }}
                </button>
                <button @click="snapshotCodeTab='meta'"
                  class="px-4 py-2 font-medium transition-colors"
                  :class="snapshotCodeTab==='meta'?'bg-card text-foreground font-semibold':'text-muted-foreground hover:text-foreground'">
                  Meta
                </button>
              </div>

              <div class="flex-1 overflow-auto">
                <div v-if="snapshotCodeTab==='meta'" class="p-5 space-y-2 text-xs">
                  <div v-for="(val, key) in snapshotMeta" :key="key" class="flex gap-3">
                    <span class="font-mono text-muted-foreground w-32 shrink-0">{{ key }}</span>
                    <span class="font-semibold text-foreground break-all">{{ Array.isArray(val) ? val.join(', ') : val }}</span>
                  </div>
                </div>
                <pre v-else class="p-5 text-xs font-mono text-[#cdd6f4] bg-[#1e1e2e] min-h-full whitespace-pre-wrap break-words">{{ snapshotCodeContent }}</pre>
              </div>

              <div class="px-5 py-3 border-t border-border bg-muted/30 flex gap-3 shrink-0">
                <button @click="restoreToVersion(snapshotModal); snapshotModal=null"
                  class="flex items-center gap-1.5 px-4 py-2 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-400">
                  ↩️ Restaurar esta versão
                </button>
                <button @click="snapshotModal=null" class="px-4 py-2 bg-muted rounded-xl text-sm font-semibold hover:bg-border">
                  Fechar
                </button>
              </div>
            </div>
          </div>
        </Transition>

      </div>

      <!-- ══════════ Tab: Macros ══════════ -->
      <div v-show="activeTab === 'recipes'" class="max-w-3xl space-y-5">
        
        <!-- Header -->
        <div class="bg-card border border-border rounded-2xl p-5">
          <div class="flex items-center gap-2">
            <span class="text-xl">⚡</span>
            <div>
              <h2 class="font-semibold text-foreground text-sm">Macros e Receitas Dinâmicas</h2>
              <p class="text-xs text-muted-foreground mt-0.5">
                Executa tarefas repetitivas instantaneamente utilizando as receitas aprendidas localmente.
              </p>
            </div>
          </div>
        </div>

        <!-- Loading state -->
        <div v-if="loadingRecipes" class="flex flex-col items-center justify-center py-12 gap-3">
          <span class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>
          <p class="text-xs text-muted-foreground">A carregar receitas...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="recipes.length === 0" class="bg-card border border-border rounded-2xl p-12 text-center flex flex-col items-center gap-3">
          <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center text-lg">⚡</div>
          <p class="text-sm font-semibold text-foreground">Nenhuma macro ou receita encontrada</p>
          <p class="text-xs text-muted-foreground max-w-sm">
            Podes registar receitas através do Chat IA usando o bloco de código <code>```recipe</code>. Uma vez registadas, elas aparecerão aqui para execução local sem gastar tokens.
          </p>
        </div>

        <!-- Grid of Recipes -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="recipe in recipes" :key="recipe.id" class="bg-card border border-border rounded-2xl p-5 flex flex-col justify-between hover:border-primary/30 transition-colors">
            <div class="space-y-3">
              <div class="flex items-start gap-2">
                <span class="text-lg shrink-0 mt-0.5 text-primary">⚡</span>
                <div>
                  <h3 class="font-semibold text-foreground text-sm font-mono truncate" :title="recipe.name">{{ recipe.name }}</h3>
                  <p class="text-xs text-muted-foreground mt-0.5 leading-relaxed" v-if="recipe.description">{{ recipe.description }}</p>
                </div>
              </div>

              <!-- Pattern -->
              <div class="bg-muted px-2.5 py-1.5 rounded-lg border border-border/50 text-[10px] font-mono text-muted-foreground break-all">
                {{ recipe.prompt_pattern }}
              </div>

              <!-- Form Fields for Placeholders -->
              <div class="space-y-2.5 pt-2" v-if="extractPlaceholders(recipe.prompt_pattern).length > 0">
                <div v-for="ph in extractPlaceholders(recipe.prompt_pattern)" :key="ph" class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">{{ ph }}</label>
                  <input v-model="recipeInputs[recipe.id][ph]" type="text" 
                    :class="inp" class="text-xs px-2.5 py-1.5 rounded-lg" 
                    :placeholder="'Insere o valor para ' + ph" />
                </div>
              </div>
            </div>

            <!-- Execute Button -->
            <div class="pt-4 mt-auto">
              <button @click="executeRecipe(recipe)" 
                class="w-full px-4 py-2 bg-primary/10 hover:bg-primary text-primary hover:text-primary-foreground rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition-all">
                <span>⚡</span> Executar Macro
              </button>
            </div>
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
            <!-- ZIP -->
            <a :href="`/plugins/${plugin.uuid}/export`"
              class="flex items-center justify-center gap-2 px-4 py-3 bg-muted text-foreground rounded-xl text-sm font-semibold hover:bg-border transition-colors">
              <DownloadIcon class="w-4 h-4" /> Descarregar ZIP (inclui DOCS.html + README.md)
            </a>
            <!-- Docs HTML -->
            <a :href="`/plugins/${plugin.uuid}/export-doc`"
              class="flex items-center justify-center gap-2 px-4 py-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-xl text-sm font-semibold hover:bg-blue-500/20 transition-colors">
              📄 Exportar Documentação Completa (.html)
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

        <!-- Header -->
        <div class="flex items-center gap-3 px-4 py-3 bg-violet-500/8 border border-violet-500/20 rounded-xl">
          <div class="w-8 h-8 rounded-lg bg-violet-500/15 flex items-center justify-center shrink-0 text-base">✦</div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-foreground">Assistente de Desenvolvimento IA</p>
            <p class="text-xs text-muted-foreground">Descreve o que queres — eu trato do resto. Podes anexar imagens ou documentos para inspiração.</p>
          </div>
          <button v-if="chatMessages.length" @click="chatMessages = []"
            class="text-xs text-muted-foreground hover:text-foreground px-2 py-1 rounded-lg hover:bg-muted transition-colors shrink-0">
            Limpar
          </button>
        </div>

        <!-- Chat messages -->
        <div ref="chatScrollEl"
          class="flex flex-col gap-3 overflow-y-auto pr-1"
          style="max-height: 55vh; min-height: 200px;">

          <!-- Empty state -->
          <div v-if="chatMessages.length === 0" class="flex flex-col items-center justify-center py-16 gap-3 text-center">
            <div class="w-14 h-14 rounded-2xl bg-violet-500/10 flex items-center justify-center text-2xl">✦</div>
            <p class="text-sm font-semibold text-foreground">O que vamos criar?</p>
            <p class="text-xs text-muted-foreground max-w-xs">Pede um plugin completo ("Cria um plugin de barra de anúncio") ou um ajuste ("Adiciona um campo de cor"). Eu trato do resto.</p>
            <div class="flex flex-wrap gap-2 justify-center mt-2">
              <button v-for="qp in chatQuickPrompts" :key="qp"
                @click="chatInput = qp; sendChatMessage()"
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

            <!-- Build progress card (Modo Construção inline, estilo Claude) -->
            <div v-else-if="msg.type === 'build'" class="flex gap-2 items-start">
              <div class="w-7 h-7 rounded-full bg-violet-500/15 flex items-center justify-center text-xs shrink-0 mt-0.5">✦</div>
              <div class="max-w-[88%] w-full">
                <div class="bg-card border border-border rounded-2xl rounded-bl-sm px-4 py-3">
                  <div class="flex items-center gap-2 mb-2">
                    <span v-if="msg.building" class="w-3.5 h-3.5 border-2 border-violet-500/30 border-t-violet-500 rounded-full animate-spin"></span>
                    <span v-else-if="msg.failed" class="text-destructive text-sm">⚠</span>
                    <span v-else class="text-success text-sm">✓</span>
                    <span class="text-sm font-semibold text-foreground flex-1">{{ msg.building ? 'A construir o teu plugin…' : (msg.failed ? 'Construção interrompida' : 'Plugin construído') }}</span>
                    <button v-if="msg.building" @click="msg.aborted = true" class="text-xs text-destructive hover:underline font-semibold shrink-0">Cancelar</button>
                  </div>

                  <div class="space-y-0.5">
                    <div v-for="(ph, pi) in msg.phases" :key="pi" class="flex items-center gap-2 py-1">
                      <span class="shrink-0 w-4 text-center">
                        <span v-if="ph.status === 'running'" class="w-3 h-3 inline-block border-2 border-violet-500/30 border-t-violet-500 rounded-full animate-spin"></span>
                        <span v-else-if="ph.status === 'done'" class="text-success text-sm">✓</span>
                        <span v-else-if="ph.status === 'error'" class="text-destructive text-sm">⚠</span>
                        <span v-else-if="ph.status === 'cancelled'" class="text-muted-foreground/40 text-xs">✕</span>
                        <span class="text-muted-foreground/40 text-sm" v-else>○</span>
                      </span>
                      <span class="text-sm" :class="ph.status === 'running' ? 'text-foreground font-medium' : (ph.status === 'pending' ? 'text-muted-foreground/50' : 'text-muted-foreground')">{{ ph.label }}</span>
                    </div>
                  </div>

                  <div v-if="msg.phases && msg.phases.some(p => p.reply)" class="border-t border-border mt-2 pt-2">
                    <button @click="msg.showDetails = !msg.showDetails"
                      class="text-[11px] text-muted-foreground hover:text-foreground flex items-center gap-1 transition-colors">
                      <span class="text-[9px]">{{ msg.showDetails ? '▾' : '▸' }}</span> Ver detalhes técnicos
                    </button>
                    <div v-if="msg.showDetails" class="mt-2 space-y-1.5">
                      <template v-for="(ph, pi) in msg.phases" :key="pi">
                        <div v-if="ph.reply" class="text-[11px] leading-relaxed">
                          <span class="font-semibold text-foreground">{{ ph.label }}:</span>
                          <span class="text-muted-foreground"> {{ ph.reply }}</span>
                        </div>
                      </template>
                    </div>
                  </div>
                </div>

                <div v-if="!msg.building && !msg.failed" class="mt-1.5 flex items-center gap-1.5">
                  <span class="text-[10px] text-success font-semibold flex items-center gap-1">✓ Aplicadas e guardadas automaticamente</span>
                </div>
                <div v-if="msg.failed && msg.snapshot" class="mt-2 flex items-center gap-2">
                  <button @click="restoreToVersion(msg.snapshot)"
                    class="px-3 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors">
                    <RotateCcwIcon class="w-3 h-3" /> Restaurar cópia de segurança (v{{ msg.snapshot.version }})
                  </button>
                </div>
                <p v-if="msg.error" class="mt-1.5 text-[10px] text-destructive">{{ msg.error }}</p>
              </div>
            </div>

            <!-- Assistant message -->
            <div v-else class="flex gap-2 items-end">
              <div class="w-7 h-7 rounded-full bg-violet-500/15 flex items-center justify-center text-xs shrink-0 mb-0.5">✦</div>
              <div class="max-w-[82%]">
                <div class="bg-card border border-border rounded-2xl rounded-bl-sm px-4 py-2.5 text-sm leading-relaxed whitespace-pre-wrap text-foreground">{{ msg.content }}</div>
                
                <div v-if="msg.cached" class="mt-1.5 flex items-center gap-1 text-[10px] text-indigo-500 font-semibold bg-indigo-500/5 px-2 py-0.5 rounded-full w-max border border-indigo-500/10">
                  ⚡ Resolvido via memória local (Sem custo de tokens)
                </div>

                <div v-if="msg.applied" class="mt-1.5 flex items-center gap-1.5">
                  <span class="text-[10px] text-success font-semibold flex items-center gap-1">✓ Aplicadas e guardadas automaticamente</span>
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
          <div v-if="chatLoading && !lastMsgBuilding" class="flex gap-2 items-end">
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
import { ref, reactive, computed, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  DownloadIcon, UploadIcon, SparklesIcon,
  CheckCircleIcon, XCircleIcon, PlusIcon, SlidersIcon, RotateCcwIcon,
} from 'lucide-vue-next';

const { t } = useI18n();
const props = defineProps({
  plugin:       { type: Object, default: null },
  pluginAgents: { type: Array,  default: () => [] },
});

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
  { id: 'preview', label: '👁️ Preview' },
  { id: 'ai',      label: '✨ IA'    },
  { id: 'chat',    label: '💬 Chat IA' },
  { id: 'versions', label: '📦 Versões' },
  { id: 'recipes', label: '⚡ Macros' },
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

// ── Inspiration ──
const categoryLabels = {
  seo: 'SEO', analytics: 'Analytics', ecommerce: 'E-commerce', social: 'Social Media',
  forms: 'Forms', ai: 'AI / Chatbot', design: 'Design', marketing: 'Marketing', utilities: 'Utilities',
};
const categoryLabel = computed(() => categoryLabels[form.category] ?? form.category);

const inspireLoading  = ref(false);
const inspireExamples = ref([]);
const inspireActive   = ref(0);
const inspireCodeTab  = ref('php');
const inspireCopied   = ref(false);

const inspireCodeTabs = [
  { id: 'php',    label: 'Plugin.php' },
  { id: 'widget', label: 'Widget Blade' },
  { id: 'js',     label: 'JavaScript' },
  { id: 'css',    label: 'CSS' },
];

const inspireCodePreview = computed(() => {
  const ex = inspireExamples.value[inspireActive.value];
  if (!ex) return '';
  const map = { php: ex.plugin_php, widget: ex.widget_blade, js: ex.widget_js, css: ex.custom_css };
  return map[inspireCodeTab.value] ?? '';
});

async function fetchInspiration() {
  if (!form.category || inspireLoading.value) return;
  inspireLoading.value = true;
  inspireExamples.value = [];
  inspireActive.value   = 0;
  inspireCodeTab.value  = 'php';
  try {
    const res = await fetch(`/plugins/${props.plugin.uuid}/inspire`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
      body: JSON.stringify({ category: form.category }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Erro desconhecido');
    inspireExamples.value = data.examples ?? [];
  } catch (e) {
    feedback.error = '❌ ' + (e.message ?? 'Erro ao buscar exemplos.');
  } finally {
    inspireLoading.value = false;
  }
}

function applyInspiration(ex) {
  if (!ex) return;
  const has = (v) => v && v.trim().length > 0;
  const confirmMsg = [
    has(form.plugin_php)   && 'PHP',
    has(form.widget_blade) && 'Widget',
    has(form.widget_js)    && 'JavaScript',
    has(form.custom_css)   && 'CSS',
  ].filter(Boolean);
  if (confirmMsg.length && !confirm(`Isto vai substituir o conteúdo actual de: ${confirmMsg.join(', ')}.\n\nContinuar?`)) return;

  if (ex.plugin_php)       form.plugin_php       = ex.plugin_php;
  if (ex.widget_blade)     form.widget_blade     = ex.widget_blade;
  if (ex.widget_js)        form.widget_js        = ex.widget_js;
  if (ex.custom_css)       form.custom_css       = ex.custom_css;
  if (ex.hooks?.length)    form.hooks            = [...ex.hooks];
  if (ex.settings_schema?.length) form.settings_schema = JSON.parse(JSON.stringify(ex.settings_schema));

  feedback.success = `✅ Exemplo «${ex.title}» aplicado! Revê e guarda.`;
  // Jump to PHP tab to review
  activeTab.value = 'php';
}

async function copyInspireCode() {
  const text = inspireCodePreview.value;
  if (!text) return;
  try {
    await navigator.clipboard.writeText(text);
    inspireCopied.value = true;
    setTimeout(() => { inspireCopied.value = false; }, 2000);
  } catch {}
}

// ── Preview ──
const previewFrame  = ref(null);
const previewDevice = ref('desktop');
const previewDevices = [
  { id: 'desktop', label: 'Desktop',  icon: '🖥️',  width: '100%',  height: '680px' },
  { id: 'tablet',  label: 'Tablet',   icon: '📱',  width: '768px', height: '600px' },
  { id: 'mobile',  label: 'Mobile',   icon: '📲',  width: '375px', height: '700px' },
];

const previewWrapStyle = computed(() => {
  const d = previewDevices.find(d => d.id === previewDevice.value) ?? previewDevices[0];
  return {
    width:    d.id === 'desktop' ? '100%' : d.width,
    maxWidth: d.id === 'desktop' ? '100%' : d.width,
  };
});

const previewFrameStyle = computed(() => {
  const d = previewDevices.find(d => d.id === previewDevice.value) ?? previewDevices[0];
  return {
    height: d.height,
    width:  d.id === 'desktop' ? '100%' : d.width,
  };
});

function refreshPreview() {
  if (previewFrame.value) {
    const src = previewFrame.value.src;
    previewFrame.value.src = '';
    nextTick(() => { previewFrame.value.src = src; });
  }
}

// ── Docs generation ──
const docsLoading = ref(false);

async function generateDocs() {
  if (docsLoading.value) return;
  docsLoading.value = true;
  try {
    const res = await fetch(`/plugins/${props.plugin.uuid}/generate-docs`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
      body: JSON.stringify({}),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Erro desconhecido');
    form.readme = data.readme ?? '';
    feedback.success = '✅ Documentação gerada! Revê e guarda.';
  } catch (e) {
    feedback.error = '❌ ' + (e.message ?? 'Erro ao gerar documentação.');
  } finally {
    docsLoading.value = false;
  }
}

// ── Versioning ──
const versions         = ref([]);
const versionsLoading  = ref(false);
const versionSaving    = ref(false);
const newVersionNum    = ref('');
const newVersionChangelog = ref('');
const compareA         = ref(null);
const compareB         = ref(null);
const compareLoading   = ref(false);
const diffResult       = ref(null);
const snapshotModal    = ref(null);      // full snapshot data for modal
const snapshotCodeTab  = ref('php');

// Pre-fill version number with current plugin version on tab open
watch(() => activeTab.value, (tab) => {
  if (tab === 'versions') {
    loadVersions();
    if (!newVersionNum.value) newVersionNum.value = form.version ?? '1.0.0';
  }
});

async function loadVersions() {
  versionsLoading.value = true;
  try {
    const res = await fetch(`/plugins/${props.plugin.uuid}/versions`, {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
    });
    const data = await res.json();
    versions.value = data.versions ?? [];
  } catch {}
  finally { versionsLoading.value = false; }
}

function bumpVersion(type) {
  const current = (newVersionNum.value || form.version || '1.0.0').trim();
  const parts = current.split('.').map(Number);
  if (parts.length < 3) { newVersionNum.value = current; return; }
  if (type === 'patch') { parts[2]++; }
  else if (type === 'minor') { parts[1]++; parts[2] = 0; }
  else if (type === 'major') { parts[0]++; parts[1] = 0; parts[2] = 0; }
  newVersionNum.value = parts.join('.');
}

async function createVersion() {
  if (!newVersionNum.value.trim() || versionSaving.value) return;
  versionSaving.value = true;
  try {
    const res = await fetch(`/plugins/${props.plugin.uuid}/versions`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
      body: JSON.stringify({ version: newVersionNum.value.trim(), changelog: newVersionChangelog.value }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Erro ao criar versão');
    versions.value.unshift(data.version);
    form.version = data.version.version;
    newVersionChangelog.value = '';
    feedback.success = `✅ Versão v${data.version.version} guardada com sucesso!`;
  } catch (e) {
    feedback.error = '❌ ' + (e.message ?? 'Erro ao guardar versão.');
  } finally {
    versionSaving.value = false;
  }
}

function selectForCompare(id) {
  if (compareA.value === id) { compareA.value = null; return; }
  if (compareB.value === id) { compareB.value = null; return; }
  if (!compareA.value) { compareA.value = id; return; }
  if (!compareB.value) { compareB.value = id; return; }
  // Both set — replace B and keep A
  compareB.value = id;
}

async function runCompare() {
  if (!compareA.value || !compareB.value || compareLoading.value) return;
  compareLoading.value = true;
  diffResult.value = null;
  try {
    const res = await fetch(`/plugins/${props.plugin.uuid}/versions/compare`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
      body: JSON.stringify({ version_a: compareA.value, version_b: compareB.value }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Erro ao comparar');
    diffResult.value = data;
  } catch (e) {
    feedback.error = '❌ ' + (e.message ?? 'Erro ao comparar versões.');
  } finally {
    compareLoading.value = false;
  }
}

async function viewSnapshot(ver) {
  snapshotCodeTab.value = 'php';
  try {
    const res = await fetch(`/plugins/${props.plugin.uuid}/versions/${ver.id}`, {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
    });
    const data = await res.json();
    snapshotModal.value = { ...ver, ...data };
  } catch {
    snapshotModal.value = { ...ver, snapshot: {} };
  }
}

const snapshotCodeContent = computed(() => {
  if (!snapshotModal.value?.snapshot) return '';
  const snap = snapshotModal.value.snapshot;
  const map = { php: snap.plugin_php, widget: snap.widget_blade, js: snap.widget_js, css: snap.custom_css };
  return map[snapshotCodeTab.value] ?? '';
});

const snapshotMeta = computed(() => {
  if (!snapshotModal.value?.snapshot) return {};
  const snap = snapshotModal.value.snapshot;
  return {
    label: snap.label, version: snap.version, description: snap.description,
    status: snap.status, category: snap.category, license: snap.license,
    author: snap.author, hooks: snap.hooks,
    fields: (snap.settings_schema ?? []).length + ' campo(s)',
  };
});

async function restoreToVersion(ver) {
  if (!confirm(`Restaurar para v${ver.version}?\n\nO estado actual do plugin será substituído pelo snapshot desta versão. Esta acção não pode ser desfeita automaticamente (guarda uma nova versão antes se quiseres preservar o estado actual).`)) return;
  try {
    const res = await fetch(`/plugins/${props.plugin.uuid}/versions/${ver.id}/restore`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Erro ao restaurar');
    // Apply snapshot fields to form
    const snap = data.plugin;
    const fields = ['label','description','version','author','author_url','category','tags','license',
                    'min_animusflow_version','homepage_url','status','hooks','plugin_php','widget_blade',
                    'widget_js','custom_css','readme','settings_schema'];
    fields.forEach(f => { if (snap[f] !== undefined) form[f] = Array.isArray(snap[f]) ? [...snap[f]] : snap[f]; });
    feedback.success = `✅ Restaurado para v${ver.version}. Revê e guarda.`;
    await loadVersions();
  } catch (e) {
    feedback.error = '❌ ' + (e.message ?? 'Erro ao restaurar.');
  }
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
    if (h === 'page.render')     return `\n    public function onPageRender($page): string\n    {\n        $html = file_get_contents(__DIR__ . '/views/widget.blade.php');\n        return $html !== false ? $html : '';\n    }`;
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
      if (data.reply) {
        chatMessages.value.push({
          role:    'assistant',
          content: data.reply,
          updates: data.updates ?? null,
          applied: data.applied ?? false,
          cached:  data.cached ?? false,
        });
      }
      if (data.applied && data.plugin) applyServerPlugin(data.plugin);

      // A IA decidiu que isto justifica uma construção completa → pipeline inline
      if (data.build && data.build.brief) {
        const buildIdx = chatMessages.value.length;
        chatMessages.value.push({ role: 'assistant', type: 'build', phases: [], building: true });
        await runBuildFlow(data.build.brief, buildIdx);
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

// Merge a fresh server plugin into the local form
function applyServerPlugin(p) {
  if (!p) return;
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

// ── Modo Construção — pipeline inline na conversa (estilo Claude) ───────────
const PHASE_META = {
  logic:    'A gerar a lógica do plugin',
  widget:   'A criar a interface (widget)',
  settings: 'A definir as configurações',
};
function phaseLabel(id) { return PHASE_META[id] ?? 'A trabalhar no plugin'; }

// Esconde o indicador genérico de "a escrever" quando há um cartão de construção activo
const lastMsgBuilding = computed(() => {
  const m = chatMessages.value[chatMessages.value.length - 1];
  return !!(m && m.type === 'build' && m.building);
});

function chatCsrf() { return document.querySelector('meta[name="csrf-token"]')?.content ?? ''; }

// Executa um agente (segundo plano); actualiza a fase e devolve {ok, isFatal}
async function runBuildAgent(phase, ctx) {
  phase.status = 'running';
  try {
    const fd = new FormData();
    fd.append('agent', phase.agent);
    if (ctx.brief)     fd.append('brief', ctx.brief);
    if (ctx.direction) fd.append('direction', ctx.direction);
    if (ctx.note)      fd.append('note', ctx.note);
    fd.append('_token', chatCsrf());
    const res = await fetch(`/plugins/${props.plugin.uuid}/build/step`, { method: 'POST', body: fd });
    if (!(res.headers.get('content-type') ?? '').includes('application/json')) {
      phase.status = 'error'; return { ok: false, isFatal: true };
    }
    const data = await res.json();
    if (!res.ok || data.error) {
      phase.status = 'error'; phase.reply = data.error ?? 'Erro.';
      return { ok: false, isFatal: !!data.is_fatal };
    }
    if (data.applied && data.plugin) applyServerPlugin(data.plugin);
    phase.reply = data.reply ?? '';
    phase.status = 'done';
    return { ok: true, isFatal: false };
  } catch (e) {
    phase.status = 'error'; phase.reply = e.message;
    return { ok: false, isFatal: false };
  }
}

// Orquestra a construção completa do plugin, mostrando fases legíveis na conversa.
async function runBuildFlow(brief, msgIdx) {
  const msg = chatMessages.value[msgIdx];
  msg.building = true; msg.failed = false; msg.error = ''; msg.aborted = false;
  msg.phases = [{ agent: '__plan__', label: 'A planear a construção', status: 'running', reply: '' }];
  chatScrollToBottom();

  let direction = '';

  // 1. Planear
  try {
    const fd = new FormData();
    fd.append('brief', brief);
    fd.append('_token', chatCsrf());
    const res = await fetch(`/plugins/${props.plugin.uuid}/build/plan`, { method: 'POST', body: fd });
    if (!(res.headers.get('content-type') ?? '').includes('application/json')) {
      msg.phases[0].status = 'error'; msg.building = false; msg.failed = true;
      msg.error = 'Sessão expirada — faz login novamente.'; return;
    }
    const data = await res.json();
    if (!res.ok || data.error) {
      msg.phases[0].status = 'error'; msg.building = false; msg.failed = true;
      msg.error = data.error ?? 'Não consegui planear a construção.'; return;
    }
    direction = data.direction ?? '';
    msg.phases[0].status = 'done';
    msg.phases[0].reply = direction;

    if (data.snapshot) {
      msg.snapshot = data.snapshot;
      await loadVersions(); // Atualizar lista na aba de versões
    }

    msg.phases.push(...(data.agents ?? []).map(id => ({ agent: id, label: phaseLabel(id), status: 'pending', reply: '' })));
  } catch (e) {
    msg.phases[0].status = 'error'; msg.building = false; msg.failed = true;
    msg.error = e.message; return;
  }

  // 2. Executar agentes em sequência
  const ctx = { brief, direction };
  for (const phase of msg.phases) {
    if (phase.agent === '__plan__' || phase.status === 'done') continue;

    if (msg.aborted) {
      phase.status = 'cancelled';
      phase.reply = 'Cancelado pelo utilizador.';
      continue;
    }

    chatScrollToBottom();
    const r = await runBuildAgent(phase, ctx);

    if (msg.aborted) {
      phase.status = 'cancelled';
      phase.reply = 'Cancelado pelo utilizador.';
      continue;
    }

    if (!r.ok && r.isFatal) {
      msg.building = false; msg.failed = true;
      msg.error = 'A construção foi interrompida por um erro do sistema de IA. Tenta novamente daqui a pouco.';
      return;
    }
  }

  if (msg.aborted) {
    msg.building = false;
    msg.failed = true;
    msg.error = 'Construção cancelada pelo utilizador.';
    chatScrollToBottom();
    return;
  }

  // 3. Rever a qualidade + corrigir automaticamente
  const verifyPhase = { agent: '__verify__', label: 'A rever a qualidade', status: 'running', reply: '' };
  msg.phases.push(verifyPhase);
  chatScrollToBottom();
  try {
    const fd = new FormData();
    fd.append('brief', brief);
    if (direction) fd.append('direction', direction);
    fd.append('_token', chatCsrf());
    const res = await fetch(`/plugins/${props.plugin.uuid}/build/verify`, { method: 'POST', body: fd });
    if (msg.aborted) {
      verifyPhase.status = 'cancelled';
      msg.building = false;
      msg.failed = true;
      msg.error = 'Construção cancelada pelo utilizador.';
      chatScrollToBottom();
      return;
    }
    const data = (res.headers.get('content-type') ?? '').includes('application/json') ? await res.json() : {};
    if (res.ok && !data.error) {
      verifyPhase.reply = data.summary ?? '';
      for (const iss of (data.issues ?? [])) {
        if (msg.aborted) break;
        const fixPhase = { agent: iss.agent, label: 'A melhorar: ' + phaseLabel(iss.agent), status: 'running', reply: '' };
        msg.phases.push(fixPhase);
        chatScrollToBottom();
        const r = await runBuildAgent(fixPhase, { brief, direction, note: iss.reason });
        if (msg.aborted) {
          fixPhase.status = 'cancelled';
          break;
        }
        if (!r.ok && r.isFatal) break;
      }
    }
    verifyPhase.status = msg.aborted ? 'cancelled' : 'done';
  } catch (e) {
    verifyPhase.status = 'done';
  }

  msg.building = false;
  if (msg.aborted) {
    msg.failed = true;
    msg.error = 'Construção cancelada pelo utilizador.';
  } else {
    feedback.success = 'Plugin construído com sucesso!';
  }
  chatScrollToBottom();
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

// ── Receitas / Macros ─────────────────────────────────────────────
const recipes = ref([]);
const loadingRecipes = ref(false);
const recipeInputs = ref({});

watch(activeTab, (tab) => {
  if (tab === 'recipes' && recipes.value.length === 0 && !loadingRecipes.value) {
    loadRecipes();
  }
});

async function loadRecipes() {
  loadingRecipes.value = true;
  try {
    const res = await axios.get(`/plugins/${props.plugin.uuid}/recipes`);
    recipes.value = res.data.recipes || [];
    recipes.value.forEach(recipe => {
      const placeholders = extractPlaceholders(recipe.prompt_pattern);
      if (!recipeInputs.value[recipe.id]) {
        recipeInputs.value[recipe.id] = {};
      }
      placeholders.forEach(ph => {
        if (recipeInputs.value[recipe.id][ph] === undefined) {
          recipeInputs.value[recipe.id][ph] = '';
        }
      });
    });
  } catch (e) {
    console.error('Erro ao carregar receitas:', e);
  } finally {
    loadingRecipes.value = false;
  }
}

function extractPlaceholders(pattern) {
  if (!pattern) return [];
  const matches = pattern.match(/\{([a-zA-Z0-9_]+)\}/g);
  if (!matches) return [];
  return matches.map(m => m.slice(1, -1));
}

async function executeRecipe(recipe) {
  let prompt = recipe.prompt_pattern;
  const inputs = recipeInputs.value[recipe.id] || {};
  const placeholders = extractPlaceholders(recipe.prompt_pattern);
  
  for (const ph of placeholders) {
    const val = inputs[ph] || '';
    prompt = prompt.replace(`{${ph}}`, val);
  }
  
  chatInput.value = prompt;
  activeTab.value = 'chat';
  await sendChatMessage();
}
</script>

<style scoped>
/* Slide-down transition (compare bar) */
.slide-down-enter-active, .slide-down-leave-active { transition: all .25s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); max-height: 0; }
.slide-down-enter-to, .slide-down-leave-from { opacity: 1; transform: translateY(0); max-height: 200px; }

/* Fade transition (snapshot modal) */
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
