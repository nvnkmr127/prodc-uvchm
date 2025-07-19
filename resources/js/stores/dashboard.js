import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useDashboardStore = defineStore('dashboard', () => {
  // State
  const roles = ref([])
  const widgetCategories = ref([])
  const currentDashboard = ref(null)
  const dashboardWidgets = ref([])
  const draggingWidget = ref(null)
  const hasUnsavedChanges = ref(false)

  // Actions
  const loadRoles = async () => {
    try {
      const response = await axios.get('/api/dashboard-builder/roles')
      roles.value = response.data.roles
    } catch (error) {
      console.error('Failed to load roles:', error)
    }
  }

  const loadWidgetCategories = async () => {
    try {
      const response = await axios.get('/api/dashboard-builder/widget-categories')
      widgetCategories.value = response.data.categories
    } catch (error) {
      console.error('Failed to load widget categories:', error)
    }
  }

  const loadDashboard = async (roleId) => {
    try {
      const response = await axios.get(`/api/dashboard-builder/dashboards/${roleId}`)
      currentDashboard.value = response.data.dashboard
      dashboardWidgets.value = response.data.widgets || []
      hasUnsavedChanges.value = false
    } catch (error) {
      console.error('Failed to load dashboard:', error)
    }
  }

  const saveDashboard = async () => {
    if (!currentDashboard.value) return

    try {
      const layout = dashboardWidgets.value.map(widget => ({
        instanceId: widget.instanceId,
        id: widget.id,
        x: widget.grid_x,
        y: widget.grid_y,
        w: widget.grid_w,
        h: widget.grid_h,
        config: widget.config
      }))

      await axios.post('/api/dashboard-builder/dashboards/save', {
        dashboard_id: currentDashboard.value.id,
        widgets: layout
      })

      hasUnsavedChanges.value = false
    } catch (error) {
      console.error('Failed to save dashboard:', error)
    }
  }

  const addWidget = async (widget, position) => {
    try {
      const response = await axios.post('/api/dashboard-builder/widgets/add', {
        dashboard_id: currentDashboard.value.id,
        widget_id: widget.id,
        position: position
      })

      dashboardWidgets.value.push(response.data.widget)
      hasUnsavedChanges.value = true
    } catch (error) {
      console.error('Failed to add widget:', error)
    }
  }

  const updateWidget = (instanceId, updates) => {
    const index = dashboardWidgets.value.findIndex(w => w.instanceId === instanceId)
    if (index !== -1) {
      dashboardWidgets.value[index] = { ...dashboardWidgets.value[index], ...updates }
      hasUnsavedChanges.value = true
    }
  }

  const removeWidget = (instanceId) => {
    const index = dashboardWidgets.value.findIndex(w => w.instanceId === instanceId)
    if (index !== -1) {
      dashboardWidgets.value.splice(index, 1)
      hasUnsavedChanges.value = true
    }
  }

  const setDraggingWidget = (widget) => {
    draggingWidget.value = widget
  }

  return {
    // State
    roles,
    widgetCategories,
    currentDashboard,
    dashboardWidgets,
    draggingWidget,
    hasUnsavedChanges,

    // Actions
    loadRoles,
    loadWidgetCategories,
    loadDashboard,
    saveDashboard,
    addWidget,
    updateWidget,
    removeWidget,
    setDraggingWidget
  }
})