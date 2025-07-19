<!-- KPI widget with trend indicators and comparisons -->
<template>
  <div class="kpi-widget" :class="`kpi-${config.variant || 'default'}`">
    <div class="kpi-header">
      <h3 class="kpi-title">{{ config.title }}</h3>
      <div class="kpi-period">{{ config.period }}</div>
    </div>
    
    <div class="kpi-main">
      <div class="kpi-value">
        <span class="value-prefix">{{ config.prefix }}</span>
        <span class="value-number">{{ formattedValue }}</span>
        <span class="value-suffix">{{ config.suffix }}</span>
      </div>
      
      <div class="kpi-trend" :class="trendClass">
        <TrendIcon :class="trendIconClass" />
        <span class="trend-value">{{ trendPercentage }}%</span>
        <span class="trend-label">{{ trendLabel }}</span>
      </div>
    </div>
    
    <div v-if="config.showSparkline" class="kpi-sparkline">
      <svg width="100%" height="40" viewBox="0 0 200 40">
        <path :d="sparklinePath" fill="none" :stroke="sparklineColor" stroke-width="2"/>
        <circle v-if="config.showCurrentPoint" 
                :cx="sparklinePoints[sparklinePoints.length - 1]?.x" 
                :cy="sparklinePoints[sparklinePoints.length - 1]?.y" 
                r="3" :fill="sparklineColor"/>
      </svg>
    </div>
    
    <div v-if="config.showComparison" class="kpi-comparison">
      <div class="comparison-item">
        <span class="comparison-label">Last Period:</span>
        <span class="comparison-value">{{ formatValue(data.previousValue) }}</span>
      </div>
      <div class="comparison-item">
        <span class="comparison-label">Target:</span>
        <span class="comparison-value">{{ formatValue(data.target) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { TrendingUpIcon, TrendingDownIcon, MinusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  data: Object,
  config: Object
})

// Computed values
const formattedValue = computed(() => formatValue(props.data.value))

const trendPercentage = computed(() => {
  if (!props.data.previousValue) return 0
  const change = ((props.data.value - props.data.previousValue) / props.data.previousValue) * 100
  return Math.abs(change).toFixed(1)
})

const trendClass = computed(() => {
  const change = props.data.value - props.data.previousValue
  if (change > 0) return 'trend-up'
  if (change < 0) return 'trend-down'
  return 'trend-neutral'
})

const TrendIcon = computed(() => {
  const change = props.data.value - props.data.previousValue
  if (change > 0) return TrendingUpIcon
  if (change < 0) return TrendingDownIcon
  return MinusIcon
})

const trendLabel = computed(() => {
  const change = props.data.value - props.data.previousValue
  if (change > 0) return 'increase'
  if (change < 0) return 'decrease'
  return 'no change'
})

const sparklinePoints = computed(() => {
  if (!props.data.historical) return []
  
  const maxValue = Math.max(...props.data.historical)
  const minValue = Math.min(...props.data.historical)
  const range = maxValue - minValue || 1
  
  return props.data.historical.map((value, index) => ({
    x: (index / (props.data.historical.length - 1)) * 200,
    y: 40 - ((value - minValue) / range) * 40
  }))
})

const sparklinePath = computed(() => {
  if (sparklinePoints.value.length === 0) return ''
  
  const pathData = sparklinePoints.value.map((point, index) => 
    `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`
  ).join(' ')
  
  return pathData
})

const sparklineColor = computed(() => {
  const change = props.data.value - props.data.previousValue
  if (change > 0) return '#10B981'
  if (change < 0) return '#EF4444'
  return '#6B7280'
})

// Helper methods
const formatValue = (value) => {
  if (typeof value !== 'number') return value
  
  switch (props.config.format) {
    case 'currency':
      return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: props.config.currency || 'USD' 
      }).format(value)
    case 'percentage':
      return `${value.toFixed(1)}%`
    case 'number':
      return new Intl.NumberFormat().format(value)
    default:
      return value.toString()
  }
}
</script>

<style scoped>
.kpi-widget {
  @apply bg-white rounded-lg shadow-sm border p-4 h-full;
}

.kpi-default { @apply border-gray-200; }
.kpi-success { @apply border-green-200 bg-green-50; }
.kpi-warning { @apply border-yellow-200 bg-yellow-50; }
.kpi-danger { @apply border-red-200 bg-red-50; }

.kpi-header {
  @apply flex justify-between items-start mb-3;
}

.kpi-title {
  @apply text-sm font-medium text-gray-700;
}

.kpi-period {
  @apply text-xs text-gray-500;
}

.kpi-value {
  @apply text-2xl font-bold text-gray-900 mb-2;
}

.kpi-trend {
  @apply flex items-center text-sm;
}

.trend-up { @apply text-green-600; }
.trend-down { @apply text-red-600; }
.trend-neutral { @apply text-gray-600; }

.kpi-sparkline {
  @apply mt-3 opacity-75;
}

.kpi-comparison {
  @apply mt-3 pt-3 border-t border-gray-200 space-y-1;
}

.comparison-item {
  @apply flex justify-between text-xs text-gray-600;
}
</style>