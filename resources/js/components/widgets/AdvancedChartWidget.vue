<template>
  <div class="advanced-chart-widget h-100">
    <div class="chart-header d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">{{ config.title || 'Advanced Chart' }}</h6>
      <div class="chart-controls" v-if="!isPreview">
        <div class="btn-group btn-group-sm">
          <button
            v-for="type in chartTypes"
            :key="type.value"
            @click="changeChartType(type.value)"
            :class="['btn', config.chartType === type.value ? 'btn-primary' : 'btn-outline-primary']"
            :title="type.label"
          >
            <i :class="type.icon"></i>
          </button>
        </div>
        <button class="btn btn-outline-secondary btn-sm ms-2" @click="exportChart">
          <i class="fas fa-download"></i>
        </button>
      </div>
    </div>

    <div class="chart-container position-relative">
      <canvas ref="chartCanvas" :id="`chart-${instanceId}`"></canvas>
      
      <!-- Chart Loading -->
      <div v-if="isLoading" class="chart-loading">
        <div class="d-flex align-items-center justify-content-center h-100">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading chart...</span>
          </div>
        </div>
      </div>

      <!-- Chart Error -->
      <div v-if="hasError" class="chart-error">
        <div class="d-flex align-items-center justify-content-center h-100 text-center">
          <div>
            <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
            <p class="text-muted">{{ errorMessage }}</p>
            <button class="btn btn-sm btn-primary" @click="reloadChart">
              <i class="fas fa-redo"></i> Retry
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart Legend (Custom) -->
    <div v-if="config.showCustomLegend && chartData?.datasets" class="custom-legend mt-2">
      <div class="d-flex flex-wrap gap-2">
        <div
          v-for="(dataset, index) in chartData.datasets"
          :key="index"
          class="legend-item"
          @click="toggleDataset(index)"
          :class="{ 'legend-hidden': hiddenDatasets.includes(index) }"
        >
          <div
            class="legend-color"
            :style="{ backgroundColor: dataset.backgroundColor || dataset.borderColor }"
          ></div>
          <span class="legend-label">{{ dataset.label }}</span>
        </div>
      </div>
    </div>

    <!-- Chart Statistics -->
    <div v-if="config.showStats && chartStats" class="chart-stats mt-2">
      <div class="row text-center">
        <div class="col">
          <div class="stat-item">
            <div class="stat-value">{{ chartStats.total }}</div>
            <div class="stat-label">Total</div>
          </div>
        </div>
        <div class="col">
          <div class="stat-item">
            <div class="stat-value">{{ chartStats.average }}</div>
            <div class="stat-label">Average</div>
          </div>
        </div>
        <div class="col">
          <div class="stat-item">
            <div class="stat-value">{{ chartStats.max }}</div>
            <div class="stat-label">Maximum</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
  data: Object,
  config: Object,
  isPreview: Boolean,
  instanceId: String
})

const emit = defineEmits(['update:config'])

// State
const chartCanvas = ref(null)
const chart = ref(null)
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const hiddenDatasets = ref([])

// Chart types configuration
const chartTypes = [
  { value: 'line', label: 'Line Chart', icon: 'fas fa-chart-line' },
  { value: 'bar', label: 'Bar Chart', icon: 'fas fa-chart-bar' },
  { value: 'pie', label: 'Pie Chart', icon: 'fas fa-chart-pie' },
  { value: 'doughnut', label: 'Doughnut Chart', icon: 'fas fa-circle-notch' },
  { value: 'radar', label: 'Radar Chart', icon: 'fas fa-spider' },
  { value: 'polarArea', label: 'Polar Area', icon: 'fas fa-circle' },
  { value: 'scatter', label: 'Scatter Plot', icon: 'fas fa-braille' },
  { value: 'bubble', label: 'Bubble Chart', icon: 'fas fa-circle' }
]

// Computed
const chartData = computed(() => {
  if (!props.data) return null

  // Transform data based on chart type
  const type = props.config.chartType || 'bar'
  let transformedData = { ...props.data }

  // Apply data transformations
  if (transformedData.datasets) {
    transformedData.datasets = transformedData.datasets.map((dataset, index) => ({
      ...dataset,
      hidden: hiddenDatasets.value.includes(index),
      // Apply theme colors if not specified
      backgroundColor: dataset.backgroundColor || getThemeColors(index, 0.2),
      borderColor: dataset.borderColor || getThemeColors(index, 1),
      borderWidth: dataset.borderWidth || (type === 'line' ? 2 : 1),
      // Chart type specific styling
      ...getChartTypeSpecificStyling(type, dataset, index)
    }))
  }

  return transformedData
})

