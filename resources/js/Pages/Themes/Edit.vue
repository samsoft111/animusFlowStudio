<template>
  <AppLayout :title="theme.label">
    <template #actions>
      <div class="flex flex-wrap gap-1 sm:gap-1.5 justify-end items-center">
        <a :href="`/preview/theme/${theme.uuid}`" target="_blank"
          class="px-2 sm:px-3 py-2 bg-muted text-foreground rounded-lg text-xs sm:text-sm font-semibold hover:bg-border transition-colors flex items-center gap-1 sm:gap-1.5">
          <EyeIcon class="w-3.5 h-3.5 shrink-0" /> <span class="hidden sm:inline">{{ t('themes.preview') }}</span>
        </a>
        <a :href="`/themes/${theme.uuid}/export`"
          class="px-2 sm:px-3 py-2 bg-muted text-foreground rounded-lg text-xs sm:text-sm font-semibold hover:bg-border transition-colors flex items-center gap-1 sm:gap-1.5">
          <DownloadIcon class="w-3.5 h-3.5 shrink-0" /> <span class="hidden sm:inline">{{ t('common.export') }}</span>
        </a>
        <button @click="showPromptModal = true"
          class="px-2 sm:px-3 py-2 bg-violet-500/10 text-violet-600 dark:text-violet-400 border border-violet-500/20 rounded-lg text-xs sm:text-sm font-semibold hover:bg-violet-500/20 transition-colors flex items-center gap-1 sm:gap-1.5">
          <SparklesIcon class="w-3.5 h-3.5 shrink-0" /> <span class="hidden md:inline">Exportar Prompt</span>
        </button>
        <button @click="installInCms" :disabled="installingCms"
          class="px-2 sm:px-3 py-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-lg text-xs sm:text-sm font-semibold hover:bg-emerald-500/20 transition-colors flex items-center gap-1 sm:gap-1.5 disabled:opacity-50">
          <template v-if="installingCms"><span class="w-3.5 h-3.5 border-2 border-emerald-500/30 border-t-emerald-500 rounded-full animate-spin inline-block"></span></template>
          <template v-else>⚡</template>
          <span class="hidden md:inline">{{ installingCms ? 'A instalar…' : 'Instalar no CMS' }}</span>
        </button>
        <button @click="publishTheme" :disabled="publishing"
          class="px-2 sm:px-3 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-colors flex items-center gap-1 sm:gap-1.5 disabled:opacity-50"
          :class="theme.is_published ? 'bg-success/10 text-success hover:bg-success/20' : 'bg-primary text-primary-foreground hover:opacity-90'">
          <UploadIcon class="w-3.5 h-3.5 shrink-0" />
          <span class="hidden sm:inline">{{ publishing ? t('common.loading') : (theme.is_published ? t('themes.republish') : t('themes.publish')) }}</span>
        </button>
      </div>
    </template>

    <!-- EDIT TABS -->
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

      <!-- ══════════════════════════════════════════════════ -->
      <!-- STEPPER DE PROGRESSO                             -->
      <!-- ══════════════════════════════════════════════════ -->
      <div class="bg-card border border-border rounded-2xl overflow-hidden">

        <!-- Cabeçalho do stepper -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between px-4 sm:px-5 py-3 border-b border-border gap-2">
          <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center shrink-0">
              <span class="text-[10px] font-bold text-primary-foreground">{{ completedCore }}/{{ coreSteps.length }}</span>
            </div>
            <span class="text-sm font-semibold text-foreground">Progresso do tema</span>
            <span class="text-xs text-muted-foreground">— {{ progressPct }}% concluído</span>
          </div>
          <div class="flex items-center gap-2">
            <button @click="guideOpen = !guideOpen"
              class="text-xs text-muted-foreground hover:text-foreground flex items-center gap-1 transition-colors">
              {{ guideOpen ? '▲ Fechar guia' : '▼ Ver guia' }}
            </button>
          </div>
        </div>

        <!-- Barra de progresso total -->
        <div class="h-0.5 bg-muted">
          <div class="h-full bg-primary transition-all duration-500"
            :style="{ width: progressPct + '%' }"></div>
        </div>

        <!-- Passos horizontais -->
        <div class="relative">
          <!-- Gradient fade direita (indica mais conteúdo) -->
          <div class="pointer-events-none absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-card to-transparent z-10"></div>
          <div class="flex overflow-x-auto px-4 py-3 gap-0"
               style="-webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none;">
            <div v-for="(step, idx) in workflowSteps" :key="step.tabId"
              class="flex items-center shrink-0">

              <!-- Passo -->
              <button @click="activeTab = step.tabId"
                class="flex flex-col items-center gap-1 px-2 sm:px-3 py-1 rounded-xl transition-all group relative"
                :class="activeTab === step.tabId
                  ? 'bg-primary/10'
                  : 'hover:bg-muted'">

                <!-- Círculo do passo -->
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all border-2"
                  :class="step.done
                    ? 'bg-primary border-primary text-primary-foreground'
                    : activeTab === step.tabId
                      ? 'bg-card border-primary text-primary'
                      : 'bg-muted border-border text-muted-foreground group-hover:border-primary/50'">
                  <span v-if="step.done">✓</span>
                  <span v-else>{{ idx + 1 }}</span>
                </div>

                <!-- Label -->
                <span class="text-[9px] font-semibold whitespace-nowrap transition-colors"
                  :class="activeTab === step.tabId
                    ? 'text-primary'
                    : step.done
                      ? 'text-foreground'
                      : 'text-muted-foreground group-hover:text-foreground'">
                  {{ step.icon }} {{ step.label }}
                </span>
              </button>

              <!-- Linha conectora -->
              <div v-if="idx < workflowSteps.length - 1"
                class="w-4 sm:w-6 h-0.5 shrink-0 transition-colors"
                :class="step.done ? 'bg-primary' : 'bg-border'">
              </div>

            </div>
          </div>
        </div>

        <!-- Painel de guia colapsável -->
        <transition name="guide-slide">
          <div v-if="guideOpen" class="border-t border-border px-5 py-4 bg-muted/40">
            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">📋 Guia passo a passo</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
              <button v-for="(step, idx) in workflowSteps" :key="step.tabId"
                @click="activeTab = step.tabId; guideOpen = false"
                class="flex items-start gap-3 p-3 rounded-xl border text-left transition-all"
                :class="step.done
                  ? 'bg-primary/5 border-primary/20 hover:bg-primary/10'
                  : activeTab === step.tabId
                    ? 'bg-card border-primary shadow-sm'
                    : 'bg-card border-border hover:border-primary/30 hover:bg-muted'">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5"
                  :class="step.done ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground border border-border'">
                  {{ step.done ? '✓' : idx + 1 }}
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-foreground flex items-center gap-1.5">
                    {{ step.icon }} {{ step.label }}
                    <span v-if="step.optional" class="text-[8px] font-medium uppercase tracking-wide text-muted-foreground bg-muted px-1 py-0.5 rounded">opcional</span>
                  </p>
                  <p class="text-[10px] text-muted-foreground mt-0.5 leading-relaxed">{{ step.hint }}</p>
                  <!-- Diário do passo (schema espelho): última origem + reverter -->
                  <div v-if="stepJournalFor(step.tabId)" class="mt-1.5 flex items-center gap-2 flex-wrap">
                    <span class="text-[9px] font-medium text-muted-foreground inline-flex items-center gap-1 bg-muted/60 px-1.5 py-0.5 rounded">
                      {{ sourceMeta(stepJournalFor(step.tabId).source).icon }} {{ sourceMeta(stepJournalFor(step.tabId).source).label }}
                    </span>
                    <span v-if="stepJournalFor(step.tabId).history?.length" class="text-[9px] text-muted-foreground">
                      · {{ stepJournalFor(step.tabId).history.length }} alteraç{{ stepJournalFor(step.tabId).history.length === 1 ? 'ão' : 'ões' }}
                    </span>
                    <span v-if="stepJournalFor(step.tabId).revertible"
                      role="button" tabindex="0"
                      @click.stop="revertStep(step.tabId)" @keydown.enter.stop="revertStep(step.tabId)"
                      class="text-[9px] font-semibold text-amber-600 dark:text-amber-400 hover:underline cursor-pointer">
                      ↩ reverter
                    </span>
                  </div>
                </div>
              </button>
            </div>
            <!-- Passo de exportação -->
            <div class="mt-3 flex items-center gap-3 p-3 rounded-xl border border-dashed border-border bg-card">
              <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 bg-muted text-muted-foreground border border-border">
                🚀
              </div>
              <div>
                <p class="text-xs font-semibold text-foreground">Exportar / Publicar</p>
                <p class="text-[10px] text-muted-foreground">Quando o tema estiver pronto, usa os botões no topo da página — <strong>Exportar</strong> para descarregar o ZIP ou <strong>Publicar</strong> para enviar ao AnimusFlow.</p>
              </div>
              <a :href="`/themes/${theme.uuid}/export`"
                class="ml-auto px-3 py-1.5 bg-primary text-primary-foreground rounded-lg text-xs font-semibold hover:opacity-90 transition-opacity shrink-0">
                ↓ Exportar
              </a>
            </div>
          </div>
        </transition>

      </div>

      <!-- TAB BAR -->
      <div class="overflow-x-auto rounded-xl" style="-webkit-overflow-scrolling: touch;">
        <div class="flex gap-1 bg-muted p-1 rounded-xl min-w-max">
          <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
            class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold transition-colors whitespace-nowrap"
            :class="activeTab === tab.id ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
            {{ tab.icon }} {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- ════════════════════ TAB: Detalhes ════════════════════ -->
      <div v-show="activeTab === 'details'" class="max-w-xl">
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground">{{ t('themes.details') }}</h2>

          <!-- Slug (name) — só leitura, indica ao utilizador como alterar -->
          <div>
            <label class="field-label">{{ t('themes.slug') }}</label>
            <div class="flex items-center gap-2">
              <input v-model="form.name" class="field-input font-mono text-xs" placeholder="ex: aurora-dark" />
            </div>
            <p class="text-xs text-muted-foreground mt-1">{{ t('themes.slug_hint') }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="field-label">{{ t('common.label') }}</label>
              <input v-model="form.label" class="field-input" />
            </div>
            <div>
              <label class="field-label">{{ t('common.version') }}</label>
              <input v-model="form.version" class="field-input" />
            </div>
          </div>
          <div>
            <label class="field-label">{{ t('common.description') }}</label>
            <textarea v-model="form.description" rows="2" class="field-input resize-none" />
          </div>
          <div>
            <label class="field-label">{{ t('common.status') }}</label>
            <select v-model="form.status" class="field-input">
              <option value="draft">{{ t('themes.status.draft') }}</option>
              <option value="ready">{{ t('themes.status.ready') }}</option>
              <option value="published">{{ t('themes.status.published') }}</option>
            </select>
          </div>
          <btn-save @click="save" :saving="saving" />
        </div>
      </div>

      <!-- ════════════════════ TAB: Layout ════════════════════ -->
      <div v-show="activeTab === 'layout'" class="max-w-3xl space-y-5">

        <!-- Legenda da aba -->
        <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl px-4 py-3 text-xs text-blue-600 dark:text-blue-400 space-y-1">
          <p class="font-semibold">📐 O que configuras aqui?</p>
          <p>A <strong>estrutura visual</strong> do tema — como o header, navegação, conteúdo e footer são apresentados. Estas opções definem a aparência e disposição dos elementos de layout.</p>
          <p class="text-blue-500/70">💡 Funcionalidades especiais (parallax, vídeo de fundo, animações) → aba <strong>⚙️ Capacidades</strong> · Ficheiros de media → aba <strong>🖼️ Assets</strong></p>
        </div>

        <!-- Header -->
        <section-card title="🔝 Header">
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="field-label">Tipo de Header</label>
              <select v-model="form.layout_config.header_type" class="field-input">
                <option value="glass">Glass / Blur</option>
                <option value="solid">Sólido / Filled</option>
                <option value="transparent">Transparente</option>
                <option value="centered">Logo centrado</option>
                <option value="sidebar">Sidebar vertical</option>
              </select>
              <p class="field-hint">Estilo visual do cabeçalho. <em>Glass</em> aplica backdrop-blur; <em>Transparente</em> mostra o fundo da hero por baixo; <em>Sidebar</em> move o menu para a lateral.</p>
            </div>
            <div>
              <label class="field-label">Posição do Menu</label>
              <select v-model="form.layout_config.nav_position" class="field-input">
                <option value="left">Esquerda</option>
                <option value="center">Centro</option>
                <option value="right">Direita</option>
              </select>
              <p class="field-hint">Alinhamento horizontal dos links de navegação no header.</p>
            </div>
            <div>
              <label class="field-label">Tipo de Navegação</label>
              <select v-model="form.layout_config.nav_type" class="field-input">
                <option value="horizontal">Horizontal clássico</option>
                <option value="hamburger">Hamburger (mobile-first)</option>
                <option value="mega">Mega Menu</option>
                <option value="fullscreen">Full-screen overlay</option>
                <option value="sidebar">Sidebar vertical</option>
                <option value="circular">Circular (In9vador)</option>
              </select>
              <p class="field-hint">Estrutura do menu. Se escolheres <em>Mega Menu</em>, activa também a capacidade <strong>Mega Menu</strong> em ⚙️ Capacidades para gerar o JS/CSS correspondente.</p>
            </div>
            <div>
              <label class="field-label">Estilo do Menu (In9vador)</label>
              <select v-model="form.layout_config.menu_layout" class="field-input">
                <option value="circular">Circular Orbital (padrão)</option>
                <option value="normal">Barra Horizontal Clássica</option>
              </select>
              <p class="field-hint">Escolhe <em>Circular Orbital</em> para o menu interativo do tema In9vador, ou <em>Barra Horizontal</em> para navegação clássica.</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="field-label">CTA Botão — Texto</label>
              <input v-model="form.layout_config.header_cta_text" placeholder="Ex: Começar agora" class="field-input" />
              <p class="field-hint">Texto do botão de chamada à acção no header. Deixa vazio para não mostrar.</p>
            </div>
            <div>
              <label class="field-label">CTA Botão — URL</label>
              <input v-model="form.layout_config.header_cta_url" placeholder="#" class="field-input" />
              <p class="field-hint">Destino do botão CTA. Usa <code class="bg-muted px-1 rounded">#contacto</code> para ancoras ou URLs completos.</p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-6 pt-1">
            <div>
              <toggle-field v-model="form.layout_config.header_sticky" label="Header fixo (sticky)" />
              <p class="field-hint mt-1.5">O header mantém-se visível enquanto o utilizador faz scroll para baixo.</p>
            </div>
            <div>
              <toggle-field v-model="form.layout_config.show_dark_toggle" label="Mostrar toggle dark/light" />
              <p class="field-hint mt-1.5">Mostra o botão de alternância entre modo claro e escuro no header. Para personalizar o aspecto do botão, vai a 🔧 Componentes → dark_toggle.</p>
            </div>
          </div>
        </section-card>

        <!-- Content / Layout -->
        <section-card title="📐 Layout & Conteúdo">
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="field-label">Tipo de Layout</label>
              <select v-model="form.layout_config.layout_type" class="field-input">
                <option value="full-width">Full-width</option>
                <option value="boxed">Boxed (com margem)</option>
                <option value="sidebar-left">Sidebar esquerda</option>
                <option value="sidebar-right">Sidebar direita</option>
              </select>
              <p class="field-hint"><em>Full-width</em>: ocupa toda a largura. <em>Boxed</em>: conteúdo centrado com margens. <em>Sidebar</em>: adiciona coluna lateral de conteúdo.</p>
            </div>
            <div>
              <label class="field-label">Largura máxima do conteúdo</label>
              <select v-model="form.layout_config.max_width" class="field-input">
                <option value="960">960px — Estreito (blog/texto)</option>
                <option value="1120">1120px — Normal (recomendado)</option>
                <option value="1280">1280px — Largo</option>
                <option value="1440">1440px — Extra largo</option>
                <option value="full">Full — Sem limite</option>
              </select>
              <p class="field-hint">Largura máxima do container de conteúdo interno. Afecta todos os blocos que usam a classe <code class="bg-muted px-1 rounded">container</code>.</p>
            </div>
            <div>
              <label class="field-label">Espaçamento entre secções</label>
              <select v-model="form.layout_config.spacing" class="field-input">
                <option value="compact">Compacto (py-10)</option>
                <option value="normal">Normal (py-16)</option>
                <option value="spacious">Espaçoso (py-24)</option>
              </select>
              <p class="field-hint">Padding vertical aplicado a cada secção do tema. <em>Compacto</em> para temas densos; <em>Espaçoso</em> para temas de luxo/minimalistas.</p>
            </div>
          </div>
        </section-card>

        <!-- Footer -->
        <section-card title="🔻 Footer">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="field-label">Tipo de Footer</label>
              <select v-model="form.layout_config.footer_type" class="field-input">
                <option value="simple">Simples (copyright + links)</option>
                <option value="columns">Colunas (com widgets)</option>
                <option value="minimal">Minimal</option>
                <option value="dark">Dark background</option>
                <option value="accent">Accent / Primário</option>
              </select>
              <p class="field-hint"><em>Simples</em>: apenas copyright e links. <em>Colunas</em>: layout multi-coluna com widgets. <em>Dark/Accent</em>: footer com cor de fundo própria. Para override completo do HTML → 🔧 Componentes → footer.</p>
            </div>
            <div>
              <label class="field-label">Texto de Copyright</label>
              <input v-model="form.layout_config.footer_copyright"
                :placeholder="`© ${new Date().getFullYear()} Empresa. Todos os direitos reservados.`"
                class="field-input" />
              <p class="field-hint">Texto mostrado no rodapé. Suporta HTML simples (ex: links). Deixa vazio para o AnimusFlow usar o nome do site automaticamente.</p>
            </div>
          </div>
        </section-card>

        <btn-save @click="save" :saving="saving" />
      </div>

      <!-- ════════════════════ TAB: Capabilities ════════════════════ -->
      <div v-show="activeTab === 'capabilities'" class="max-w-2xl space-y-5">

        <!-- Legenda da aba -->
        <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl px-4 py-3 text-xs text-amber-700 dark:text-amber-400 space-y-1">
          <p class="font-semibold">⚙️ O que são Capacidades?</p>
          <p>São <strong>feature flags</strong> — interruptores que dizem ao AnimusFlow que funcionalidades este tema suporta. Cada flag activa <strong>gera o código correspondente</strong> (JS/CSS) no ZIP exportado.</p>
          <p class="text-amber-600/70">⚠️ Activar uma capacidade <strong>não faz upload dos ficheiros</strong> (imagens/vídeos). Para isso vai a 🖼️ <strong>Assets</strong>. Também não personaliza o HTML do componente — para isso vai a 🔧 <strong>Componentes</strong>.</p>
        </div>

        <!-- Grupo: Media -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-3">
          <div class="flex items-center gap-2 pb-1 border-b border-border">
            <span class="text-base">🎬</span>
            <h3 class="text-sm font-semibold text-foreground">Media & Visuais</h3>
          </div>
          <capability-row v-model="form.capabilities.video_bg"
            label="🎬 Vídeo de fundo"
            hint="Gera o código de suporte para vídeos MP4/WebM em autoplay, muted e loop no hero e em secções. Após activar, faz o upload do vídeo em 🖼️ Assets → Fundo Global ou Hero." />
          <capability-row v-model="form.capabilities.parallax"
            label="🌊 Efeito Parallax"
            hint="Activa o script de parallax scrolling nas imagens de fundo das secções. As imagens devem ser maiores que o contentor (min 1.5× altura). Faz upload das imagens em 🖼️ Assets → Fundos de Secções." />
          <capability-row v-model="form.capabilities.animations"
            label="✨ Animações de entrada"
            hint="Adiciona fade-in e slide-up automáticos via IntersectionObserver quando os elementos entram no viewport durante o scroll. Não requer assets adicionais." />
          <capability-row v-model="form.capabilities.lightbox"
            label="🖼️ Lightbox de imagens"
            hint="Permite clicar em imagens para as ver em ecrã completo com navegação por teclado. Aplica-se automaticamente às secções Galeria e Portfólio." />
        </div>

        <!-- Grupo: Navegação -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-3">
          <div class="flex items-center gap-2 pb-1 border-b border-border">
            <span class="text-base">🗺️</span>
            <h3 class="text-sm font-semibold text-foreground">Navegação</h3>
          </div>
          <capability-row v-model="form.capabilities.mega_menu"
            label="📋 Mega Menu"
            hint="Gera o JS/CSS para menus de navegação com sub-colunas, imagens e descrições. Requer que o Tipo de Navegação em 📐 Layout → Header esteja definido como 'Mega Menu'. Personaliza o HTML em 🔧 Componentes → nav_mega." />
          <capability-row v-model="form.capabilities.search"
            label="🔍 Pesquisa no site"
            hint="Adiciona ícone de pesquisa no header com overlay de resultados em tempo real. Personaliza o formulário de pesquisa em 🔧 Componentes → form_search." />
        </div>

        <!-- Grupo: UX & Acessibilidade -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-3">
          <div class="flex items-center gap-2 pb-1 border-b border-border">
            <span class="text-base">♿</span>
            <h3 class="text-sm font-semibold text-foreground">UX & Acessibilidade</h3>
          </div>
          <capability-row v-model="form.capabilities.back_to_top"
            label="⬆️ Botão Voltar ao topo"
            hint="Botão flutuante no canto inferior direito para voltar ao início da página. Aparece apenas após o utilizador fazer scroll. Personaliza o HTML em 🔧 Componentes → back_to_top." />
          <capability-row v-model="form.capabilities.scroll_progress"
            label="📏 Barra de progresso de scroll"
            hint="Linha fina no topo da página que cresce à medida que o utilizador faz scroll — indica o progresso de leitura. Personaliza o aspecto em 💻 Código → custom.css." />
          <capability-row v-model="form.capabilities.preloader"
            label="⏳ Preloader de página"
            hint="Ecrã de carregamento animado antes do conteúdo aparecer. Melhora a percepção de velocidade em páginas pesadas. Personaliza o HTML/animação em 🔧 Componentes → preloader." />
        </div>

        <!-- Grupo: Legal & Compliance -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-3">
          <div class="flex items-center gap-2 pb-1 border-b border-border">
            <span class="text-base">⚖️</span>
            <h3 class="text-sm font-semibold text-foreground">Legal & Compliance</h3>
          </div>
          <capability-row v-model="form.capabilities.cookie_banner"
            label="🍪 Banner de cookies (RGPD)"
            hint="Aviso de cookies conforme o RGPD/GDPR com botões Aceitar / Recusar / Preferências. Guarda a escolha em localStorage. Personaliza o HTML em 🔧 Componentes → cookie_bar." />
        </div>

        <btn-save @click="save" :saving="saving" />
      </div>

      <!-- ════════════════════ TAB: Design ════════════════════ -->
      <div v-show="activeTab === 'design'" class="max-w-3xl space-y-5">

        <!-- AI Generator -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
              <SparklesIcon class="w-4 h-4 text-primary" />
            </div>
            <div>
              <h2 class="font-semibold text-foreground">{{ t('themes.ai_generator') }}</h2>
              <p class="text-xs text-muted-foreground">{{ t('themes.ai_generator_hint') }}</p>
            </div>
          </div>
          <div class="flex gap-2">
            <input v-model="aiPrompt" @keyup.enter="generateAi"
              :placeholder="t('themes.ai_prompt_placeholder')" :disabled="aiLoading"
              class="flex-1 field-input disabled:opacity-50" />
            <button @click="generateAi" :disabled="aiLoading || !aiPrompt.trim()"
              class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold disabled:opacity-50 flex items-center gap-1.5 whitespace-nowrap">
              <div v-if="aiLoading" class="w-3.5 h-3.5 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
              <SparklesIcon v-else class="w-3.5 h-3.5" />
              {{ aiLoading ? t('themes.generating') : t('themes.generate') }}
            </button>
          </div>
        </div>

        <!-- Typography -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground">{{ t('themes.fonts') }}</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="field-label">{{ t('themes.font_heading') }}</label>
              <select v-model="form.fonts.heading" class="field-input">
                <option value="">System default</option>
                <option v-for="f in googleFonts" :key="f" :value="f">{{ f }}</option>
              </select>
            </div>
            <div>
              <label class="field-label">{{ t('themes.font_body') }}</label>
              <select v-model="form.fonts.body" class="field-input">
                <option value="">System default</option>
                <option v-for="f in googleFonts" :key="f" :value="f">{{ f }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Color tokens -->
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-foreground">{{ t('themes.colors') }}</h2>
            <div class="flex gap-1 bg-muted p-0.5 rounded-lg">
              <button @click="colorMode = 'light'"
                class="px-3 py-1 rounded-md text-xs font-semibold transition-colors"
                :class="colorMode === 'light' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'">☀️ Light</button>
              <button @click="colorMode = 'dark'"
                class="px-3 py-1 rounded-md text-xs font-semibold transition-colors"
                :class="colorMode === 'dark' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'">🌙 Dark</button>
            </div>
          </div>
          <div class="space-y-2">
            <div v-for="token in colorTokens" :key="token.var"
              class="grid grid-cols-[1fr_auto_1fr] gap-3 items-center">
              <span class="text-xs font-mono text-muted-foreground truncate">{{ token.var }}</span>
              <div class="w-6 h-6 rounded border border-border flex-shrink-0 overflow-hidden">
                <input type="color"
                  :value="hexFallback(currentColors[token.var] || token.default)"
                  @input="e => currentColors[token.var] = e.target.value"
                  class="w-8 h-8 -m-1 cursor-pointer border-0 bg-transparent" />
              </div>
              <input v-model="currentColors[token.var]" :placeholder="token.default"
                class="px-3 py-1.5 bg-muted border border-border rounded-lg text-xs font-mono focus:outline-none focus:border-primary" />
            </div>
          </div>
          <btn-save @click="save" :saving="saving" label="Guardar Design" />
        </div>
      </div>

      <!-- ════════════════════ TAB: Assets ════════════════════ -->
      <div v-show="activeTab === 'assets'" class="max-w-4xl space-y-5">

        <!-- Legenda da aba -->
        <div class="bg-green-500/10 border border-green-500/20 rounded-xl px-4 py-3 text-xs text-green-700 dark:text-green-400 space-y-1">
          <p class="font-semibold">🖼️ O que configuras aqui?</p>
          <p>O <strong>upload e configuração dos ficheiros de media</strong> do tema — logos, imagens de fundo, vídeos, ícones sociais. Todos os ficheiros são incluídos na pasta <code class="bg-green-500/10 px-1 rounded">assets/</code> do ZIP exportado.</p>
          <p class="text-green-600/70">💡 Para que o <strong>vídeo de fundo</strong> ou o <strong>parallax</strong> funcionem, activa as capacidades correspondentes em ⚙️ <strong>Capacidades</strong>. Os assets aqui são apenas os ficheiros — a lógica é gerada pelas capacidades.</p>
        </div>

        <!-- Identidade -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <div class="flex items-start gap-2 pb-2 border-b border-border">
            <span class="text-base">🏷️</span>
            <div>
              <h3 class="text-sm font-semibold text-foreground">Identidade</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Logos e ícones da marca. Recomendado SVG para logos (escalável) e PNG 64×64 para favicon.</p>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <asset-slot v-for="slot in assetGroups.identity" :key="slot.id"
              :slot-id="slot.id" :label="slot.label" :hint="slot.hint" :accept="slot.accept"
              :current-url="form.assets[slot.id]" :uploading="uploadingSlot === slot.id"
              @upload="handleAssetUpload" @delete="handleAssetDelete" />
          </div>
        </div>

        <!-- Fundo global -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-semibold text-foreground">🎞️ Fundo Global</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Fundo aplicado a toda a página (atrás de todas as secções). Para vídeo ou parallax, activa a capacidade correspondente em ⚙️ Capacidades.</p>
            </div>
            <!-- Tipo de fundo -->
            <div class="shrink-0 w-52">
              <label class="field-label">Tipo de fundo</label>
              <select v-model="form.assets.bg_type" class="field-input text-xs">
                <option value="">Nenhum</option>
                <option value="color">Cor sólida (via Design → cores)</option>
                <option value="image">Imagem estática</option>
                <option value="video">Vídeo ⚠️ requer capacidade</option>
                <option value="gradient">Gradiente CSS</option>
                <option value="pattern">Padrão / textura repetida</option>
              </select>
              <p class="field-hint">Escolhe o tipo de fundo. Se seleccionares <em>Vídeo</em>, activa a capacidade <strong>🎬 Vídeo de fundo</strong> em ⚙️ Capacidades para gerar o código JS necessário.</p>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <asset-slot v-for="slot in assetGroups.background" :key="slot.id"
              :slot-id="slot.id" :label="slot.label" :hint="slot.hint" :accept="slot.accept"
              :current-url="form.assets[slot.id]" :uploading="uploadingSlot === slot.id"
              @upload="handleAssetUpload" @delete="handleAssetDelete" />
          </div>
          <!-- Gradiente CSS (quando tipo = gradient) -->
          <div v-if="form.assets.bg_type === 'gradient'" class="space-y-1">
            <label class="field-label">CSS do Gradiente</label>
            <input v-model="form.assets.bg_gradient" class="field-input font-mono text-xs"
              placeholder="linear-gradient(135deg, #667eea 0%, #764ba2 100%)" />
          </div>
          <!-- Opções de vídeo -->
          <div v-if="form.assets.bg_type === 'video'" class="grid grid-cols-3 gap-4">
            <div>
              <label class="field-label">Velocidade</label>
              <select v-model="form.assets.bg_video_speed" class="field-input text-xs">
                <option value="0.5">0.5× — Muito lento</option>
                <option value="1">1× — Normal</option>
                <option value="1.5">1.5× — Rápido</option>
              </select>
            </div>
            <div>
              <label class="field-label">Overlay opacidade</label>
              <input type="range" min="0" max="1" step="0.05"
                v-model="form.assets.bg_video_overlay"
                class="w-full mt-2 accent-primary" />
              <span class="text-xs text-muted-foreground">{{ form.assets.bg_video_overlay ?? 0.4 }}</span>
            </div>
            <div class="flex flex-col gap-2 pt-5">
              <toggle-field v-model="form.assets.bg_video_muted" label="Muted (sem som)" />
              <toggle-field v-model="form.assets.bg_video_loop" label="Loop automático" />
            </div>
          </div>
        </div>

        <!-- Hero Section -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-semibold text-foreground">🌅 Hero / Banner</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Imagem ou vídeo da primeira secção visível da página. Para vídeo activa <strong>🎬 Vídeo de fundo</strong> em ⚙️ Capacidades. Para slideshow activa <strong>✨ Animações</strong>.</p>
            </div>
            <div class="shrink-0 w-44">
              <label class="field-label">Tipo de hero</label>
              <select v-model="form.assets.hero_type" class="field-input text-xs">
                <option value="image">Imagem estática</option>
                <option value="video">Vídeo de fundo</option>
                <option value="slideshow">Slideshow / Carrossel</option>
                <option value="particles">Partículas animadas</option>
                <option value="none">Sem media</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <asset-slot v-for="slot in assetGroups.hero" :key="slot.id"
              :slot-id="slot.id" :label="slot.label" :hint="slot.hint" :accept="slot.accept"
              :current-url="form.assets[slot.id]" :uploading="uploadingSlot === slot.id"
              @upload="handleAssetUpload" @delete="handleAssetDelete" />
          </div>
          <!-- Slideshow extra -->
          <div v-if="form.assets.hero_type === 'slideshow'" class="grid grid-cols-3 gap-4">
            <asset-slot v-for="slot in assetGroups.slideshow" :key="slot.id"
              :slot-id="slot.id" :label="slot.label" :hint="slot.hint" :accept="slot.accept"
              :current-url="form.assets[slot.id]" :uploading="uploadingSlot === slot.id"
              @upload="handleAssetUpload" @delete="handleAssetDelete" />
          </div>
          <!-- Opções de posição -->
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="field-label">Posição da imagem</label>
              <select v-model="form.assets.hero_position" class="field-input text-xs">
                <option value="center">Centro</option>
                <option value="top">Topo</option>
                <option value="bottom">Rodapé</option>
                <option value="left">Esquerda</option>
                <option value="right">Direita</option>
              </select>
            </div>
            <div>
              <label class="field-label">Tamanho (object-fit)</label>
              <select v-model="form.assets.hero_fit" class="field-input text-xs">
                <option value="cover">Cover (preenche)</option>
                <option value="contain">Contain (mostra tudo)</option>
                <option value="fill">Fill (estica)</option>
              </select>
            </div>
            <div>
              <label class="field-label">Altura mínima</label>
              <select v-model="form.assets.hero_height" class="field-input text-xs">
                <option value="50vh">50vh — Médio</option>
                <option value="70vh">70vh — Alto</option>
                <option value="100vh">100vh — Ecrã completo</option>
                <option value="auto">Auto</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Secções específicas -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <div class="pb-2 border-b border-border">
            <h3 class="text-sm font-semibold text-foreground">📐 Fundos de Secções</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Imagens de fundo para secções específicas. Para o efeito parallax nestas imagens, activa <strong>🌊 Parallax</strong> em ⚙️ Capacidades. Tamanho mínimo recomendado: 1920×600px.</p>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <asset-slot v-for="slot in assetGroups.sections" :key="slot.id"
              :slot-id="slot.id" :label="slot.label" :hint="slot.hint" :accept="slot.accept"
              :current-url="form.assets[slot.id]" :uploading="uploadingSlot === slot.id"
              @upload="handleAssetUpload" @delete="handleAssetDelete" />
          </div>
        </div>

        <!-- Social / SEO -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <div class="pb-2 border-b border-border">
            <h3 class="text-sm font-semibold text-foreground">📤 Social & SEO</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Imagens usadas ao partilhar o site nas redes sociais e resultados de pesquisa. OG Image: 1200×630px. Twitter Card: 1200×600px. Apple Touch: 180×180px PNG.</p>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <asset-slot v-for="slot in assetGroups.social" :key="slot.id"
              :slot-id="slot.id" :label="slot.label" :hint="slot.hint" :accept="slot.accept"
              :current-url="form.assets[slot.id]" :uploading="uploadingSlot === slot.id"
              @upload="handleAssetUpload" @delete="handleAssetDelete" />
          </div>
        </div>

        <btn-save @click="save" :saving="saving" label="Guardar Assets" />
      </div>

      <!-- ════════════════════ TAB: Secções ════════════════════ -->
      <div v-show="activeTab === 'sections'" class="max-w-5xl space-y-5">

        <!-- Legenda da aba -->
        <div class="bg-orange-500/10 border border-orange-500/20 rounded-xl px-4 py-3 text-xs text-orange-700 dark:text-orange-400 space-y-1">
          <p class="font-semibold">🧩 O que configuras aqui?</p>
          <p>As <strong>secções de página</strong> do tema — os blocos de conteúdo que compõem cada página (Hero, Features, Testemunhos, Preços, etc.). Selecciona os blocos na biblioteca e ordena-os conforme necessário.</p>
          <p class="text-orange-600/70">💡 Cada bloco pode ter um <strong>Blade override</strong> — código HTML/Blade personalizado que substitui o template padrão. Deixa vazio para usar o template gerado automaticamente pelo AnimusFlow. Para imagens de fundo das secções → 🖼️ <strong>Assets → Fundos de Secções</strong>.</p>
        </div>

        <!-- Layout: painel esquerdo (biblioteca) + direita (activas) -->
        <div class="grid grid-cols-[260px_1fr] gap-5 items-start">

          <!-- ── Biblioteca de blocos ── -->
          <div class="bg-card border border-border rounded-2xl overflow-hidden sticky top-4">
            <div class="px-4 py-3 border-b border-border bg-muted/50">
              <p class="text-xs font-semibold text-foreground">📚 Biblioteca de Blocos</p>
              <p class="text-xs text-muted-foreground mt-0.5">Clica para adicionar ao tema</p>
            </div>

            <!-- Filtro de categorias -->
            <div class="flex flex-wrap gap-1 px-3 py-2 border-b border-border">
              <button v-for="cat in sectionCategories" :key="cat.id"
                @click="activeSectionCat = cat.id"
                class="px-2 py-0.5 rounded-md text-xs font-semibold transition-colors"
                :class="activeSectionCat === cat.id
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-muted text-muted-foreground hover:text-foreground'">
                {{ cat.icon }} {{ cat.label }}
              </button>
            </div>

            <!-- Blocos da categoria activa -->
            <div class="p-3 space-y-1.5 max-h-[520px] overflow-y-auto">
              <button v-for="block in filteredSectionBlocks" :key="block.id"
                @click="toggleSection(block)"
                class="w-full flex items-start gap-3 p-2.5 rounded-xl border text-left transition-all"
                :class="form.sections[block.id] !== undefined
                  ? 'border-primary/40 bg-primary/5 text-primary'
                  : 'border-transparent bg-muted/50 hover:bg-muted text-foreground'">
                <span class="text-lg leading-none mt-0.5">{{ block.icon }}</span>
                <div class="min-w-0">
                  <p class="text-xs font-semibold leading-tight">{{ block.label }}</p>
                  <p class="text-xs text-muted-foreground leading-tight mt-0.5 truncate">{{ block.hint }}</p>
                </div>
                <span v-if="form.sections[block.id] !== undefined"
                  class="ml-auto text-xs text-primary font-bold shrink-0">✓</span>
              </button>
            </div>
          </div>

          <!-- ── Secções activas ── -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <p class="text-sm font-semibold text-foreground">
                Secções activas
                <span class="ml-1.5 text-xs font-normal text-muted-foreground">({{ Object.keys(form.sections).length }} blocos)</span>
              </p>
              <btn-save @click="save" :saving="saving" label="Guardar Secções" />
            </div>

            <!-- Vazio -->
            <div v-if="!Object.keys(form.sections).length"
              class="bg-card border border-dashed border-border rounded-2xl p-12 text-center">
              <p class="text-2xl mb-2">🧩</p>
              <p class="text-sm font-semibold text-foreground mb-1">Sem blocos activos</p>
              <p class="text-xs text-muted-foreground">Selecciona blocos na biblioteca à esquerda</p>
            </div>

            <!-- Blocos activos com editor Blade -->
            <div v-for="(blade, sectionType) in form.sections" :key="sectionType"
              class="bg-card border border-border rounded-2xl overflow-hidden">

              <!-- Header do bloco -->
              <div class="flex items-center gap-3 px-4 py-2.5 border-b border-border bg-muted/40">
                <span class="text-base">{{ getSectionBlock(sectionType)?.icon ?? '🧩' }}</span>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-foreground leading-tight">{{ getSectionBlock(sectionType)?.label ?? sectionType }}</p>
                  <p class="text-xs text-muted-foreground font-mono">{{ sectionType }}</p>
                </div>
                <!-- Variante do bloco -->
                <select v-if="getSectionBlock(sectionType)?.variants?.length"
                  v-model="sectionVariants[sectionType]"
                  class="text-xs bg-muted border border-border rounded-lg px-2 py-1 focus:outline-none">
                  <option v-for="v in getSectionBlock(sectionType).variants" :key="v.id" :value="v.id">{{ v.label }}</option>
                </select>
                <button @click="toggleSectionEditor(sectionType)"
                  class="text-xs text-muted-foreground hover:text-foreground px-2 py-1 rounded bg-muted hover:bg-border transition-colors">
                  {{ openSectionEditors[sectionType] ? '▲ Fechar' : '▼ Editar Blade' }}
                </button>
                <button @click="removeSection(sectionType)"
                  class="w-6 h-6 flex items-center justify-center rounded-lg text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors text-xs font-bold">✕</button>
              </div>

              <!-- Editor Blade (colapsável) -->
              <div v-show="openSectionEditors[sectionType]">
                <div class="px-4 py-2 bg-muted/20 border-b border-border flex items-center gap-2">
                  <span class="text-xs text-muted-foreground">Blade personalizado — sobrepõe o template gerado pelo AnimusFlow</span>
                  <button @click="form.sections[sectionType] = ''"
                    class="ml-auto text-xs text-muted-foreground hover:text-destructive px-2 py-0.5 rounded hover:bg-destructive/10">
                    Limpar
                  </button>
                </div>
                <textarea v-model="form.sections[sectionType]" rows="10" spellcheck="false"
                  placeholder="Blade personalizado da secção — deixa vazio para usar o template padrão do AnimusFlow"
                  class="w-full px-4 py-3 bg-muted/10 text-xs font-mono focus:outline-none resize-y border-0" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════════════ TAB: Componentes ════════════════════ -->
      <div v-show="activeTab === 'components'" class="max-w-5xl space-y-5">

        <!-- Legenda da aba -->
        <div class="bg-purple-500/10 border border-purple-500/20 rounded-xl px-4 py-3 text-xs text-purple-700 dark:text-purple-400 space-y-1">
          <p class="font-semibold">🔧 O que configuras aqui?</p>
          <p>Selecciona e ordena os <strong>componentes de UI</strong> do tema. Cada componente pode ter um <strong>Blade override</strong> — HTML personalizado que substitui completamente o template padrão do AnimusFlow.</p>
          <p class="text-purple-600/70">💡 Componentes como <em>Cookie Banner</em>, <em>Preloader</em> e <em>Pesquisa</em> só aparecem no site se a <strong>capacidade correspondente</strong> estiver activa em ⚙️ Capacidades. O componente aqui controla apenas o <strong>aspecto visual</strong>.</p>
          <p class="text-purple-600/70">🔀 <strong>Arrasta pelo símbolo ⠿</strong> para reordenar os componentes. A ordem define a sequência de renderização no tema.</p>
        </div>

        <div class="grid grid-cols-[260px_1fr] gap-5 items-start">

          <!-- ── Biblioteca de componentes ── -->
          <div class="bg-card border border-border rounded-2xl overflow-hidden sticky top-4">
            <div class="px-4 py-3 border-b border-border bg-muted/50">
              <p class="text-xs font-semibold text-foreground">🧱 Biblioteca de Componentes</p>
              <p class="text-xs text-muted-foreground mt-0.5">Clica para adicionar à composição</p>
            </div>

            <!-- Filtro de categorias -->
            <div class="flex flex-wrap gap-1 px-3 py-2 border-b border-border">
              <button v-for="cat in compCategories" :key="cat.id"
                @click="activeCompCat = cat.id"
                class="px-2 py-0.5 rounded-md text-xs font-semibold transition-colors"
                :class="activeCompCat === cat.id
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-muted text-muted-foreground hover:text-foreground'">
                {{ cat.icon }} {{ cat.label }}
              </button>
            </div>

            <!-- Blocos disponíveis -->
            <div class="p-3 space-y-1.5 max-h-[560px] overflow-y-auto">
              <button v-for="block in filteredCompBlocks" :key="block.id"
                @click="addCompBlock(block)"
                class="w-full flex items-start gap-3 p-2.5 rounded-xl border text-left transition-all bg-muted/50 hover:bg-muted border-transparent hover:border-border">
                <span class="text-lg leading-none mt-0.5 shrink-0">{{ block.icon }}</span>
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-foreground leading-tight">{{ block.label }}</p>
                  <p class="text-xs text-muted-foreground leading-tight mt-0.5">{{ block.hint }}</p>
                </div>
                <PlusIcon class="w-3.5 h-3.5 shrink-0 mt-0.5 text-muted-foreground" />
              </button>
            </div>
          </div>

          <!-- ── Área de composição (drag & drop) ── -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <p class="text-sm font-semibold text-foreground">
                Componentes activos
                <span class="ml-1.5 text-xs font-normal text-muted-foreground">({{ compOrder.length }} itens · arrasta para reordenar)</span>
              </p>
              <btn-save @click="save" :saving="saving" label="Guardar" />
            </div>

            <!-- Vazio -->
            <div v-if="!compOrder.length"
              class="bg-card border-2 border-dashed border-border rounded-2xl p-16 text-center">
              <p class="text-3xl mb-2">🧱</p>
              <p class="text-sm font-semibold text-foreground mb-1">Sem componentes</p>
              <p class="text-xs text-muted-foreground">Adiciona componentes da biblioteca à esquerda</p>
            </div>

            <!-- Lista drag & drop -->
            <div
              @dragover.prevent
              @drop="onCompDrop($event)"
              class="space-y-2 min-h-[60px]">

              <div v-for="(item, idx) in compOrder" :key="item.uid"
                draggable="true"
                @dragstart="onCompDragStart($event, idx)"
                @dragover.prevent="onCompDragOver($event, idx)"
                @dragend="onCompDragEnd"
                class="bg-card border border-border rounded-2xl overflow-hidden transition-opacity select-none"
                :class="dragCompIdx === idx ? 'opacity-40' : 'opacity-100'">

                <!-- Header do componente -->
                <div class="flex items-center gap-2 px-3 py-2.5 border-b border-border bg-muted/40 cursor-grab active:cursor-grabbing">
                  <!-- Handle -->
                  <span class="text-muted-foreground/50 hover:text-muted-foreground text-sm select-none px-0.5">⠿</span>
                  <span class="text-base">{{ getCompBlock(item.type)?.icon ?? '🧱' }}</span>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-foreground leading-tight">{{ getCompBlock(item.type)?.label ?? item.type }}</p>
                    <p class="text-xs text-muted-foreground font-mono">{{ item.type }}{{ item.uid !== item.type ? ' #' + item.uid.split('_').pop() : '' }}</p>
                  </div>
                  <!-- Variante -->
                  <select v-if="getCompBlock(item.type)?.variants?.length"
                    v-model="item.variant"
                    class="text-xs bg-muted border border-border rounded-lg px-2 py-1 focus:outline-none">
                    <option v-for="v in getCompBlock(item.type).variants" :key="v.id" :value="v.id">{{ v.label }}</option>
                  </select>
                  <!-- Toggle editor -->
                  <button @click="item.open = !item.open"
                    class="text-xs text-muted-foreground hover:text-foreground px-2 py-1 rounded bg-muted hover:bg-border transition-colors">
                    {{ item.open ? '▲' : '▼ Blade' }}
                  </button>
                  <!-- Duplicar -->
                  <button @click="duplicateCompBlock(idx)"
                    title="Duplicar" class="w-6 h-6 flex items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors text-xs">⧉</button>
                  <!-- Remover -->
                  <button @click="removeCompBlock(idx)"
                    class="w-6 h-6 flex items-center justify-center rounded-lg text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors text-xs font-bold">✕</button>
                </div>

                <!-- Editor Blade (colapsável) -->
                <div v-show="item.open">
                  <div class="px-4 py-2 bg-muted/20 border-b border-border flex items-center gap-2">
                    <span class="text-xs text-muted-foreground flex-1">Blade override — deixa vazio para usar o componente padrão</span>
                    <button @click="item.blade = ''"
                      class="text-xs text-muted-foreground hover:text-destructive px-2 py-0.5 rounded hover:bg-destructive/10">Limpar</button>
                  </div>
                  <textarea v-model="item.blade" rows="8" spellcheck="false"
                    placeholder="Blade personalizado do componente — deixa vazio para usar o padrão do AnimusFlow"
                    class="w-full px-4 py-3 bg-muted/10 text-xs font-mono focus:outline-none resize-y border-0" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════════════ TAB: Código ════════════════════ -->
      <div v-show="activeTab === 'code'" class="max-w-4xl space-y-4">

        <!-- Legenda da aba -->
        <div class="bg-slate-500/10 border border-slate-500/20 rounded-xl px-4 py-3 text-xs text-slate-700 dark:text-slate-400 space-y-1">
          <p class="font-semibold">💻 O que configuras aqui?</p>
          <p><strong>CSS e JavaScript personalizados</strong> adicionados ao tema. O CSS é injectado após os tokens de cor (podes usar variáveis como <code class="bg-slate-500/10 px-1 rounded">var(--color-primary)</code>). O JS é injectado antes do <code class="bg-slate-500/10 px-1 rounded">&lt;/body&gt;</code>.</p>
          <p class="text-slate-600/70">⚠️ Este código aplica-se ao <strong>tema inteiro</strong>. Para estilos específicos de um componente, usa o Blade override em 🔧 Componentes. Para tokens de cor → 🎨 Design.</p>
        </div>

        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <span class="text-sm font-semibold">🎨 custom.css</span>
            <span class="text-xs text-muted-foreground">Injectado após os tokens de cor do tema</span>
          </div>
          <textarea v-model="form.custom_css" rows="14" spellcheck="false"
            placeholder="/* CSS personalizado — usa as variáveis do tema */&#10;.my-hero { background: var(--color-primary); }&#10;@media (max-width: 768px) { ... }"
            class="w-full px-4 py-3 bg-muted/30 text-xs font-mono focus:outline-none resize-y border-0" />
        </div>

        <div class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <span class="text-sm font-semibold">⚡ custom.js</span>
            <span class="text-xs text-muted-foreground">Injectado antes de &lt;/body&gt;</span>
          </div>
          <textarea v-model="form.custom_js" rows="10" spellcheck="false"
            placeholder="// JavaScript personalizado do tema&#10;document.addEventListener('DOMContentLoaded', () => {&#10;  // O teu código aqui&#10;});"
            class="w-full px-4 py-3 bg-muted/30 text-xs font-mono focus:outline-none resize-y border-0" />
        </div>

        <btn-save @click="save" :saving="saving" label="Guardar Código" />
      </div>

      <!-- ════════════════════ TAB: Variantes ════════════════════ -->
      <div v-show="activeTab === 'variants'" class="max-w-5xl space-y-5">

        <!-- Legenda -->
        <div class="bg-pink-500/10 border border-pink-500/20 rounded-xl px-4 py-3 text-xs text-pink-700 dark:text-pink-400 space-y-1">
          <p class="font-semibold">🌈 O que são Variantes?</p>
          <p><strong>Paletas de cor alternativas</strong> (skins) para o mesmo tema. Cada variante redefine todos os tokens de cor em modo claro e escuro. O utilizador final escolhe a skin no painel AnimusFlow.</p>
          <p class="text-pink-600/70">💡 Clica em <strong>"+ Usar esta paleta"</strong> para adicionar uma paleta pré-definida, ou cria a tua própria com <strong>"+ Variante em branco"</strong>. Edita as cores individualmente em cada variante.</p>
        </div>

        <!-- Paletas pré-definidas -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-semibold text-foreground">🎨 Paletas Pré-definidas</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Clica numa paleta para a adicionar directamente às variantes do tema.</p>
            </div>
            <button @click="addVariant()"
              class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border transition-colors flex items-center gap-1.5">
              <PlusIcon class="w-3.5 h-3.5" /> Variante em branco
            </button>
          </div>

          <!-- Grid de paletas -->
          <div class="grid grid-cols-2 gap-3">
            <button v-for="preset in colorPresets" :key="preset.name"
              @click="addPresetVariant(preset)"
              class="flex items-center gap-3 p-3 rounded-xl border border-border hover:border-primary/40 hover:bg-primary/5 transition-all text-left group">
              <!-- Swatches da paleta -->
              <div class="flex gap-1 shrink-0">
                <div class="w-8 h-8 rounded-lg shadow-sm border border-white/20" :style="{ background: preset.colors.light['--color-primary'] }" />
                <div class="flex flex-col gap-1">
                  <div class="w-4 h-3.5 rounded" :style="{ background: preset.colors.light['--color-background'] }" />
                  <div class="w-4 h-3.5 rounded" :style="{ background: preset.colors.dark['--color-background'] }" />
                </div>
                <div class="w-8 h-8 rounded-lg shadow-sm border border-white/20" :style="{ background: preset.colors.dark['--color-primary'] }" />
              </div>
              <!-- Info -->
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-foreground">{{ preset.label }}</p>
                <p class="text-[10px] text-muted-foreground mt-0.5 truncate">{{ preset.description }}</p>
              </div>
              <PlusIcon class="w-3.5 h-3.5 text-muted-foreground group-hover:text-primary shrink-0 transition-colors" />
            </button>
          </div>
        </div>

        <!-- Variantes activas -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-foreground">
              Variantes activas
              <span class="ml-1.5 text-xs font-normal text-muted-foreground">({{ form.variants.length }})</span>
            </h3>
            <btn-save v-if="form.variants.length" @click="save" :saving="saving" label="Guardar Variantes" />
          </div>

          <div v-if="!form.variants.length"
            class="bg-card border border-dashed border-border rounded-2xl p-10 text-center">
            <PaletteIcon class="w-8 h-8 text-muted-foreground opacity-30 mx-auto mb-3" />
            <p class="text-sm font-semibold text-foreground mb-1">Sem variantes activas</p>
            <p class="text-xs text-muted-foreground">Adiciona uma paleta pré-definida acima ou cria uma variante em branco.</p>
          </div>

          <!-- Cards de variantes -->
          <div v-for="(variant, idx) in form.variants" :key="idx"
            class="bg-card border border-border rounded-2xl overflow-hidden">

            <!-- Header com swatches e controlos -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-border bg-muted/30">
              <!-- Swatch preview -->
              <div class="flex gap-1 shrink-0">
                <div v-for="tok in ['--color-primary','--color-secondary','--color-accent']" :key="tok"
                  class="w-5 h-5 rounded-full border border-border shadow-sm"
                  :style="{ background: variant.colors?.light?.[tok] || '#888' }"
                  :title="tok + ' (light)'" />
                <div class="w-px bg-border mx-1" />
                <div v-for="tok in ['--color-primary','--color-secondary','--color-accent']" :key="'d'+tok"
                  class="w-5 h-5 rounded-full border border-border shadow-sm"
                  :style="{ background: variant.colors?.dark?.[tok] || '#555' }"
                  :title="tok + ' (dark)'" />
              </div>
              <!-- Nome e label -->
              <div class="flex-1 grid grid-cols-2 gap-2">
                <input v-model="variant.name" placeholder="slug-da-variante" class="field-input font-mono text-xs py-1.5" />
                <input v-model="variant.label" placeholder="Nome da variante" class="field-input text-xs py-1.5" />
              </div>
              <!-- Toggle editor -->
              <button @click="variant._open = !variant._open"
                class="text-xs text-muted-foreground hover:text-foreground px-2 py-1 rounded bg-muted hover:bg-border transition-colors shrink-0">
                {{ variant._open ? '▲ Fechar' : '▼ Editar cores' }}
              </button>
              <!-- Remover -->
              <button @click="form.variants.splice(idx, 1)"
                class="w-6 h-6 flex items-center justify-center rounded-lg text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors text-xs font-bold shrink-0">✕</button>
            </div>

            <!-- Editor de cores (colapsável) -->
            <div v-show="variant._open" class="p-4 space-y-4">

              <!-- Tabs light/dark -->
              <div class="flex gap-1 bg-muted p-0.5 rounded-lg w-fit">
                <button @click="variant._mode = 'light'"
                  class="px-3 py-1 rounded-md text-xs font-semibold transition-colors"
                  :class="(!variant._mode || variant._mode==='light') ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'">
                  ☀️ Light
                </button>
                <button @click="variant._mode = 'dark'"
                  class="px-3 py-1 rounded-md text-xs font-semibold transition-colors"
                  :class="variant._mode==='dark' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'">
                  🌙 Dark
                </button>
              </div>

              <!-- Tokens de cor -->
              <div class="grid grid-cols-2 gap-3">
                <div v-for="token in fullVariantTokens" :key="token.var">
                  <div class="flex items-center gap-1.5 mb-1">
                    <div class="w-2.5 h-2.5 rounded-full border border-border"
                      :style="{ background: variant.colors?.[(variant._mode||'light')]?.[token.var] || token[(variant._mode||'light')] }" />
                    <label class="text-[10px] font-mono text-muted-foreground leading-none">{{ token.var }}</label>
                  </div>
                  <p class="text-[10px] text-muted-foreground mb-1.5">{{ token.hint }}</p>
                  <div class="flex gap-2 items-center">
                    <input type="color"
                      :value="hexFallback(variant.colors?.[(variant._mode||'light')]?.[token.var] || token[(variant._mode||'light')])"
                      @input="e => setVariantColor(idx, variant._mode||'light', token.var, e.target.value)"
                      class="w-8 h-7 rounded border border-border cursor-pointer bg-transparent p-0.5 shrink-0" />
                    <input
                      :value="variant.colors?.[(variant._mode||'light')]?.[token.var] || ''"
                      @input="e => setVariantColor(idx, variant._mode||'light', token.var, e.target.value)"
                      :placeholder="token[(variant._mode||'light')]"
                      class="flex-1 px-2 py-1.5 bg-muted border border-border rounded-lg text-xs font-mono focus:outline-none focus:border-primary" />
                  </div>
                </div>
              </div>

              <!-- Preview rápido da variante -->
              <div class="rounded-xl overflow-hidden border border-border">
                <div class="px-4 py-2 text-xs font-semibold text-muted-foreground bg-muted/50 border-b border-border">Pré-visualização da variante</div>
                <div class="p-4 flex gap-3 flex-wrap"
                  :style="{
                    background: variant.colors?.[(variant._mode||'light')]?.['--color-background'] || '#ffffff',
                    color: variant.colors?.[(variant._mode||'light')]?.['--color-foreground'] || '#111111',
                  }">
                  <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white"
                    :style="{ background: variant.colors?.[(variant._mode||'light')]?.['--color-primary'] || '#6366f1' }">
                    Botão Primary
                  </span>
                  <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white"
                    :style="{ background: variant.colors?.[(variant._mode||'light')]?.['--color-secondary'] || '#8b5cf6' }">
                    Secondary
                  </span>
                  <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white"
                    :style="{ background: variant.colors?.[(variant._mode||'light')]?.['--color-accent'] || '#f59e0b' }">
                    Accent
                  </span>
                  <span class="px-3 py-1.5 rounded-lg text-xs border"
                    :style="{
                      background: variant.colors?.[(variant._mode||'light')]?.['--color-card'] || '#ffffff',
                      borderColor: variant.colors?.[(variant._mode||'light')]?.['--color-border'] || '#e5e7eb',
                    }">
                    Card
                  </span>
                  <span class="text-xs font-semibold self-center">Texto normal</span>
                  <span class="text-xs self-center opacity-60">Texto secundário</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════════════ TAB: Ícones ════════════════════ -->
      <div v-show="activeTab === 'icons'" class="space-y-5">

        <!-- Legenda -->
        <div class="bg-violet-500/10 border border-violet-500/20 rounded-xl px-4 py-3 text-xs text-violet-600 dark:text-violet-400 space-y-1">
          <p class="font-semibold">✦ Galeria de Ícones — Lucide Icon Set</p>
          <p>Passa o rato por cima de um ícone para ver as 3 opções de cópia:</p>
          <div class="flex flex-wrap gap-4 mt-1">
            <span><strong class="bg-blue-500/20 px-1 rounded">Vue</strong> — importa e usa como componente <code>&lt;HomeIcon /&gt;</code></span>
            <span><strong class="bg-green-500/20 px-1 rounded">Blade</strong> — usa com o pacote blade-lucide-icons <code>&lt;x-lucide-home /&gt;</code></span>
            <span><strong class="bg-orange-500/20 px-1 rounded">SVG</strong> — copia o SVG inline completo, sem dependências</span>
          </div>
          <p class="text-violet-500/70 mt-1">💡 O SVG é carregado via CDN Lucide — requer ligação à internet no momento da cópia.</p>
        </div>

        <!-- Barra de pesquisa + categorias -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <div class="flex gap-3 items-center">
            <div class="relative flex-1 max-w-sm">
              <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <input v-model="iconSearch" type="search" placeholder="Pesquisar ícone…"
                class="field-input pl-9" />
            </div>
            <span class="text-xs text-muted-foreground">{{ filteredIcons.length }} ícones</span>
          </div>
          <!-- Categoria pills -->
          <div class="flex flex-wrap gap-1.5">
            <button v-for="cat in iconCategories" :key="cat.id"
              @click="activeIconCat = cat.id"
              class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
              :class="activeIconCat === cat.id
                ? 'bg-primary text-primary-foreground'
                : 'bg-muted text-muted-foreground hover:bg-border hover:text-foreground'">
              {{ cat.label }}
            </button>
          </div>
        </div>

        <!-- Toast "copiado" -->
        <transition name="fade">
          <div v-if="iconCopied.text"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-lg pointer-events-none text-xs font-semibold"
            :class="{
              'bg-blue-600 text-white':   iconCopied.type === 'vue',
              'bg-green-600 text-white':  iconCopied.type === 'blade',
              'bg-orange-500 text-white': iconCopied.type === 'svg',
            }">
            <CheckCircleIcon class="w-3.5 h-3.5" />
            <span>{{ iconCopied.type === 'vue' ? 'Vue' : iconCopied.type === 'blade' ? 'Blade' : 'SVG' }} copiado — {{ iconCopied.label }}</span>
          </div>
        </transition>

        <!-- Grade de ícones -->
        <div class="grid grid-cols-[repeat(auto-fill,minmax(110px,1fr))] gap-2">
          <template v-for="icon in filteredIcons" :key="icon.name">
            <div class="relative group bg-card border border-border rounded-xl p-3 flex flex-col items-center gap-2 transition-all hover:border-primary/40 hover:shadow-sm">

              <!-- Ícone + nome -->
              <component :is="LucideIcons[icon.name]" class="w-6 h-6 text-foreground flex-shrink-0" />
              <span class="text-[9px] text-center leading-tight text-muted-foreground break-all">
                {{ icon.name.replace(/Icon$/, '') }}
              </span>

              <!-- Overlay com botões ao hover -->
              <div class="absolute inset-0 rounded-xl bg-card/95 backdrop-blur-sm flex flex-col items-center justify-center gap-1.5 px-2
                          opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                <!-- Vue -->
                <button @click="copyIcon(icon.name, 'vue')"
                  class="w-full py-1 rounded-lg text-[10px] font-bold bg-blue-500/15 text-blue-600 hover:bg-blue-500/30 transition-colors">
                  &lt;&gt; Vue
                </button>
                <!-- Blade -->
                <button @click="copyIcon(icon.name, 'blade')"
                  class="w-full py-1 rounded-lg text-[10px] font-bold bg-green-500/15 text-green-700 hover:bg-green-500/30 transition-colors">
                  🔪 Blade
                </button>
                <!-- SVG -->
                <button @click="copyIcon(icon.name, 'svg')"
                  :disabled="iconSvgLoading === icon.name"
                  class="w-full py-1 rounded-lg text-[10px] font-bold bg-orange-500/15 text-orange-600 hover:bg-orange-500/30 transition-colors disabled:opacity-50">
                  {{ iconSvgLoading === icon.name ? '…' : '&#60;svg&#62;' }}
                </button>
              </div>

            </div>
          </template>
          <div v-if="filteredIcons.length === 0" class="col-span-full text-center py-16 text-muted-foreground text-sm">
            Nenhum ícone encontrado para "<span class="font-semibold">{{ iconSearch }}</span>"
          </div>
        </div>
      </div>

      <!-- ════════════════════ TAB: Demo Data ════════════════════ -->
      <div v-show="activeTab === 'demo'" class="space-y-5">

        <!-- Legenda -->
        <div class="bg-pink-500/10 border border-pink-500/20 rounded-xl px-4 py-3 text-xs text-pink-600 dark:text-pink-400 space-y-1">
          <p class="font-semibold">🎭 O que é o Demo Data?</p>
          <p>Preenche automaticamente o tema com <strong>conteúdo de demonstração realista</strong> — imagens placeholder, paletas de cores, layouts, secções e componentes — de acordo com o tipo de site escolhido.</p>
          <p class="text-pink-500/70">💡 Podes aplicar por categoria (só cores, só imagens…) ou tudo de uma vez. Os dados existentes são <strong>substituídos</strong>. Guarda depois para persistir.</p>
        </div>

        <!-- Seletor de tipo de site -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <h3 class="font-semibold text-sm text-foreground">1. Escolhe o tipo de site</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <button v-for="st in demoSiteTypes" :key="st.id"
              @click="selectedDemoType = st.id"
              class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all text-center"
              :class="selectedDemoType === st.id
                ? 'border-primary bg-primary/5 shadow-sm'
                : 'border-border bg-muted hover:border-primary/40 hover:bg-card'">
              <span class="text-2xl">{{ st.emoji }}</span>
              <span class="text-xs font-bold text-foreground">{{ st.label }}</span>
              <span class="text-[10px] text-muted-foreground leading-tight">{{ st.description }}</span>
              <!-- Preview de cores -->
              <div class="flex gap-1 mt-1" v-if="st.palette">
                <div v-for="c in st.palette" :key="c" class="w-4 h-4 rounded-full border border-white/20" :style="{ background: c }"></div>
              </div>
            </button>
          </div>
        </div>

        <!-- Categorias a aplicar -->
        <div v-if="selectedDemoType" class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-sm text-foreground">2. O que queres preencher?</h3>
            <button @click="toggleAllDemoCategories"
              class="text-xs text-primary hover:underline">
              {{ demoCategories.every(c => demoSelected.includes(c.id)) ? 'Desmarcar tudo' : 'Selecionar tudo' }}
            </button>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <label v-for="cat in demoCategories" :key="cat.id"
              class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
              :class="demoSelected.includes(cat.id)
                ? 'border-primary/40 bg-primary/5'
                : 'border-border bg-muted hover:bg-card'">
              <input type="checkbox" :value="cat.id" v-model="demoSelected" class="accent-primary w-4 h-4 rounded" />
              <div>
                <p class="text-xs font-semibold text-foreground">{{ cat.icon }} {{ cat.label }}</p>
                <p class="text-[10px] text-muted-foreground">{{ cat.hint }}</p>
              </div>
            </label>
          </div>
        </div>

        <!-- Pré-visualização do que vai ser aplicado -->
        <div v-if="selectedDemoType && demoSelected.length" class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <h3 class="font-semibold text-sm text-foreground">3. Pré-visualização</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <!-- Cores -->
            <div v-if="demoSelected.includes('colors')" class="space-y-2">
              <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">🎨 Cores</p>
              <div class="flex flex-wrap gap-2">
                <div v-for="(val, key) in currentDemoData?.colors?.light" :key="key"
                  class="flex items-center gap-1.5 bg-muted rounded-lg px-2 py-1">
                  <div class="w-3.5 h-3.5 rounded-full border border-white/20 shrink-0" :style="{ background: val }"></div>
                  <span class="text-[10px] text-muted-foreground">{{ key.replace('--color-','') }}</span>
                </div>
              </div>
            </div>

            <!-- Imagens demo -->
            <div v-if="demoSelected.includes('assets')" class="space-y-2">
              <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">🖼️ Imagens</p>
              <div class="flex gap-2 flex-wrap">
                <img v-for="(url, slot) in currentDemoData?.assets" :key="slot"
                  :src="url" :alt="slot"
                  class="w-16 h-12 object-cover rounded-lg border border-border" />
              </div>
            </div>

            <!-- Layout -->
            <div v-if="demoSelected.includes('layout')" class="space-y-2">
              <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">📐 Layout</p>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="(val, key) in currentDemoData?.layout_config" :key="key"
                  class="text-[10px] bg-muted rounded-md px-2 py-0.5 text-foreground">
                  {{ key.replace(/_/g, ' ') }}: <strong>{{ val }}</strong>
                </span>
              </div>
            </div>

            <!-- Secções -->
            <div v-if="demoSelected.includes('sections')" class="space-y-2">
              <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">🧩 Secções</p>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="(cfg, name) in currentDemoData?.sections" :key="name"
                  class="text-[10px] bg-blue-500/10 text-blue-600 rounded-md px-2 py-0.5 font-semibold">
                  {{ name }}
                </span>
              </div>
            </div>

            <!-- Componentes -->
            <div v-if="demoSelected.includes('components')" class="space-y-2">
              <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">🔧 Componentes</p>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="(cfg, name) in currentDemoData?.components" :key="name"
                  class="text-[10px] bg-violet-500/10 text-violet-600 rounded-md px-2 py-0.5 font-semibold">
                  {{ name }}
                </span>
              </div>
            </div>

            <!-- Capacidades -->
            <div v-if="demoSelected.includes('capabilities')" class="space-y-2">
              <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">⚙️ Capacidades</p>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="(val, key) in currentDemoData?.capabilities" :key="key"
                  class="text-[10px] rounded-md px-2 py-0.5 font-semibold"
                  :class="val ? 'bg-green-500/10 text-green-600' : 'bg-muted text-muted-foreground'">
                  {{ val ? '✓' : '✗' }} {{ key.replace(/_/g, ' ') }}
                </span>
              </div>
            </div>

          </div>
        </div>

        <!-- Botão de aplicar -->
        <div v-if="selectedDemoType && demoSelected.length"
          class="flex items-center justify-between gap-4 bg-card border border-border rounded-2xl p-5">
          <div>
            <p class="text-sm font-semibold text-foreground">Pronto para aplicar!</p>
            <p class="text-xs text-muted-foreground">Serão preenchidas {{ demoSelected.length }} categorias com dados do tema <strong>{{ demoSiteTypes.find(t=>t.id===selectedDemoType)?.label }}</strong>.</p>
          </div>
          <div class="flex gap-2 shrink-0">
            <button @click="selectedDemoType = null; demoSelected = []"
              class="px-4 py-2 bg-muted text-foreground rounded-xl text-sm font-semibold hover:bg-border transition-colors">
              Cancelar
            </button>
            <button @click="applyDemoData"
              class="px-5 py-2 bg-primary text-primary-foreground rounded-xl text-sm font-bold hover:opacity-90 transition-opacity flex items-center gap-2">
              🎭 Aplicar Demo Data
            </button>
          </div>
        </div>

        <!-- Estado vazio -->
        <div v-if="!selectedDemoType" class="flex flex-col items-center justify-center py-16 text-center text-muted-foreground space-y-3">
          <span class="text-5xl">🎭</span>
          <p class="text-sm font-semibold">Escolhe um tipo de site acima para começar</p>
          <p class="text-xs max-w-sm">Os dados de demo são baseados em sites reais de cada categoria — cores, imagens, layout e conteúdo coerentes.</p>
        </div>

      </div>

      <!-- ════════════════════ TAB: Preview ════════════════════ -->
      <div v-show="activeTab === 'preview'" class="space-y-3">
        <div class="flex items-center gap-3">
          <p class="text-sm text-muted-foreground flex-1">{{ t('themes.preview_hint') }}</p>

          <!-- Edit mode toggle -->
          <button @click="togglePreviewEditMode"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all"
            :class="previewEditMode
              ? 'bg-violet-500 text-white shadow-md'
              : 'bg-muted text-foreground hover:bg-border'">
            ✏️ {{ previewEditMode ? 'Modo Edição ON' : 'Modo Edição' }}
          </button>

          <button @click="previewKey++"
            class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border flex items-center gap-1">
            <RefreshCwIcon class="w-3.5 h-3.5" /> {{ t('themes.reload_preview') }}
          </button>
          <a :href="`/preview/theme/${theme.uuid}`" target="_blank"
            class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border flex items-center gap-1">
            <ExternalLinkIcon class="w-3.5 h-3.5" /> {{ t('themes.open_preview') }}
          </a>
        </div>

        <!-- Edit mode hint -->
        <div v-if="previewEditMode"
          class="flex items-center gap-2 px-3 py-2 bg-violet-500/10 border border-violet-500/20 rounded-xl text-xs text-violet-600 dark:text-violet-400">
          <span>✏️</span>
          <span>Clica em qualquer elemento no preview para editar as suas cores e fontes. As alterações são aplicadas em tempo real.</span>
        </div>

        <!-- Token toast -->
        <Transition name="fade">
          <div v-if="previewToast"
            class="flex items-center gap-2 px-3 py-2 bg-success/10 border border-success/20 rounded-xl text-xs text-success">
            ✦ {{ previewToast }}
          </div>
        </Transition>

        <div class="bg-muted rounded-2xl overflow-hidden border border-border" style="height:72vh;">
          <iframe ref="previewIframe" :key="previewKey"
            :src="`/preview/theme/${theme.uuid}`"
            class="w-full h-full border-0" />
        </div>
      </div>

      <!-- ════════════════════ TAB: Chat IA ════════════════════ -->
      <div v-show="activeTab === 'chat'" class="flex flex-col gap-4">

        <!-- Header -->
        <div class="flex items-center gap-3 px-4 py-3 bg-violet-500/8 border border-violet-500/20 rounded-xl">
          <div class="w-8 h-8 rounded-lg bg-violet-500/15 flex items-center justify-center shrink-0 text-base">✦</div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-foreground">Assistente de Design IA</p>
            <p class="text-xs text-muted-foreground">Descreve o que queres — eu trato do resto. Podes anexar imagens, vídeos ou documentos para inspiração.</p>
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
            <p class="text-xs text-muted-foreground max-w-xs">Pede um tema completo ("Cria um tema para um restaurante") ou um ajuste ("Torna mais minimalista"). Eu trato do resto — podes anexar imagens para inspiração.</p>
            <!-- Quick prompts -->
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
                <!-- Attachments preview -->
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
                    <span class="text-sm font-semibold text-foreground flex-1">{{ msg.building ? 'A construir o teu tema…' : (msg.failed ? 'Construção interrompida' : 'Tema construído') }}</span>
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

                  <!-- Detalhes técnicos (opcional) -->
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
                  <button @click="restoreVersion(msg.snapshot)"
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

                <!-- Applied updates badge — chat auto-guarda no servidor -->
                <div v-if="msg.applied" class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                  <span class="text-[10px] text-success font-semibold flex items-center gap-1">✓ Aplicadas e guardadas automaticamente</span>
                  <button v-if="msg.stepLabel" @click="activeTab = msg.step"
                    class="text-[10px] font-semibold text-primary bg-primary/10 border border-primary/20 px-2 py-0.5 rounded-full hover:bg-primary/20 transition-colors"
                    :title="'Ir para o passo ' + msg.stepLabel">
                    🎯 Passo: {{ msg.stepLabel }}
                  </button>
                </div>
                <div v-else-if="msg.updates && !msg.applied" class="mt-1.5 flex items-center gap-2">
                  <button @click="applyChatUpdates(msg.updates, i)"
                    class="text-[10px] px-2.5 py-1 bg-primary text-primary-foreground rounded-full font-semibold hover:opacity-90 transition-opacity">
                    ✦ Aplicar alterações
                  </button>
                  <span class="text-[10px] text-muted-foreground">A IA sugeriu mudanças ao tema</span>
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

        <!-- Drag & drop zone (shows when dragging) -->
        <div v-if="chatDragging"
          class="border-2 border-dashed border-primary rounded-2xl p-8 text-center text-primary text-sm font-semibold"
          @dragover.prevent @dragleave="chatDragging = false"
          @drop.prevent="onChatDrop($event)">
          📎 Solta os ficheiros aqui
        </div>

        <!-- Skill carregado + Input row (quando não se está a arrastar) -->
        <template v-else>
        <div v-if="buildSkillName" class="flex items-center gap-2 px-3 py-1.5 mb-2 bg-primary/8 border border-primary/20 rounded-xl text-xs">
          <span class="text-sm">✦</span>
          <span class="text-foreground font-medium">Skill: {{ buildSkillName }}</span>
          <span class="text-muted-foreground">— vai guiar a construção</span>
          <button @click="clearSkill" class="ml-auto text-muted-foreground hover:text-destructive" title="Remover skill">✕</button>
        </div>

        <!-- Input row -->
        <div class="flex gap-2 items-end"
          @dragover.prevent="chatDragging = true">
          <!-- Attach button -->
          <button @click="$refs.chatFileInput.click()"
            class="w-9 h-9 rounded-xl bg-muted border border-border flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-border transition-colors shrink-0 mb-0.5"
            title="Anexar ficheiro (imagem, PDF…)">
            📎
          </button>
          <input ref="chatFileInput" type="file" class="hidden" multiple
            accept="image/*,video/*,audio/*,.pdf,.txt,.md,.csv,.docx"
            @change="onChatFileSelect($event)" />

          <!-- Skill upload button -->
          <button @click="$refs.skillFileInput.click()"
            class="w-9 h-9 rounded-xl bg-muted border border-border flex items-center justify-center shrink-0 mb-0.5 transition-colors"
            :class="buildSkillName ? 'text-primary border-primary/40' : 'text-muted-foreground hover:text-foreground hover:bg-border'"
            title="Carregar skill / instruções (.md, .txt) para guiar a construção">
            ✦
          </button>
          <input ref="skillFileInput" type="file" class="hidden"
            accept=".md,.markdown,.txt,.json,.text"
            @change="loadSkillFile($event)" />

          <!-- Construção rápida (poupa tokens: sem plano por IA nem revisão) -->
          <button @click="fastBuild = !fastBuild"
            class="w-9 h-9 rounded-xl bg-muted border border-border flex items-center justify-center shrink-0 mb-0.5 transition-colors"
            :class="fastBuild ? 'text-amber-500 border-amber-500/40' : 'text-muted-foreground hover:text-foreground hover:bg-border'"
            :title="fastBuild ? 'Construção rápida LIGADA — sem revisão de qualidade (menos tokens)' : 'Construção rápida desligada — clica para poupar tokens'">
            ⚡
          </button>

          <!-- Text area -->
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

          <!-- Send button -->
          <button @click="sendChatMessage" :disabled="chatLoading || !chatInput.trim()"
            class="w-9 h-9 rounded-xl bg-primary text-primary-foreground flex items-center justify-center shrink-0 mb-0.5 disabled:opacity-40 hover:opacity-90 transition-opacity"
            title="Enviar (Enter)">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
            </svg>
          </button>
        </div>
        </template>

        <!-- Hints -->
        <p class="text-[10px] text-muted-foreground text-center">
          Suporta imagens (JPG, PNG, GIF, WebP), PDFs, áudios, vídeos e documentos de texto · Enter envia · Shift+Enter nova linha
        </p>

      </div>

      <!-- ══════════════════════════════════════════════════ -->
      <!-- TAB: VERSÕES                                      -->
      <!-- ══════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'versions'" class="max-w-3xl space-y-5">

        <!-- Header + criar versão -->
        <div class="bg-card border border-border rounded-2xl p-5">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <HistoryIcon class="w-4 h-4 text-muted-foreground" />
              <h2 class="font-semibold text-foreground text-sm">Histórico do tema</h2>
              <span class="px-2 py-0.5 rounded-full bg-muted text-muted-foreground text-[10px] font-semibold">
                {{ historyTimeline.length }} {{ historyTimeline.length === 1 ? 'entrada' : 'entradas' }}
              </span>
            </div>
            <button @click="showCreateVersionModal = true"
              class="px-3 py-1.5 bg-primary text-primary-foreground rounded-lg text-xs font-semibold hover:opacity-90 flex items-center gap-1.5">
              <PlusCircleIcon class="w-3.5 h-3.5" /> Guardar versão
            </button>
          </div>

          <!-- Versão actual -->
          <div class="flex items-center gap-3 p-3 bg-primary/5 border border-primary/20 rounded-xl mb-4">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
              <TagIcon class="w-4 h-4 text-primary" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-foreground">v{{ form.version || '1.0.0' }} — <span class="text-primary">Versão actual</span></p>
              <p class="text-[10px] text-muted-foreground truncate">{{ form.label }}</p>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-primary/10 text-primary font-semibold">Actual</span>
          </div>

          <!-- Lista de versões -->
          <div v-if="loadingVersions" class="flex items-center justify-center py-10">
            <div class="w-5 h-5 border-2 border-primary/30 border-t-primary rounded-full animate-spin"></div>
          </div>

          <div v-else-if="historyTimeline.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
            <HistoryIcon class="w-8 h-8 text-muted-foreground opacity-30 mb-2" />
            <p class="text-sm text-muted-foreground">Ainda não há histórico.</p>
            <p class="text-xs text-muted-foreground mt-1">Cada alteração (via Chat IA ou manual) e cada versão guardada aparecem aqui.</p>
          </div>

          <!-- Timeline unificado: versões (restauro completo) + alterações por passo (granular) -->
          <div v-else class="space-y-2">
            <div v-for="item in historyTimeline" :key="item.key"
              class="flex items-start gap-3 p-3 border rounded-xl transition-colors group"
              :class="item.kind === 'version' ? 'bg-primary/[0.03] border-primary/15' : 'bg-muted/40 border-border hover:border-border/80'">

              <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5 bg-muted">
                <span class="text-sm">{{ item.icon }}</span>
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-xs font-bold text-foreground">{{ item.title }}</span>
                  <span class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold"
                    :class="item.kind === 'version' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'">
                    {{ item.kind === 'version' ? '🏷️ Versão · ' + item.tag : '🔹 Passo · ' + item.tag }}
                  </span>
                </div>
                <p v-if="item.subtitle" class="text-[11px] text-muted-foreground mt-0.5 line-clamp-2">{{ item.subtitle }}</p>
                <p class="text-[10px] text-muted-foreground/60 mt-1">{{ formatVersionDate(item.at) }}</p>
              </div>

              <!-- Acções -->
              <div class="flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                <template v-if="item.kind === 'version'">
                  <button @click="restoreVersion(item.ver)"
                    :disabled="restoringVersion === item.ver.uuid"
                    class="px-2 py-1.5 bg-primary/10 text-primary rounded-lg text-[10px] font-semibold hover:bg-primary/20 transition-colors flex items-center gap-1 disabled:opacity-50">
                    <RotateCcwIcon class="w-3 h-3" />
                    {{ restoringVersion === item.ver.uuid ? '…' : 'Restaurar' }}
                  </button>
                  <button @click="deleteVersion(item.ver)"
                    class="px-2 py-1.5 bg-destructive/10 text-destructive rounded-lg text-[10px] font-semibold hover:bg-destructive/20 transition-colors">
                    <Trash2Icon class="w-3 h-3" />
                  </button>
                </template>
                <button v-else-if="item.revertible" @click="revertStep(item.step)"
                  class="px-2 py-1.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-lg text-[10px] font-semibold hover:bg-amber-500/20 transition-colors flex items-center gap-1">
                  <RotateCcwIcon class="w-3 h-3" /> Reverter
                </button>
                <span v-else-if="item.kind === 'step'" class="text-[9px] text-muted-foreground/50 self-center px-1">arquivado</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Info: uma narrativa, duas granularidades -->
        <div class="flex items-start gap-2 px-4 py-3 bg-muted/50 border border-border rounded-xl text-xs text-muted-foreground">
          <span class="mt-0.5">ℹ️</span>
          <p>Um só histórico, duas granularidades: <strong>🏷️ Versões</strong> são snapshots completos do tema (restauro grande — guardadas por ti 📌, automáticas ⚡ antes de cada restauro, ou de publicação 🚀). <strong>🔹 Passos</strong> são alterações pontuais (Chat IA 💬, manual ✏️ ou construção ✦) que podes <strong>reverter</strong> uma a uma — as mais recentes; as antigas ficam arquivadas (usa uma Versão para recuar mais).</p>
        </div>

      </div>

      <!-- ════════════════════ TAB: MACROS / RECEITAS ════════════════════ -->
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
                  
                  <template v-if="recipe.placeholder_types?.[ph] === 'color'">
                    <div class="flex gap-2 items-center">
                      <input v-model="recipeInputs[recipe.id][ph]" type="color" 
                        class="w-8 h-8 rounded border border-border cursor-pointer bg-transparent p-0.5 shrink-0 animate-pulse hover:animate-none" />
                      <input v-model="recipeInputs[recipe.id][ph]" type="text" 
                        class="field-input text-xs px-2.5 py-1.5 rounded-lg flex-1 font-mono" 
                        placeholder="#ffffff" />
                    </div>
                  </template>
                  
                  <template v-else-if="recipe.placeholder_types?.[ph] === 'number'">
                    <input v-model="recipeInputs[recipe.id][ph]" type="number" 
                      class="field-input text-xs px-2.5 py-1.5 rounded-lg" 
                      :placeholder="'Insira um número para ' + ph" />
                  </template>
                  
                  <template v-else-if="recipe.placeholder_types?.[ph] === 'url'">
                    <input v-model="recipeInputs[recipe.id][ph]" type="url" 
                      class="field-input text-xs px-2.5 py-1.5 rounded-lg" 
                      :placeholder="'https://exemplo.com (' + ph + ')'" />
                  </template>
                  
                  <template v-else>
                    <input v-model="recipeInputs[recipe.id][ph]" type="text" 
                      class="field-input text-xs px-2.5 py-1.5 rounded-lg" 
                      :placeholder="'Insere o valor para ' + ph" />
                  </template>
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

    </div>

    <!-- Add section modal -->
    <div v-if="showAddSection" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showAddSection = false">
      <div class="bg-card border border-border rounded-2xl p-6 w-full max-w-sm space-y-4">
        <h3 class="font-semibold text-foreground">{{ t('themes.add_section') }}</h3>
        <div>
          <label class="field-label">{{ t('themes.section_type') }}</label>
          <select v-model="newSectionType" class="field-input">
            <option v-for="s in availableSectionTypes" :key="s" :value="s">{{ s }}</option>
            <option value="__custom__">Tipo personalizado…</option>
          </select>
          <input v-if="newSectionType === '__custom__'" v-model="customSectionType"
            placeholder="ex: my_section" class="field-input mt-2 font-mono text-xs" />
        </div>
        <div class="flex gap-2">
          <button @click="confirmAddSection"
            class="flex-1 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold">Add</button>
          <button @click="showAddSection = false"
            class="flex-1 py-2.5 bg-muted text-foreground rounded-xl text-sm font-semibold hover:bg-border">{{ t('common.cancel') }}</button>
        </div>
      </div>
    </div>

  <!-- ════════════════════ MODAL: Exportar Prompt ════════════════════ -->
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
              <h2 class="font-bold text-foreground text-sm">Exportar Theme Prompt</h2>
              <p class="text-[10px] text-muted-foreground">Formato <code>.afprompt</code> — lido pelo AnimusFlow para instalar o tema</p>
            </div>
          </div>
          <button @click="showPromptModal = false" class="w-7 h-7 rounded-lg bg-muted hover:bg-border flex items-center justify-center text-muted-foreground transition-colors">✕</button>
        </div>

        <!-- Conteúdo -->
        <div class="overflow-y-auto flex-1 p-6 space-y-5">

          <!-- O que é -->
          <div class="bg-violet-500/10 border border-violet-500/20 rounded-xl px-4 py-3 text-xs text-violet-600 dark:text-violet-400 space-y-2">
            <p class="font-semibold">✦ O que é um Theme Prompt?</p>
            <p>É um ficheiro de texto estruturado (<code>.afprompt</code>) que contém <strong>todo o tema num único bloco</strong> — cores, layout, secções, componentes, assets, CSS e JS. O AnimusFlow lê este ficheiro e instala o tema automaticamente.</p>
          </div>

          <!-- Resumo do tema -->
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

          <!-- Formato do ficheiro -->
          <div class="bg-muted rounded-xl overflow-hidden">
            <div class="flex items-center justify-between px-4 py-2 border-b border-border">
              <span class="text-xs font-semibold text-muted-foreground">Pré-visualização do formato</span>
              <span class="text-[10px] text-muted-foreground font-mono">{{ theme.name }}.afprompt</span>
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
          <a :href="`/themes/${theme.uuid}/export-prompt`"
            class="px-5 py-2 bg-violet-600 text-white rounded-xl text-sm font-bold hover:bg-violet-700 transition-colors flex items-center gap-2">
            <SparklesIcon class="w-4 h-4" /> Descarregar .afprompt
          </a>
        </div>

      </div>
    </div>
  </transition>

  <!-- ── Modal: Guardar Versão ────────────────────────────── -->
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="showCreateVersionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCreateVersionModal = false" />
        <div class="relative w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl p-6 space-y-4">
          <div class="flex items-center gap-2 mb-1">
            <HistoryIcon class="w-5 h-5 text-primary" />
            <h3 class="font-bold text-foreground">Guardar versão</h3>
          </div>
          <p class="text-xs text-muted-foreground">Vai ser criado um snapshot da versão <strong>v{{ form.version || '1.0.0' }}</strong> do tema. Podes adicionar uma nota do que mudou.</p>

          <div>
            <label class="field-label">Nota da versão <span class="text-muted-foreground font-normal">(opcional)</span></label>
            <textarea v-model="newVersionChangelog" rows="3"
              placeholder="Ex: Ajustei as cores primárias e adicionei nova secção de testemunhos…"
              class="field-input resize-none text-sm" />
          </div>

          <div class="flex gap-2 justify-end pt-1">
            <button @click="showCreateVersionModal = false"
              class="px-4 py-2 bg-muted text-foreground rounded-xl text-sm font-semibold hover:bg-border transition-colors">
              Cancelar
            </button>
            <button @click="saveVersion" :disabled="savingVersion"
              class="px-5 py-2 bg-primary text-primary-foreground rounded-xl text-sm font-semibold hover:opacity-90 disabled:opacity-50 flex items-center gap-2">
              <span v-if="savingVersion" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin inline-block"></span>
              <HistoryIcon v-else class="w-4 h-4" />
              {{ savingVersion ? 'A guardar…' : 'Guardar versão' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import * as LucideIcons from 'lucide-vue-next';
import {
  EyeIcon, DownloadIcon, UploadIcon, SparklesIcon,
  CheckCircleIcon, XCircleIcon, PlusIcon, CodeIcon,
  RefreshCwIcon, ExternalLinkIcon, PaletteIcon, SearchIcon,
  HistoryIcon, RotateCcwIcon, Trash2Icon, TagIcon, PlusCircleIcon,
} from 'lucide-vue-next';

const { t } = useI18n();
const props = defineProps({
  theme:       { type: Object, default: null },
  themeAgents: { type: Array,  default: () => [] },
  stepJournal: { type: Object, default: () => ({}) },
});

// ── Tabs ──────────────────────────────────────────────────────────
const activeTab = ref('details');
const tabs = [
  { id: 'details',      icon: '📋', label: 'Detalhes'    },
  { id: 'layout',       icon: '📐', label: 'Layout'      },
  { id: 'capabilities', icon: '⚙️',  label: 'Capacidades' },
  { id: 'design',       icon: '🎨', label: 'Design'      },
  { id: 'assets',       icon: '🖼️',  label: 'Assets'      },
  { id: 'sections',     icon: '🧩', label: 'Secções'     },
  { id: 'components',   icon: '🔧', label: 'Componentes' },
  { id: 'code',         icon: '💻', label: 'Código'      },
  { id: 'variants',     icon: '🌈', label: 'Variantes'   },
  { id: 'icons',        icon: '✦',  label: 'Ícones'      },
  { id: 'demo',         icon: '🎭', label: 'Demo Data'   },
  { id: 'preview',      icon: '👁️',  label: 'Preview'     },
  { id: 'chat',         icon: '💬', label: 'Chat IA'     },
  { id: 'versions',     icon: '🕐', label: 'Versões'     },
  { id: 'recipes',      icon: '⚡',  label: 'Macros'      },
];

// ── Workflow stepper ──────────────────────────────────────────────
const guideOpen = ref(props.theme?.status === 'draft' && !props.theme?.description);

const workflowSteps = computed(() => {
  const f = form; // alias — computed reage às propriedades do reactive
  const hasColors     = f.colors && Object.keys(f.colors).length > 0;
  const hasVariants   = f.variants && f.variants.length > 0;
  const hasAssets     = f.assets && Object.values(f.assets).some(v => !!v);
  const hasSections   = f.sections && Object.keys(f.sections).length > 0;
  const hasComponents = f.components && Object.keys(f.components).length > 0;
  const hasCode       = !!(f.custom_css?.trim() || f.custom_js?.trim());
  const isDefaultLabel = !f.label || f.label.startsWith('Novo Tema');

  return [
    {
      tabId: 'details', icon: '📋', label: 'Detalhes',
      hint: 'Define o nome, slug, versão e descrição do tema.',
      done: !isDefaultLabel && !!f.description,
    },
    {
      tabId: 'design', icon: '🎨', label: 'Design',
      hint: 'Escolhe as cores base, tipografia e estilos globais.',
      done: hasColors,
    },
    {
      tabId: 'variants', icon: '🌈', label: 'Variantes',
      hint: 'Adiciona paletas de cores alternativas (dark mode, skins).',
      done: hasVariants, optional: true,
    },
    {
      tabId: 'layout', icon: '📐', label: 'Layout',
      hint: 'Configura header, navegação, footer e estrutura da página.',
      done: !!f.layout_config?.header_type,
    },
    {
      tabId: 'capabilities', icon: '⚙️', label: 'Capacidades',
      hint: 'Ativa funcionalidades especiais: parallax, animações, banners…',
      done: f.capabilities && Object.values(f.capabilities).some(v => v === true),
    },
    {
      tabId: 'assets', icon: '🖼️', label: 'Assets',
      hint: 'Faz upload do logo, favicon, imagens de fundo e OG image.',
      done: hasAssets, optional: true,
    },
    {
      tabId: 'sections', icon: '🧩', label: 'Secções',
      hint: 'Seleciona os blocos de conteúdo: hero, features, pricing…',
      done: hasSections,
    },
    {
      tabId: 'components', icon: '🔧', label: 'Componentes',
      hint: 'Adiciona e ordena componentes: navbar, footer, modais…',
      done: hasComponents, optional: true,
    },
    {
      tabId: 'code', icon: '💻', label: 'Código',
      hint: 'Adiciona CSS ou JS personalizado se necessário.',
      done: hasCode,
    },
    {
      tabId: 'demo', icon: '🎭', label: 'Demo Data',
      hint: 'Preenche o tema com imagens, cores e conteúdo de demonstração por tipo de site.',
      done: !!f._demoApplied, optional: true,
    },
    {
      tabId: 'preview', icon: '👁️', label: 'Preview',
      hint: 'Visualiza o tema no browser antes de exportar.',
      done: hasColors && hasSections,
    },
  ];
});

// A percentagem conta apenas os passos ESSENCIAIS (não-opcionais). Os passos
// opcionais (variantes, assets, componentes, demo data) são melhorias manuais
// e não devem fazer um tema completo via IA parecer "incompleto".
const coreSteps      = computed(() => workflowSteps.value.filter(s => !s.optional));
const completedCore  = computed(() => coreSteps.value.filter(s => s.done).length);
const progressPct    = computed(() => Math.round((completedCore.value / coreSteps.value.length) * 100));
// Total concluído (inclui opcionais) — usado apenas em listagens auxiliares.
const completedSteps = computed(() => workflowSteps.value.filter(s => s.done).length);

// ── Schema espelho (step_journal) — estado/origem/histórico por passo ─────
// Vem num prop separado e LEVE (sem snapshots pesados — só metadados).
const stepJournal = ref(props.stepJournal ?? {});
const SOURCE_META = {
  chat:   { icon: '💬', label: 'Chat IA' },
  manual: { icon: '✏️', label: 'Manual' },
  build:  { icon: '✦',  label: 'Construção IA' },
  revert: { icon: '↩',  label: 'Revertido' },
};
function stepJournalFor(tabId) { return stepJournal.value?.[tabId] ?? null; }
function sourceMeta(src) { return SOURCE_META[src] ?? { icon: '•', label: src || '—' }; }

async function revertStep(tabId) {
  const node = stepJournalFor(tabId);
  if (!node || !node.revertible) return;
  const stepName = workflowSteps.value.find(s => s.tabId === tabId)?.label ?? tabId;
  if (!confirm(`Reverter a última alteração do passo "${stepName}"? O valor anterior será restaurado.`)) return;
  try {
    const fd = new FormData();
    fd.append('step', tabId);
    fd.append('_token', csrf());
    const res = await fetch(`/themes/${props.theme.uuid}/revert-step`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.reverted) {
      if (data.theme) applyServerTheme(data.theme);
      stepJournal.value = data.journal ?? {};
      feedback.success = 'Passo revertido — valor anterior restaurado.';
    } else {
      feedback.error = 'Esta alteração já não é revertível (o snapshot foi arquivado). Usa o Histórico de versões para recuar mais.';
    }
  } catch (e) { feedback.error = e.message; }
}

// ── Edit form ─────────────────────────────────────────────────────
const defaultLayout = props.theme?.layout_config ?? {};
const defaultCaps   = props.theme?.capabilities  ?? {};

const form = reactive({
  name:        props.theme?.name        ?? '',
  label:       props.theme?.label       ?? '',
  description: props.theme?.description ?? '',
  version:     props.theme?.version     ?? '1.0.0',
  status:      props.theme?.status      ?? 'draft',
  fonts: {
    heading: props.theme?.fonts?.heading ?? '',
    body:    props.theme?.fonts?.body    ?? '',
  },
  colors: {
    light: { ...(props.theme?.colors?.light ?? {}) },
    dark:  { ...(props.theme?.colors?.dark  ?? {}) },
  },
  sections:   { ...(props.theme?.sections   ?? {}) },
  components: { ...(props.theme?.components ?? {}) },
  custom_css: props.theme?.custom_css ?? '',
  custom_js:  props.theme?.custom_js  ?? '',
  assets:     { ...(props.theme?.assets ?? {}) },
  variants:   JSON.parse(JSON.stringify(props.theme?.variants ?? [])),
  _demoApplied: false, // flag local — não enviada ao servidor
  layout_config: {
    header_type:      defaultLayout.header_type      ?? 'glass',
    header_sticky:    defaultLayout.header_sticky    ?? true,
    header_cta_text:  defaultLayout.header_cta_text  ?? '',
    header_cta_url:   defaultLayout.header_cta_url   ?? '#',
    nav_type:         defaultLayout.nav_type         ?? 'horizontal',
    nav_position:     defaultLayout.nav_position     ?? 'right',
    footer_type:      defaultLayout.footer_type      ?? 'simple',
    footer_copyright: defaultLayout.footer_copyright ?? '',
    layout_type:      defaultLayout.layout_type      ?? 'full-width',
    max_width:        defaultLayout.max_width        ?? '1120',
    spacing:          defaultLayout.spacing          ?? 'normal',
    show_dark_toggle: defaultLayout.show_dark_toggle ?? true,
    back_to_top:      defaultLayout.back_to_top      ?? true,
    menu_layout:      defaultLayout.menu_layout      ?? 'circular',
  },
  capabilities: {
    video_bg:        defaultCaps.video_bg        ?? false,
    parallax:        defaultCaps.parallax        ?? false,
    animations:      defaultCaps.animations      ?? true,
    lightbox:        defaultCaps.lightbox        ?? false,
    mega_menu:       defaultCaps.mega_menu       ?? false,
    search:          defaultCaps.search          ?? false,
    cookie_banner:   defaultCaps.cookie_banner   ?? false,
    preloader:       defaultCaps.preloader       ?? false,
    scroll_progress: defaultCaps.scroll_progress ?? false,
    back_to_top:     defaultCaps.back_to_top     ?? true,
  },
});

const saving   = ref(false);
const feedback = reactive({ success: '', error: '' });

function syncComponents() {
  form.components = {};
  compOrder.value.forEach(item => {
    form.components[item.uid] = { type: item.type, variant: item.variant, blade: item.blade };
  });
}

function save() {
  syncComponents();
  saving.value = true;
  feedback.success = ''; feedback.error = '';
  router.put(`/themes/${props.theme.uuid}`, form, {
    onFinish:  () => { saving.value = false; },
    onSuccess: () => { feedback.success = t('themes.saved'); },
    onError:   (e) => { feedback.error = Object.values(e)[0] ?? 'Erro ao guardar.'; },
  });
}

// ── Color mode ────────────────────────────────────────────────────
const colorMode     = ref('light');
const currentColors = computed(() =>
  colorMode.value === 'light' ? form.colors.light : form.colors.dark
);
function hexFallback(v) { return (!v || v.startsWith('oklch')) ? '#6366f1' : v; }

const colorTokens = [
  { var: '--color-primary',            default: 'oklch(0.55 0.22 265)' },
  { var: '--color-primary-foreground', default: 'oklch(1 0 0)' },
  { var: '--color-background',         default: 'oklch(0.99 0.003 265)' },
  { var: '--color-foreground',         default: 'oklch(0.13 0.02 265)' },
  { var: '--color-card',               default: 'oklch(1 0 0)' },
  { var: '--color-muted',              default: 'oklch(0.96 0.005 265)' },
  { var: '--color-muted-foreground',   default: 'oklch(0.50 0.02 265)' },
  { var: '--color-border',             default: 'oklch(0.91 0.005 265)' },
  { var: '--color-success',            default: 'oklch(0.65 0.20 150)' },
  { var: '--color-warning',            default: 'oklch(0.75 0.18 80)' },
  { var: '--color-destructive',        default: 'oklch(0.60 0.22 25)' },
];

// ── Google Fonts ──────────────────────────────────────────────────
const googleFonts = [
  'Inter', 'Poppins', 'DM Sans', 'Outfit', 'Plus Jakarta Sans', 'Sora',
  'Nunito', 'Raleway', 'Lato', 'Montserrat', 'Playfair Display',
  'Merriweather', 'Lora', 'Fraunces', 'Space Grotesk',
  'Geist', 'IBM Plex Sans', 'Source Sans 3',
];

// ── AI Generator ──────────────────────────────────────────────────
const aiPrompt  = ref('');
const aiLoading = ref(false);

async function generateAi() {
  if (!aiPrompt.value.trim() || aiLoading.value) return;
  aiLoading.value = true; feedback.error = ''; feedback.success = '';
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const res  = await fetch(`/themes/${props.theme.uuid}/generate-ai`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ prompt: aiPrompt.value }),
    });
    const data = await res.json();
    if (!res.ok || data.error) { feedback.error = data.error ?? 'Erro IA'; return; }
    if (data.colors?.light) Object.assign(form.colors.light, data.colors.light);
    if (data.colors?.dark)  Object.assign(form.colors.dark,  data.colors.dark);
    if (data.fonts?.heading) form.fonts.heading = data.fonts.heading;
    if (data.fonts?.body)    form.fonts.body    = data.fonts.body;
    if (data.sections)       Object.assign(form.sections, data.sections);
    feedback.success = t('themes.ai_success');
    aiPrompt.value   = '';
    activeTab.value  = 'design';
  } catch(e) { feedback.error = e.message; }
  finally { aiLoading.value = false; }
}

