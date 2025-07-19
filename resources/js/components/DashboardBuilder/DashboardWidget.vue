<template>
  <div
    class="dashboard-widget"
    :class="{ 'editing-mode': isEditing }"
    :style="widgetStyles"
    @click="selectWidget"
  >
    <!-- Widget Header (Edit Mode) -->
    <div v-if="isEditing" class="widget-header">
      <span class="widget-title">{{ widget.name }}</span>
      <div class="widget-controls">
        <button @click.stop="$emit('delete')" class="delete-btn">×</button>
      </div>
    </div>

    <!-- Widget Content -->
    <div class="widget-content">
      <component
        :is="widgetComponent"
        :data="widget.data"
        :config="widget.config"
        :is-preview="!isEditing"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue'

const props = defineProps({
  widget: Object,
  isEditing: Boolean
})

const emit = defineEmits(['update', 'delete', 'select'])

// Dynamic component loading
const widgetComponent = computed(() => {
  try {
    return defineAsyncComponent(() => 
      import(`../widgets/${props.widget.component}.vue`)
    )
  } catch (error) {
    console.error(`Failed to load widget: ${props.widget.component}`)
    return null
  }
})

const widgetStyles = computed(() => ({
  position: 'absolute',
  left: `${props.widget.grid_x * 100}px`,
  top: `${props.widget.grid_y * 100}px`,
  width: `${props.widget.grid_w * 100}px`,
  height: `${props.widget.grid_h * 100}px`,
  zIndex: 1
}))

const selectWidget = () => {
  emit('select', props.widget)
}
</script>

<style scoped>
.dashboard-widget {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.2s ease;
}

.editing-mode:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border-color: #3B82F6;
}

.widget-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.widget-title {
  font-weight: 600;
  font-size: 14px;
  color: #374151;
}

.widget-controls {
  display: flex;
  gap: 4px;
}

.delete-btn {
  width: 20px;
  height: 20px;
  border: none;
  background: #ef4444;
  color: white;
  border-radius: 50%;
  cursor: pointer;
  font-size: 16px;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.delete-btn:hover {
  background: #dc2626;
}

.widget-content {
  padding: 16px;
  height: calc(100% - 40px);
}

.editing-mode .widget-content {
  height: calc(100% - 40px);
}

:not(.editing-mode) .widget-content {
  height: 100%;
}
</style>