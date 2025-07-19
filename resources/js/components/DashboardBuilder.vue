<template>
  <div class="dashboard-builder h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <h1 class="text-2xl font-bold text-gray-900">Dashboard Builder</h1>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">Editing for:</span>
            <select v-model="selectedRole" @change="loadDashboard" 
                    class="rounded-md border-gray-300 shadow-sm">
              <option v-for="role in roles" :key="role.id" :value="role">
                {{ role.name }}
              </option>
            </select>
          </div>
        </div>
        
        <div class="flex items-center space-x-3">
          <button @click="togglePreview" 
                  class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
            {{ isPreview ? 'Edit Mode' : 'Preview' }}
          </button>
          <button @click="saveDashboard" :disabled="!hasChanges"
                  class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 disabled:opacity-50">
            Save Layout
          </button>
          <button @click="exportDashboard"
                  class="px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600">
            Export
          </button>
        </div>
      </div>
    </header>

    <div class="flex h-full">
      <!-- Widget Sidebar -->
      <aside v-show="!isPreview" class="w-80 bg-white shadow-lg border-r overflow-y-auto">
        <div class="p-4">
          <h2 class="text-lg font-semibold mb-4">Widget Library</h2>
          
          <!-- Widget Categories -->
          <div class="space-y-4">
            <div v-for="category in widgetCategories" :key="category.name">
              <h3 class="text-sm font-medium text-gray-700 mb-2">{{ category.name }}</h3>
              <div class="grid grid-cols-2 gap-2">
                <div v-for="widget in category.widgets" :key="widget.id"
                     :draggable="true" @dragstart="handleDragStart(widget)"
                     class="widget-item p-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 cursor-grab active:cursor-grabbing transition-colors">
                  <div class="flex flex-col items-center text-center">
                    <component :is="widget.icon" class="w-6 h-6 text-gray-600 mb-1" />
                    <span class="text-xs font-medium">{{ widget.name }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Custom Widget Creator -->
          <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-medium mb-2">Create Custom Widget</h3>
            <button @click="openCustomWidgetModal" 
                    class="w-full px-3 py-2 bg-indigo-500 text-white rounded-md hover:bg-indigo-600">
              + New Widget
            </button>
          </div>
        </div>
      </aside>

      <!-- Main Canvas -->
      <main class="flex-1 p-6">
        <div class="bg-white rounded-lg shadow-sm border h-full">
          <!-- Grid Container -->
          <div ref="gridContainer" 
               class="dashboard-grid h-full p-4"
               :class="{ 'preview-mode': isPreview }"
               @drop="handleDrop" @dragover="handleDragOver">
            
            <!-- Widget Instances -->
            <DashboardWidget
              v-for="widget in dashboardWidgets"
              :key="widget.instanceId"
              :widget="widget"
              :is-editing="!isPreview"
              @update="updateWidget"
              @delete="deleteWidget"
              @resize="handleResize"
              @move="handleMove"
            />
            
            <!-- Drop Zone Indicator -->
            <div v-if="showDropZone && !isPreview" 
                 class="drop-zone absolute border-2 border-dashed border-blue-400 bg-blue-50 rounded-lg"
                 :style="dropZoneStyle">
              <div class="flex items-center justify-center h-full text-blue-600">
                Drop widget here
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Modals -->
    <CustomWidgetModal v-if="showCustomWidgetModal" @close="showCustomWidgetModal = false" />
    <WidgetSettingsModal v-if="selectedWidget" :widget="selectedWidget" @close="selectedWidget = null" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useGridStack } from '@/composables/useGridStack'
import { useDashboardStore } from '@/stores/dashboard'
import DashboardWidget from './DashboardWidget.vue'
import CustomWidgetModal from './modals/CustomWidgetModal.vue'
import WidgetSettingsModal from './modals/WidgetSettingsModal.vue'

// Reactive state
const isPreview = ref(false)
const selectedRole = ref(null)
const showDropZone = ref(false)
const dropZoneStyle = ref({})
const selectedWidget = ref(null)
const showCustomWidgetModal = ref(false)

// Store
const dashboardStore = useDashboardStore()

// Computed
const roles = computed(() => dashboardStore.roles)
const widgetCategories = computed(() => dashboardStore.widgetCategories)
const dashboardWidgets = computed(() => dashboardStore.dashboardWidgets)
const hasChanges = computed(() => dashboardStore.hasUnsavedChanges)

// Grid system
const { gridContainer, initializeGrid, addWidget, updateLayout } = useGridStack()

// Methods
const togglePreview = () => {
  isPreview.value = !isPreview.value
  if (isPreview.value) {
    // Switch to preview mode - disable editing
    // Refresh widget data
    dashboardStore.refreshWidgetData()
  }
}

const loadDashboard = async () => {
  if (!selectedRole.value) return
  await dashboardStore.loadDashboard(selectedRole.value.id)
}

const saveDashboard = async () => {
  await dashboardStore.saveDashboard()
}

const exportDashboard = () => {
  dashboardStore.exportDashboard()
}

const handleDragStart = (widget) => {
  // Store widget data for drop handling
  dashboardStore.setDraggingWidget(widget)
}

const handleDrop = (event) => {
  event.preventDefault()
  const widget = dashboardStore.draggingWidget
  if (!widget) return

  // Calculate drop position
  const rect = gridContainer.value.getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top
  
  // Add widget to dashboard
  addWidget(widget, { x, y })
  showDropZone.value = false
}

const handleDragOver = (event) => {
  event.preventDefault()
  // Show drop zone indicator
  showDropZone.value = true
  // Update drop zone position
  // Implementation details...
}

onMounted(() => {
  initializeGrid()
})
</script>