// ── Assets ────────────────────────────────────────────────────────
const uploadingSlot = ref('');
const assetGroups = {
  identity: [
    { id: 'logo',        label: '🏷️ Logótipo',         hint: 'SVG/PNG recomendado',       accept: 'image/*,.svg' },
    { id: 'logo_dark',   label: '🏷️ Logo (dark)',       hint: 'Versão para fundo escuro',  accept: 'image/*,.svg' },
    { id: 'favicon',     label: '🔖 Favicon',           hint: 'ICO ou PNG 32×32 / 64×64', accept: 'image/*,.ico' },
  ],
  background: [
    { id: 'bg_image',    label: '🖼️ Imagem de fundo',   hint: 'JPG/PNG/WebP, min 1920px',  accept: 'image/*' },
    { id: 'bg_video',    label: '🎬 Vídeo de fundo',    hint: 'MP4/WebM, máx 50MB',        accept: 'video/*' },
    { id: 'bg_pattern',  label: '🔲 Padrão / textura',  hint: 'PNG transparente, tileable', accept: 'image/*' },
  ],
  hero: [
    { id: 'hero_image',  label: '🌅 Hero — Imagem',     hint: 'JPG/PNG/WebP, min 1920px',  accept: 'image/*' },
    { id: 'hero_video',  label: '🎬 Hero — Vídeo',      hint: 'MP4/WebM, máx 30MB',        accept: 'video/*' },
    { id: 'hero_poster', label: '🖼️ Hero — Poster',     hint: 'Thumbnail do vídeo (fallback)', accept: 'image/*' },
  ],
  slideshow: [
    { id: 'slide_1',     label: '🖼️ Slide 1',           hint: 'JPG/PNG/WebP',              accept: 'image/*' },
    { id: 'slide_2',     label: '🖼️ Slide 2',           hint: 'JPG/PNG/WebP',              accept: 'image/*' },
    { id: 'slide_3',     label: '🖼️ Slide 3',           hint: 'JPG/PNG/WebP',              accept: 'image/*' },
  ],
  sections: [
    { id: 'about_bg',    label: '📖 About — Fundo',     hint: 'Fundo da secção About',     accept: 'image/*' },
    { id: 'features_bg', label: '⚡ Features — Fundo',  hint: 'Fundo da secção Features',  accept: 'image/*' },
    { id: 'cta_bg',      label: '📢 CTA — Fundo',       hint: 'Fundo da secção CTA',       accept: 'image/*' },
    { id: 'testimonials_bg', label: '💬 Testemunhos — Fundo', hint: 'Fundo dos testemunhos', accept: 'image/*' },
    { id: 'pricing_bg',  label: '💰 Preços — Fundo',    hint: 'Fundo da secção de preços', accept: 'image/*' },
    { id: 'footer_bg',   label: '🔻 Footer — Fundo',    hint: 'Fundo do rodapé',           accept: 'image/*' },
  ],
  social: [
    { id: 'og_image',    label: '📤 OG Image',          hint: '1200×630px — redes sociais', accept: 'image/*' },
    { id: 'twitter_card',label: '🐦 Twitter Card',      hint: '1200×600px — Twitter/X',    accept: 'image/*' },
    { id: 'apple_touch', label: '🍎 Apple Touch Icon',  hint: '180×180px — iOS',           accept: 'image/*' },
  ],
};

