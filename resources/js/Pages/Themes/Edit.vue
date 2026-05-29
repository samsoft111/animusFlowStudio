<template>
  <AppLayout :title="theme.label">
    <template #actions>
      <a :href="`/preview/theme/${theme.uuid}`" target="_blank"
        class="px-3 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors flex items-center gap-1.5">
        <EyeIcon class="w-3.5 h-3.5" /> {{ t('themes.preview') }}
      </a>
      <a :href="`/themes/${theme.uuid}/export`"
        class="px-3 py-2 bg-muted text-foreground rounded-lg text-sm font-semibold hover:bg-border transition-colors flex items-center gap-1.5">
        <DownloadIcon class="w-3.5 h-3.5" /> {{ t('common.export') }}
      </a>
      <button @click="publishTheme" :disabled="publishing"
        class="px-3 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1.5 disabled:opacity-50"
        :class="theme.is_published ? 'bg-success/10 text-success hover:bg-success/20' : 'bg-primary text-primary-foreground hover:opacity-90'">
        <UploadIcon class="w-3.5 h-3.5" />
        {{ publishing ? t('common.loading') : (theme.is_published ? t('themes.republish') : t('themes.publish')) }}
      </button>
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

      <!-- TAB BAR -->
      <div class="flex flex-wrap gap-1 bg-muted p-1 rounded-xl w-fit">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
          class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors"
          :class="activeTab === tab.id ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
          {{ tab.icon }} {{ tab.label }}
        </button>
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
            </div>
            <div>
              <label class="field-label">Posição do Menu</label>
              <select v-model="form.layout_config.nav_position" class="field-input">
                <option value="left">Esquerda</option>
                <option value="center">Centro</option>
                <option value="right">Direita</option>
              </select>
            </div>
            <div>
              <label class="field-label">Tipo de Navegação</label>
              <select v-model="form.layout_config.nav_type" class="field-input">
                <option value="horizontal">Horizontal clássico</option>
                <option value="hamburger">Hamburger (mobile-first)</option>
                <option value="mega">Mega Menu</option>
                <option value="fullscreen">Full-screen overlay</option>
                <option value="sidebar">Sidebar vertical</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="field-label">CTA Botão — Texto</label>
              <input v-model="form.layout_config.header_cta_text" placeholder="Ex: Começar agora" class="field-input" />
            </div>
            <div>
              <label class="field-label">CTA Botão — URL</label>
              <input v-model="form.layout_config.header_cta_url" placeholder="#" class="field-input" />
            </div>
          </div>
          <div class="flex gap-6">
            <toggle-field v-model="form.layout_config.header_sticky" label="Header fixo (sticky)" />
            <toggle-field v-model="form.layout_config.show_dark_toggle" label="Mostrar toggle dark/light" />
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
            </div>
            <div>
              <label class="field-label">Largura máxima</label>
              <select v-model="form.layout_config.max_width" class="field-input">
                <option value="960">960px — Estreito</option>
                <option value="1120">1120px — Normal</option>
                <option value="1280">1280px — Largo</option>
                <option value="1440">1440px — Extra largo</option>
                <option value="full">Full — Sem limite</option>
              </select>
            </div>
            <div>
              <label class="field-label">Espaçamento entre secções</label>
              <select v-model="form.layout_config.spacing" class="field-input">
                <option value="compact">Compacto</option>
                <option value="normal">Normal</option>
                <option value="spacious">Espaçoso</option>
              </select>
            </div>
          </div>
          <toggle-field v-model="form.layout_config.back_to_top" label="Botão 'Voltar ao topo'" />
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
            </div>
            <div>
              <label class="field-label">Texto de Copyright</label>
              <input v-model="form.layout_config.footer_copyright"
                :placeholder="`© ${new Date().getFullYear()} Empresa. Todos os direitos reservados.`"
                class="field-input" />
            </div>
          </div>
        </section-card>

        <btn-save @click="save" :saving="saving" />
      </div>

      <!-- ════════════════════ TAB: Capabilities ════════════════════ -->
      <div v-show="activeTab === 'capabilities'" class="max-w-2xl">
        <div class="bg-card border border-border rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-foreground">⚙️ Funcionalidades do Tema</h2>
          <p class="text-xs text-muted-foreground">Define que funcionalidades este tema suporta. Cada opção activa gera o código correspondente no ZIP exportado.</p>

          <div class="grid grid-cols-1 gap-2">
            <capability-row v-model="form.capabilities.video_bg"
              label="🎬 Vídeo de fundo"
              hint="Suporte para vídeo MP4/WebM autoplay no hero e em secções" />
            <capability-row v-model="form.capabilities.parallax"
              label="🌊 Efeito Parallax"
              hint="Scrolling parallax em imagens de fundo" />
            <capability-row v-model="form.capabilities.animations"
              label="✨ Animações de scroll"
              hint="Fade-in e slide-up via IntersectionObserver quando os elementos entram no viewport" />
            <capability-row v-model="form.capabilities.lightbox"
              label="🖼️ Lightbox de imagens"
              hint="Clique em imagens para abrir em fullscreen (galeria, portfolio)" />
            <capability-row v-model="form.capabilities.mega_menu"
              label="📋 Mega Menu"
              hint="Menu de navegação com sub-colunas, imagens e descrições" />
            <capability-row v-model="form.capabilities.search"
              label="🔍 Pesquisa no site"
              hint="Ícone de pesquisa no header com overlay de resultados" />
            <capability-row v-model="form.capabilities.cookie_banner"
              label="🍪 Banner de cookies"
              hint="Aviso RGPD/GDPR de cookies com botão aceitar/recusar" />
            <capability-row v-model="form.capabilities.preloader"
              label="⏳ Preloader de página"
              hint="Animação de carregamento antes do conteúdo aparecer" />
            <capability-row v-model="form.capabilities.scroll_progress"
              label="📏 Barra de progresso de scroll"
              hint="Linha fina no topo da página que mostra o progresso de leitura" />
            <capability-row v-model="form.capabilities.back_to_top"
              label="⬆️ Botão Voltar ao topo"
              hint="Botão flutuante para voltar ao início da página" />
          </div>

          <btn-save @click="save" :saving="saving" />
        </div>
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

        <div class="bg-muted/50 border border-border rounded-xl px-4 py-3 text-xs text-muted-foreground">
          📦 Todos os ficheiros são incluídos na pasta <code class="bg-muted px-1 rounded">assets/</code> do ZIP exportado.
        </div>

        <!-- Identidade -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <h3 class="text-sm font-semibold text-foreground">🏷️ Identidade</h3>
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
              <p class="text-xs text-muted-foreground mt-0.5">Fundo aplicado a toda a página. Vídeo tem prioridade sobre imagem quando ambos existem.</p>
            </div>
            <!-- Tipo de fundo -->
            <div class="shrink-0 w-44">
              <label class="field-label">Tipo de fundo</label>
              <select v-model="form.assets.bg_type" class="field-input text-xs">
                <option value="">Nenhum</option>
                <option value="color">Cor sólida (CSS)</option>
                <option value="image">Imagem</option>
                <option value="video">Vídeo</option>
                <option value="gradient">Gradiente</option>
                <option value="pattern">Padrão / textura</option>
              </select>
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
              <p class="text-xs text-muted-foreground mt-0.5">Media da secção principal (primeira secção visível).</p>
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
          <h3 class="text-sm font-semibold text-foreground">📐 Fundos de Secções</h3>
          <p class="text-xs text-muted-foreground -mt-1">Imagens de fundo para secções específicas do tema.</p>
          <div class="grid grid-cols-3 gap-4">
            <asset-slot v-for="slot in assetGroups.sections" :key="slot.id"
              :slot-id="slot.id" :label="slot.label" :hint="slot.hint" :accept="slot.accept"
              :current-url="form.assets[slot.id]" :uploading="uploadingSlot === slot.id"
              @upload="handleAssetUpload" @delete="handleAssetDelete" />
          </div>
        </div>

        <!-- Social / SEO -->
        <div class="bg-card border border-border rounded-2xl p-5 space-y-4">
          <h3 class="text-sm font-semibold text-foreground">📤 Social &amp; SEO</h3>
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
      <div v-show="activeTab === 'components'" class="max-w-4xl space-y-4">
        <div class="bg-muted/50 border border-border rounded-xl px-4 py-3 text-sm text-muted-foreground">
          💡 Substitui completamente o header/footer/nav gerado pelo AnimusFlow. Usa variáveis Blade normais: <code class="bg-muted px-1 rounded text-xs">$layout</code>, <code class="bg-muted px-1 rounded text-xs">$segment</code>, <code class="bg-muted px-1 rounded text-xs">$page</code>.
        </div>

        <div v-for="comp in componentSlots" :key="comp.id"
          class="bg-card border border-border rounded-2xl overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-muted/50">
            <div>
              <span class="text-sm font-semibold text-foreground">{{ comp.icon }} {{ comp.label }}</span>
              <span class="text-xs text-muted-foreground ml-2">{{ comp.hint }}</span>
            </div>
            <span class="text-xs text-muted-foreground font-mono">{{ (form.components[comp.id]||'').length }} chars</span>
          </div>
          <textarea v-model="form.components[comp.id]" :rows="comp.rows" spellcheck="false"
            :placeholder="comp.placeholder"
            class="w-full px-4 py-3 bg-muted/30 text-xs font-mono focus:outline-none resize-y border-0" />
        </div>

        <btn-save @click="save" :saving="saving" label="Guardar Componentes" />
      </div>

      <!-- ════════════════════ TAB: Código ════════════════════ -->
      <div v-show="activeTab === 'code'" class="max-w-4xl space-y-4">

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
      <div v-show="activeTab === 'variants'" class="max-w-3xl space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="font-semibold text-foreground">🎨 Variantes de Cor (Skins)</h2>
            <p class="text-xs text-muted-foreground mt-0.5">Paletas alternativas para o mesmo tema — o utilizador pode escolher no painel AnimusFlow.</p>
          </div>
          <button @click="addVariant"
            class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border transition-colors flex items-center gap-1">
            <PlusIcon class="w-3.5 h-3.5" /> Adicionar variante
          </button>
        </div>

        <div v-if="!form.variants.length"
          class="bg-card border border-border border-dashed rounded-2xl p-10 text-center">
          <PaletteIcon class="w-8 h-8 text-muted-foreground opacity-30 mx-auto mb-3" />
          <p class="text-sm text-muted-foreground">Ainda não há variantes.</p>
          <p class="text-xs text-muted-foreground mt-1">As variantes permitem que o mesmo tema tenha múltiplos esquemas de cor.</p>
        </div>

        <div v-for="(variant, idx) in form.variants" :key="idx"
          class="bg-card border border-border rounded-2xl p-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="grid grid-cols-2 gap-3 flex-1 mr-4">
              <div>
                <label class="field-label">Nome interno</label>
                <input v-model="variant.name" placeholder="ocean-blue" class="field-input font-mono text-xs" />
              </div>
              <div>
                <label class="field-label">Label</label>
                <input v-model="variant.label" placeholder="Ocean Blue" class="field-input" />
              </div>
            </div>
            <!-- Swatch preview -->
            <div class="flex gap-1.5">
              <div v-for="(val, varKey) in (variant.colors?.light ?? {})" :key="varKey"
                v-if="['--color-primary','--color-background','--color-card'].includes(varKey)"
                class="w-6 h-6 rounded-full border border-border"
                :style="{ background: val }" :title="varKey" />
            </div>
            <button @click="form.variants.splice(idx, 1)"
              class="ml-3 text-xs text-destructive/60 hover:text-destructive px-2 py-1 rounded hover:bg-destructive/10">
              {{ t('common.delete') }}
            </button>
          </div>

          <!-- Simplified color pickers for variant -->
          <div class="grid grid-cols-2 gap-3">
            <div v-for="token in variantTokens" :key="token.var">
              <label class="text-[10px] font-mono text-muted-foreground block mb-1">{{ token.var }} (light)</label>
              <div class="flex gap-2 items-center">
                <input type="color"
                  :value="hexFallback(variant.colors?.light?.[token.var] || token.default)"
                  @input="e => setVariantColor(idx, 'light', token.var, e.target.value)"
                  class="w-8 h-7 rounded border border-border cursor-pointer bg-transparent p-0.5" />
                <input
                  :value="variant.colors?.light?.[token.var] || ''"
                  @input="e => setVariantColor(idx, 'light', token.var, e.target.value)"
                  :placeholder="token.default"
                  class="flex-1 px-2 py-1.5 bg-muted border border-border rounded-lg text-xs font-mono focus:outline-none focus:border-primary" />
              </div>
            </div>
          </div>
        </div>

        <btn-save v-if="form.variants.length" @click="save" :saving="saving" label="Guardar Variantes" />
      </div>

      <!-- ════════════════════ TAB: Preview ════════════════════ -->
      <div v-show="activeTab === 'preview'" class="space-y-3">
        <div class="flex items-center gap-3">
          <p class="text-sm text-muted-foreground flex-1">{{ t('themes.preview_hint') }}</p>
          <button @click="previewKey++"
            class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border flex items-center gap-1">
            <RefreshCwIcon class="w-3.5 h-3.5" /> {{ t('themes.reload_preview') }}
          </button>
          <a :href="`/preview/theme/${theme.uuid}`" target="_blank"
            class="px-3 py-1.5 bg-muted text-foreground rounded-lg text-xs font-semibold hover:bg-border flex items-center gap-1">
            <ExternalLinkIcon class="w-3.5 h-3.5" /> {{ t('themes.open_preview') }}
          </a>
        </div>
        <div class="bg-muted rounded-2xl overflow-hidden border border-border" style="height:72vh;">
          <iframe :key="previewKey" :src="`/preview/theme/${theme.uuid}`" class="w-full h-full border-0" />
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

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  EyeIcon, DownloadIcon, UploadIcon, SparklesIcon,
  CheckCircleIcon, XCircleIcon, PlusIcon, CodeIcon,
  RefreshCwIcon, ExternalLinkIcon, PaletteIcon,
} from 'lucide-vue-next';

