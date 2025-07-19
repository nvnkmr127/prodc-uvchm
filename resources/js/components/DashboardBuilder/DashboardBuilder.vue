<template>
  <div class="dashboard-builder min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b px-6 py-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Builder</h1>
        
        <div class="flex items-center space-x-4">
          <!-- Role Selector -->
          <select v-model="selectedRole" @change="loadDashboard" class="rounded-md border-gray-300">
            <option value="">Select Role</option>
            <option v-for="role in roles" :key="role.id" :value="role">
              {{ role.name }}
            </option>
          </select>
          
          <!-- Actions -->
          <button @click="togglePreview" :class="['btn', isPreview ? 'btn-secondary' : 'btn-primary']">
            {{ isPreview ? 'Edit Mode' : 'Preview' }}
          </button>
          
          <button @click="saveDashboard" :disabled="!hasChanges" class="btn btn-success">
            Save Layout
          </button>
        </div>
      </div>
    </header>

    <div class="flex h-full">
      <!-- Widget Sidebar -->
      <aside v-show="!isPreview" class="w-80 bg-white shadow-lg border-r">
        <div class="p-4">
          <h2 class="text-lg font-semibold mb-4">Widget Library</h2>
          
          <div v-for="category in widgetCategories" :key="category.id" class="mb-6">
            <h3 class="text-sm font-medium text-gray-700 mb-2">{{ category.name }}</h3>
            <div class="grid grid-cols-2 gap-2">
              <div
                v-for="widget in category.widgets"
                :key="widget.id"
                :draggable="true"
                @dragstart="handleDragStart(widget)"
                class="widget-item p-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 cursor-grab"
              >
                <div class="text-center">
                  <div class="text-2xl mb-1">📊</div>
                  <span class="text-xs font-medium">{{ widget.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Canvas -->
      <main class="flex-1 p-6">
        <div class="bg-white rounded-lg shadow-sm border h-full">
          <div class="p-4">
            <GridCanvas
              :widgets="dashboardWidgets"
              :is-preview="isPreview"
              @drop="handleDrop"
              @widget-updated="updateWidget"
              @widget-deleted="deleteWidget"
            />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import GridCanvas from './GridCanvas.vue'

const dashboardStore = useDashboardStore()

// State
const selectedRole = ref(null)
const isPreview = ref(false)

// Computed
const roles = computed(() => dashboardStore.roles)
const widgetCategories = computed(() => dashboardStore.widgetCategories)
const dashboardWidgets = computed(() => dashboardStore.dashboardWidgets)
const hasChanges = computed(() => dashboardStore.hasUnsavedChanges)

// Methods
const loadDashboard = async () => {
  if (selectedRole.value) {
    await dashboardStore.loadDashboard(selectedRole.value.id)
  }
}

const togglePreview = () => {
  isPreview.value = !isPreview.value
}

const saveDashboard = async () => {
  await dashboardStore.saveDashboard()
}

const handleDragStart = (widget) => {
  dashboardStore.setDraggingWidget(widget)
}

const handleDrop = (event) => {
  const widget = dashboardStore.draggingWidget
  if (widget) {
    const position = { x: 0, y: 0 } // Calculate position from event
    dashboardStore.addWidget(widget, position)
  }
}

const updateWidget = (widget, changes) => {
  dashboardStore.updateWidget(widget.instanceId, changes)
}

const deleteWidget = (widget) => {
  dashboardStore.removeWidget(widget.instanceId)
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    dashboardStore.loadRoles(),
    dashboardStore.loadWidgetCategories()
  ])
})
</script>

<style scoped>
.btn {
  @apply px-4 py-2 rounded-md font-medium transition-colors;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.btn-secondary {
  @apply bg-gray-600 text-white hover:bg-gray-700;
}

.btn-success {
  @apply bg-green-600 text-white hover:bg-green-700;
}

.btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.widget-item {
  transition: all 0.2s ease;
}

.widget-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>