async function handleAssetUpload({ slotId, file }) {
  uploadingSlot.value = slotId;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  const fd   = new FormData();
  fd.append('file', file); fd.append('slot', slotId);
  try {
    const res  = await fetch(`/themes/${props.theme.uuid}/upload-asset`, {
      method: 'POST', headers: { 'X-CSRF-TOKEN': csrf }, body: fd,
    });
    const data = await res.json();
    if (data.success) {
      form.assets[data.slot] = data.url;
      feedback.success = `Asset "${slotId}" carregado.`;
    } else {
      feedback.error = data.message ?? 'Upload falhou.';
    }
  } catch(e) { feedback.error = e.message; }
  finally { uploadingSlot.value = ''; }
}

async function handleAssetDelete({ slotId }) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  await fetch(`/themes/${props.theme.uuid}/asset`, {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
    body: JSON.stringify({ slot: slotId }),
  });
  delete form.assets[slotId];
  feedback.success = `Asset "${slotId}" removido.`;
}

// ── Sections — biblioteca de blocos ──────────────────────────────
const activeSectionCat   = ref('all');
const sectionVariants    = reactive({});
const openSectionEditors = reactive({});

const sectionCategories = [
  { id: 'all',        icon: '🔠', label: 'Todos'       },
  { id: 'hero',       icon: '🌅', label: 'Hero'        },
  { id: 'content',    icon: '📄', label: 'Conteúdo'    },
  { id: 'media',      icon: '🎬', label: 'Media'       },
  { id: 'social',     icon: '💬', label: 'Social'      },
  { id: 'conversion', icon: '🎯', label: 'Conversão'   },
  { id: 'layout',     icon: '📐', label: 'Layout'      },
  { id: 'ecommerce',  icon: '🛒', label: 'E-commerce'  },
  { id: 'ai',         icon: '🤖', label: 'IA / Inteligente' },
];

