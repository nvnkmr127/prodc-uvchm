<!-- Real-time data table with sorting, filtering, pagination -->
<template>
  <div class="data-table-widget">
    <div class="table-controls">
      <input v-model="searchTerm" placeholder="Search..." class="search-input">
      <select v-model="selectedFilter" class="filter-select">
        <option value="">All</option>
        <option v-for="filter in availableFilters" :key="filter.value" :value="filter.value">
          {{ filter.label }}
        </option>
      </select>
      <button @click="exportData" class="export-btn">Export</button>
    </div>
    
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th v-for="column in columns" :key="column.key" 
                @click="sort(column.key)" 
                :class="{ 'sorted': sortColumn === column.key }">
              {{ column.label }}
              <span v-if="sortColumn === column.key" class="sort-indicator">
                {{ sortDirection === 'asc' ? '↑' : '↓' }}
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in paginatedData" :key="row.id" 
              @click="selectRow(row)" 
              :class="{ 'selected': selectedRows.includes(row.id) }">
            <td v-for="column in columns" :key="column.key">
              <component v-if="column.component" 
                        :is="column.component" 
                        :value="row[column.key]" 
                        :row="row" />
              <span v-else>{{ formatValue(row[column.key], column.type) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div class="table-pagination">
      <div class="pagination-info">
        Showing {{ startIndex + 1 }}-{{ endIndex }} of {{ filteredData.length }} items
      </div>
      <div class="pagination-controls">
        <button @click="prevPage" :disabled="currentPage === 1">Previous</button>
        <span class="page-numbers">
          <button v-for="page in visiblePages" :key="page" 
                  @click="currentPage = page" 
                  :class="{ 'active': page === currentPage }">
            {{ page }}
          </button>
        </span>
        <button @click="nextPage" :disabled="currentPage === totalPages">Next</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRealTimeData } from '@/composables/useRealTimeData'

const props = defineProps({
  data: Array,
  config: Object,
  isPreview: Boolean
})

// State
const searchTerm = ref('')
const selectedFilter = ref('')
const sortColumn = ref('')
const sortDirection = ref('asc')
const currentPage = ref(1)
const pageSize = ref(props.config.pageSize || 10)
const selectedRows = ref([])

// Real-time data
const { data: liveData } = useRealTimeData(props.config.dataSource)

// Computed
const columns = computed(() => props.config.columns || [])
const availableFilters = computed(() => props.config.filters || [])

const filteredData = computed(() => {
  let result = liveData.value || props.data || []
  
  // Apply search filter
  if (searchTerm.value) {
    result = result.filter(row => 
      Object.values(row).some(val => 
        String(val).toLowerCase().includes(searchTerm.value.toLowerCase())
      )
    )
  }
  
  // Apply column filter
  if (selectedFilter.value) {
    result = result.filter(row => row[props.config.filterColumn] === selectedFilter.value)
  }
  
  // Apply sorting
  if (sortColumn.value) {
    result.sort((a, b) => {
      const aVal = a[sortColumn.value]
      const bVal = b[sortColumn.value]
      const multiplier = sortDirection.value === 'asc' ? 1 : -1
      return aVal < bVal ? -multiplier : aVal > bVal ? multiplier : 0
    })
  }
  
  return result
})

const totalPages = computed(() => Math.ceil(filteredData.value.length / pageSize.value))
const startIndex = computed(() => (currentPage.value - 1) * pageSize.value)
const endIndex = computed(() => Math.min(startIndex.value + pageSize.value, filteredData.value.length))

const paginatedData = computed(() => 
  filteredData.value.slice(startIndex.value, endIndex.value)
)

const visiblePages = computed(() => {
  const delta = 2
  const range = []
  const rangeWithDots = []
  
  for (let i = Math.max(2, currentPage.value - delta); 
       i <= Math.min(totalPages.value - 1, currentPage.value + delta); 
       i++) {
    range.push(i)
  }
  
  if (currentPage.value - delta > 2) {
    rangeWithDots.push(1, '...')
  } else {
    rangeWithDots.push(1)
  }
  
  rangeWithDots.push(...range)
  
  if (currentPage.value + delta < totalPages.value - 1) {
    rangeWithDots.push('...', totalPages.value)
  } else {
    rangeWithDots.push(totalPages.value)
  }
  
  return rangeWithDots
})

// Methods
const sort = (column) => {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortColumn.value = column
    sortDirection.value = 'asc'
  }
  currentPage.value = 1
}

const selectRow = (row) => {
  const index = selectedRows.value.indexOf(row.id)
  if (index > -1) {
    selectedRows.value.splice(index, 1)
  } else {
    selectedRows.value.push(row.id)
  }
}

const formatValue = (value, type) => {
  switch (type) {
    case 'date':
      return new Date(value).toLocaleDateString()
    case 'currency':
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)
    case 'number':
      return new Intl.NumberFormat().format(value)
    default:
      return value
  }
}

const exportData = () => {
  // Export filtered data as CSV
  const csv = [
    columns.value.map(col => col.label).join(','),
    ...filteredData.value.map(row => 
      columns.value.map(col => row[col.key]).join(',')
    )
  ].join('\n')
  
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'data-export.csv'
  a.click()
}

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++
}
</script>