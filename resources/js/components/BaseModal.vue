<template>
  <Teleport to="body">
    <Transition name="fade">
      <div 
        v-if="modelValue" 
        class="fixed inset-0 bg-black/50 flex justify-center items-center z-[1000] backdrop-blur-[4px] p-4"
        @click.self="handleOverlayClick"
      >
        <div 
          :class="['bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.12)] text-black relative flex flex-col', sizeClass]"
          @click.stop
        >
          <!-- Close Button -->
          <button 
            v-if="showClose"
            class="absolute top-4 right-4 w-9 h-9 rounded-full border-none bg-transparent text-2xl leading-none cursor-pointer text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors z-10" 
            @click="close"
          >
            &times;
          </button>

          <!-- Header -->
          <div v-if="title || $slots.header" class="p-6 pb-0">
            <slot name="header">
              <h3 class="modal-title mb-0">{{ title }}</h3>
            </slot>
          </div>

          <!-- Body -->
          <div class="p-6 overflow-y-auto max-h-[80vh]">
            <slot></slot>
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="p-6 pt-0 flex justify-end gap-3">
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg, xl, full
    validator: (value) => ['sm', 'md', 'lg', 'xl', 'full'].includes(value)
  },
  showClose: {
    type: Boolean,
    default: true
  },
  closeOnOverlay: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['update:modelValue', 'close']);

const sizeClass = computed(() => {
  const sizes = {
    sm: 'w-full max-w-sm',
    md: 'w-full max-w-md',
    lg: 'w-full max-w-2xl',
    xl: 'w-full max-w-4xl',
    full: 'w-[95%] max-w-none h-[90vh]'
  };
  return sizes[props.size] || sizes.md;
});

const close = () => {
  emit('update:modelValue', false);
  emit('close');
};

const handleOverlayClick = () => {
  if (props.closeOnOverlay) {
    close();
  }
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-enter-active .modal-content,
.fade-leave-active .modal-content {
  transition: transform 0.3s ease;
}

.fade-enter-from .modal-content {
  transform: scale(0.9);
}

.fade-leave-to .modal-content {
  transform: scale(0.9);
}
</style>
