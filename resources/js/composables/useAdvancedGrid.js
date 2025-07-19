// resources/js/composables/useAdvancedGrid.js
import { ref, reactive, nextTick } from 'vue'

export function useAdvancedGrid() {
  const gridContainer = ref(null)
  const gridSettings = reactive({
    columns: 12,
    rows: 20,
    gap: 8,
    cellSize: 80,
    snapToGrid: true,
    autoResize: true
  })

  const widgets = ref([])
  const isDragging = ref(false)
  const draggedWidget = ref(null)

  const initializeGrid = () => {
    // Initialize responsive grid system
    updateGridDimensions()
    setupResizeObserver()
  }

  const addWidget = async (widgetData, position) => {
    const newWidget = {
      ...widgetData,
      instanceId: generateId(),
      x: Math.floor(position.x / (gridSettings.cellSize + gridSettings.gap)),
      y: Math.floor(position.y / (gridSettings.cellSize + gridSettings.gap)),
      w: widgetData.defaultWidth || 2,
      h: widgetData.defaultHeight || 2
    }

    // Check for collisions and adjust position
    const finalPosition = findAvailablePosition(newWidget)
    widgets.value.push({ ...newWidget, ...finalPosition })

    await nextTick()
    return newWidget
  }

  const updateWidget = (instanceId, updates) => {
    const index = widgets.value.findIndex(w => w.instanceId === instanceId)
    if (index !== -1) {
      widgets.value[index] = { ...widgets.value[index], ...updates }
    }
  }

  const deleteWidget = (instanceId) => {
    const index = widgets.value.findIndex(w => w.instanceId === instanceId)
    if (index !== -1) {
      widgets.value.splice(index, 1)
      compactGrid()
    }
  }

  const findAvailablePosition = (widget) => {
    // Implement collision detection and automatic positioning
    for (let y = 0; y < gridSettings.rows; y++) {
      for (let x = 0; x <= gridSettings.columns - widget.w; x++) {
        if (!hasCollision(x, y, widget.w, widget.h)) {
          return { x, y }
        }
      }
    }
    return { x: 0, y: gridSettings.rows }
  }

  const hasCollision = (x, y, w, h) => {
    return widgets.value.some(widget => 
      widget.x < x + w && 
      widget.x + widget.w > x && 
      widget.y < y + h && 
      widget.y + widget.h > y
    )
  }

  const compactGrid = () => {
    // Move widgets up to fill empty spaces
    widgets.value.sort((a, b) => a.y - b.y)
    
    widgets.value.forEach(widget => {
      let newY = 0
      while (hasCollision(widget.x, newY, widget.w, widget.h, widget.instanceId)) {
        newY++
      }
      widget.y = newY
    })
  }

  return {
    gridContainer,
    gridSettings,
    widgets,
    isDragging,
    draggedWidget,
    initializeGrid,
    addWidget,
    updateWidget,
    deleteWidget
  }
}