const sectionBlocks = [
  // Hero
  { id: 'hero',              cat: 'hero',       icon: '🌅', label: 'Hero Principal',        hint: 'Secção de topo com título, subtítulo e CTA',
    variants: [{ id: 'centered', label: 'Centrado' }, { id: 'split', label: 'Split (img/texto)' }, { id: 'fullscreen', label: 'Ecrã completo' }, { id: 'video', label: 'Com vídeo' }] },
  { id: 'hero_slider',       cat: 'hero',       icon: '🎠', label: 'Hero Slider',           hint: 'Carrossel de banners no topo',
    variants: [{ id: 'fade', label: 'Fade' }, { id: 'slide', label: 'Deslizar' }] },
  { id: 'hero_minimal',      cat: 'hero',       icon: '✨', label: 'Hero Minimalista',      hint: 'Header limpo com apenas texto e CTA' },
  { id: 'hero_parallax',     cat: 'hero',       icon: '🌊', label: 'Hero com Parallax',     hint: 'Efeito parallax no fundo',
    variants: [{ id: 'light', label: 'Light' }, { id: 'dark', label: 'Dark' }] },
  // Conteúdo
  { id: 'about',             cat: 'content',    icon: '👋', label: 'Sobre Nós',             hint: 'Apresentação da empresa ou produto' },
  { id: 'features',          cat: 'content',    icon: '⚡', label: 'Funcionalidades',       hint: 'Grid de features com ícones e descrição',
    variants: [{ id: 'grid', label: 'Grid 3 col' }, { id: 'list', label: 'Lista' }, { id: 'tabs', label: 'Com tabs' }] },
  { id: 'services',          cat: 'content',    icon: '🔧', label: 'Serviços',              hint: 'Cards de serviços oferecidos',
    variants: [{ id: 'cards', label: 'Cards' }, { id: 'icons', label: 'Ícones' }] },
  { id: 'text',              cat: 'content',    icon: '📝', label: 'Texto Livre',           hint: 'Bloco de texto genérico (rich text)' },
  { id: 'columns',           cat: 'content',    icon: '▦',  label: 'Colunas',              hint: 'Layout multi-coluna configurável',
    variants: [{ id: 'two', label: '2 Colunas' }, { id: 'three', label: '3 Colunas' }, { id: 'four', label: '4 Colunas' }] },
  { id: 'stats',             cat: 'content',    icon: '📊', label: 'Estatísticas',          hint: 'Números e métricas animados' },
  { id: 'team',              cat: 'content',    icon: '👥', label: 'Equipa',               hint: 'Cards dos membros da equipa' },
  { id: 'timeline',          cat: 'content',    icon: '📅', label: 'Linha do Tempo',        hint: 'Histórico ou processo em timeline' },
  { id: 'steps',             cat: 'content',    icon: '🪜', label: 'Passos / How it Works', hint: 'Processo numerado passo a passo' },
  { id: 'faq',               cat: 'content',    icon: '❓', label: 'FAQ',                  hint: 'Perguntas frequentes em acordeão' },
  // Media
  { id: 'gallery',           cat: 'media',      icon: '🖼️',  label: 'Galeria',             hint: 'Grid de imagens com lightbox',
    variants: [{ id: 'masonry', label: 'Masonry' }, { id: 'grid', label: 'Grid uniforme' }, { id: 'carousel', label: 'Carrossel' }] },
  { id: 'video_section',     cat: 'media',      icon: '🎬', label: 'Secção Vídeo',          hint: 'Vídeo embed ou de fundo com overlay' },
  { id: 'portfolio',         cat: 'media',      icon: '🎨', label: 'Portfólio',             hint: 'Trabalhos/projectos com filtro por categoria',
    variants: [{ id: 'grid', label: 'Grid' }, { id: 'masonry', label: 'Masonry' }] },
  { id: 'logos',             cat: 'media',      icon: '🤝', label: 'Parceiros / Logos',     hint: 'Carousel de logos de clientes/parceiros' },
  // Social
  { id: 'testimonials',      cat: 'social',     icon: '💬', label: 'Testemunhos',           hint: 'Avaliações e depoimentos de clientes',
    variants: [{ id: 'cards', label: 'Cards' }, { id: 'carousel', label: 'Carrossel' }, { id: 'masonry', label: 'Masonry' }] },
  { id: 'reviews',           cat: 'social',     icon: '⭐', label: 'Avaliações',            hint: 'Reviews com estrelas e rating médio' },
  { id: 'social_feed',       cat: 'social',     icon: '📱', label: 'Feed Social',           hint: 'Posts do Instagram, Twitter, etc.' },
  { id: 'blog',              cat: 'social',     icon: '✏️',  label: 'Blog / Artigos',       hint: 'Últimos posts do blog em cards' },
  // Conversão
  { id: 'cta',               cat: 'conversion', icon: '🎯', label: 'Call to Action',        hint: 'Secção de chamada à acção com botão',
    variants: [{ id: 'simple', label: 'Simples' }, { id: 'banner', label: 'Banner' }, { id: 'split', label: 'Split' }] },
  { id: 'pricing',           cat: 'conversion', icon: '💰', label: 'Preços / Planos',       hint: 'Tabela de planos com toggle mensal/anual',
    variants: [{ id: 'cards', label: 'Cards' }, { id: 'table', label: 'Tabela' }] },
  { id: 'newsletter',        cat: 'conversion', icon: '📧', label: 'Newsletter',            hint: 'Subscrição de email com campo + botão' },
  { id: 'contact',           cat: 'conversion', icon: '📩', label: 'Contacto',              hint: 'Formulário de contacto',
    variants: [{ id: 'simple', label: 'Simples' }, { id: 'split', label: 'Split (mapa)' }] },
  { id: 'countdown',         cat: 'conversion', icon: '⏱️',  label: 'Countdown',           hint: 'Temporizador de contagem decrescente' },
  { id: 'popup',             cat: 'conversion', icon: '🪟', label: 'Popup / Modal',         hint: 'Popup de exit-intent ou temporizado' },
  // Layout
  { id: 'banner',            cat: 'layout',     icon: '📢', label: 'Banner / Anúncio',      hint: 'Faixa de anúncio acima do header' },
  { id: 'breadcrumb',        cat: 'layout',     icon: '🗺️',  label: 'Breadcrumb',          hint: 'Navegação em migalhas de pão' },
  { id: 'cards',             cat: 'layout',     icon: '🃏', label: 'Cards Genéricos',       hint: 'Grid de cards configuráveis' },
  { id: 'divider',           cat: 'layout',     icon: '➖', label: 'Separador / Divider',   hint: 'Separador visual entre secções' },
  { id: 'quote',             cat: 'layout',     icon: '💡', label: 'Citação / Quote',       hint: 'Bloco de citação destacado' },
  { id: 'map',               cat: 'layout',     icon: '📍', label: 'Mapa',                  hint: 'Google Maps embed' },
  // E-commerce
  { id: 'products',          cat: 'ecommerce',  icon: '📦', label: 'Produtos em Destaque',  hint: 'Grid de produtos do catálogo',
    variants: [{ id: 'grid', label: 'Grid' }, { id: 'carousel', label: 'Carrossel' }] },
  { id: 'cart',              cat: 'ecommerce',  icon: '🛒', label: 'Mini Carrinho',         hint: 'Widget de carrinho lateral' },
  { id: 'checkout',          cat: 'ecommerce',  icon: '💳', label: 'Checkout',             hint: 'Página de checkout integrada' },
  // IA / Inteligente
  { id: 'ai_chatbox',         cat: 'ai',         icon: '🤖', label: 'AI Chatbox',           hint: 'Janela de chat interativa alimentada por IA',
    variants: [{ id: 'card', label: 'Card Integrado' }, { id: 'floating', label: 'Flutuante' }] },
  { id: 'ai_recommendations', cat: 'ai',         icon: '✨', label: 'Recomendações IA',      hint: 'Recomendações dinâmicas de conteúdo/produtos baseadas em IA',
    variants: [{ id: 'cards', label: 'Cards (Grid)' }, { id: 'list', label: 'Lista' }] },
  { id: 'ai_summary',         cat: 'ai',         icon: '📄', label: 'Resumo com IA',         hint: 'Resumo inteligente do conteúdo da página gerado automaticamente',
    variants: [{ id: 'card', label: 'Card' }, { id: 'inline', label: 'Minimalista' }] },
  { id: 'ai_faq',             cat: 'ai',         icon: '❓', label: 'FAQ Inteligente',       hint: 'Lista de perguntas frequentes geradas e respondidas por IA',
    variants: [{ id: 'accordion', label: 'Acordeão' }, { id: 'list', label: 'Lista' }] },
  { id: 'ai_search',          cat: 'ai',         icon: '🔍', label: 'Pesquisa Semântica',     hint: 'Caixa de pesquisa inteligente e semântica com sugestões de IA',
    variants: [{ id: 'bar', label: 'Barra' }, { id: 'minimal', label: 'Minimalista' }] },
  { id: 'ai_personalized',    cat: 'ai',         icon: '👤', label: 'Conteúdo Personalizado', hint: 'Secção com conteúdo dinâmico baseado no segmento do visitante',
    variants: [{ id: 'card', label: 'Card' }, { id: 'banner', label: 'Banner' }] },
];

