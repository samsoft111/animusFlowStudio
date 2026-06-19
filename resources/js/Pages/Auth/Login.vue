<template>
  <div class="login-page group">

    <!-- Screensaver Video Container -->
    <div class="screensaver-container">
      <video ref="videoRef" autoplay loop muted playsinline class="screensaver-video">
        <source :src="'/videos/AnimusFlowStudioFundo.mp4'" type="video/mp4">
      </video>
      <div class="info-panel">
        <div class="info-card">
          <h3 class="text-lg font-bold text-white mb-1">AnimusFlow Studio</h3>
          <p class="text-xs text-white/70">{{ t('auth.tagline') }}</p>
        </div>
      </div>
    </div>

    <!-- Full-page grid overlay -->
    <div class="grid-overlay"></div>

    <!-- Orbs — scattered across the full page -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="orb orb-4"></div>

    <!-- Content wrapper (Centered Layout) -->
    <div class="login-content relative z-20 min-h-screen flex flex-col items-center justify-center px-6 py-12">
      <div class="w-full max-w-md flex flex-col items-center">

        <!-- Centered Logo -->
        <div class="mb-8 transform transition-all duration-700 hover:scale-105">
          <img :src="'/images/logos/animusflowstudio-logo-white.png'"
               alt="AnimusFlowStudio" style="height:38px;width:auto;" />
        </div>

        <!-- Form card -->
        <div class="form-card w-full">

          <h2 class="text-2xl font-bold text-white mb-1 text-center">{{ t('auth.sign_in') }}</h2>
          <p class="text-sm mb-8 text-center" style="color: rgba(255,255,255,0.45);">{{ t('auth.sign_in_subtitle') }}</p>

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
              class="w-full py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 mt-1 hover:brightness-110 active:scale-[0.98] cursor-pointer"
              style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; opacity: 1; cursor: pointer;"
              :style="form.processing ? 'opacity: 0.5; pointer-events: none;' : ''">
              {{ form.processing ? t('auth.signing_in') : t('auth.sign_in') }}
            </button>
          </form>
        </div>


        <!-- Centered Footer -->
        <p class="text-[10px] mt-6 tracking-wide text-center" style="color: rgba(255,255,255,0.25);">
          {{ t('auth.footer') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { AlertCircleIcon } from 'lucide-vue-next';

const { t } = useI18n();

const videoRef = ref(null);

const form = useForm({
  email:    '',
  password: '',
  remember: false,
});

function submit() {
  form.post('/login');
}

function handleVisibilityChange() {
  if (!videoRef.value) return;
  if (document.hidden) {
    videoRef.value.pause();
  } else {
    videoRef.value.play().catch(() => {});
  }
}

onMounted(() => {
  document.addEventListener('visibilitychange', handleVisibilityChange);
});

onBeforeUnmount(() => {
  document.removeEventListener('visibilitychange', handleVisibilityChange);
});
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

/* ── Login Content (Form and Logo wrapper) ── */
.login-content {
  opacity: 0;
  pointer-events: none;
  transition: opacity 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.login-page.group:hover .login-content {
  opacity: 1;
  pointer-events: auto;
  transition: opacity 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Form card ── */
.form-card {
  background: rgba(10, 10, 20, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 1.75rem;
  padding: 2.25rem 2.5rem;
  backdrop-filter: blur(20px);
  box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
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

/* ── Screensaver Video & Hover Effects ── */
.screensaver-container {
  position: absolute;
  inset: 0;
  z-index: 30;
  opacity: 0.95;
  pointer-events: auto;
  transition: 
    opacity 1.5s cubic-bezier(0.4, 0, 0.2, 1),
    z-index 0s 1.5s step-end;
  overflow: hidden;
}

.screensaver-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: 
    filter 1.5s cubic-bezier(0.4, 0, 0.2, 1), 
    transform 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.info-panel {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.35);
  pointer-events: none;
  transition: 
    opacity 1.2s cubic-bezier(0.4, 0, 0.2, 1), 
    transform 1.2s cubic-bezier(0.4, 0, 0.2, 1);
  padding: 1.5rem;
}

.info-card {
  background: rgba(10, 10, 15, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 1.25rem;
  padding: 1.5rem 2rem;
  text-align: center;
  max-width: 320px;
  backdrop-filter: blur(12px);
  box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
  animation: pulse 2.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Hover States (controlled by .group) */
.login-page.group:hover .screensaver-container {
  z-index: 0;
  opacity: 0.15;
  pointer-events: none;
  transition: 
    opacity 1.5s cubic-bezier(0.4, 0, 0.2, 1),
    z-index 0s step-start;
}

.login-page.group:hover .screensaver-video {
  filter: blur(8px);
  transform: scale(0.95);
  transition: 
    filter 1.5s cubic-bezier(0.4, 0, 0.2, 1), 
    transform 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.login-page.group:hover .info-panel {
  opacity: 0;
  transform: scale(1.08);
  transition: 
    opacity 1.5s cubic-bezier(0.4, 0, 0.2, 1), 
    transform 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.75; transform: scale(0.97); }
}
</style>
