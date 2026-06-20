<template>
  <Link
    :href="href"
    :title="title"
    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all"
    :class="[
      active
        ? 'bg-sidebar-active text-white font-semibold'
        : 'text-sidebar-muted hover:bg-sidebar-border hover:text-sidebar-foreground',
      collapsed ? 'justify-center px-2.5' : ''
    ]"
  >
    <slot />
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ href: String, active: Boolean, collapsed: Boolean, title: String });
</script>
