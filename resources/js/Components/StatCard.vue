<template>
  <component
    :is="href ? Link : 'div'"
    :href="href"
    class="bg-card border border-border rounded-2xl p-5 block transition-all"
    :class="[
      href ? 'hover:border-primary/40 hover:shadow-sm cursor-pointer group' : ''
    ]"
  >
    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mb-1.5 transition-colors" :class="href ? 'group-hover:text-foreground' : ''">{{ label }}</p>
    <div class="flex items-baseline justify-between">
      <p class="text-3xl font-black transition-all duration-300" :class="[valueColor, href ? 'group-hover:translate-x-0.5' : '']">{{ value }}</p>
      <span v-if="href" class="text-muted-foreground/0 group-hover:text-primary/70 group-hover:translate-x-0.5 transition-all text-sm font-bold">&rarr;</span>
    </div>
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
const props = defineProps({ label: String, value: [Number, String], color: { type: String, default: 'primary' }, href: String });
const valueColor = computed(() => ({
  primary: 'text-primary',
  success: 'text-success',
  warning: 'text-warning',
}[props.color] ?? 'text-primary'));
</script>