const filteredSectionBlocks = computed(() =>
  activeSectionCat.value === 'all'
    ? sectionBlocks
    : sectionBlocks.filter(b => b.cat === activeSectionCat.value)
);

function getSectionBlock(id) {
  return sectionBlocks.find(b => b.id === id);
}

function toggleSection(block) {
  if (form.sections[block.id] !== undefined) {
    if (!confirm(`Remover bloco "${block.label}"?`)) return;
    delete form.sections[block.id];
    delete openSectionEditors[block.id];
  } else {
    form.sections[block.id] = '';
    openSectionEditors[block.id] = false;
    if (block.variants?.length) sectionVariants[block.id] = block.variants[0].id;
  }
}

function toggleSectionEditor(type) {
  openSectionEditors[type] = !openSectionEditors[type];
}

function removeSection(type) {
  if (!confirm(`Remover bloco "${type}"?`)) return;
  delete form.sections[type];
  delete openSectionEditors[type];
}

// ── Components — biblioteca + drag & drop ─────────────────────────
const activeCompCat = ref('all');

const compCategories = [
  { id: 'all',       icon: '🔠', label: 'Todos'      },
  { id: 'structure', icon: '🏗️',  label: 'Estrutura'  },
  { id: 'nav',       icon: '🗺️',  label: 'Navegação'  },
  { id: 'ui',        icon: '🎨', label: 'UI'          },
  { id: 'forms',     icon: '📝', label: 'Formulários' },
  { id: 'media',     icon: '🖼️',  label: 'Media'      },
  { id: 'feedback',  icon: '💬', label: 'Feedback'    },
  { id: 'data',      icon: '📊', label: 'Dados'       },
];

const compLibrary = [
  // Estrutura
  { id: 'header',       cat: 'structure', icon: '🔝', label: 'Header',            hint: 'Cabeçalho principal da página',
    variants: [{ id: 'glass', label: 'Glass' }, { id: 'solid', label: 'Solid' }, { id: 'transparent', label: 'Transparente' }, { id: 'centered', label: 'Logo centrado' }] },
  { id: 'footer',       cat: 'structure', icon: '🔻', label: 'Footer',            hint: 'Rodapé da página',
    variants: [{ id: 'simple', label: 'Simples' }, { id: 'columns', label: 'Colunas' }, { id: 'dark', label: 'Dark' }, { id: 'minimal', label: 'Minimal' }] },
  { id: 'sidebar',      cat: 'structure', icon: '◧',  label: 'Sidebar',          hint: 'Painel lateral fixo ou flutuante',
    variants: [{ id: 'left', label: 'Esquerda' }, { id: 'right', label: 'Direita' }] },
  { id: 'topbar',       cat: 'structure', icon: '📢', label: 'Top Bar',           hint: 'Barra de aviso acima do header' },
  { id: 'breadcrumb',   cat: 'structure', icon: '🗺️',  label: 'Breadcrumb',      hint: 'Navegação em migalhas de pão' },
  { id: 'back_to_top',  cat: 'structure', icon: '⬆️',  label: 'Voltar ao Topo',  hint: 'Botão flutuante de regresso ao topo' },
  { id: 'preloader',    cat: 'structure', icon: '⏳', label: 'Preloader',         hint: 'Ecrã de carregamento inicial' },
  { id: 'cookie_bar',   cat: 'structure', icon: '🍪', label: 'Cookie Banner',     hint: 'Aviso de cookies (GDPR)' },
  // Navegação
  { id: 'nav_horizontal', cat: 'nav',    icon: '➡️',  label: 'Nav Horizontal',   hint: 'Menu horizontal clássico' },
  { id: 'nav_hamburger',  cat: 'nav',    icon: '☰',   label: 'Nav Hamburger',    hint: 'Menu mobile com botão hamburger' },
  { id: 'nav_mega',       cat: 'nav',    icon: '📋', label: 'Mega Menu',          hint: 'Menu com painéis de sub-navegação' },
  { id: 'nav_sidebar',    cat: 'nav',    icon: '◧',  label: 'Nav Sidebar',       hint: 'Navegação em painel lateral',
    variants: [{ id: 'push', label: 'Push' }, { id: 'overlay', label: 'Overlay' }] },
  { id: 'nav_fullscreen', cat: 'nav',    icon: '⛶',  label: 'Nav Fullscreen',   hint: 'Menu sobreposição ecrã completo' },
  { id: 'pagination',     cat: 'nav',    icon: '📄', label: 'Paginação',          hint: 'Navegação entre páginas' },
  { id: 'tabs',           cat: 'nav',    icon: '🗂️',  label: 'Tabs',             hint: 'Abas de navegação horizontal',
    variants: [{ id: 'line', label: 'Linha' }, { id: 'pill', label: 'Pill' }, { id: 'boxed', label: 'Boxed' }] },
  // UI
  { id: 'button',       cat: 'ui',       icon: '🔘', label: 'Botão',             hint: 'Componente de botão reutilizável',
    variants: [{ id: 'primary', label: 'Primary' }, { id: 'secondary', label: 'Secondary' }, { id: 'ghost', label: 'Ghost' }, { id: 'outline', label: 'Outline' }] },
  { id: 'card',         cat: 'ui',       icon: '🃏', label: 'Card',              hint: 'Cartão de conteúdo genérico',
    variants: [{ id: 'flat', label: 'Flat' }, { id: 'shadow', label: 'Shadow' }, { id: 'bordered', label: 'Bordered' }] },
  { id: 'badge',        cat: 'ui',       icon: '🏷️',  label: 'Badge',           hint: 'Etiqueta/badge de estado' },
  { id: 'alert',        cat: 'ui',       icon: '⚠️',  label: 'Alert',           hint: 'Mensagem de alerta/info',
    variants: [{ id: 'info', label: 'Info' }, { id: 'success', label: 'Success' }, { id: 'warning', label: 'Warning' }, { id: 'error', label: 'Error' }] },
  { id: 'modal',        cat: 'ui',       icon: '🪟', label: 'Modal / Dialog',    hint: 'Janela popup modal' },
  { id: 'drawer',       cat: 'ui',       icon: '◨',  label: 'Drawer',           hint: 'Painel deslizante lateral' },
  { id: 'tooltip',      cat: 'ui',       icon: '💬', label: 'Tooltip',           hint: 'Dica de contexto ao hover' },
  { id: 'accordion',    cat: 'ui',       icon: '🪗', label: 'Acordeão',          hint: 'Lista colapsável de itens' },
  { id: 'progress',     cat: 'ui',       icon: '📶', label: 'Barra de Progresso',hint: 'Barra de progresso animada' },
  { id: 'dark_toggle',  cat: 'ui',       icon: '🌓', label: 'Toggle Dark Mode',  hint: 'Botão de alternância claro/escuro' },
  { id: 'scroll_progress', cat: 'ui',   icon: '📏', label: 'Scroll Progress',   hint: 'Barra de progresso de scroll' },
  // Formulários
  { id: 'form_input',   cat: 'forms',    icon: '✏️',  label: 'Input',            hint: 'Campo de texto' },
  { id: 'form_select',  cat: 'forms',    icon: '📋', label: 'Select',            hint: 'Lista de selecção' },
  { id: 'form_search',  cat: 'forms',    icon: '🔍', label: 'Pesquisa',          hint: 'Barra de pesquisa com autocomplete' },
  { id: 'form_contact', cat: 'forms',    icon: '📩', label: 'Form de Contacto',  hint: 'Formulário de contacto completo' },
  { id: 'form_newsletter', cat: 'forms', icon: '📧', label: 'Form Newsletter',   hint: 'Subscrição de newsletter' },
  // Media
  { id: 'carousel',     cat: 'media',    icon: '🎠', label: 'Carrossel',         hint: 'Slider de imagens/conteúdo',
    variants: [{ id: 'fade', label: 'Fade' }, { id: 'slide', label: 'Slide' }, { id: 'loop', label: 'Loop' }] },
  { id: 'lightbox',     cat: 'media',    icon: '🔍', label: 'Lightbox',          hint: 'Visualizador de imagens ampliadas' },
  { id: 'video_player', cat: 'media',    icon: '▶️',  label: 'Video Player',     hint: 'Player de vídeo embed ou nativo' },
  { id: 'audio_player', cat: 'media',    icon: '🎵', label: 'Audio Player',      hint: 'Player de áudio' },
  { id: 'map_embed',    cat: 'media',    icon: '📍', label: 'Mapa Embed',        hint: 'Google Maps incorporado' },
  // Feedback
  { id: 'toast',        cat: 'feedback', icon: '🔔', label: 'Toast Notification',hint: 'Notificação temporária' },
  { id: 'rating',       cat: 'feedback', icon: '⭐', label: 'Rating',            hint: 'Sistema de avaliação por estrelas' },
  { id: 'review_card',  cat: 'feedback', icon: '💬', label: 'Review Card',       hint: 'Card de avaliação de utilizador' },
  { id: 'chat_widget',  cat: 'feedback', icon: '💬', label: 'Chat Widget',       hint: 'Widget de chat ao vivo' },
  // Dados
  { id: 'data_table',   cat: 'data',     icon: '📊', label: 'Tabela de Dados',   hint: 'Tabela com ordenação e filtros' },
  { id: 'chart_bar',    cat: 'data',     icon: '📊', label: 'Gráfico de Barras', hint: 'Gráfico de barras interactivo' },
  { id: 'chart_pie',    cat: 'data',     icon: '🥧', label: 'Gráfico Circular',  hint: 'Gráfico em fatia/donut' },
  { id: 'counter',      cat: 'data',     icon: '🔢', label: 'Contador Animado',  hint: 'Número que conta até ao valor' },
];

const filteredCompBlocks = computed(() =>
  activeCompCat.value === 'all'
    ? compLibrary
    : compLibrary.filter(b => b.cat === activeCompCat.value)
);

function getCompBlock(id) {
  return compLibrary.find(b => b.id === id);
}

// Estado dos componentes activos (ordem + blade + variante)
// Carregamos a partir de form.components que é { uid: { type, variant, blade } }
const compOrder = ref(
  Object.entries(props.theme?.components ?? {})
    .filter(([, v]) => v && typeof v === 'object' && v.type)
    .map(([uid, v]) => ({ uid, type: v.type, variant: v.variant ?? '', blade: v.blade ?? '', open: false }))
);



let uidCounter = compOrder.value.length;

function addCompBlock(block) {
  uidCounter++;
  const uid = block.id + '_' + uidCounter;
  compOrder.value.push({
    uid,
    type: block.id,
    variant: block.variants?.[0]?.id ?? '',
    blade: '',
    open: false,
  });
}

function duplicateCompBlock(idx) {
  uidCounter++;
  const src = compOrder.value[idx];
  const uid = src.type + '_' + uidCounter;
  compOrder.value.splice(idx + 1, 0, { ...src, uid, open: false });
}

function removeCompBlock(idx) {
  compOrder.value.splice(idx, 1);
}

// ── Drag & Drop ──────────────────────────────────────────────────
const dragCompIdx = ref(null);
const dragOverIdx = ref(null);

function onCompDragStart(e, idx) {
  dragCompIdx.value = idx;
  e.dataTransfer.effectAllowed = 'move';
}
function onCompDragOver(e, idx) {
  e.preventDefault();
  dragOverIdx.value = idx;
  if (dragCompIdx.value !== null && dragCompIdx.value !== idx) {
    const items = compOrder.value;
    const moved = items.splice(dragCompIdx.value, 1)[0];
    items.splice(idx, 0, moved);
    dragCompIdx.value = idx;
  }
}
function onCompDrop(e) {
  e.preventDefault();
  dragCompIdx.value = null;
  dragOverIdx.value = null;
}
function onCompDragEnd() {
  dragCompIdx.value = null;
  dragOverIdx.value = null;
}

// ── Variants ──────────────────────────────────────────────────────

// Todos os tokens de cor por variante (light + dark)
const fullVariantTokens = [
  { var: '--color-primary',    hint: 'Cor principal — botões, links, destaques',              light: '#6366f1', dark: '#818cf8' },
  { var: '--color-secondary',  hint: 'Cor secundária — badges, tags, botões ghost',            light: '#8b5cf6', dark: '#a78bfa' },
  { var: '--color-accent',     hint: 'Cor de destaque — hover states, ícones especiais',       light: '#f59e0b', dark: '#fbbf24' },
  { var: '--color-background', hint: 'Fundo geral da página',                                  light: '#ffffff', dark: '#0f172a' },
  { var: '--color-foreground', hint: 'Cor do texto principal',                                 light: '#0f172a', dark: '#f1f5f9' },
  { var: '--color-card',       hint: 'Fundo de cards, painéis e modais',                      light: '#ffffff', dark: '#1e293b' },
  { var: '--color-muted',      hint: 'Fundo de inputs, áreas secundárias',                    light: '#f1f5f9', dark: '#1e293b' },
  { var: '--color-border',     hint: 'Cor das linhas divisórias e bordas',                    light: '#e2e8f0', dark: '#334155' },
  { var: '--color-success',    hint: 'Mensagens de sucesso, estados positivos',                light: '#10b981', dark: '#34d399' },
  { var: '--color-warning',    hint: 'Alertas e avisos',                                       light: '#f59e0b', dark: '#fbbf24' },
  { var: '--color-destructive',hint: 'Erros, acções destrutivas',                              light: '#ef4444', dark: '#f87171' },
];

