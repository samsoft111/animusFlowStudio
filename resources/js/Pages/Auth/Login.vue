<template>
  <div class="login-page">

    <!-- Full-page grid overlay -->
    <div class="grid-overlay"></div>

    <!-- Orbs — scattered across the full page -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="orb orb-4"></div>

    <!-- Content wrapper -->
    <div class="relative z-10 min-h-screen flex">

      <!-- Left brand panel -->
      <div class="hidden lg:flex lg:w-5/12 flex-col px-12 py-14">

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
          <h1 class="font-black text-4xl leading-tight mb-5"
            style="background: linear-gradient(135deg, #a5b4fc, #e879f9, #38bdf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            Theme &amp;<br>Plugin<br>Builder
          </h1>
          <p class="text-sm leading-relaxed mb-10" style="color: rgba(255,255,255,0.50);">
            {{ t('auth.tagline') }}
          </p>

          <!-- Feature chips -->
          <div class="flex flex-wrap gap-2">
            <span v-for="chip in chips" :key="chip"
              class="px-3 py-1.5 rounded-full text-[11px] font-semibold"
              style="background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.65); border: 1px solid rgba(255,255,255,0.10);">
              {{ chip }}
            </span>
          </div>
        </div>

        <!-- Footer -->
        <p class="text-[11px]" style="color: rgba(255,255,255,0.25);">{{ t('auth.footer') }}</p>
      </div>

      <!-- Right form panel -->
      <div class="flex-1 flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">

          <!-- Mobile logo -->
          <div class="flex items-center gap-3 mb-10 lg:hidden">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
              style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
              <span class="text-white font-black text-sm">AF</span>
            </div>
            <span class="font-bold text-white text-base">AnimusFlowStudio</span>
          </div>

          <!-- Form card -->
          <div class="form-card">

            <h2 class="text-2xl font-bold text-white mb-1">{{ t('auth.sign_in') }}</h2>
            <p class="text-sm mb-8" style="color: rgba(255,255,255,0.45);">{{ t('auth.sign_in_subtitle') }}</p>

            <!-- Error -->
            <div v-if="form.errors.email || form.errors.password"
              class="flex items-start gap-2 px-4 py-3 rounded-xl text-sm mb-5"
              style="background: rgba(239,68,68,0.12); color: #fca5a5; border: 1px solid rgba(239,68,68,0.25);">
              <AlertCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
              <span>{{ form.errors.email || form.errors.password }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
              <div>
                <label class="text-xs font-semibold uppercase tracking-wider" style="color: rgba(255,255,255,0.45);">
                  {{ t('auth.email') }}
                </label>
                <input v-model="form.email" type="email" autocomplete="email" required
                  class="form-input"
                  :placeholder="t('auth.email')" />
              </div>

              <div>
                <label class="text-xs font-semibold uppercase tracking-wider" style="color: rgba(255,255,255,0.45);">
                  {{ t('auth.password') }}
                </label>
                <input v-model="form.password" type="password" autocomplete="current-password" required
                  class="form-input"
                  :placeholder="t('auth.password')" />
              </div>

              <div class="flex items-center gap-2">
                <input v-model="form.remember" type="checkbox" id="remember"
                  class="w-4 h-4 rounded accent-indigo-500" style="border-color: rgba(255,255,255,0.2);" />
                <label for="remember" class="text-sm cursor-pointer" style="color: rgba(255,255,255,0.50);">
                  {{ t('auth.remember') }}
                </label>
              </div>

              <button type="submit" :disabled="form.processing"
                class="w-full py-2.5 rounded-xl text-sm font-semibold transition-opacity mt-1"
                style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; opacity: 1;"
                :style="form.processing ? 'opacity: 0.5' : ''">
                {{ form.processing ? t('auth.signing_in') : t('auth.sign_in') }}
              </button>
            </form>
          </div>
        </div>
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
/* ── Page background ── */
.login-page {
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  background: linear-gradient(145deg, #070c18 0%, #0d1340 40%, #0a0620 70%, #070c18 100%);
}

/* ── Grid overlay ── */
.grid-overlay {
  position: absolute;
  inset: 0;
  opacity: 0.045;
  background-image:
    linear-gradient(rgba(255,255,255,0.6) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.6) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none;
}

/* ── Orbs ── */
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  pointer-events: none;
  animation: drift 14s ease-in-out infinite;
}
.orb-1 {
  width: 380px; height: 380px;
  background: radial-gradient(circle, rgba(99,102,241,0.30) 0%, transparent 70%);
  top: -100px; left: -80px;
  animation-delay: 0s;
}
.orb-2 {
  width: 300px; height: 300px;
  background: radial-gradient(circle, rgba(168,85,247,0.28) 0%, transparent 70%);
  bottom: -60px; right: -60px;
  animation-delay: -5s;
}
.orb-3 {
  width: 250px; height: 250px;
  background: radial-gradient(circle, rgba(56,189,248,0.22) 0%, transparent 70%);
  top: 50%; right: 25%;
  animation-delay: -9s;
}
.orb-4 {
  width: 200px; height: 200px;
  background: radial-gradient(circle, rgba(236,72,153,0.18) 0%, transparent 70%);
  bottom: 20%; left: 30%;
  animation-delay: -3s;
}

@keyframes drift {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33%       { transform: translate(25px, -30px) scale(1.06); }
  66%       { transform: translate(-18px, 18px) scale(0.96); }
}

/* ── Form card ── */
.form-card {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.10);
  border-radius: 1.5rem;
  padding: 2rem;
  backdrop-filter: blur(12px);
}

/* ── Form inputs ── */
.form-input {
  margin-top: 0.375rem;
  width: 100%;
  padding: 0.625rem 0.75rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 0.75rem;
  font-size: 0.875rem;
  color: #fff;
  outline: none;
  transition: border-color 0.2s;
}
.form-input::placeholder { color: rgba(255,255,255,0.28); }
.form-input:focus { border-color: rgba(99,102,241,0.70); }
</style>
