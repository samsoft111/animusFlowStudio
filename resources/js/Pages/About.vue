<template>
  <AppLayout :title="t('about.title')">
    <div class="space-y-6">

      <!-- Card Principal -->
      <div class="bg-card border border-border rounded-2xl overflow-hidden shadow-sm">
        <!-- Header com gradiente -->
        <div class="relative bg-gradient-to-br from-primary/90 via-primary/80 to-indigo-600 px-8 py-10 text-white overflow-hidden">
          <!-- Decoração de fundo -->
          <div class="absolute inset-0 opacity-10"
            style="background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px), radial-gradient(circle at 80% 20%, white 1px, transparent 1px); background-size: 40px 40px;" />
          <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-6">
            <!-- Avatar -->
            <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm border border-white/30 flex items-center justify-center shrink-0 shadow-lg">
              <span class="text-3xl font-black text-white">PS</span>
            </div>
            <div>
              <p class="text-sm font-semibold text-white/70 uppercase tracking-widest mb-1">{{ t('about.developer') }}</p>
              <h2 class="text-2xl font-black tracking-tight text-white">Pedro Salumu</h2>
              <p class="text-white/80 text-sm mt-1">Full-stack Developer &amp; AI Architect</p>
            </div>
          </div>
        </div>

        <!-- Contacts -->
        <div class="divide-y divide-border">
          <!-- Email -->
          <div class="flex items-center gap-4 px-6 sm:px-8 py-5 hover:bg-muted/40 transition-colors group">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
              <MailIcon class="w-5 h-5 text-primary" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-0.5">{{ t('about.email') }}</p>
              <a href="mailto:samsoft111@gmail.com"
                class="text-sm font-medium text-foreground hover:text-primary transition-colors truncate block">
                samsoft111@gmail.com
              </a>
            </div>
            <a href="mailto:samsoft111@gmail.com"
              class="opacity-0 group-hover:opacity-100 transition-opacity p-2 rounded-lg hover:bg-muted text-muted-foreground hover:text-primary">
              <ExternalLinkIcon class="w-4 h-4" />
            </a>
          </div>

          <!-- Phone -->
          <div class="flex items-center gap-4 px-6 sm:px-8 py-5 hover:bg-muted/40 transition-colors group">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center shrink-0">
              <PhoneIcon class="w-5 h-5 text-emerald-500" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-0.5">{{ t('about.phone') }}</p>
              <a href="tel:+244945379755"
                class="text-sm font-medium text-foreground hover:text-emerald-500 transition-colors truncate block">
                +244 945 37 97 55
              </a>
            </div>
            <a href="tel:+244945379755"
              class="opacity-0 group-hover:opacity-100 transition-opacity p-2 rounded-lg hover:bg-muted text-muted-foreground hover:text-emerald-500">
              <ExternalLinkIcon class="w-4 h-4" />
            </a>
          </div>
        </div>
      </div>

      <!-- Project card -->
      <div class="bg-card border border-border rounded-2xl p-6 shadow-sm">
        <div class="flex items-start gap-4 mb-5">
          <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0 p-2.5 shadow-inner">
            <img :src="'/images/logos/animusflowstudio-icon.png'"
                 alt="AnimusFlowStudio" class="w-full h-full object-contain" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
              <h3 class="font-bold text-foreground text-lg">AnimusFlowStudio</h3>
              <span class="px-2.5 py-0.5 bg-success/10 text-success rounded-full text-[10px] font-bold uppercase tracking-wider">
                v1.0.0
              </span>
            </div>
            <p class="text-xs text-muted-foreground mt-0.5">Plataforma do Criador AnimusFlow</p>
          </div>
        </div>

        <p class="text-sm text-muted-foreground leading-relaxed mb-6">
          {{ t('about.description') }}
        </p>

        <!-- Stats row -->
        <div class="grid grid-cols-3 gap-3">
          <div class="text-center p-3.5 bg-muted/50 rounded-xl border border-border/30 hover:border-primary/20 hover:bg-muted transition-all duration-300">
            <p class="text-2xl font-black text-primary">{{ stats.themes }}</p>
            <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider mt-1">
              {{ t('nav.themes') }}
            </p>
          </div>
          <div class="text-center p-3.5 bg-muted/50 rounded-xl border border-border/30 hover:border-primary/20 hover:bg-muted transition-all duration-300">
            <p class="text-2xl font-black text-primary">{{ stats.plugins }}</p>
            <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider mt-1">
              {{ t('nav.plugins') }}
            </p>
          </div>
          <div class="text-center p-3.5 bg-muted/50 rounded-xl border border-border/30 hover:border-primary/20 hover:bg-muted transition-all duration-300">
            <p class="text-2xl font-black text-primary">{{ stats.published }}</p>
            <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-wider mt-1">
              Publicados
            </p>
          </div>
        </div>
      </div>

      <!-- Open source -->
      <div class="bg-card border border-border rounded-2xl p-5 flex items-center gap-4 shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-muted flex items-center justify-center shrink-0">
          <ScaleIcon class="w-5 h-5 text-muted-foreground" />
        </div>
        <div>
          <p class="text-sm font-semibold text-foreground">{{ t('about.open_source') }}</p>
          <p class="text-xs text-muted-foreground">{{ t('about.license') }}</p>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { UserIcon, MailIcon, PhoneIcon, ScaleIcon, ExternalLinkIcon } from 'lucide-vue-next';

const { t } = useI18n();

defineProps({
  stats: { type: Object, default: () => ({ themes: 0, plugins: 0, published: 0 }) },
});
</script>
