<template>
  <Link
    :href="href"
    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors"
    :class="active
      ? 'bg-sidebar-active text-white font-semibold'
      : 'text-sidebar-muted hover:bg-sidebar-border hover:text-sidebar-foreground'"
  >
    <slot />
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ href: String, active: Boolean });
</script>
