<template>
  <div class="min-h-screen flex">

    <!-- Left brand panel -->
    <div class="hidden lg:flex lg:w-5/12 flex-col relative overflow-hidden"
      style="background: linear-gradient(145deg, #0d1340, #130b35, #0a0620);">

      <!-- Grid pattern -->
      <div class="absolute inset-0 opacity-[0.06]"
        style="background-image: linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px); background-size: 40px 40px;">
      </div>

      <!-- Animated orbs -->
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
      <div class="orb orb-3"></div>

      <!-- Content -->
      <div class="relative z-10 flex flex-col h-full px-10 py-12">
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <span class="text-white font-black text-sm">AF</span>
          </div>
          <span class="font-bold text-white text-base">AnimusFlowStudio</span>
        </div>

        <!-- Center text -->
        <div class="flex-1 flex flex-col justify-center">
          <h1 class="font-black text-3xl leading-tight mb-4"
            style="background: linear-gradient(135deg, #a5b4fc, #e879f9, #38bdf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            Theme &amp;<br>Plugin Builder
          </h1>
          <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.55);">
            {{ t('auth.tagline') }}
          </p>

          <!-- Feature chips -->
          <div class="flex flex-wrap gap-2 mt-8">
            <span v-for="chip in chips" :key="chip"
              class="px-3 py-1.5 rounded-full text-[11px] font-semibold"
              style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.12);">
              {{ chip }}
            </span>
          </div>
        </div>

        <!-- Footer -->
        <p class="text-[11px]" style="color: rgba(255,255,255,0.3);">{{ t('auth.footer') }}</p>
      </div>
    </div>

    <!-- Right form panel -->
    <div class="flex-1 flex items-center justify-center bg-background px-6 py-12">
      <div class="w-full max-w-sm">

        <!-- Mobile logo -->
        <div class="flex items-center gap-3 mb-8 lg:hidden">
          <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
            <span class="text-white font-black text-sm">AF</span>
          </div>
          <span class="font-bold text-foreground">AnimusFlowStudio</span>
        </div>

        <h2 class="text-2xl font-bold text-foreground mb-1">{{ t('auth.sign_in') }}</h2>
        <p class="text-sm text-muted-foreground mb-8">{{ t('auth.sign_in_subtitle') }}</p>

        <!-- Error -->
        <div v-if="form.errors.email || form.errors.password"
          class="flex items-start gap-2 px-4 py-3 bg-destructive/10 text-destructive border border-destructive/20 rounded-xl text-sm mb-5">
          <AlertCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
          <span>{{ form.errors.email || form.errors.password }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              {{ t('auth.email') }}
            </label>
            <input v-model="form.email" type="email" autocomplete="email" required
              class="mt-1.5 w-full px-3 py-2.5 bg-muted border border-border rounded-xl text-sm focus:outline-none focus:border-primary transition-colors"
              :placeholder="t('auth.email')" />
          </div>

          <div>
            <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              {{ t('auth.password') }}
            </label>
            <input v-model="form.password" type="password" autocomplete="current-password" required
              class="mt-1.5 w-full px-3 py-2.5 bg-muted border border-border rounded-xl text-sm focus:outline-none focus:border-primary transition-colors"
              :placeholder="t('auth.password')" />
          </div>

          <div class="flex items-center gap-2">
            <input v-model="form.remember" type="checkbox" id="remember"
              class="w-4 h-4 rounded border-border accent-primary" />
            <label for="remember" class="text-sm text-muted-foreground cursor-pointer">
              {{ t('auth.remember') }}
            </label>
          </div>

          <button type="submit" :disabled="form.processing"
            class="w-full py-2.5 bg-primary text-primary-foreground rounded-xl text-sm font-semibold disabled:opacity-50 hover:opacity-90 transition-opacity mt-2">
            {{ form.processing ? t('auth.signing_in') : t('auth.sign_in') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { AlertCircleIcon } from 'lucide-vue-next';

const { t } = useI18n();

const chips = ['Vue 3', 'Inertia.js', 'Laravel 12', 'Tailwind v4', 'AI-powered'];

const form = useForm({
  email:    '',
  password: '',
  remember: false,
});

function submit() {
  form.post('/login');
}
</script>

<style scoped>
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  animation: drift 12s ease-in-out infinite;
}
.orb-1 {
  width: 280px; height: 280px;
  background: radial-gradient(circle, rgba(99,102,241,0.35) 0%, transparent 70%);
  top: -60px; left: -60px;
  animation-delay: 0s;
}
.orb-2 {
  width: 220px; height: 220px;
  background: radial-gradient(circle, rgba(168,85,247,0.30) 0%, transparent 70%);
  bottom: 80px; right: -40px;
  animation-delay: -4s;
}
.orb-3 {
  width: 160px; height: 160px;
  background: radial-gradient(circle, rgba(56,189,248,0.25) 0%, transparent 70%);
  top: 50%; left: 50%; transform: translate(-50%, -50%);
  animation-delay: -8s;
}
@keyframes drift {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33%       { transform: translate(20px, -25px) scale(1.05); }
  66%       { transform: translate(-15px, 15px) scale(0.97); }
}
.orb-3 {
  animation-name: drift3;
}
@keyframes drift3 {
  0%, 100% { transform: translate(-50%, -50%) scale(1); }
  33%       { transform: translate(calc(-50% + 18px), calc(-50% - 20px)) scale(1.08); }
  66%       { transform: translate(calc(-50% - 12px), calc(-50% + 12px)) scale(0.95); }
}
</style>
