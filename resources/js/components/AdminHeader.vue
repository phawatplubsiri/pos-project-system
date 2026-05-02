<template>
  <header class="bg-[var(--color-primary)] py-5 px-10 flex items-center gap-6 shadow-[var(--shadow-md)] min-h-[100px] box-border mb-0">
    <!-- Back Button -->
    <router-link 
      v-if="backRoute"
      :to="backRoute"
      class="w-10 h-10 rounded-full bg-white/20 border-none text-white cursor-pointer flex items-center justify-center transition-all duration-300 hover:bg-white/30 hover:scale-105" 
    >
      <ArrowLeft :size="24" />
    </router-link>
    
    <!-- Title Section -->
    <div>
      <h1 class="text-[28px] font-bold text-[var(--color-highlight-light)] m-0 mb-1">{{ title }}</h1>
      <p v-if="subtitle" class="text-sm text-white/80 m-0">{{ subtitle }}</p>
    </div>

    <!-- Right Slot (for extra buttons/actions) -->
    <div class="ml-auto">
      <slot name="actions"></slot>
    </div>
  </header>
</template>

<script setup>
import { ArrowLeft } from 'lucide-vue-next';

defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  },
  backRoute: {
    type: String,
    default: '/admin/dashboard'
  }
});
</script>