// Paletas pré-definidas (18 paletas)
const colorPresets = [
  {
    name: 'indigo-night', label: 'Indigo Night', description: 'Elegante roxo índigo com fundos escuros',
    colors: {
      light: { '--color-primary':'#6366f1','--color-secondary':'#8b5cf6','--color-accent':'#f59e0b','--color-background':'#ffffff','--color-foreground':'#0f172a','--color-card':'#f8fafc','--color-muted':'#f1f5f9','--color-border':'#e2e8f0','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#818cf8','--color-secondary':'#a78bfa','--color-accent':'#fbbf24','--color-background':'#0f172a','--color-foreground':'#f1f5f9','--color-card':'#1e293b','--color-muted':'#1e293b','--color-border':'#334155','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'ocean-blue', label: 'Ocean Blue', description: 'Azul oceano fresco e profissional',
    colors: {
      light: { '--color-primary':'#0ea5e9','--color-secondary':'#38bdf8','--color-accent':'#f97316','--color-background':'#f0f9ff','--color-foreground':'#0c4a6e','--color-card':'#ffffff','--color-muted':'#e0f2fe','--color-border':'#bae6fd','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#38bdf8','--color-secondary':'#7dd3fc','--color-accent':'#fb923c','--color-background':'#082f49','--color-foreground':'#e0f2fe','--color-card':'#0c4a6e','--color-muted':'#0c4a6e','--color-border':'#075985','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'emerald-nature', label: 'Emerald Nature', description: 'Verde esmeralda natural e orgânico',
    colors: {
      light: { '--color-primary':'#10b981','--color-secondary':'#34d399','--color-accent':'#f59e0b','--color-background':'#f0fdf4','--color-foreground':'#064e3b','--color-card':'#ffffff','--color-muted':'#dcfce7','--color-border':'#bbf7d0','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#34d399','--color-secondary':'#6ee7b7','--color-accent':'#fbbf24','--color-background':'#022c22','--color-foreground':'#d1fae5','--color-card':'#064e3b','--color-muted':'#064e3b','--color-border':'#065f46','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'sunset-orange', label: 'Sunset Orange', description: 'Laranja vibrante com energia mediterrânica',
    colors: {
      light: { '--color-primary':'#f97316','--color-secondary':'#fb923c','--color-accent':'#8b5cf6','--color-background':'#fff7ed','--color-foreground':'#431407','--color-card':'#ffffff','--color-muted':'#ffedd5','--color-border':'#fed7aa','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#fb923c','--color-secondary':'#fdba74','--color-accent':'#a78bfa','--color-background':'#1c0a00','--color-foreground':'#ffedd5','--color-card':'#431407','--color-muted':'#431407','--color-border':'#7c2d12','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'rose-luxury', label: 'Rose Luxury', description: 'Rosa sofisticado para marcas premium',
    colors: {
      light: { '--color-primary':'#f43f5e','--color-secondary':'#fb7185','--color-accent':'#fbbf24','--color-background':'#fff1f2','--color-foreground':'#4c0519','--color-card':'#ffffff','--color-muted':'#ffe4e6','--color-border':'#fecdd3','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#fb7185','--color-secondary':'#fda4af','--color-accent':'#fcd34d','--color-background':'#1a000a','--color-foreground':'#ffe4e6','--color-card':'#4c0519','--color-muted':'#4c0519','--color-border':'#881337','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'slate-minimal', label: 'Slate Minimal', description: 'Cinzento neutro ultra-minimalista',
    colors: {
      light: { '--color-primary':'#475569','--color-secondary':'#64748b','--color-accent':'#0ea5e9','--color-background':'#f8fafc','--color-foreground':'#0f172a','--color-card':'#ffffff','--color-muted':'#f1f5f9','--color-border':'#e2e8f0','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#94a3b8','--color-secondary':'#cbd5e1','--color-accent':'#38bdf8','--color-background':'#020617','--color-foreground':'#f1f5f9','--color-card':'#0f172a','--color-muted':'#0f172a','--color-border':'#1e293b','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'purple-galaxy', label: 'Purple Galaxy', description: 'Roxo galáxia para tech & criatividade',
    colors: {
      light: { '--color-primary':'#9333ea','--color-secondary':'#a855f7','--color-accent':'#06b6d4','--color-background':'#faf5ff','--color-foreground':'#3b0764','--color-card':'#ffffff','--color-muted':'#f3e8ff','--color-border':'#e9d5ff','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#c084fc','--color-secondary':'#d8b4fe','--color-accent':'#22d3ee','--color-background':'#0d0019','--color-foreground':'#f3e8ff','--color-card':'#1e0038','--color-muted':'#1e0038','--color-border':'#3b0764','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'teal-corporate', label: 'Teal Corporate', description: 'Verde-azulado profissional para empresas',
    colors: {
      light: { '--color-primary':'#0d9488','--color-secondary':'#14b8a6','--color-accent':'#f97316','--color-background':'#f0fdfa','--color-foreground':'#042f2e','--color-card':'#ffffff','--color-muted':'#ccfbf1','--color-border':'#99f6e4','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#2dd4bf','--color-secondary':'#5eead4','--color-accent':'#fb923c','--color-background':'#011a18','--color-foreground':'#ccfbf1','--color-card':'#042f2e','--color-muted':'#042f2e','--color-border':'#134e4a','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'gold-premium', label: 'Gold Premium', description: 'Dourado luxuoso para marcas de topo',
    colors: {
      light: { '--color-primary':'#d97706','--color-secondary':'#f59e0b','--color-accent':'#0f172a','--color-background':'#fffbeb','--color-foreground':'#451a03','--color-card':'#ffffff','--color-muted':'#fef3c7','--color-border':'#fde68a','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#f59e0b','--color-secondary':'#fbbf24','--color-accent':'#f1f5f9','--color-background':'#0d0800','--color-foreground':'#fef3c7','--color-card':'#1c1000','--color-muted':'#1c1000','--color-border':'#451a03','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'crimson-bold', label: 'Crimson Bold', description: 'Vermelho intenso para marcas assertivas',
    colors: {
      light: { '--color-primary':'#dc2626','--color-secondary':'#ef4444','--color-accent':'#1d4ed8','--color-background':'#fff5f5','--color-foreground':'#450a0a','--color-card':'#ffffff','--color-muted':'#fee2e2','--color-border':'#fecaca','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#f87171','--color-secondary':'#fca5a5','--color-accent':'#60a5fa','--color-background':'#0f0000','--color-foreground':'#fee2e2','--color-card':'#450a0a','--color-muted':'#450a0a','--color-border':'#7f1d1d','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'midnight-blue', label: 'Midnight Blue', description: 'Azul meia-noite elegante e sério',
    colors: {
      light: { '--color-primary':'#1d4ed8','--color-secondary':'#3b82f6','--color-accent':'#f59e0b','--color-background':'#eff6ff','--color-foreground':'#1e3a5f','--color-card':'#ffffff','--color-muted':'#dbeafe','--color-border':'#bfdbfe','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#60a5fa','--color-secondary':'#93c5fd','--color-accent':'#fbbf24','--color-background':'#030712','--color-foreground':'#dbeafe','--color-card':'#0f172a','--color-muted':'#0f172a','--color-border':'#1e3a5f','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'forest-dark', label: 'Forest Dark', description: 'Verde floresta para temas naturais e ecológicos',
    colors: {
      light: { '--color-primary':'#15803d','--color-secondary':'#16a34a','--color-accent':'#ca8a04','--color-background':'#f7fee7','--color-foreground':'#14532d','--color-card':'#ffffff','--color-muted':'#ecfccb','--color-border':'#d9f99d','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#4ade80','--color-secondary':'#86efac','--color-accent':'#fde047','--color-background':'#020c02','--color-foreground':'#dcfce7','--color-card':'#052e16','--color-muted':'#052e16','--color-border':'#14532d','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'neon-cyber', label: 'Neon Cyber', description: 'Neón cyberpunk para gaming e tech',
    colors: {
      light: { '--color-primary':'#06b6d4','--color-secondary':'#22d3ee','--color-accent':'#a855f7','--color-background':'#ecfeff','--color-foreground':'#083344','--color-card':'#ffffff','--color-muted':'#cffafe','--color-border':'#a5f3fc','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#22d3ee','--color-secondary':'#67e8f9','--color-accent':'#c084fc','--color-background':'#000d10','--color-foreground':'#cffafe','--color-card':'#001a1f','--color-muted':'#001a1f','--color-border':'#083344','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'warm-earth', label: 'Warm Earth', description: 'Tons terra quentes para lifestyle e moda',
    colors: {
      light: { '--color-primary':'#92400e','--color-secondary':'#b45309','--color-accent':'#065f46','--color-background':'#fffbf5','--color-foreground':'#1c0a00','--color-card':'#ffffff','--color-muted':'#fef3c7','--color-border':'#fde68a','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#fbbf24','--color-secondary':'#fcd34d','--color-accent':'#34d399','--color-background':'#0c0500','--color-foreground':'#fef3c7','--color-card':'#1c0a00','--color-muted':'#1c0a00','--color-border':'#451a03','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'pink-candy', label: 'Pink Candy', description: 'Rosa doce e divertido para marcas jovens',
    colors: {
      light: { '--color-primary':'#ec4899','--color-secondary':'#f472b6','--color-accent':'#818cf8','--color-background':'#fdf2f8','--color-foreground':'#500724','--color-card':'#ffffff','--color-muted':'#fce7f3','--color-border':'#fbcfe8','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#f472b6','--color-secondary':'#f9a8d4','--color-accent':'#a5b4fc','--color-background':'#12000a','--color-foreground':'#fce7f3','--color-card':'#1f0011','--color-muted':'#1f0011','--color-border':'#500724','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'mono-black', label: 'Mono Black', description: 'Preto e branco puro — elegância absoluta',
    colors: {
      light: { '--color-primary':'#000000','--color-secondary':'#171717','--color-accent':'#525252','--color-background':'#ffffff','--color-foreground':'#000000','--color-card':'#fafafa','--color-muted':'#f5f5f5','--color-border':'#e5e5e5','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#ffffff','--color-secondary':'#e5e5e5','--color-accent':'#a3a3a3','--color-background':'#000000','--color-foreground':'#ffffff','--color-card':'#0a0a0a','--color-muted':'#171717','--color-border':'#262626','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'lavender-soft', label: 'Lavender Soft', description: 'Lavanda suave para wellness e saúde',
    colors: {
      light: { '--color-primary':'#7c3aed','--color-secondary':'#8b5cf6','--color-accent':'#db2777','--color-background':'#faf5ff','--color-foreground':'#2e1065','--color-card':'#ffffff','--color-muted':'#ede9fe','--color-border':'#ddd6fe','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#a78bfa','--color-secondary':'#c4b5fd','--color-accent':'#f472b6','--color-background':'#0c0014','--color-foreground':'#ede9fe','--color-card':'#1a0033','--color-muted':'#1a0033','--color-border':'#2e1065','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
  {
    name: 'autumn-rust', label: 'Autumn Rust', description: 'Ferrugem outonal para marcas artesanais',
    colors: {
      light: { '--color-primary':'#c2410c','--color-secondary':'#ea580c','--color-accent':'#a16207','--color-background':'#fff8f5','--color-foreground':'#431407','--color-card':'#ffffff','--color-muted':'#ffedd5','--color-border':'#fed7aa','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
      dark:  { '--color-primary':'#fb923c','--color-secondary':'#fdba74','--color-accent':'#fde047','--color-background':'#0d0400','--color-foreground':'#ffedd5','--color-card':'#1c0a00','--color-muted':'#1c0a00','--color-border':'#7c2d12','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
    },
  },
];

function addVariant() {
  form.variants.push({ name: '', label: '', _open: true, _mode: 'light', colors: { light: {}, dark: {} } });
}

function addPresetVariant(preset) {
  form.variants.push({
    name: preset.name,
    label: preset.label,
    _open: false,
    _mode: 'light',
    colors: {
      light: { ...preset.colors.light },
      dark:  { ...preset.colors.dark  },
    },
  });
}

function setVariantColor(idx, mode, varName, val) {
  if (!form.variants[idx].colors) form.variants[idx].colors = { light: {}, dark: {} };
  if (!form.variants[idx].colors[mode]) form.variants[idx].colors[mode] = {};
  form.variants[idx].colors[mode][varName] = val;
}

// ── Theme Prompt Modal ────────────────────────────────────────────
const showPromptModal = ref(false);

const promptSummary = computed(() => {
  const f = form;
  return [
    { icon: '📋', label: 'Metadados',    value: `${f.label} v${f.version}`,                                                ok: !!f.label },
    { icon: '🎨', label: 'Cores',        value: `Light + Dark tokens`,                                                      ok: !!(f.colors?.light && Object.keys(f.colors.light).length) },
    { icon: '🌈', label: 'Variantes',    value: `${f.variants?.length ?? 0} paletas`,                                       ok: (f.variants?.length ?? 0) > 0 },
    { icon: '📐', label: 'Layout',       value: `Header: ${f.layout_config?.header_type ?? '—'}, Nav: ${f.layout_config?.nav_type ?? '—'}`, ok: !!f.layout_config?.header_type },
    { icon: '⚙️',  label: 'Capacidades', value: `${Object.values(f.capabilities ?? {}).filter(Boolean).length} ativas`,     ok: Object.values(f.capabilities ?? {}).some(Boolean) },
    { icon: '🧩', label: 'Secções',      value: `${Object.keys(f.sections ?? {}).length} blocos`,                           ok: Object.keys(f.sections ?? {}).length > 0 },
    { icon: '🔧', label: 'Componentes',  value: `${Object.keys(f.components ?? {}).length} componentes`,                    ok: Object.keys(f.components ?? {}).length > 0 },
    { icon: '🖼️',  label: 'Assets',       value: `${Object.values(f.assets ?? {}).filter(Boolean).length} ficheiros`,        ok: Object.values(f.assets ?? {}).some(Boolean) },
    { icon: '🖋️',  label: 'Tipografia',   value: `${f.fonts?.heading ?? 'padrão'} / ${f.fonts?.body ?? 'padrão'}`,          ok: !!f.fonts?.heading },
    { icon: '💻', label: 'CSS Custom',   value: f.custom_css?.trim() ? `${f.custom_css.split('\n').length} linhas` : 'Sem CSS', ok: !!f.custom_css?.trim() },
    { icon: '⚡', label: 'JS Custom',    value: f.custom_js?.trim()  ? `${f.custom_js.split('\n').length} linhas`  : 'Sem JS',  ok: !!f.custom_js?.trim()  },
  ];
});

const installSteps = [
  'Descarrega o ficheiro .afprompt ou copia o conteúdo para o clipboard.',
  'Abre o AnimusFlow Admin → Extensões → Temas → Importar Prompt.',
  'Cola o bloco completo (incluindo as marcações [AF:THEME:BEGIN] e [AF:THEME:END]).',
  'Clica em "Instalar Tema" — o AnimusFlow valida o checksum e aplica todas as configurações.',
  'Activa o tema em AnimusFlow Admin → Aparência → Tema Activo.',
];

const promptPreview = computed(() => {
  const divider = '━'.repeat(50);
  const caps = Object.entries(form.capabilities ?? {}).filter(([,v]) => v).map(([k]) => k.replace(/_/g,' ')).join(', ') || '—';
  const secs = Object.keys(form.sections   ?? {}).join(', ') || '—';
  const comps = Object.keys(form.components ?? {}).join(', ') || '—';
  return `${divider}
 ANIMUSFLOW THEME PROMPT  v1.0
 Tema: ${form.label}  (${form.name})
 Versão: ${form.version}
${divider}

[AF:THEME:BEGIN]
{
  "af_prompt_version": "1.0",
  "meta": { "name": "${form.name}", "label": "${form.label}", ... },
  "design": { "colors": {...}, "variants": [...] },
  "layout": { "header_type": "${form.layout_config?.header_type}", ... },
  "capabilities": { ${caps} },
  "structure": {
    "sections":   { ${secs} },
    "components": { ${comps} }
  },
  "assets": {...},
  "code": { "css": "...", "js": "..." }
}
[AF:THEME:END]
${divider}
CHECKSUM: sha256:<gerado no servidor>
${divider}`;
});

async function copyPromptToClipboard() {
  try {
    const res = await fetch(`/themes/${props.theme.uuid}/export-prompt`);
    const text = await res.text();
    await navigator.clipboard.writeText(text);
    feedback.success = '📋 Theme Prompt copiado para o clipboard!';
    setTimeout(() => { feedback.success = ''; }, 3000);
    showPromptModal.value = false;
  } catch {
    feedback.error = 'Não foi possível copiar. Usa o botão Descarregar.';
  }
}

// ── Demo Data ─────────────────────────────────────────────────────
const selectedDemoType = ref(null);
const demoSelected     = ref(['colors','assets','layout','sections','components','capabilities','variants','code']);

const demoCategories = [
  { id: 'colors',       icon: '🎨', label: 'Cores',        hint: 'Paleta primária e tokens CSS' },
  { id: 'assets',       icon: '🖼️',  label: 'Imagens',      hint: 'Logo, hero, fundos, OG image' },
  { id: 'layout',       icon: '📐', label: 'Layout',       hint: 'Header, nav, footer, estrutura' },
  { id: 'sections',     icon: '🧩', label: 'Secções',      hint: 'Blocos de conteúdo da página' },
  { id: 'components',   icon: '🔧', label: 'Componentes',  hint: 'Navbar, footer, modais, etc.' },
  { id: 'capabilities', icon: '⚙️',  label: 'Capacidades',  hint: 'Features especiais do tema' },
  { id: 'variants',     icon: '🌈', label: 'Variantes',    hint: 'Dark mode e paletas alternativas' },
  { id: 'code',         icon: '💻', label: 'CSS / JS Demo',hint: 'Estilos e scripts de exemplo' },
];

function toggleAllDemoCategories() {
  if (demoSelected.value.length === demoCategories.length) {
    demoSelected.value = [];
  } else {
    demoSelected.value = demoCategories.map(c => c.id);
  }
}

const demoSiteTypes = [
  {
    id: 'saas', emoji: '🚀', label: 'SaaS / Tech',
    description: 'Software, apps, plataformas digitais',
    palette: ['#6366f1','#8b5cf6','#06b6d4','#0f172a','#f8fafc'],
    data: {
      colors: {
        light: { '--color-primary':'#6366f1','--color-secondary':'#8b5cf6','--color-accent':'#06b6d4','--color-background':'#f8fafc','--color-foreground':'#0f172a','--color-card':'#ffffff','--color-muted':'#f1f5f9','--color-border':'#e2e8f0','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
        dark:  { '--color-primary':'#818cf8','--color-secondary':'#a78bfa','--color-accent':'#22d3ee','--color-background':'#0a0a0f','--color-foreground':'#f1f5f9','--color-card':'#13131a','--color-muted':'#1e1e2e','--color-border':'#2d2d3f','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
      },
      assets: {
        logo:       'https://placehold.co/200x60/6366f1/ffffff?text=SaaSApp',
        logo_dark:  'https://placehold.co/200x60/818cf8/0a0a0f?text=SaaSApp',
        favicon:    'https://placehold.co/64x64/6366f1/ffffff?text=S',
        hero_image: 'https://picsum.photos/seed/saas-hero/1280/720',
        bg_image:   'https://picsum.photos/seed/saas-bg/1920/1080',
        og_image:   'https://picsum.photos/seed/saas-og/1200/630',
      },
      layout_config: { header_type:'glass', nav_type:'horizontal', nav_position:'right', footer_type:'columns', layout_type:'full-width', max_width:'1200', spacing:'normal', header_sticky:true, show_dark_toggle:true, back_to_top:true, header_cta_text:'Começar grátis', header_cta_url:'#pricing' },
      capabilities:  { animations:true, scroll_progress:true, back_to_top:true, search:true, preloader:true, parallax:false, video_bg:false, lightbox:false, mega_menu:false, cookie_banner:true },
      sections:      { hero:{variant:'centered-cta'}, features:{variant:'grid-3'}, pricing:{variant:'cards'}, testimonials:{variant:'carousel'}, faq:{variant:'accordion'}, cta:{variant:'gradient'}, footer:{variant:'columns'} },
      components:    { navbar:{variant:'transparent'}, 'cookie-banner':{variant:'bottom-bar'}, 'scroll-progress':{variant:'top-bar'}, 'back-to-top':{variant:'circle'} },
      variants: [
        { name:'dark', label:'Dark Mode', _open:false, _mode:'dark', colors:{ light:{}, dark:{ '--color-primary':'#818cf8','--color-secondary':'#a78bfa','--color-accent':'#22d3ee','--color-background':'#0a0a0f','--color-foreground':'#f1f5f9','--color-card':'#13131a','--color-muted':'#1e1e2e','--color-border':'#2d2d3f','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' } } },
        { name:'ocean', label:'Ocean', _open:false, _mode:'light', colors:{ light:{ '--color-primary':'#0ea5e9','--color-secondary':'#38bdf8','--color-accent':'#6366f1','--color-background':'#f0f9ff','--color-foreground':'#0c4a6e','--color-card':'#ffffff','--color-muted':'#e0f2fe','--color-border':'#bae6fd','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' }, dark:{} } },
      ],
      custom_css: `:root { --radius: 0.75rem; }\n.hero-gradient { background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%); }\n.glass { backdrop-filter: blur(12px); background: rgba(255,255,255,0.08); }`,
      custom_js:  `// Demo: smooth scroll\ndocument.querySelectorAll('a[href^="#"]').forEach(a => {\n  a.addEventListener('click', e => {\n    e.preventDefault();\n    document.querySelector(a.getAttribute('href'))?.scrollIntoView({ behavior: 'smooth' });\n  });\n});`,
    },
  },
  {
    id: 'portfolio', emoji: '🎨', label: 'Portfolio',
    description: 'Criativos, designers, fotógrafos',
    palette: ['#111111','#f5f5f0','#d4a853','#6b7280','#ffffff'],
    data: {
      colors: {
        light: { '--color-primary':'#111111','--color-secondary':'#374151','--color-accent':'#d4a853','--color-background':'#f5f5f0','--color-foreground':'#111111','--color-card':'#ffffff','--color-muted':'#f3f4f6','--color-border':'#e5e7eb','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
        dark:  { '--color-primary':'#f5f5f0','--color-secondary':'#d1d5db','--color-accent':'#d4a853','--color-background':'#0a0a09','--color-foreground':'#f5f5f0','--color-card':'#141413','--color-muted':'#1c1c1a','--color-border':'#2a2a28','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
      },
      assets: {
        logo:       'https://placehold.co/160x50/111111/f5f5f0?text=Studio',
        logo_dark:  'https://placehold.co/160x50/f5f5f0/111111?text=Studio',
        favicon:    'https://placehold.co/64x64/111111/d4a853?text=P',
        hero_image: 'https://picsum.photos/seed/portfolio-hero/1280/800',
        about_bg:   'https://picsum.photos/seed/portfolio-about/800/600',
        og_image:   'https://picsum.photos/seed/portfolio-og/1200/630',
      },
      layout_config: { header_type:'transparent', nav_type:'horizontal', nav_position:'center', footer_type:'minimal', layout_type:'full-width', max_width:'1100', spacing:'spacious', header_sticky:true, show_dark_toggle:true, back_to_top:false, header_cta_text:'Contactar', header_cta_url:'#contact' },
      capabilities:  { animations:true, lightbox:true, back_to_top:false, parallax:true, scroll_progress:false, search:false, preloader:true, video_bg:false, mega_menu:false, cookie_banner:false },
      sections:      { hero:{variant:'fullscreen-image'}, about:{variant:'side-by-side'}, portfolio:{variant:'masonry'}, testimonials:{variant:'minimal'}, contact:{variant:'simple'} },
      components:    { navbar:{variant:'minimal'}, lightbox:{variant:'standard'} },
      variants: [
        { name:'dark', label:'Dark Mode', _open:false, _mode:'dark', colors:{ light:{}, dark:{ '--color-primary':'#f5f5f0','--color-secondary':'#d1d5db','--color-accent':'#d4a853','--color-background':'#0a0a09','--color-foreground':'#f5f5f0','--color-card':'#141413','--color-muted':'#1c1c1a','--color-border':'#2a2a28','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' } } },
      ],
      custom_css: `body { font-family: 'Georgia', serif; }\n.portfolio-grid { column-count: 3; column-gap: 1.5rem; }\n.portfolio-item { break-inside: avoid; margin-bottom: 1.5rem; }`,
      custom_js:  `// Demo: lazy load images\ndocument.querySelectorAll('img[data-src]').forEach(img => {\n  new IntersectionObserver(([e]) => { if(e.isIntersecting) { img.src = img.dataset.src; } }).observe(img);\n});`,
    },
  },
  {
    id: 'ecommerce', emoji: '🛒', label: 'E-commerce',
    description: 'Lojas online, produtos, marketplace',
    palette: ['#16a34a','#15803d','#f59e0b','#ffffff','#f9fafb'],
    data: {
      colors: {
        light: { '--color-primary':'#16a34a','--color-secondary':'#15803d','--color-accent':'#f59e0b','--color-background':'#f9fafb','--color-foreground':'#111827','--color-card':'#ffffff','--color-muted':'#f3f4f6','--color-border':'#e5e7eb','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
        dark:  { '--color-primary':'#4ade80','--color-secondary':'#86efac','--color-accent':'#fbbf24','--color-background':'#030a05','--color-foreground':'#f0fdf4','--color-card':'#0a1a0d','--color-muted':'#14291a','--color-border':'#1a3a21','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
      },
      assets: {
        logo:       'https://placehold.co/180x55/16a34a/ffffff?text=MyShop',
        logo_dark:  'https://placehold.co/180x55/4ade80/030a05?text=MyShop',
        favicon:    'https://placehold.co/64x64/16a34a/ffffff?text=S',
        hero_image: 'https://picsum.photos/seed/shop-hero/1280/600',
        hero_poster:'https://picsum.photos/seed/shop-hero2/1280/600',
        og_image:   'https://picsum.photos/seed/shop-og/1200/630',
        features_bg:'https://picsum.photos/seed/shop-feat/1200/500',
      },
      layout_config: { header_type:'solid', nav_type:'horizontal', nav_position:'right', footer_type:'columns', layout_type:'full-width', max_width:'1280', spacing:'normal', header_sticky:true, show_dark_toggle:false, back_to_top:true, header_cta_text:'Ver promoções', header_cta_url:'#products' },
      capabilities:  { animations:true, lightbox:true, back_to_top:true, search:true, scroll_progress:false, preloader:false, parallax:false, video_bg:false, mega_menu:true, cookie_banner:true },
      sections:      { hero:{variant:'promo-banner'}, products:{variant:'grid-4'}, categories:{variant:'image-cards'}, features:{variant:'icon-list'}, testimonials:{variant:'stars'}, newsletter:{variant:'inline'}, footer:{variant:'columns'} },
      components:    { navbar:{variant:'with-cart'}, 'mega-menu':{variant:'categories'}, 'cookie-banner':{variant:'bottom-bar'}, 'back-to-top':{variant:'circle'} },
      variants: [
        { name:'sale', label:'Sale Mode', _open:false, _mode:'light', colors:{ light:{ '--color-primary':'#dc2626','--color-secondary':'#ef4444','--color-accent':'#f59e0b','--color-background':'#fff5f5','--color-foreground':'#450a0a','--color-card':'#ffffff','--color-muted':'#fee2e2','--color-border':'#fecaca','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' }, dark:{} } },
      ],
      custom_css: `.badge-sale { background: var(--color-destructive); color: white; border-radius: 9999px; padding: 2px 8px; font-size: 11px; font-weight: 700; }\n.product-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,.1); }`,
      custom_js:  `// Demo: add to cart feedback\ndocument.querySelectorAll('.btn-cart').forEach(btn => {\n  btn.addEventListener('click', () => { btn.textContent = '✓ Adicionado!'; setTimeout(() => btn.textContent = 'Adicionar ao carrinho', 2000); });\n});`,
    },
  },
  {
    id: 'blog', emoji: '✍️', label: 'Blog / Editorial',
    description: 'Artigos, notícias, revistas online',
    palette: ['#1d4ed8','#1e40af','#f8fafc','#374151','#ffffff'],
    data: {
      colors: {
        light: { '--color-primary':'#1d4ed8','--color-secondary':'#1e40af','--color-accent':'#f59e0b','--color-background':'#f8fafc','--color-foreground':'#1e293b','--color-card':'#ffffff','--color-muted':'#f1f5f9','--color-border':'#e2e8f0','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
        dark:  { '--color-primary':'#60a5fa','--color-secondary':'#93c5fd','--color-accent':'#fbbf24','--color-background':'#020617','--color-foreground':'#e2e8f0','--color-card':'#0f172a','--color-muted':'#1e293b','--color-border':'#334155','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
      },
      assets: {
        logo:       'https://placehold.co/160x45/1d4ed8/ffffff?text=TheBlog',
        logo_dark:  'https://placehold.co/160x45/60a5fa/020617?text=TheBlog',
        favicon:    'https://placehold.co/64x64/1d4ed8/ffffff?text=B',
        hero_image: 'https://picsum.photos/seed/blog-hero/1280/500',
        og_image:   'https://picsum.photos/seed/blog-og/1200/630',
      },
      layout_config: { header_type:'solid', nav_type:'horizontal', nav_position:'right', footer_type:'simple', layout_type:'boxed', max_width:'900', spacing:'spacious', header_sticky:true, show_dark_toggle:true, back_to_top:true, header_cta_text:'Newsletter', header_cta_url:'#newsletter' },
      capabilities:  { animations:true, scroll_progress:true, back_to_top:true, search:true, preloader:false, parallax:false, video_bg:false, lightbox:true, mega_menu:false, cookie_banner:true },
      sections:      { hero:{variant:'editorial'}, 'featured-posts':{variant:'grid-3'}, categories:{variant:'tags'}, newsletter:{variant:'centered'}, footer:{variant:'simple'} },
      components:    { navbar:{variant:'with-search'}, 'scroll-progress':{variant:'top-bar'}, 'back-to-top':{variant:'circle'} },
      variants: [
        { name:'dark', label:'Dark Mode', _open:false, _mode:'dark', colors:{ light:{}, dark:{ '--color-primary':'#60a5fa','--color-secondary':'#93c5fd','--color-accent':'#fbbf24','--color-background':'#020617','--color-foreground':'#e2e8f0','--color-card':'#0f172a','--color-muted':'#1e293b','--color-border':'#334155','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' } } },
      ],
      custom_css: `article { font-family: 'Georgia', serif; line-height: 1.85; }\narticle h2 { font-size: 1.5rem; margin-top: 2.5rem; }\n.reading-time { font-size: 0.75rem; color: var(--color-muted-foreground); }`,
      custom_js:  `// Demo: reading progress\nconst bar = document.querySelector('.reading-bar');\nif(bar) window.addEventListener('scroll', () => { const p = window.scrollY / (document.body.scrollHeight - window.innerHeight); bar.style.width = (p*100)+'%'; });`,
    },
  },
  {
    id: 'restaurant', emoji: '🍽️', label: 'Restaurante',
    description: 'Restaurantes, cafés, take-away',
    palette: ['#b45309','#92400e','#fef3c7','#111827','#ffffff'],
    data: {
      colors: {
        light: { '--color-primary':'#b45309','--color-secondary':'#92400e','--color-accent':'#d97706','--color-background':'#fffbeb','--color-foreground':'#1c1917','--color-card':'#ffffff','--color-muted':'#fef3c7','--color-border':'#fde68a','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
        dark:  { '--color-primary':'#fbbf24','--color-secondary':'#fcd34d','--color-accent':'#f59e0b','--color-background':'#0d0800','--color-foreground':'#fef3c7','--color-card':'#1c1000','--color-muted':'#2a1a00','--color-border':'#451a03','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
      },
      assets: {
        logo:       'https://placehold.co/180x60/b45309/ffffff?text=Restaurante',
        logo_dark:  'https://placehold.co/180x60/fbbf24/0d0800?text=Restaurante',
        favicon:    'https://placehold.co/64x64/b45309/fef3c7?text=R',
        hero_image: 'https://picsum.photos/seed/restaurant-hero/1280/700',
        about_bg:   'https://picsum.photos/seed/restaurant-about/900/600',
        bg_image:   'https://picsum.photos/seed/restaurant-bg/1920/1080',
        og_image:   'https://picsum.photos/seed/restaurant-og/1200/630',
      },
      layout_config: { header_type:'transparent', nav_type:'horizontal', nav_position:'center', footer_type:'dark', layout_type:'full-width', max_width:'1200', spacing:'spacious', header_sticky:true, show_dark_toggle:false, back_to_top:false, header_cta_text:'Reservar mesa', header_cta_url:'#reservas' },
      capabilities:  { animations:true, parallax:true, back_to_top:false, lightbox:true, video_bg:false, scroll_progress:false, search:false, preloader:false, mega_menu:false, cookie_banner:false },
      sections:      { hero:{variant:'fullscreen-parallax'}, menu:{variant:'grid-categories'}, about:{variant:'side-image'}, gallery:{variant:'masonry'}, testimonials:{variant:'minimal'}, reservations:{variant:'form'}, footer:{variant:'dark'} },
      components:    { navbar:{variant:'transparent'} },
      variants: [
        { name:'dark', label:'Dark Dining', _open:false, _mode:'dark', colors:{ light:{}, dark:{ '--color-primary':'#fbbf24','--color-secondary':'#fcd34d','--color-accent':'#f59e0b','--color-background':'#0d0800','--color-foreground':'#fef3c7','--color-card':'#1c1000','--color-muted':'#2a1a00','--color-border':'#451a03','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' } } },
      ],
      custom_css: `.menu-card { border-radius: 1rem; overflow: hidden; transition: transform .3s; }\n.menu-card:hover { transform: scale(1.02); }\n.hero-overlay { background: linear-gradient(to bottom, rgba(0,0,0,.3) 0%, rgba(0,0,0,.6) 100%); }`,
      custom_js:  `// Demo: reservation form validation\ndocument.querySelector('#reservation-form')?.addEventListener('submit', e => { e.preventDefault(); alert('Reserva recebida! Confirmaremos por email.'); });`,
    },
  },
  {
    id: 'agency', emoji: '🏢', label: 'Agência',
    description: 'Marketing, design, consultoria',
    palette: ['#7c3aed','#6d28d9','#ec4899','#fafafa','#09090b'],
    data: {
      colors: {
        light: { '--color-primary':'#7c3aed','--color-secondary':'#6d28d9','--color-accent':'#ec4899','--color-background':'#fafafa','--color-foreground':'#09090b','--color-card':'#ffffff','--color-muted':'#f4f4f5','--color-border':'#e4e4e7','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
        dark:  { '--color-primary':'#a78bfa','--color-secondary':'#c4b5fd','--color-accent':'#f472b6','--color-background':'#09090b','--color-foreground':'#fafafa','--color-card':'#111113','--color-muted':'#18181b','--color-border':'#27272a','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
      },
      assets: {
        logo:       'https://placehold.co/160x50/7c3aed/ffffff?text=Agency',
        logo_dark:  'https://placehold.co/160x50/a78bfa/09090b?text=Agency',
        favicon:    'https://placehold.co/64x64/7c3aed/ffffff?text=A',
        hero_image: 'https://picsum.photos/seed/agency-hero/1280/720',
        about_bg:   'https://picsum.photos/seed/agency-about/900/600',
        og_image:   'https://picsum.photos/seed/agency-og/1200/630',
      },
      layout_config: { header_type:'glass', nav_type:'horizontal', nav_position:'right', footer_type:'dark', layout_type:'full-width', max_width:'1280', spacing:'normal', header_sticky:true, show_dark_toggle:true, back_to_top:true, header_cta_text:'Falar connosco', header_cta_url:'#contact' },
      capabilities:  { animations:true, scroll_progress:true, back_to_top:true, parallax:true, video_bg:false, lightbox:true, search:false, preloader:true, mega_menu:false, cookie_banner:true },
      sections:      { hero:{variant:'split-content'}, services:{variant:'grid-3'}, 'case-studies':{variant:'large-cards'}, team:{variant:'grid'}, testimonials:{variant:'carousel'}, contact:{variant:'split'}, footer:{variant:'dark'} },
      components:    { navbar:{variant:'glass'}, 'scroll-progress':{variant:'top-bar'}, 'back-to-top':{variant:'circle'}, 'cookie-banner':{variant:'bottom-bar'} },
      variants: [
        { name:'dark', label:'Dark Mode', _open:false, _mode:'dark', colors:{ light:{}, dark:{ '--color-primary':'#a78bfa','--color-secondary':'#c4b5fd','--color-accent':'#f472b6','--color-background':'#09090b','--color-foreground':'#fafafa','--color-card':'#111113','--color-muted':'#18181b','--color-border':'#27272a','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' } } },
        { name:'pink', label:'Pink Energy', _open:false, _mode:'light', colors:{ light:{ '--color-primary':'#ec4899','--color-secondary':'#db2777','--color-accent':'#7c3aed','--color-background':'#fdf2f8','--color-foreground':'#500724','--color-card':'#ffffff','--color-muted':'#fce7f3','--color-border':'#fbcfe8','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' }, dark:{} } },
      ],
      custom_css: `.gradient-text { background: linear-gradient(135deg, var(--color-primary), var(--color-accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }\n.card-hover { transition: all .3s; }\n.card-hover:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(124,58,237,.15); }`,
      custom_js:  `// Demo: counter animation\ndocument.querySelectorAll('[data-counter]').forEach(el => {\n  const target = parseInt(el.dataset.counter);\n  let count = 0;\n  const step = target / 60;\n  const timer = setInterval(() => { count = Math.min(count + step, target); el.textContent = Math.round(count); if(count >= target) clearInterval(timer); }, 16);\n});`,
    },
  },
  {
    id: 'health', emoji: '💊', label: 'Saúde / Wellness',
    description: 'Clínicas, bem-estar, fitness',
    palette: ['#0d9488','#0f766e','#f0fdfa','#134e4a','#ffffff'],
    data: {
      colors: {
        light: { '--color-primary':'#0d9488','--color-secondary':'#0f766e','--color-accent':'#6ee7b7','--color-background':'#f0fdfa','--color-foreground':'#134e4a','--color-card':'#ffffff','--color-muted':'#ccfbf1','--color-border':'#99f6e4','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
        dark:  { '--color-primary':'#2dd4bf','--color-secondary':'#5eead4','--color-accent':'#a7f3d0','--color-background':'#010f0e','--color-foreground':'#ccfbf1','--color-card':'#042f2e','--color-muted':'#042f2e','--color-border':'#134e4a','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
      },
      assets: {
        logo:       'https://placehold.co/180x55/0d9488/ffffff?text=WellnessClinic',
        logo_dark:  'https://placehold.co/180x55/2dd4bf/010f0e?text=WellnessClinic',
        favicon:    'https://placehold.co/64x64/0d9488/f0fdfa?text=W',
        hero_image: 'https://picsum.photos/seed/health-hero/1280/700',
        about_bg:   'https://picsum.photos/seed/health-about/900/600',
        og_image:   'https://picsum.photos/seed/health-og/1200/630',
      },
      layout_config: { header_type:'solid', nav_type:'horizontal', nav_position:'right', footer_type:'simple', layout_type:'boxed', max_width:'1100', spacing:'spacious', header_sticky:true, show_dark_toggle:false, back_to_top:true, header_cta_text:'Marcar consulta', header_cta_url:'#appointments' },
      capabilities:  { animations:true, back_to_top:true, scroll_progress:false, parallax:false, lightbox:false, video_bg:false, search:false, preloader:false, mega_menu:false, cookie_banner:true },
      sections:      { hero:{variant:'centered-cta'}, services:{variant:'icon-cards'}, team:{variant:'grid'}, about:{variant:'side-by-side'}, testimonials:{variant:'stars'}, contact:{variant:'form'}, footer:{variant:'simple'} },
      components:    { navbar:{variant:'solid'}, 'cookie-banner':{variant:'bottom-bar'}, 'back-to-top':{variant:'circle'} },
      variants: [],
      custom_css: `.trust-badge { display: flex; align-items: center; gap: .5rem; padding: .5rem 1rem; background: var(--color-muted); border-radius: .5rem; font-size: .75rem; font-weight: 600; }\n.appointment-card { border-left: 4px solid var(--color-primary); }`,
      custom_js:  '',
    },
  },
  {
    id: 'realestate', emoji: '🏠', label: 'Imobiliário',
    description: 'Imóveis, arrendamento, promoção',
    palette: ['#1e40af','#1d4ed8','#fbbf24','#f8fafc','#1e293b'],
    data: {
      colors: {
        light: { '--color-primary':'#1e40af','--color-secondary':'#1d4ed8','--color-accent':'#fbbf24','--color-background':'#f8fafc','--color-foreground':'#1e293b','--color-card':'#ffffff','--color-muted':'#f1f5f9','--color-border':'#e2e8f0','--color-success':'#10b981','--color-warning':'#f59e0b','--color-destructive':'#ef4444' },
        dark:  { '--color-primary':'#60a5fa','--color-secondary':'#93c5fd','--color-accent':'#fbbf24','--color-background':'#060d1a','--color-foreground':'#e2e8f0','--color-card':'#0f1d35','--color-muted':'#1e293b','--color-border':'#1e3a5f','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' },
      },
      assets: {
        logo:       'https://placehold.co/180x55/1e40af/ffffff?text=ImobPrime',
        logo_dark:  'https://placehold.co/180x55/60a5fa/060d1a?text=ImobPrime',
        favicon:    'https://placehold.co/64x64/1e40af/ffffff?text=I',
        hero_image: 'https://picsum.photos/seed/realestate-hero/1280/700',
        features_bg:'https://picsum.photos/seed/realestate-feat/1200/500',
        og_image:   'https://picsum.photos/seed/realestate-og/1200/630',
      },
      layout_config: { header_type:'solid', nav_type:'horizontal', nav_position:'right', footer_type:'columns', layout_type:'full-width', max_width:'1280', spacing:'normal', header_sticky:true, show_dark_toggle:true, back_to_top:true, header_cta_text:'Ver imóveis', header_cta_url:'#listings' },
      capabilities:  { animations:true, search:true, back_to_top:true, lightbox:true, scroll_progress:false, parallax:false, video_bg:false, preloader:false, mega_menu:false, cookie_banner:true },
      sections:      { hero:{variant:'search-hero'}, listings:{variant:'grid-3'}, features:{variant:'icon-list'}, about:{variant:'stats'}, testimonials:{variant:'carousel'}, cta:{variant:'contact'}, footer:{variant:'columns'} },
      components:    { navbar:{variant:'solid'}, 'back-to-top':{variant:'circle'}, 'cookie-banner':{variant:'bottom-bar'} },
      variants: [
        { name:'dark', label:'Dark Mode', _open:false, _mode:'dark', colors:{ light:{}, dark:{ '--color-primary':'#60a5fa','--color-secondary':'#93c5fd','--color-accent':'#fbbf24','--color-background':'#060d1a','--color-foreground':'#e2e8f0','--color-card':'#0f1d35','--color-muted':'#1e293b','--color-border':'#1e3a5f','--color-success':'#34d399','--color-warning':'#fbbf24','--color-destructive':'#f87171' } } },
      ],
      custom_css: `.listing-card { border-radius: 1rem; overflow: hidden; }\n.listing-card:hover .listing-image { transform: scale(1.05); }\n.listing-image { transition: transform .4s; }\n.price-tag { font-size: 1.25rem; font-weight: 800; color: var(--color-primary); }`,
      custom_js:  '',
    },
  },
];

const currentDemoData = computed(() => {
  if (!selectedDemoType.value) return null;
  return demoSiteTypes.find(t => t.id === selectedDemoType.value)?.data ?? null;
});

function applyDemoData() {
  const data = currentDemoData.value;
  if (!data) return;
  const cats = demoSelected.value;

  if (cats.includes('colors') && data.colors) {
    form.colors = JSON.parse(JSON.stringify(data.colors));
  }
  if (cats.includes('assets') && data.assets) {
    form.assets = { ...form.assets, ...data.assets };
  }
  if (cats.includes('layout') && data.layout_config) {
    Object.assign(form.layout_config, data.layout_config);
  }
  if (cats.includes('capabilities') && data.capabilities) {
    Object.assign(form.capabilities, data.capabilities);
  }
  if (cats.includes('sections') && data.sections) {
    form.sections = JSON.parse(JSON.stringify(data.sections));
    // Sincroniza com compOrder se aplicável
    compOrder.value = Object.entries(form.sections).map(([id]) => ({ id, variant: form.sections[id]?.variant ?? '' }));
  }
  if (cats.includes('components') && data.components) {
    form.components = JSON.parse(JSON.stringify(data.components));
    compOrder.value = Object.entries(form.components).map(([id, cfg]) => ({ id, variant: cfg?.variant ?? '' }));
  }
  if (cats.includes('variants') && data.variants) {
    form.variants = JSON.parse(JSON.stringify(data.variants));
  }
  if (cats.includes('code') && (data.custom_css || data.custom_js)) {
    if (data.custom_css) form.custom_css = data.custom_css;
    if (data.custom_js)  form.custom_js  = data.custom_js;
  }

  form._demoApplied = true;

  // Guarda automaticamente
  save();

  feedback.success = `🎭 Demo Data "${demoSiteTypes.find(t=>t.id===selectedDemoType.value)?.label}" aplicado com sucesso!`;
  setTimeout(() => { feedback.success = ''; }, 4000);

  // Navega para preview
  setTimeout(() => { activeTab.value = 'preview'; }, 600);
}

// ── Icons ─────────────────────────────────────────────────────────
const iconSearch      = ref('');
const activeIconCat   = ref('all');
const iconCopied      = reactive({ text: '', type: '', label: '' });
const iconSvgLoading  = ref('');
const iconSvgCache    = {}; // name → svg string

const iconCategories = [
  { id: 'all',         label: '🔍 Todos'         },
  { id: 'interface',   label: '🖱️ Interface'      },
  { id: 'navigation',  label: '🧭 Navegação'      },
  { id: 'media',       label: '🎬 Media'          },
  { id: 'communication', label: '💬 Comunicação'  },
  { id: 'commerce',    label: '🛒 Comércio'       },
  { id: 'files',       label: '📁 Ficheiros'      },
  { id: 'data',        label: '📊 Dados'          },
  { id: 'layout',      label: '🧩 Layout'         },
  { id: 'social',      label: '📡 Social'         },
  { id: 'nature',      label: '🌿 Natureza'       },
  { id: 'devices',     label: '💻 Dispositivos'   },
  { id: 'security',    label: '🔐 Segurança'      },
  { id: 'arrows',      label: '↗️ Setas'           },
  { id: 'shapes',      label: '⬡ Formas'          },
];

const iconCatalog = [
  // Interface
  { name: 'HomeIcon',          cat: 'interface' },
  { name: 'SearchIcon',        cat: 'interface' },
  { name: 'BellIcon',          cat: 'interface' },
  { name: 'StarIcon',          cat: 'interface' },
  { name: 'HeartIcon',         cat: 'interface' },
  { name: 'BookmarkIcon',      cat: 'interface' },
  { name: 'ThumbsUpIcon',      cat: 'interface' },
  { name: 'ThumbsDownIcon',    cat: 'interface' },
  { name: 'EyeIcon',           cat: 'interface' },
  { name: 'EyeOffIcon',        cat: 'interface' },
  { name: 'SunIcon',           cat: 'interface' },
  { name: 'MoonIcon',          cat: 'interface' },
  { name: 'ZapIcon',           cat: 'interface' },
  { name: 'FlagIcon',          cat: 'interface' },
  { name: 'TagIcon',           cat: 'interface' },
  { name: 'FilterIcon',        cat: 'interface' },
  { name: 'SlidersIcon',       cat: 'interface' },
  { name: 'AdjustmentsHorizontalIcon', cat: 'interface' },
  { name: 'SettingsIcon',      cat: 'interface' },
  { name: 'Settings2Icon',     cat: 'interface' },
  { name: 'MenuIcon',          cat: 'interface' },
  { name: 'GridIcon',          cat: 'interface' },
  { name: 'ListIcon',          cat: 'interface' },
  { name: 'MoreHorizontalIcon',cat: 'interface' },
  { name: 'MoreVerticalIcon',  cat: 'interface' },
  { name: 'PlusIcon',          cat: 'interface' },
  { name: 'MinusIcon',         cat: 'interface' },
  { name: 'XIcon',             cat: 'interface' },
  { name: 'CheckIcon',         cat: 'interface' },
  { name: 'CheckCircleIcon',   cat: 'interface' },
  { name: 'XCircleIcon',       cat: 'interface' },
  { name: 'InfoIcon',          cat: 'interface' },
  { name: 'AlertCircleIcon',   cat: 'interface' },
  { name: 'AlertTriangleIcon', cat: 'interface' },
  { name: 'HelpCircleIcon',    cat: 'interface' },
  { name: 'LoaderIcon',        cat: 'interface' },
  { name: 'RefreshCwIcon',     cat: 'interface' },
  { name: 'RotateCcwIcon',     cat: 'interface' },
  { name: 'RotateCwIcon',      cat: 'interface' },
  { name: 'ZoomInIcon',        cat: 'interface' },
  { name: 'ZoomOutIcon',       cat: 'interface' },
  { name: 'MaximizeIcon',      cat: 'interface' },
  { name: 'Maximize2Icon',     cat: 'interface' },
  { name: 'MinimizeIcon',      cat: 'interface' },
  { name: 'SidebarIcon',       cat: 'interface' },
  { name: 'PanelLeftIcon',     cat: 'interface' },
  { name: 'PanelRightIcon',    cat: 'interface' },
  { name: 'LayoutIcon',        cat: 'interface' },
  { name: 'ColumnsIcon',       cat: 'interface' },
  // Navigation
  { name: 'NavigationIcon',    cat: 'navigation' },
  { name: 'Navigation2Icon',   cat: 'navigation' },
  { name: 'MapIcon',           cat: 'navigation' },
  { name: 'MapPinIcon',        cat: 'navigation' },
  { name: 'CompassIcon',       cat: 'navigation' },
  { name: 'RouteIcon',         cat: 'navigation' },
  { name: 'GlobeIcon',         cat: 'navigation' },
  { name: 'Globe2Icon',        cat: 'navigation' },
  { name: 'LinkIcon',          cat: 'navigation' },
  { name: 'ExternalLinkIcon',  cat: 'navigation' },
  { name: 'CornerDownRightIcon', cat: 'navigation' },
  { name: 'CornerUpRightIcon', cat: 'navigation' },
  { name: 'ChevronUpIcon',     cat: 'navigation' },
  { name: 'ChevronDownIcon',   cat: 'navigation' },
  { name: 'ChevronLeftIcon',   cat: 'navigation' },
  { name: 'ChevronRightIcon',  cat: 'navigation' },
  { name: 'ChevronsUpDownIcon',cat: 'navigation' },
  { name: 'MenuSquareIcon',    cat: 'navigation' },
  // Media
  { name: 'PlayIcon',          cat: 'media' },
  { name: 'PauseIcon',         cat: 'media' },
  { name: 'StopCircleIcon',    cat: 'media' },
  { name: 'SkipBackIcon',      cat: 'media' },
  { name: 'SkipForwardIcon',   cat: 'media' },
  { name: 'RewindIcon',        cat: 'media' },
  { name: 'FastForwardIcon',   cat: 'media' },
  { name: 'VolumeIcon',        cat: 'media' },
  { name: 'Volume1Icon',       cat: 'media' },
  { name: 'Volume2Icon',       cat: 'media' },
  { name: 'VolumeXIcon',       cat: 'media' },
  { name: 'MicIcon',           cat: 'media' },
  { name: 'MicOffIcon',        cat: 'media' },
  { name: 'VideoIcon',         cat: 'media' },
  { name: 'VideoOffIcon',      cat: 'media' },
  { name: 'CameraIcon',        cat: 'media' },
  { name: 'ImageIcon',         cat: 'media' },
  { name: 'ImagesIcon',        cat: 'media' },
  { name: 'GalleryHorizontalIcon', cat: 'media' },
  { name: 'FilmIcon',          cat: 'media' },
  { name: 'MusicIcon',         cat: 'media' },
  { name: 'HeadphonesIcon',    cat: 'media' },
  { name: 'RadioIcon',         cat: 'media' },
  { name: 'PodcastIcon',       cat: 'media' },
  { name: 'YoutubeIcon',       cat: 'media' },
  // Communication
  { name: 'MessageCircleIcon', cat: 'communication' },
  { name: 'MessageSquareIcon', cat: 'communication' },
  { name: 'MessagesSquareIcon',cat: 'communication' },
  { name: 'MailIcon',          cat: 'communication' },
  { name: 'MailOpenIcon',      cat: 'communication' },
  { name: 'InboxIcon',         cat: 'communication' },
  { name: 'SendIcon',          cat: 'communication' },
  { name: 'PhoneIcon',         cat: 'communication' },
  { name: 'PhoneCallIcon',     cat: 'communication' },
  { name: 'PhoneMissedIcon',   cat: 'communication' },
  { name: 'SmartphoneIcon',    cat: 'communication' },
  { name: 'ContactIcon',       cat: 'communication' },
  { name: 'UsersIcon',         cat: 'communication' },
  { name: 'UserIcon',          cat: 'communication' },
  { name: 'UserPlusIcon',      cat: 'communication' },
  { name: 'UserCheckIcon',     cat: 'communication' },
  { name: 'RssIcon',           cat: 'communication' },
  { name: 'AtSignIcon',        cat: 'communication' },
  { name: 'BellRingIcon',      cat: 'communication' },
  { name: 'ShareIcon',         cat: 'communication' },
  { name: 'Share2Icon',        cat: 'communication' },
  // Commerce
  { name: 'ShoppingCartIcon',  cat: 'commerce' },
  { name: 'ShoppingBagIcon',   cat: 'commerce' },
  { name: 'PackageIcon',       cat: 'commerce' },
  { name: 'Package2Icon',      cat: 'commerce' },
  { name: 'TruckIcon',         cat: 'commerce' },
  { name: 'CreditCardIcon',    cat: 'commerce' },
  { name: 'WalletIcon',        cat: 'commerce' },
  { name: 'BanknoteIcon',      cat: 'commerce' },
  { name: 'CoinsIcon',         cat: 'commerce' },
  { name: 'DollarSignIcon',    cat: 'commerce' },
  { name: 'EuroIcon',          cat: 'commerce' },
  { name: 'ReceiptIcon',       cat: 'commerce' },
  { name: 'BarChart2Icon',     cat: 'commerce' },
  { name: 'TrendingUpIcon',    cat: 'commerce' },
  { name: 'TrendingDownIcon',  cat: 'commerce' },
  { name: 'PercentIcon',       cat: 'commerce' },
  { name: 'TagsIcon',          cat: 'commerce' },
  { name: 'GiftIcon',          cat: 'commerce' },
  { name: 'AwardIcon',         cat: 'commerce' },
  { name: 'BadgeIcon',         cat: 'commerce' },
  // Files
  { name: 'FileIcon',          cat: 'files' },
  { name: 'FileTextIcon',      cat: 'files' },
  { name: 'FileImageIcon',     cat: 'files' },
  { name: 'FileVideoIcon',     cat: 'files' },
  { name: 'FileAudioIcon',     cat: 'files' },
  { name: 'FilePdfIcon',       cat: 'files' },
  { name: 'FileCodeIcon',      cat: 'files' },
  { name: 'FileJsonIcon',      cat: 'files' },
  { name: 'FolderIcon',        cat: 'files' },
  { name: 'FolderOpenIcon',    cat: 'files' },
  { name: 'UploadIcon',        cat: 'files' },
  { name: 'DownloadIcon',      cat: 'files' },
  { name: 'DownloadCloudIcon', cat: 'files' },
  { name: 'UploadCloudIcon',   cat: 'files' },
  { name: 'CloudIcon',         cat: 'files' },
  { name: 'HardDriveIcon',     cat: 'files' },
  { name: 'DatabaseIcon',      cat: 'files' },
  { name: 'ServerIcon',        cat: 'files' },
  { name: 'ArchiveIcon',       cat: 'files' },
  { name: 'ClipboardIcon',     cat: 'files' },
  { name: 'ClipboardCheckIcon',cat: 'files' },
  { name: 'CopyIcon',          cat: 'files' },
  { name: 'ScissorsIcon',      cat: 'files' },
  { name: 'PaperclipIcon',     cat: 'files' },
  { name: 'PrinterIcon',       cat: 'files' },
  // Data
  { name: 'BarChartIcon',      cat: 'data' },
  { name: 'BarChart3Icon',     cat: 'data' },
  { name: 'BarChart4Icon',     cat: 'data' },
  { name: 'LineChartIcon',     cat: 'data' },
  { name: 'PieChartIcon',      cat: 'data' },
  { name: 'AreaChartIcon',     cat: 'data' },
  { name: 'ActivityIcon',      cat: 'data' },
  { name: 'GaugeIcon',         cat: 'data' },
  { name: 'CalendarIcon',      cat: 'data' },
  { name: 'CalendarDaysIcon',  cat: 'data' },
  { name: 'ClockIcon',         cat: 'data' },
  { name: 'TimerIcon',         cat: 'data' },
  { name: 'AlarmClockIcon',    cat: 'data' },
  { name: 'TableIcon',         cat: 'data' },
  { name: 'Table2Icon',        cat: 'data' },
  { name: 'HashIcon',          cat: 'data' },
  { name: 'TypeIcon',          cat: 'data' },
  { name: 'CaseSensitiveIcon', cat: 'data' },
  { name: 'BinaryIcon',        cat: 'data' },
  // Layout
  { name: 'LayoutDashboardIcon', cat: 'layout' },
  { name: 'LayoutGridIcon',    cat: 'layout' },
  { name: 'LayoutListIcon',    cat: 'layout' },
  { name: 'LayoutTemplateIcon',cat: 'layout' },
  { name: 'PanelTopIcon',      cat: 'layout' },
  { name: 'PanelBottomIcon',   cat: 'layout' },
  { name: 'FootprintsIcon',    cat: 'layout' },
  { name: 'AlignLeftIcon',     cat: 'layout' },
  { name: 'AlignCenterIcon',   cat: 'layout' },
  { name: 'AlignRightIcon',    cat: 'layout' },
  { name: 'AlignJustifyIcon',  cat: 'layout' },
  { name: 'CenterIcon',        cat: 'layout' },
  { name: 'RectangleHorizontalIcon', cat: 'layout' },
  { name: 'RectangleVerticalIcon', cat: 'layout' },
  { name: 'SquareIcon',        cat: 'layout' },
  { name: 'CircleIcon',        cat: 'layout' },
  { name: 'GripIcon',          cat: 'layout' },
  { name: 'GripHorizontalIcon',cat: 'layout' },
  { name: 'GripVerticalIcon',  cat: 'layout' },
  { name: 'SeparatorHorizontalIcon', cat: 'layout' },
  { name: 'SeparatorVerticalIcon', cat: 'layout' },
  // Social
  { name: 'TwitterIcon',       cat: 'social' },
  { name: 'FacebookIcon',      cat: 'social' },
  { name: 'InstagramIcon',     cat: 'social' },
  { name: 'LinkedinIcon',      cat: 'social' },
  { name: 'GithubIcon',        cat: 'social' },
  { name: 'GitlabIcon',        cat: 'social' },
  { name: 'YoutubeIcon',       cat: 'social' },
  { name: 'TwitchIcon',        cat: 'social' },
  { name: 'DiscordIcon',       cat: 'social' },
  { name: 'SlackIcon',         cat: 'social' },
  { name: 'FigmaIcon',         cat: 'social' },
  { name: 'ChromeIcon',        cat: 'social' },
  { name: 'CodepenIcon',       cat: 'social' },
  { name: 'DribbbleIcon',      cat: 'social' },
  { name: 'GlobeIcon',         cat: 'social' },
  // Nature
  { name: 'LeafIcon',          cat: 'nature' },
  { name: 'TreesIcon',         cat: 'nature' },
  { name: 'TreePineIcon',      cat: 'nature' },
  { name: 'FlowerIcon',        cat: 'nature' },
  { name: 'Flower2Icon',       cat: 'nature' },
  { name: 'SunIcon',           cat: 'nature' },
  { name: 'SunMediumIcon',     cat: 'nature' },
  { name: 'MoonIcon',          cat: 'nature' },
  { name: 'CloudIcon',         cat: 'nature' },
  { name: 'CloudRainIcon',     cat: 'nature' },
  { name: 'CloudSnowIcon',     cat: 'nature' },
  { name: 'CloudLightningIcon',cat: 'nature' },
  { name: 'WindIcon',          cat: 'nature' },
  { name: 'UmbrellaIcon',      cat: 'nature' },
  { name: 'ThermometerIcon',   cat: 'nature' },
  { name: 'DropletIcon',       cat: 'nature' },
  { name: 'WavesIcon',         cat: 'nature' },
  { name: 'MountainIcon',      cat: 'nature' },
  { name: 'MountainSnowIcon',  cat: 'nature' },
  // Devices
  { name: 'MonitorIcon',       cat: 'devices' },
  { name: 'LaptopIcon',        cat: 'devices' },
  { name: 'TabletIcon',        cat: 'devices' },
  { name: 'SmartphoneIcon',    cat: 'devices' },
  { name: 'WatchIcon',         cat: 'devices' },
  { name: 'PrinterIcon',       cat: 'devices' },
  { name: 'KeyboardIcon',      cat: 'devices' },
  { name: 'MouseIcon',         cat: 'devices' },
  { name: 'MousePointerIcon',  cat: 'devices' },
  { name: 'TvIcon',            cat: 'devices' },
  { name: 'RadioReceiverIcon', cat: 'devices' },
  { name: 'WifiIcon',          cat: 'devices' },
  { name: 'BluetoothIcon',     cat: 'devices' },
  { name: 'BatteryIcon',       cat: 'devices' },
  { name: 'BatteryFullIcon',   cat: 'devices' },
  { name: 'BatteryLowIcon',    cat: 'devices' },
  { name: 'BatteryChargingIcon', cat: 'devices' },
  { name: 'PowerIcon',         cat: 'devices' },
  { name: 'CpuIcon',           cat: 'devices' },
  { name: 'HardDriveIcon',     cat: 'devices' },
  // Security
  { name: 'LockIcon',          cat: 'security' },
  { name: 'LockKeyholeIcon',   cat: 'security' },
  { name: 'UnlockIcon',        cat: 'security' },
  { name: 'KeyIcon',           cat: 'security' },
  { name: 'KeyRoundIcon',      cat: 'security' },
  { name: 'ShieldIcon',        cat: 'security' },
  { name: 'ShieldCheckIcon',   cat: 'security' },
  { name: 'ShieldAlertIcon',   cat: 'security' },
  { name: 'ShieldBanIcon',     cat: 'security' },
  { name: 'EyeIcon',           cat: 'security' },
  { name: 'EyeOffIcon',        cat: 'security' },
  { name: 'ScanIcon',          cat: 'security' },
  { name: 'FingerprintIcon',   cat: 'security' },
  { name: 'BadgeCheckIcon',    cat: 'security' },
  { name: 'AlertOctagonIcon',  cat: 'security' },
  // Arrows
  { name: 'ArrowUpIcon',       cat: 'arrows' },
  { name: 'ArrowDownIcon',     cat: 'arrows' },
  { name: 'ArrowLeftIcon',     cat: 'arrows' },
  { name: 'ArrowRightIcon',    cat: 'arrows' },
  { name: 'ArrowUpRightIcon',  cat: 'arrows' },
  { name: 'ArrowUpLeftIcon',   cat: 'arrows' },
  { name: 'ArrowDownRightIcon',cat: 'arrows' },
  { name: 'ArrowDownLeftIcon', cat: 'arrows' },
  { name: 'ArrowsUpFromLineIcon', cat: 'arrows' },
  { name: 'MoveIcon',          cat: 'arrows' },
  { name: 'MoveHorizontalIcon',cat: 'arrows' },
  { name: 'MoveVerticalIcon',  cat: 'arrows' },
  { name: 'MoveUpRightIcon',   cat: 'arrows' },
  { name: 'MoveDownRightIcon', cat: 'arrows' },
  { name: 'ArrowBigUpIcon',    cat: 'arrows' },
  { name: 'ArrowBigDownIcon',  cat: 'arrows' },
  { name: 'ArrowBigLeftIcon',  cat: 'arrows' },
  { name: 'ArrowBigRightIcon', cat: 'arrows' },
  { name: 'ChevronsUpIcon',    cat: 'arrows' },
  { name: 'ChevronsDownIcon',  cat: 'arrows' },
  { name: 'ChevronsLeftIcon',  cat: 'arrows' },
  { name: 'ChevronsRightIcon', cat: 'arrows' },
  // Shapes
  { name: 'CircleIcon',        cat: 'shapes' },
  { name: 'SquareIcon',        cat: 'shapes' },
  { name: 'TriangleIcon',      cat: 'shapes' },
  { name: 'HexagonIcon',       cat: 'shapes' },
  { name: 'OctagonIcon',       cat: 'shapes' },
  { name: 'DiamondIcon',       cat: 'shapes' },
  { name: 'PentagonIcon',      cat: 'shapes' },
  { name: 'StarIcon',          cat: 'shapes' },
  { name: 'Cross',             cat: 'shapes' },
  { name: 'MinusCircleIcon',   cat: 'shapes' },
  { name: 'PlusCircleIcon',    cat: 'shapes' },
  { name: 'XCircleIcon',       cat: 'shapes' },
  { name: 'Slice',             cat: 'shapes' },
  { name: 'EllipsisIcon',      cat: 'shapes' },
  { name: 'DotIcon',           cat: 'shapes' },
  { name: 'CircleDotIcon',     cat: 'shapes' },
];

// Only keep icons that actually exist in the imported module
const validIconCatalog = iconCatalog.filter(i => !!LucideIcons[i.name]);

const filteredIcons = computed(() => {
  const q   = iconSearch.value.toLowerCase().trim();
  const cat = activeIconCat.value;
  return validIconCatalog.filter(i => {
    const matchCat  = cat === 'all' || i.cat === cat;
    const matchName = !q || i.name.toLowerCase().includes(q);
    return matchCat && matchName;
  });
});

/** Converte 'HomeIcon' → 'home', 'ArrowUpRightIcon' → 'arrow-up-right' */
function iconToKebab(name) {
  return name
    .replace(/Icon$/, '')
    .replace(/([A-Z])/g, (m, l, o) => (o > 0 ? '-' : '') + l.toLowerCase());
}

function showCopied(type, label) {
  iconCopied.text  = label;
  iconCopied.type  = type;
  iconCopied.label = label;
  clearTimeout(iconCopied._t);
  iconCopied._t = setTimeout(() => { iconCopied.text = ''; }, 2000);
}

async function copyIcon(name, type) {
  const kebab = iconToKebab(name);

  if (type === 'vue') {
    const snippet = `import { ${name} } from 'lucide-vue-next';\n// No template: <${name} class="w-5 h-5" />`;
    await navigator.clipboard.writeText(snippet);
    showCopied('vue', name.replace(/Icon$/, ''));

  } else if (type === 'blade') {
    const snippet = `<x-lucide-${kebab} class="w-5 h-5" />`;
    await navigator.clipboard.writeText(snippet);
    showCopied('blade', kebab);

  } else if (type === 'svg') {
    // Cache hit
    if (iconSvgCache[name]) {
      await navigator.clipboard.writeText(iconSvgCache[name]);
      showCopied('svg', kebab);
      return;
    }
    // Fetch from Lucide CDN
    iconSvgLoading.value = name;
    try {
      const url = `https://unpkg.com/lucide-static@latest/icons/${kebab}.svg`;
      const res = await fetch(url);
      if (!res.ok) throw new Error('not found');
      const svg = await res.text();
      // Add width/height/class attributes for convenience
      const ready = svg.replace('<svg ', '<svg class="w-5 h-5" ');
      iconSvgCache[name] = ready;
      await navigator.clipboard.writeText(ready);
      showCopied('svg', kebab);
    } catch {
      // Fallback: copy a basic SVG wrapper with a comment
      const fallback = `<!-- Lucide icon: ${kebab} -->\n<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">\n  <!-- paths here -->\n</svg>`;
      await navigator.clipboard.writeText(fallback);
      showCopied('svg', kebab + ' (fallback)');
    } finally {
      iconSvgLoading.value = '';
    }
  }
}

// ── Versionamento ────────────────────────────────────────────────
const themeVersions         = ref([]);
const loadingVersions       = ref(false);

// Histórico UNIFICADO — uma só narrativa que funde duas granularidades:
//  • versões (snapshots completos, pontos de restauro grandes)
//  • alterações por passo (granular: cada edição de chat/manual/build)
// Ordenado do mais recente para o mais antigo.
const historyTimeline = computed(() => {
  const items = [];

  for (const v of themeVersions.value) {
    const t = v.snapshot_type;
    items.push({
      key: 'v-' + v.uuid, kind: 'version', at: v.created_at,
      icon: t === 'publish' ? '🚀' : t === 'auto' ? '⚡' : t === 'system' ? '🛡️' : '📌',
      title: 'v' + v.version,
      tag: t === 'publish' ? 'Publicação' : t === 'auto' ? 'Automático' : t === 'system' ? 'Sistema' : 'Manual',
      subtitle: v.changelog || '', ver: v,
    });
  }

  for (const [step, node] of Object.entries(stepJournal.value || {})) {
    const hist = node?.history || [];
    hist.forEach((e, idx) => {
      const stepName = workflowSteps.value.find(s => s.tabId === step)?.label ?? step;
      items.push({
        key: 'j-' + step + '-' + idx + '-' + (e.at || ''), kind: 'step', at: e.at,
        icon: sourceMeta(e.source).icon, title: stepName, tag: sourceMeta(e.source).label,
        subtitle: e.summary || '', step,
        revertible: idx === hist.length - 1 && !!node.revertible,
        pruned: !!e.pruned,
      });
    });
  }

  return items.sort((a, b) => new Date(b.at || 0) - new Date(a.at || 0));
});

const showCreateVersionModal = ref(false);
const newVersionChangelog   = ref('');
const savingVersion         = ref(false);
const restoringVersion      = ref(null);

async function loadVersions() {
  loadingVersions.value = true;
  try {
    const res = await axios.get(`/themes/${props.theme.uuid}/versions`);
    themeVersions.value = res.data.versions ?? [];
  } catch {
    themeVersions.value = [];
  } finally {
    loadingVersions.value = false;
  }
}

async function saveVersion() {
  savingVersion.value = true;
  try {
    const res = await axios.post(`/themes/${props.theme.uuid}/versions`, {
      changelog: newVersionChangelog.value,
    });
    if (res.data.success) {
      themeVersions.value.unshift(res.data.version);
      showCreateVersionModal.value = false;
      newVersionChangelog.value = '';
      feedback.success = `Versão v${res.data.version.version} guardada com sucesso.`;
    }
  } catch (e) {
    feedback.error = e.response?.data?.error ?? 'Erro ao guardar versão.';
  } finally {
    savingVersion.value = false;
  }
}

async function restoreVersion(ver) {
  if (!confirm(`Restaurar para v${ver.version}?\n\nO estado actual será guardado automaticamente antes do restauro.`)) return;
  restoringVersion.value = ver.uuid;
  try {
    const res = await axios.post(`/themes/${props.theme.uuid}/versions/${ver.uuid}/restore`);
    if (res.data.success) {
      // Actualizar o form com os dados restaurados
      const t = res.data.theme;
      Object.assign(form, {
        label:         t.label         ?? form.label,
        description:   t.description   ?? form.description,
        version:       t.version       ?? form.version,
        colors:        t.colors        ?? form.colors,
        fonts:         t.fonts         ?? form.fonts,
        sections:      t.sections      ?? form.sections,
        layout_config: t.layout_config ?? form.layout_config,
        capabilities:  t.capabilities  ?? form.capabilities,
        assets:        t.assets        ?? form.assets,
        components:    t.components    ?? form.components,
        variants:      t.variants      ?? form.variants,
        custom_css:    t.custom_css    ?? form.custom_css,
        custom_js:     t.custom_js     ?? form.custom_js,
      });
      await loadVersions();
      feedback.success = res.data.message;
    }
  } catch (e) {
    feedback.error = e.response?.data?.error ?? 'Erro ao restaurar versão.';
  } finally {
    restoringVersion.value = null;
  }
}

async function deleteVersion(ver) {
  if (!confirm(`Eliminar snapshot v${ver.version} de ${formatVersionDate(ver.created_at)}?`)) return;
  try {
    await axios.delete(`/themes/${props.theme.uuid}/versions/${ver.uuid}`);
    themeVersions.value = themeVersions.value.filter(v => v.uuid !== ver.uuid);
  } catch (e) {
    feedback.error = e.response?.data?.error ?? 'Erro ao eliminar versão.';
  }
}

function formatVersionDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('pt-PT', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// Carrega versões ou receitas ao activar o tab
watch(activeTab, (tab) => {
  if (tab === 'versions' && themeVersions.value.length === 0 && !loadingVersions.value) {
    loadVersions();
  }
  if (tab === 'recipes' && recipes.value.length === 0 && !loadingRecipes.value) {
    loadRecipes();
  }
});

// ── Receitas / Macros ─────────────────────────────────────────────
const recipes = ref([]);
const loadingRecipes = ref(false);
const recipeInputs = ref({});

async function loadRecipes() {
  loadingRecipes.value = true;
  try {
    const res = await axios.get(`/themes/${props.theme.uuid}/recipes`);
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


// ── Preview ───────────────────────────────────────────────────────
const previewKey      = ref(0);
const previewIframe   = ref(null);
const previewEditMode = ref(false);
const previewToast    = ref('');
let   previewToastTimer = null;

function showPreviewToast(msg) {
  previewToast.value = msg;
  clearTimeout(previewToastTimer);
  previewToastTimer = setTimeout(() => { previewToast.value = ''; }, 3000);
}

function togglePreviewEditMode() {
  previewEditMode.value = !previewEditMode.value;
  const iframe = previewIframe.value;
  if (!iframe?.contentWindow) return;
  if (previewEditMode.value) {
    iframe.contentWindow.postMessage({ type: 'af-enable-edit' }, '*');
    // Sync current color vars into iframe
    const vars = { ...(form.colors?.light ?? {}) };
    if (form.fonts?.heading) vars['--font-heading'] = form.fonts.heading;
    if (form.fonts?.body)    vars['--font-body']    = form.fonts.body;
    iframe.contentWindow.postMessage({ type: 'af-apply-vars', vars }, '*');
  } else {
    iframe.contentWindow.postMessage({ type: 'af-disable-edit' }, '*');
  }
}

function handlePreviewMessage(e) {
  const d = e.data;
  if (!d || typeof d !== 'object') return;

  if (d.type === 'af-ready' && previewEditMode.value) {
    // iframe reloaded while edit mode was on — re-enable
    const iframe = previewIframe.value;
    if (!iframe?.contentWindow) return;
    iframe.contentWindow.postMessage({ type: 'af-enable-edit' }, '*');
    const vars = { ...(form.colors?.light ?? {}) };
    if (form.fonts?.heading) vars['--font-heading'] = form.fonts.heading;
    if (form.fonts?.body)    vars['--font-body']    = form.fonts.body;
    iframe.contentWindow.postMessage({ type: 'af-apply-vars', vars }, '*');
  }

  if (d.type === 'af-token-change') {
    const { var: varName, value } = d;
    if (!varName || !value) return;
    if (varName === '--font-heading') {
      form.fonts = { ...(form.fonts ?? {}), heading: value };
    } else if (varName === '--font-body') {
      form.fonts = { ...(form.fonts ?? {}), body: value };
    } else if (varName.startsWith('--')) {
      form.colors = {
        ...(form.colors ?? {}),
        light: { ...(form.colors?.light ?? {}), [varName]: value },
      };
    }
    showPreviewToast('✦ Token actualizado — Guarda para persistir');
  }

  if (d.type === 'af-save-request') {
    save();
  }
}

onMounted(() => { window.addEventListener('message', handlePreviewMessage); });
onUnmounted(() => { window.removeEventListener('message', handlePreviewMessage); clearTimeout(previewToastTimer); });

// ── Publish ───────────────────────────────────────────────────────
const installingCms = ref(false);
async function installInCms() {
  if (!confirm('Instalar este tema directamente no CMS local? Certifica-te de que a URL e API Key estão configuradas em Definições.')) return;
  installingCms.value = true; feedback.error = ''; feedback.success = '';
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const res  = await fetch(`/themes/${props.theme.uuid}/install-in-cms`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } });
    const data = await res.json();
    if (!res.ok || data.error) { feedback.error = data.error ?? 'Instalação falhou.'; }
    else { feedback.success = data.message ?? 'Tema instalado no CMS com sucesso!'; }
  } catch (e) { feedback.error = e.message; }
  finally { installingCms.value = false; }
}

const publishing = ref(false);
async function publishTheme() {
  if (!confirm(t('themes.publish_confirm'))) return;
  publishing.value = true; feedback.error = ''; feedback.success = '';
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const res  = await fetch(`/themes/${props.theme.uuid}/publish`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf} });
    const data = await res.json();
    if (!res.ok || data.error) { feedback.error = data.error ?? 'Publish failed.'; }
    else { feedback.success = t('themes.publish_success'); setTimeout(() => router.reload(), 1500); }
  } catch(e) { feedback.error = e.message; }
  finally { publishing.value = false; }
}

// ── Chat IA ──────────────────────────────────────────────────────────────

// Restaura mensagens guardadas na BD, garantindo que nenhum cartão de build
// fica preso em estado "a construir" e que fases inacabadas não giram para sempre.
function sanitizeStoredMessages(list) {
  if (!Array.isArray(list)) return [];
  return list.map((m) => {
    const msg = { ...m };
    if (msg.type === 'build') {
      msg.building = false;
      if (Array.isArray(msg.phases)) {
        msg.phases = msg.phases.map((p) => ({
          ...p,
          status: (p.status === 'running' || p.status === 'pending') ? 'done' : p.status,
        }));
      }
    }
    return msg;
  });
}

const chatMessages       = ref(sanitizeStoredMessages(props.theme?.chat_history));   // [{role, content, attachmentPreviews?, updates?, applied?}]
const chatInput          = ref('');
const chatLoading        = ref(false);
const chatAttachments    = ref([]);   // [{file, type, name, icon, url, sizeLabel}]
const chatDragging       = ref(false);
const chatPendingUpdates = ref(null);
const chatScrollEl       = ref(null);
const chatTextarea       = ref(null);
const chatFileInput      = ref(null);

// Persiste o histórico do chat na BD (debounced) sempre que muda, para que ao
// reentrar no tema a conversa e as tarefas feitas não se percam. Remove dados
// pesados/transitórios (object URLs de anexos, flag de "a construir").
let chatPersistTimer = null;
function persistChatHistory() {
  if (!props.theme?.uuid) return;
  clearTimeout(chatPersistTimer);
  chatPersistTimer = setTimeout(() => {
    const messages = chatMessages.value
      .filter((m) => !m.content || !m.content.startsWith('⚠️'))
      .map((m) => {
        const out = { ...m };
        if (out.type === 'build') out.building = false;
        if (Array.isArray(out.attachmentPreviews)) {
          // object URLs deixam de ser válidos após reload — guarda só o essencial
          out.attachmentPreviews = out.attachmentPreviews.map(({ url, ...rest }) => rest);
        }
        return out;
      });
    axios.post(`/themes/${props.theme.uuid}/chat-history`, { messages }).catch(() => {});
  }, 1500);
}
watch(chatMessages, persistChatHistory, { deep: true });

// Skill (instruções detalhadas) — carregado de ficheiro, guia a construção do tema
const buildSkill     = ref('');
const buildSkillName = ref('');
const skillFileInput = ref(null);

// Construção rápida — salta o plano por IA (usa plano inline) e a revisão de
// qualidade, reduzindo o consumo de tokens. Persistido em localStorage.
const fastBuild = ref(false);
try { fastBuild.value = localStorage.getItem('af_fast_build') === '1'; } catch (e) {}
watch(fastBuild, (v) => { try { localStorage.setItem('af_fast_build', v ? '1' : '0'); } catch (e) {} });
function loadSkillFile(event) {
  const file = event.target.files?.[0];
  event.target.value = '';
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    buildSkill.value = String(e.target?.result ?? '').slice(0, 60000);
    buildSkillName.value = file.name;
    feedback.success = `Skill "${file.name}" carregado — vai guiar a próxima construção.`;
  };
  reader.readAsText(file);
}
function clearSkill() { buildSkill.value = ''; buildSkillName.value = ''; }

const chatQuickPrompts = [
  'Cria um tema para um restaurante italiano',
  'Constrói um site moderno para uma clínica',
  'Torna o tema mais minimalista',
  'Sugere uma paleta de cores elegante',
  'Adiciona suporte a dark mode',
];

function autoResizeChatTextarea() {
  const el = chatTextarea.value;
  if (!el) return;
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

// ── Modo Construção (multi-agente) ──────────────────────────────────────────
// Mapa de agentes técnicos → fases legíveis para o utilizador final.
// O trabalho multi-agente corre em segundo plano; o utilizador só vê estas fases.
const PHASE_META = {
  design:     'A definir estilo, cores e tipografia',
  intro:      'A criar a apresentação (hero, funcionalidades…)',
  conversion: 'A criar as secções de negócio',
  code:       'A afinar os detalhes visuais',
};
function phaseLabel(agentId) {
  return PHASE_META[agentId] ?? 'A trabalhar no tema';
}

// Esconde o indicador genérico de "a escrever" quando há um cartão de construção activo
const lastMsgBuilding = computed(() => {
  const m = chatMessages.value[chatMessages.value.length - 1];
  return !!(m && m.type === 'build' && m.building);
});

// Merge a fresh server theme into the local form (deep-merge — preserves untouched keys)
function applyServerTheme(t) {
  if (!t) return;
  if (t.colors)        { form.colors        = { ...(form.colors ?? {}),        ...t.colors,        light: { ...(form.colors?.light ?? {}), ...(t.colors.light ?? {}) }, dark: { ...(form.colors?.dark ?? {}), ...(t.colors.dark ?? {}) } }; }
  if (t.fonts)         { form.fonts         = { ...(form.fonts ?? {}),         ...t.fonts }; }
  if (t.layout_config) { form.layout_config = { ...(form.layout_config ?? {}), ...t.layout_config }; }
  if (t.capabilities)  { form.capabilities  = { ...(form.capabilities ?? {}),  ...t.capabilities }; }
  if (t.assets)        { form.assets        = { ...(form.assets ?? {}),        ...t.assets }; }
  if (t.sections)      form.sections   = t.sections;
  if (t.components)    form.components = t.components;
  if (t.variants)      form.variants   = t.variants;
  if (t.custom_css !== undefined && t.custom_css !== null) form.custom_css = t.custom_css;
  if (t.custom_js  !== undefined && t.custom_js  !== null) form.custom_js  = t.custom_js;
  if (t.label)         form.label       = t.label;
  if (t.description)   form.description = t.description;
  if (t.version)       form.version     = t.version;
  if (t.status)        form.status      = t.status;
}

function csrf() { return document.querySelector('meta[name="csrf-token"]')?.content ?? ''; }

// Executa um agente (segundo plano); actualiza a fase e devolve {ok, isFatal}
async function runBuildAgent(phase, ctx) {
  phase.status = 'running';
  try {
    const fd = new FormData();
    fd.append('agent', phase.agent);
    if (ctx.brief)     fd.append('brief', ctx.brief);
    if (ctx.direction) fd.append('direction', ctx.direction);
    if (ctx.note)      fd.append('note', ctx.note);
    if (ctx.skill)     fd.append('skill', ctx.skill);
    fd.append('_token', csrf());
    const res = await fetch(`/themes/${props.theme.uuid}/build/step`, { method: 'POST', body: fd });
    if (!(res.headers.get('content-type') ?? '').includes('application/json')) {
      window.location.href = '/login';
      return { ok: false, isFatal: true };
    }
    const data = await res.json();
    if (!res.ok || data.error) {
      phase.status = 'error'; phase.reply = data.error ?? 'Erro.';
      return { ok: false, isFatal: !!data.is_fatal };
    }
    if (data.applied && data.theme) applyServerTheme(data.theme);
    if (data.step_journal) stepJournal.value = data.step_journal;
    phase.reply = data.reply ?? '';
    phase.status = 'done';
    return { ok: true, isFatal: false };
  } catch (e) {
    phase.status = 'error'; phase.reply = e.message;
    return { ok: false, isFatal: false };
  }
}

// Orquestra a construção completa do tema, mostrando fases legíveis na conversa.
// Planear → agentes → rever & corrigir corre tudo em segundo plano (estilo Claude).
async function runBuildFlow(build, msgIdx) {
  // Aceita string (brief) ou objecto {brief, direction?, agents?} — o plano
  // inline (vindo da deteção de intenção) evita uma chamada de IA ao planeador.
  const brief = typeof build === 'string' ? build : (build?.brief ?? '');
  const inlineAgents = (build && typeof build === 'object' && Array.isArray(build.agents) && build.agents.length) ? build.agents : null;
  const inlineDirection = (build && typeof build === 'object') ? (build.direction ?? '') : '';

  const msg = chatMessages.value[msgIdx];
  msg.building = true; msg.failed = false; msg.error = ''; msg.aborted = false;
  msg.phases = [{ agent: '__plan__', label: 'A planear a construção', status: 'running', reply: '' }];
  chatScrollToBottom();

  let direction = '';

  // 1. Planear (se há plano inline, o backend salta a IA do planeador)
  try {
    const fd = new FormData();
    fd.append('brief', brief);
    if (inlineDirection) fd.append('direction', inlineDirection);
    if (inlineAgents) inlineAgents.forEach((id, i) => fd.append(`agents[${i}]`, id));
    if (buildSkill.value) fd.append('skill', buildSkill.value);
    fd.append('_token', csrf());
    const res = await fetch(`/themes/${props.theme.uuid}/build/plan`, { method: 'POST', body: fd });
    if (!(res.headers.get('content-type') ?? '').includes('application/json')) {
      window.location.href = '/login';
      return;
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
      await loadVersions(); // Carregar a lista para mostrar na tab de versões
    }

    msg.phases.push(...(data.agents ?? []).map(id => ({ agent: id, label: phaseLabel(id), status: 'pending', reply: '' })));
  } catch (e) {
    msg.phases[0].status = 'error'; msg.building = false; msg.failed = true;
    msg.error = e.message; return;
  }

  // 2. Executar agentes em sequência
  const ctx = { brief, direction, skill: buildSkill.value };
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

  // 3a. Construção rápida → salta a revisão de qualidade (poupa tokens e latência)
  if (fastBuild.value) {
    msg.building = false;
    feedback.success = 'Tema construído (modo rápido — sem revisão).';
    chatScrollToBottom();
    return;
  }

  // 3b. Rever a qualidade + corrigir automaticamente
  const verifyPhase = { agent: '__verify__', label: 'A rever a qualidade', status: 'running', reply: '' };
  msg.phases.push(verifyPhase);
  chatScrollToBottom();
  try {
    const fd = new FormData();
    fd.append('brief', brief);
    if (direction) fd.append('direction', direction);
    if (buildSkill.value) fd.append('skill', buildSkill.value);
    fd.append('_token', csrf());
    const res = await fetch(`/themes/${props.theme.uuid}/build/verify`, { method: 'POST', body: fd });
    if (msg.aborted) {
      verifyPhase.status = 'cancelled';
      msg.building = false;
      msg.failed = true;
      msg.error = 'Construção cancelada pelo utilizador.';
      chatScrollToBottom();
      return;
    }
    if (!(res.headers.get('content-type') ?? '').includes('application/json')) {
      window.location.href = '/login';
      return;
    }
    const data = await res.json();
    if (res.ok && !data.error) {
      verifyPhase.reply = data.summary ?? '';
      for (const iss of (data.issues ?? [])) {
        if (msg.aborted) break;
        const fixPhase = { agent: iss.agent, label: 'A melhorar: ' + phaseLabel(iss.agent), status: 'running', reply: '' };
        msg.phases.push(fixPhase);
        chatScrollToBottom();
        const r = await runBuildAgent(fixPhase, { brief, direction, note: iss.reason, skill: buildSkill.value });
        if (msg.aborted) {
          fixPhase.status = 'cancelled';
          break;
        }
        if (!r.ok && r.isFatal) break;
      }
    }
    verifyPhase.status = msg.aborted ? 'cancelled' : 'done';
  } catch (e) {
    verifyPhase.status = 'done'; // não bloquear a construção por falha na revisão
  }

  msg.building = false;
  if (msg.aborted) {
    msg.failed = true;
    msg.error = 'Construção cancelada pelo utilizador.';
  } else {
    feedback.success = 'Tema construído e guardado!';
  }
  chatScrollToBottom();
}

function chatScrollToBottom() {
  nextTick(() => {
    const el = chatScrollEl.value;
    if (el) el.scrollTop = el.scrollHeight;
  });
}

function fileToAttachment(file) {
  const mime = file.type;
  const name = file.name;
  const sizeLabel = file.size > 1024 * 1024
    ? (file.size / 1024 / 1024).toFixed(1) + ' MB'
    : Math.round(file.size / 1024) + ' KB';

  if (mime.startsWith('image/')) {
    return { file, type: 'image', name, icon: '🖼️', url: URL.createObjectURL(file), sizeLabel };
  } else if (mime === 'application/pdf') {
    return { file, type: 'document', name, icon: '📄', url: null, sizeLabel };
  } else if (mime.startsWith('audio/')) {
    return { file, type: 'audio', name, icon: '🎵', url: null, sizeLabel };
  } else if (mime.startsWith('video/')) {
    return { file, type: 'video', name, icon: '🎬', url: null, sizeLabel };
  } else {
    return { file, type: 'document', name, icon: '📎', url: null, sizeLabel };
  }
}

function onChatFileSelect(event) {
  const files = Array.from(event.target.files ?? []);
  files.forEach(f => chatAttachments.value.push(fileToAttachment(f)));
  event.target.value = '';
}

function onChatDrop(event) {
  chatDragging.value = false;
  const files = Array.from(event.dataTransfer.files ?? []);
  files.forEach(f => chatAttachments.value.push(fileToAttachment(f)));
}

async function sendChatMessage() {
  const text = chatInput.value.trim();
  if (!text || chatLoading.value) return;

  const attachPreviews = chatAttachments.value.map(a => ({
    type: a.type === 'image' ? 'image' : 'other',
    url:  a.url,
    icon: a.icon,
    name: a.name,
  }));

  chatMessages.value.push({ role: 'user', content: text, attachmentPreviews: attachPreviews });
  chatScrollToBottom();

  const filesToSend = [...chatAttachments.value];
  chatInput.value = '';
  chatAttachments.value = [];
  chatLoading.value = true;

  // Build history (role/content only, last 20 messages)
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
    filesToSend.forEach((att, i) => {
      formData.append(`files[${i}]`, att.file);
    });
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

    const res  = await fetch(`/themes/${props.theme.uuid}/chat`, { method: 'POST', body: formData });

    // Se o servidor devolver HTML em vez de JSON (sessão expirada → redirect)
    const contentType = res.headers.get('content-type') ?? '';
    if (!contentType.includes('application/json')) {
      window.location.href = '/login';
      return;
    }

    const data = await res.json();

    if (!res.ok || data.error) {
      chatMessages.value.push({ role: 'assistant', content: '⚠️ ' + (data.error ?? 'Erro desconhecido.') });
    } else {
      // Resposta textual do assistente (confirmação ou resposta a uma pergunta)
      if (data.reply) {
        chatMessages.value.push({
          role:    'assistant',
          content: data.reply,
          updates: data.updates ?? null,
          applied: data.applied ?? false,
          cached:  data.cached ?? false,
          step:    data.step ?? null,
          stepLabel: data.step_label ?? null,
        });
      }
      if (data.applied && data.theme) applyServerTheme(data.theme);
      if (data.step_journal) stepJournal.value = data.step_journal;

      // A IA decidiu que isto justifica uma construção completa → pipeline inline
      if (data.build && data.build.brief) {
        const buildIdx = chatMessages.value.length;
        chatMessages.value.push({ role: 'assistant', type: 'build', phases: [], building: true });
        await runBuildFlow(data.build, buildIdx);
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
  // Deep-merge nested fields — preserve untouched manual edits
  if (updates.colors)        { form.colors        = { ...(form.colors ?? {}), ...updates.colors, light: { ...(form.colors?.light ?? {}), ...(updates.colors.light ?? {}) }, dark: { ...(form.colors?.dark ?? {}), ...(updates.colors.dark ?? {}) } }; }
  if (updates.fonts)         { form.fonts         = { ...(form.fonts ?? {}),         ...updates.fonts }; }
  if (updates.layout_config) { form.layout_config = { ...(form.layout_config ?? {}), ...updates.layout_config }; }
  if (updates.capabilities)  { form.capabilities  = { ...(form.capabilities ?? {}),  ...updates.capabilities }; }
  if (updates.assets)        { form.assets        = { ...(form.assets ?? {}),        ...updates.assets }; }
  if (updates.sections)      form.sections   = updates.sections;
  if (updates.components)    form.components = updates.components;
  if (updates.variants)      form.variants   = updates.variants;
  if (updates.custom_css !== undefined) form.custom_css = updates.custom_css;
  if (updates.custom_js  !== undefined) form.custom_js  = updates.custom_js;
  if (updates.label)         form.label       = updates.label;
  if (updates.description)   form.description = updates.description;
  if (updates.version)       form.version     = updates.version;
  if (updates.status)        form.status      = updates.status;
  chatMessages.value[msgIdx] = { ...chatMessages.value[msgIdx], applied: true };
  feedback.success = 'Alterações do chat aplicadas! Guarda para persistir.';
}

// ── Micro-componentes (registados no script setup para Vue os detectar) ───

const BtnSave = {
  props: { saving: Boolean, label: { type: String, default: 'Guardar' } },
  emits: ['click'],
  template: `<button type="button" @click="$emit('click')" :disabled="saving"
    class="px-5 py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50 flex items-center gap-2">
    <div v-if="saving" class="w-3.5 h-3.5 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
    {{ saving ? 'A guardar…' : label }}
  </button>`,
};

const SectionCard = {
  props: { title: String },
  template: `<div class="bg-card border border-border rounded-2xl p-6 space-y-4">
    <h3 class="font-semibold text-foreground text-sm">{{ title }}</h3>
    <slot />
  </div>`,
};

const ToggleField = {
  props: { modelValue: Boolean, label: String },
  emits: ['update:modelValue'],
  template: `<label class="flex items-center gap-2.5 cursor-pointer select-none">
    <div class="relative" @click="$emit('update:modelValue', !modelValue)">
      <div class="w-9 h-5 rounded-full transition-colors" :class="modelValue ? 'bg-primary' : 'bg-border'">
        <div class="w-3.5 h-3.5 bg-white rounded-full shadow absolute top-0.5 transition-transform"
          :class="modelValue ? 'translate-x-4' : 'translate-x-0.5'"></div>
      </div>
    </div>
    <span class="text-sm text-foreground">{{ label }}</span>
  </label>`,
};

const CapabilityRow = {
  props: { modelValue: Boolean, label: String, hint: String },
  emits: ['update:modelValue'],
  template: `<label class="flex items-start gap-3 p-3 bg-muted rounded-xl cursor-pointer hover:bg-border/50 transition-colors">
    <div class="relative mt-0.5 flex-shrink-0" @click.stop="$emit('update:modelValue', !modelValue)">
      <div class="w-9 h-5 rounded-full transition-colors" :class="modelValue ? 'bg-primary' : 'bg-border'">
        <div class="w-3.5 h-3.5 bg-white rounded-full shadow absolute top-0.5 transition-transform"
          :class="modelValue ? 'translate-x-4' : 'translate-x-0.5'"></div>
      </div>
    </div>
    <div>
      <p class="text-sm font-semibold text-foreground">{{ label }}</p>
      <p class="text-xs text-muted-foreground mt-0.5">{{ hint }}</p>
    </div>
  </label>`,
};

const AssetSlot = {
  props: { slotId: String, label: String, hint: String, accept: String, currentUrl: String, uploading: Boolean },
  emits: ['upload', 'delete'],
  template: `<div class="border border-border rounded-xl p-4 space-y-3">
    <div>
      <p class="text-sm font-semibold text-foreground">{{ label }}</p>
      <p class="text-xs text-muted-foreground">{{ hint }}</p>
    </div>
    <div v-if="currentUrl" class="relative group">
      <img v-if="!currentUrl.endsWith('.mp4') && !currentUrl.endsWith('.webm')"
        :src="currentUrl" class="w-full h-28 object-cover rounded-lg border border-border" />
      <video v-else :src="currentUrl" class="w-full h-28 object-cover rounded-lg border border-border" muted />
      <button @click="$emit('delete', { slotId })"
        class="absolute top-1 right-1 w-6 h-6 bg-destructive text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
    </div>
    <div v-else class="w-full h-20 bg-muted rounded-lg border border-dashed border-border flex items-center justify-center text-xs text-muted-foreground">
      Sem ficheiro
    </div>
    <label class="block">
      <input type="file" :accept="accept" class="sr-only"
        @change="e => e.target.files[0] && $emit('upload', { slotId, file: e.target.files[0] })" />
      <span class="block w-full py-2 bg-muted text-foreground rounded-lg text-xs font-semibold text-center cursor-pointer hover:bg-border transition-colors"
        :class="uploading ? 'opacity-50 pointer-events-none' : ''">
        {{ uploading ? 'A carregar…' : (currentUrl ? '🔄 Substituir' : '📁 Escolher ficheiro') }}
      </span>
    </label>
  </div>`,
};
</script>

<style scoped>
@reference "../../../css/app.css";
.field-label { @apply block text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1.5; }
.field-input  { @apply w-full px-3 py-2 bg-muted border border-border rounded-lg text-sm focus:outline-none focus:border-primary; }
.field-hint   { @apply text-xs text-muted-foreground mt-1; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.guide-slide-enter-active, .guide-slide-leave-active { transition: all 0.25s ease; overflow: hidden; }
.guide-slide-enter-from, .guide-slide-leave-to { opacity: 0; max-height: 0; }
.guide-slide-enter-to, .guide-slide-leave-from { opacity: 1; max-height: 600px; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
