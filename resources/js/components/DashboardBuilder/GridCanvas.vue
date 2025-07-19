<template>
  <div 
    ref="canvasContainer"
    class="grid-canvas"
    :class="{ 'preview-mode': isPreview }"
    @drop="handleDrop"
    @dragover="handleDragOver"
  >
    <!-- Widgets -->
    <DashboardWidget
      v-for="widget in widgets"
      :key="widget.instanceId"
      :widget="widget"
      :is-editing="!isPreview"
      @update="$emit('widget-updated', widget, $event)"
      @delete="$emit('widget-deleted', widget)"
    />

    <!-- Drop Indicator -->
    <div
      v-if="showDropIndicator"
      class="drop-indicator"
      :style="dropIndicatorStyle"
    >
      Drop widget here
    </div>

    <!-- Empty State -->
    <div v-if="widgets.length === 0" class="empty-state">
      <div class="text-center text-gray-500">
        <div class="text-6xl mb-4">📊</div>
        <h3 class="text-lg font-medium mb-2">No widgets added yet</h3>
        <p>Drag widgets from the sidebar to get started</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import DashboardWidget from './DashboardWidget.vue'

const props = defineProps({
  widgets: Array,
  isPreview: Boolean
})

const emit = defineEmits(['drop', 'widget-updated', 'widget-deleted'])

// State
const canvasContainer = ref(null)
const showDropIndicator = ref(false)
const dropIndicatorStyle = ref({})

// Methods
const handleDrop = (event) => {
  event.preventDefault()
  showDropIndicator.value = false
  emit('drop', event)
}

const handleDragOver = (event) => {
  event.preventDefault()
  showDropIndicator.value = true
  
  // Calculate drop position
  const rect = canvasContainer.value.getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top
  
  dropIndicatorStyle.value = {
    left: `${x - 100}px`,
    top: `${y - 50}px`,
    width: '200px',
    height: '100px'
  }
}
</script>

<style scoped>
.grid-canvas {
  min-height: 600px;
  position: relative;
  background-image: 
    linear-gradient(rgba(0,0,0,0.1) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0,0,0,0.1) 1px, transparent 1px);
  background-size: 20px 20px;
}

.preview-mode {
  background-image: none;
}

.drop-indicator {
  position: absolute;
  border: 2px dashed #3B82F6;
  background: rgba(59, 130, 246, 0.1);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3B82F6;
  font-weight: 500;
  pointer-events: none;
}

.empty-state {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
</style>