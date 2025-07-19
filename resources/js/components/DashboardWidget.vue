<template>
  <div class="dashboard-widget" 
       :class="widgetClasses"
       :style="widgetStyles"
       @mouseenter="showControls = true"
       @mouseleave="showControls = false">
    
    <!-- Widget Header (Edit Mode) -->
    <div v-if="isEditing" class="widget-header">
      <div class="flex items-center justify-between">
        <h3 class="font-medium truncate">{{ widget.name }}</h3>
        <div class="widget-controls" v-show="showControls">
          <button @click="openSettings" class="control-btn">
            <CogIcon class="w-4 h-4" />
          </button>
          <button @click="duplicateWidget" class="control-btn">
            <DocumentDuplicateIcon class="w-4 h-4" />
          </button>
          <button @click="deleteWidget" class="control-btn text-red-500">
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Widget Content -->
    <div class="widget-content" :class="{ 'full-height': !isEditing }">
      <Suspense>
        <component 
          :is="widgetComponent" 
          :data="widget.data"
          :config="widget.config"
          :is-preview="!isEditing"
          @update:config="updateConfig"
        />
        <template #fallback>
          <div class="loading-widget">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          </div>
        </template>
      </Suspense>
    </div>

    <!-- Resize Handles (Edit Mode) -->
    <div v-if="isEditing" class="resize-handles">
      <div class="resize-handle resize-handle-se"></div>
      <div class="resize-handle resize-handle-sw"></div>
      <div class="resize-handle resize-handle-ne"></div>
      <div class="resize-handle resize-handle-nw"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, defineAsyncComponent } from 'vue'
import { CogIcon, DocumentDuplicateIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  widget: Object,
  isEditing: Boolean
})

const emit = defineEmits(['update', 'delete', 'settings', 'duplicate'])

// State
const showControls = ref(false)

// Dynamic widget component loading
const widgetComponent = computed(() => {
  return defineAsyncComponent(() => import(`./widgets/${props.widget.type}.vue`))
})

const widgetClasses = computed(() => ({
  'editing-mode': props.isEditing,
  'preview-mode': !props.isEditing,
  [`widget-${props.widget.type}`]: true
}))

const widgetStyles = computed(() => ({
  gridColumn: `span ${props.widget.width}`,
  gridRow: `span ${props.widget.height}`,
  '--widget-color': props.widget.config?.color || '#3B82F6'
}))
</script>