<!-- Advanced chart with drill-down capabilities -->
<template>
  <div class="advanced-chart-widget">
    <div class="chart-header">
      <h3>{{ config.title }}</h3>
      <div class="chart-controls">
        <select v-model="selectedPeriod" @change="updateChart">
          <option value="7d">Last 7 days</option>
          <option value="30d">Last 30 days</option>
          <option value="1y">Last year</option>
        </select>
      </div>
    </div>
    
    <div class="chart-container" ref="chartContainer">
      <canvas ref="chart"></canvas>
    </div>
    
    <!-- Drill-down modal -->
    <DrillDownModal v-if="showDrillDown" :data="drillDownData" @close="showDrillDown = false" />
  </div>
</template>