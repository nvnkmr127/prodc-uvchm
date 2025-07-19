<template>
  <div class="chart-widget h-full">
    <div v-if="config.title" class="mb-3">
      <h4 class="text-lg font-semibold">{{ config.title }}</h4>
    </div>
    
    <div class="chart-container" style="height: 300px;">
      <canvas ref="chartCanvas"></canvas>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  data: Object,
  config: Object,
  isPreview: Boolean
})

const chartCanvas = ref(null)
let chart = null

const initChart = () => {
  if (chart) chart.destroy()
  
  if (!props.data || !chartCanvas.value) return

  chart = new Chart(chartCanvas.value, {
    type: props.config.chartType || 'bar',
    data: props.data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: props.config.showLegend !== false
        }
      }
    }
  })
}

onMounted(() => {
  initChart()
})

watch(() => props.data, initChart, { deep: true })
</script>