const chartOptions = computed(() => {
  const type = props.config.chartType || 'bar'
  
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: props.config.showLegend !== false && !props.config.showCustomLegend,
        position: props.config.legendPosition || 'top',
        labels: {
          usePointStyle: true,
          boxWidth: 12,
          font: { size: 11 }
        }
      },
      tooltip: {
        enabled: props.config.showTooltips !== false,
        mode: props.config.tooltipMode || 'index',
        intersect: false,
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleColor: '#fff',
        bodyColor: '#fff',
        borderColor: 'rgba(255, 255, 255, 0.1)',
        borderWidth: 1,
        cornerRadius: 6,
        callbacks: {
          label: function(context) {
            let label = context.dataset.label || ''
            if (label) label += ': '
            
            if (props.config.format === 'currency') {
              label += new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
              }).format(context.parsed.y || context.parsed)
            } else if (props.config.format === 'percentage') {
              label += (context.parsed.y || context.parsed).toFixed(1) + '%'
            } else {
              label += new Intl.NumberFormat().format(context.parsed.y || context.parsed)
            }
            
            return label
          }
        }
      },
      title: {
        display: props.config.showTitle && props.isPreview,
        text: props.config.title,
        font: { size: 16, weight: 'bold' },
        padding: { bottom: 20 }
      }
    },
    scales: getScalesConfig(type),
    animation: {
      duration: props.isPreview ? 1000 : 0,
      easing: 'easeInOutQuart'
    },
    interaction: {
      intersect: false,
      mode: 'index'
    },
    elements: {
      point: {
        radius: type === 'line' ? 4 : 0,
        hoverRadius: 6,
        backgroundColor: '#fff',
        borderWidth: 2
      },
      line: {
        tension: props.config.smooth ? 0.3 : 0
      }
    },
    onHover: (event, elements) => {
      event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default'
    },
    onClick: (event, elements) => {
      if (elements.length > 0 && props.config.onClickAction) {
        const element = elements[0]
        emit('data-point-click', {
          datasetIndex: element.datasetIndex,
          index: element.index,
          value: chartData.value.datasets[element.datasetIndex].data[element.index],
          label: chartData.value.labels[element.index]
        })
      }
    }
  }
})

const chartStats = computed(() => {
  if (!chartData.value?.datasets?.length) return null

  const allValues = chartData.value.datasets
    .filter((_, index) => !hiddenDatasets.value.includes(index))
    .flatMap(dataset => dataset.data)
    .filter(value => typeof value === 'number')

  if (allValues.length === 0) return null

  return {
    total: allValues.reduce((sum, val) => sum + val, 0).toLocaleString(),
    average: (allValues.reduce((sum, val) => sum + val, 0) / allValues.length).toFixed(1),
    max: Math.max(...allValues).toLocaleString(),
    min: Math.min(...allValues).toLocaleString()
  }
})

// Methods
const initChart = async () => {
  if (!chartCanvas.value || !chartData.value) return

  try {
    // Ensure Chart.js is loaded
    if (typeof Chart === 'undefined') {
      await loadChartJS()
    }

    // Destroy existing chart
    if (chart.value) {
      chart.value.destroy()
    }

    // Create new chart
    chart.value = new Chart(chartCanvas.value, {
      type: props.config.chartType || 'bar',
      data: chartData.value,
      options: chartOptions.value
    })

    hasError.value = false
  } catch (error) {
    console.error('Chart initialization error:', error)
    hasError.value = true
    errorMessage.value = 'Failed to initialize chart: ' + error.message
  }
}

const loadChartJS = () => {
  return new Promise((resolve, reject) => {
    if (typeof Chart !== 'undefined') {
      resolve()
      return
    }

    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js'
    script.onload = resolve
    script.onerror = reject
    document.head.appendChild(script)
  })
}

const updateChart = () => {
  if (!chart.value) return

  chart.value.data = chartData.value
  chart.value.options = chartOptions.value
  chart.value.update('none')
}

const changeChartType = (newType) => {
  emit('update:config', { chartType: newType })
}

const toggleDataset = (index) => {
  if (hiddenDatasets.value.includes(index)) {
    hiddenDatasets.value = hiddenDatasets.value.filter(i => i !== index)
  } else {
    hiddenDatasets.value.push(index)
  }
}

const exportChart = () => {
  if (!chart.value) return

  const url = chart.value.toBase64Image('image/png', 1.0)
  const link = document.createElement('a')
  link.download = `${props.config.title || 'chart'}.png`
  link.href = url
  link.click()
}

