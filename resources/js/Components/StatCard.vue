<template>
  <div class="bg-card border border-border rounded-2xl p-5">
    <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">{{ label }}</p>
    <p class="text-3xl font-black" :class="valueColor">{{ value }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({ label: String, value: [Number, String], color: { type: String, default: 'primary' } });
const valueColor = computed(() => ({
  primary: 'text-primary',
  success: 'text-success',
  warning: 'text-warning',
}[props.color] ?? 'text-primary'));
</script>
