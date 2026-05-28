<template>
  <div class="flex h-screen bg-background text-foreground overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-60 flex-shrink-0 flex flex-col bg-sidebar text-sidebar-foreground border-r border-sidebar-border">
      <!-- Brand -->
      <div class="px-5 py-5 border-b border-sidebar-border">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center shrink-0">
            <span class="text-white font-black text-sm">AF</span>
          </div>
          <div>
            <p class="font-bold text-sidebar-foreground text-sm leading-tight">AnimusFlowStudio</p>
            <p class="text-[11px] text-sidebar-muted">Theme & Plugin Builder</p>
          </div>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <SidebarLink :href="route('dashboard')" :active="isActive('dashboard')">
          <LayoutDashboardIcon class="w-4 h-4" />
          Dashboard
        </SidebarLink>
        <div class="px-3 pt-4 pb-1">
          <p class="text-[10px] uppercase tracking-widest text-sidebar-muted font-semibold">Create</p>
        </div>
        <SidebarLink :href="route('themes.index')" :active="isActive('themes')">
          <PaletteIcon class="w-4 h-4" />
          Themes
        </SidebarLink>
        <SidebarLink :href="route('plugins.index')" :active="isActive('plugins')">
          <PuzzleIcon class="w-4 h-4" />
          Plugins
        </SidebarLink>
        <div class="px-3 pt-4 pb-1">
          <p class="text-[10px] uppercase tracking-widest text-sidebar-muted font-semibold">System</p>
        </div>
        <SidebarLink :href="route('settings.index')" :active="isActive('settings')">
          <SettingsIcon class="w-4 h-4" />
          Settings
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
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SidebarLink from '@/Components/SidebarLink.vue';
import {
  LayoutDashboardIcon, PaletteIcon, PuzzleIcon,
  SettingsIcon, CheckCircleIcon, XCircleIcon,
} from 'lucide-vue-next';

const props = defineProps({ title: { type: String, default: '' } });

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

function isActive(segment) {
  return window.location.pathname.startsWith('/' + segment);
}

function route(name) {
  const map = {
    'dashboard':    '/dashboard',
    'themes.index': '/themes',
    'plugins.index':'/plugins',
    'settings.index':'/settings',
  };
  return map[name] ?? '/';
}
</script>