const reloadChart = () => {
  hasError.value = false
  errorMessage.value = ''
  initChart()
}

const getThemeColors = (index, alpha = 1) => {
  const colors = [
    `rgba(59, 130, 246, ${alpha})`,   // Blue
    `rgba(16, 185, 129, ${alpha})`,   // Green
    `rgba(245, 158, 11, ${alpha})`,   // Yellow
    `rgba(239, 68, 68, ${alpha})`,    // Red
    `rgba(139, 92, 246, ${alpha})`,   // Purple
    `rgba(236, 72, 153, ${alpha})`,   // Pink
    `rgba(14, 165, 233, ${alpha})`,   // Sky
    `rgba(34, 197, 94, ${alpha})`     // Emerald
  ]
  return colors[index % colors.length]
}

const getChartTypeSpecificStyling = (type, dataset, index) => {
  switch (type) {
    case 'line':
      return {
        fill: props.config.fillArea ? 'origin' : false,
        pointBackgroundColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6
      }
    case 'bar':
      return {
        borderRadius: 4,
        borderSkipped: false
      }
    case 'pie':
    case 'doughnut':
      return {
        borderWidth: 2,
        hoverBorderWidth: 4
      }
    case 'radar':
      return {
        fill: true,
        backgroundColor: getThemeColors(index, 0.1),
        pointBackgroundColor: getThemeColors(index, 1),
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: getThemeColors(index, 1)
      }
    default:
      return {}
  }
}

const getScalesConfig = (type) => {
  if (['pie', 'doughnut', 'radar', 'polarArea'].includes(type)) {
    return {}
  }

  return {
    x: {
      display: props.config.showXAxis !== false,
      title: {
        display: !!props.config.xAxisLabel,
        text: props.config.xAxisLabel,
        font: { weight: 'bold' }
      },
      grid: {
        display: props.config.showGridLines !== false,
        color: 'rgba(0, 0, 0, 0.1)'
      }
    },
    y: {
      display: props.config.showYAxis !== false,
      beginAtZero: props.config.beginAtZero !== false,
      title: {
        display: !!props.config.yAxisLabel,
        text: props.config.yAxisLabel,
        font: { weight: 'bold' }
      },
      grid: {
        display: props.config.showGridLines !== false,
        color: 'rgba(0, 0, 0, 0.1)'
      },
      ticks: {
        callback: function(value) {
          if (props.config.format === 'currency') {
            return new Intl.NumberFormat('en-US', {
              style: 'currency',
              currency: 'USD',
              notation: 'compact'
            }).format(value)
          } else if (props.config.format === 'percentage') {
            return value + '%'
          }
          return new Intl.NumberFormat('en-US', {
            notation: 'compact'
          }).format(value)
        }
      }
    }
  }
}

// Watchers
watch(() => props.data, () => {
  if (chart.value) {
    updateChart()
  } else {
    initChart()
  }
}, { deep: true })

watch(() => props.config, () => {
  if (chart.value) {
    // For chart type changes, reinitialize
    if (chart.value.config.type !== props.config.chartType) {
      initChart()
    } else {
      updateChart()
    }
  }
}, { deep: true })

// Lifecycle
onMounted(() => {
  nextTick(() => {
    initChart()
  })
})

onUnmounted(() => {
  if (chart.value) {
    chart.value.destroy()
  }
})
</script>

<style scoped>
.advanced-chart-widget {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.chart-container {
  flex: 1;
  min-height: 200px;
  position: relative;
}

.chart-loading,
.chart-error {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  z-index: 10;
}

.chart-controls .btn-group {
  border-radius: 6px;
  overflow: hidden;
}

.chart-controls .btn {
  font-size: 11px;
  padding: 4px 8px;
}

.custom-legend {
  max-height: 60px;
  overflow-y: auto;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 12px;
}

.legend-item:hover {
  background: #f8f9fa;
}

.legend-item.legend-hidden {
  opacity: 0.5;
  text-decoration: line-through;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.legend-label {
  font-weight: 500;
}

.chart-stats {
  border-top: 1px solid #dee2e6;
  padding-top: 8px;
}

.stat-item {
  padding: 4px;
}

.stat-value {
  font-size: 14px;
  font-weight: bold;
  color: #374151;
}

.stat-label {
  font-size: 11px;
  color: #6b7280;
  text-transform: uppercase;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .chart-controls {
    flex-wrap: wrap;
    gap: 8px;
  }

  .chart-controls .btn-group {
    flex: 1;
  }

  .legend-item {
    font-size: 11px;
    padding: 2px 6px;
  }
}
</style>