const { t } = useI18n();
const props = defineProps({ theme: { type: Object, default: null } });

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
  { id: 'preview',      icon: '👁️',  label: 'Preview'     },
];

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

function save() {
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

// ── Components ────────────────────────────────────────────────────
const componentSlots = [
  {
    id: 'header', icon: '🔝', label: 'Header',
    hint: 'Override completo do cabeçalho',
    rows: 12,
    placeholder: `{{-- Header personalizado --}}\n<header class="af-header" style="...">\n  <nav>...</nav>\n</header>`,
  },
  {
    id: 'nav', icon: '📋', label: 'Navegação',
    hint: 'Menu de navegação (injectado no header)',
    rows: 10,
    placeholder: `{{-- Navegação --}}\n<ul class="af-nav">\n  @foreach($navLinks as $link)\n    <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>\n  @endforeach\n</ul>`,
  },
  {
    id: 'footer', icon: '🔻', label: 'Footer',
    hint: 'Override completo do rodapé',
    rows: 12,
    placeholder: `{{-- Footer personalizado --}}\n<footer class="af-footer" style="...">\n  <p>© {{ date('Y') }} {{ $siteName }}</p>\n</footer>`,
  },
];

// ── Variants ──────────────────────────────────────────────────────
const variantTokens = [
  { var: '--color-primary',    default: 'oklch(0.55 0.22 265)' },
  { var: '--color-background', default: 'oklch(0.99 0.003 265)' },
  { var: '--color-card',       default: 'oklch(1 0 0)' },
  { var: '--color-foreground', default: 'oklch(0.13 0.02 265)' },
];

function addVariant() {
  form.variants.push({ name: '', label: '', colors: { light: {}, dark: {} } });
}
function setVariantColor(idx, mode, varName, val) {
  if (!form.variants[idx].colors) form.variants[idx].colors = { light: {}, dark: {} };
  if (!form.variants[idx].colors[mode]) form.variants[idx].colors[mode] = {};
  form.variants[idx].colors[mode][varName] = val;
}

// ── Preview ───────────────────────────────────────────────────────
const previewKey = ref(0);

// ── Publish ───────────────────────────────────────────────────────
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
</style>
