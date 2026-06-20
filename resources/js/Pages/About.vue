<template>
  <AppLayout :title="t('about.title')">
    <div class="space-y-5">

      <!-- Developer card -->
      <div class="bg-card border border-border rounded-2xl p-6">
        <h2 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-4">
          {{ t('about.developer') }}
        </h2>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0">
            <UserIcon class="w-7 h-7 text-primary" />
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-bold text-foreground text-base">Pedro Salumu</h3>
            <p class="text-sm text-muted-foreground">Full-stack Developer &amp; AI Architect</p>
            <div class="flex flex-wrap gap-3 mt-3">
              <a href="mailto:samsoft111@gmail.com"
                class="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary transition-colors">
                <MailIcon class="w-3.5 h-3.5" />
                samsoft111@gmail.com
              </a>
              <a href="tel:+244945379755"
                class="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary transition-colors">
                <PhoneIcon class="w-3.5 h-3.5" />
                +244 945 37 97 55
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Project card -->
      <div class="bg-card border border-border rounded-2xl p-6">
        <h2 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-4">
          {{ t('about.project') }}
        </h2>
        <div class="flex items-start gap-4">
          <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0 p-2">
            <img :src="'/images/logos/animusflowstudio-icon.png'"
                 alt="AnimusFlowStudio" class="w-full h-full object-contain" />
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-1">
              <h3 class="font-bold text-foreground text-base">AnimusFlowStudio</h3>
              <span class="px-2 py-0.5 bg-success/15 text-success rounded-full text-[10px] font-semibold">
                v1.0.0
              </span>
            </div>
            <p class="text-sm text-muted-foreground mb-4">{{ t('about.description') }}</p>

            <!-- Stats row -->
            <div class="grid grid-cols-3 gap-3">
              <div class="bg-muted rounded-xl p-3 text-center">
                <p class="text-lg font-black text-primary">{{ stats.themes }}</p>
                <p class="text-[10px] text-muted-foreground font-semibold uppercase tracking-wider mt-0.5">
                  {{ t('nav.themes') }}
                </p>
              </div>
              <div class="bg-muted rounded-xl p-3 text-center">
                <p class="text-lg font-black text-primary">{{ stats.plugins }}</p>
                <p class="text-[10px] text-muted-foreground font-semibold uppercase tracking-wider mt-0.5">
                  {{ t('nav.plugins') }}
                </p>
              </div>
              <div class="bg-muted rounded-xl p-3 text-center">
                <p class="text-lg font-black text-primary">{{ stats.published }}</p>
                <p class="text-[10px] text-muted-foreground font-semibold uppercase tracking-wider mt-0.5">
                  {{ t('dashboard.published') }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- Open source -->
      <div class="bg-card border border-border rounded-2xl p-5 flex items-center gap-4">
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
import { UserIcon, MailIcon, PhoneIcon, ScaleIcon } from 'lucide-vue-next';

const { t } = useI18n();

const props = defineProps({
  stats: { type: Object, default: () => ({ themes: 0, plugins: 0, published: 0 }) },
});


</script>
