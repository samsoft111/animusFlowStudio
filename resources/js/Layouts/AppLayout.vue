<template>
  <div class="flex h-screen bg-background text-foreground overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-60 flex-shrink-0 flex flex-col bg-sidebar text-sidebar-foreground border-r border-sidebar-border">
      <!-- Brand -->
      <div class="px-5 py-4 border-b border-sidebar-border">
        <img :src="isDark ? '/images/logos/animusflowstudio-logo-white.png' : '/images/logos/animusflowstudio-logo.png'"
             alt="AnimusFlowStudio"
             class="h-8 w-auto" />
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <SidebarLink href="/dashboard" :active="isActive('dashboard')">
          <LayoutDashboardIcon class="w-4 h-4" />
          {{ t('nav.dashboard') }}
        </SidebarLink>
        <div class="px-3 pt-4 pb-1">
          <p class="text-[10px] uppercase tracking-widest text-sidebar-muted font-semibold">{{ t('nav.create') }}</p>
        </div>
        <SidebarLink href="/themes" :active="isActive('themes')">
          <PaletteIcon class="w-4 h-4" />
          {{ t('nav.themes') }}
        </SidebarLink>
        <SidebarLink href="/plugins" :active="isActive('plugins')">
          <PuzzleIcon class="w-4 h-4" />
          {{ t('nav.plugins') }}
        </SidebarLink>
        <div class="px-3 pt-4 pb-1">
          <p class="text-[10px] uppercase tracking-widest text-sidebar-muted font-semibold">{{ t('nav.system') }}</p>
        </div>
        <SidebarLink href="/recipes" :active="isActive('recipes')">
          <ZapIcon class="w-4 h-4" />
          Receitas IA
        </SidebarLink>
        <SidebarLink href="/settings" :active="isActive('settings')">
          <SettingsIcon class="w-4 h-4" />
          {{ t('nav.settings') }}
        </SidebarLink>
        <SidebarLink href="/about" :active="isActive('about')">
          <InfoIcon class="w-4 h-4" />
          {{ t('nav.about') }}
        </SidebarLink>
      </nav>

      <!-- Version footer -->
      <div class="px-5 py-3 border-t border-sidebar-border">
        <p class="text-[11px] text-sidebar-muted">v1.0.0 — AnimusFlow v1+</p>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Topbar -->
      <header class="h-14 border-b border-border flex items-center justify-between px-6 bg-card shrink-0">
        <h1 class="font-semibold text-foreground text-sm">{{ title }}</h1>
        <div class="flex items-center gap-2">
          <slot name="actions" />

          <!-- Language switcher -->
          <div class="relative">
            <button @click="langOpen = !langOpen"
              class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-muted-foreground hover:bg-muted transition-colors cursor-pointer">
              <GlobeIcon class="w-3.5 h-3.5" />
              <span class="uppercase">{{ locale }}</span>
            </button>
            <div v-if="langOpen"
              class="absolute right-0 top-full mt-1 w-28 bg-card border border-border rounded-xl shadow-lg z-50 py-1 overflow-hidden">
              <button v-for="lang in languages" :key="lang.code"
                @click="setLocale(lang.code)"
                class="w-full flex items-center gap-2 px-3 py-2 text-xs hover:bg-muted transition-colors cursor-pointer"
                :class="locale === lang.code ? 'text-primary font-semibold' : 'text-foreground'">
                <span>{{ lang.flag }}</span>
                <span>{{ lang.label }}</span>
              </button>
            </div>
          </div>

          <!-- Theme toggle -->
          <button @click="toggleTheme" :title="isDark ? t('topbar.light_mode') : t('topbar.dark_mode')"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-muted transition-colors cursor-pointer">
            <MoonIcon v-if="!isDark" class="w-4 h-4" />
            <SunIcon v-else class="w-4 h-4" />
          </button>

          <!-- Logout -->
          <button @click="logout"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-muted transition-colors cursor-pointer"
            :title="t('topbar.logout')">
            <LogOutIcon class="w-4 h-4" />
          </button>
        </div>
      </header>

      <!-- Flash messages -->
      <div v-if="flash.success || flash.error" class="px-6 pt-4">
        <div v-if="flash.success"
          class="flex items-center gap-2 px-4 py-3 bg-success/10 text-success border border-success/20 rounded-xl text-sm font-medium">
          <CheckCircleIcon class="w-4 h-4 shrink-0" />
          {{ flash.success }}
        </div>
        <div v-if="flash.error"
          class="flex items-center gap-2 px-4 py-3 bg-destructive/10 text-destructive border border-destructive/20 rounded-xl text-sm font-medium">
          <XCircleIcon class="w-4 h-4 shrink-0" />
          {{ flash.error }}
        </div>
      </div>

      <!-- Content -->
      <main class="flex-1 overflow-y-auto p-6 relative">
        <!-- Optional Background Video for Dashboard & About -->
        <div v-if="showVideoBg" class="absolute inset-0 z-0 overflow-hidden pointer-events-none opacity-[0.06] transition-opacity duration-500">
          <video ref="videoRef" autoplay loop muted playsinline class="w-full h-full object-cover">
            <source :src="'/videos/AnimusFlowStudioFundo.mp4'" type="video/mp4">
          </video>
        </div>

        <div class="relative z-10">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SidebarLink from '@/Components/SidebarLink.vue';
import {
  LayoutDashboardIcon, PaletteIcon, PuzzleIcon,
  SettingsIcon, CheckCircleIcon, XCircleIcon,
  MoonIcon, SunIcon, GlobeIcon, LogOutIcon, InfoIcon, ZapIcon,
} from 'lucide-vue-next';

defineProps({ title: { type: String, default: '' } });

const { t, locale } = useI18n();

const videoRef = ref(null);

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});
const showVideoBg = computed(() => page.url === '/dashboard' || page.url === '/about');

/* ── Dark / light theme ── */
const isDark = ref((localStorage.getItem('theme') ?? 'dark') !== 'light');

function toggleTheme() {
  isDark.value = !isDark.value;
  const theme = isDark.value ? 'dark' : 'light';
  localStorage.setItem('theme', theme);
  document.documentElement.setAttribute('data-theme', isDark.value ? 'dark' : '');
}

/* ── Language switcher ── */
const langOpen = ref(false);
const languages = [
  { code: 'pt', label: 'Português', flag: '🇵🇹' },
  { code: 'en', label: 'English',   flag: '🇬🇧' },
];

function setLocale(code) {
  locale.value = code;
  localStorage.setItem('locale', code);
  langOpen.value = false;
}

function closeLang(e) {
  if (!e.target.closest('.relative')) langOpen.value = false;
}

function handleVisibilityChange() {
  if (!videoRef.value) return;
  if (document.hidden) {
    videoRef.value.pause();
  } else {
    if (showVideoBg.value) {
      videoRef.value.play().catch(() => {});
    }
  }
}

onMounted(() => {
  document.addEventListener('click', closeLang);
  document.addEventListener('visibilitychange', handleVisibilityChange);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeLang);
  document.removeEventListener('visibilitychange', handleVisibilityChange);
});

/* ── Navigation helpers ── */
function isActive(segment) {
  return window.location.pathname.startsWith('/' + segment);
}

/* ── Logout ── */
function logout() {
  router.post('/logout');
}
</script>
