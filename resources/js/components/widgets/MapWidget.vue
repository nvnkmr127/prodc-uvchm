<template>
  <div class="map-widget h-100">
    <div class="map-header d-flex justify-content-between align-items-center mb-2" v-if="!isPreview">
      <h6 class="mb-0">{{ config.title || 'Campus Map' }}</h6>
      <div class="map-controls">
        <select v-model="selectedLayer" @change="updateMapLayer" class="form-select form-select-sm">
          <option value="street">Street View</option>
          <option value="satellite">Satellite</option>
          <option value="terrain">Terrain</option>
        </select>
      </div>
    </div>

    <div class="map-container" ref="mapContainer">
      <div id="map" :style="{ height: mapHeight }"></div>
      
      <!-- Map overlays -->
      <div class="map-overlays">
        <div class="location-info" v-if="selectedLocation">
          <div class="card">
            <div class="card-body p-2">
              <h6 class="card-title mb-1">{{ selectedLocation.name }}</h6>
              <p class="card-text small mb-1">{{ selectedLocation.description }}</p>
              <div class="d-flex gap-1">
                <button class="btn btn-primary btn-sm" @click="getDirections">
                  <i class="fas fa-directions"></i> Directions
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="closeLocationInfo">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Map Legend -->
    <div class="map-legend mt-2" v-if="!isPreview">
      <div class="d-flex flex-wrap gap-2">
        <div v-for="category in locationCategories" :key="category.type" class="legend-item">
          <div :class="`legend-marker marker-${category.type}`"></div>
          <span class="small">{{ category.label }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  data: Object,
  config: Object,
  isPreview: Boolean,
  instanceId: String
})

// State
const mapContainer = ref(null)
const selectedLayer = ref('street')
const selectedLocation = ref(null)
const map = ref(null)
const markers = ref([])

// Computed
const mapHeight = computed(() => {
  return props.isPreview ? '100%' : 'calc(100% - 80px)'
})

const locationCategories = [
  { type: 'building', label: 'Buildings', color: '#dc3545' },
  { type: 'parking', label: 'Parking', color: '#0d6efd' },
  { type: 'facility', label: 'Facilities', color: '#198754' },
  { type: 'emergency', label: 'Emergency', color: '#ffc107' }
]

// Sample locations data
const locations = ref([
  {
    id: 1,
    name: 'Main Building',
    description: 'Administrative offices and main auditorium',
    type: 'building',
    lat: 40.7128,
    lng: -74.0060,
    address: '123 College Ave'
  },
  {
    id: 2,
    name: 'Library',
    description: 'Central library with study areas',
    type: 'building',
    lat: 40.7130,
    lng: -74.0058,
    address: '125 College Ave'
  },
  {
    id: 3,
    name: 'Student Parking',
    description: 'Main student parking area',
    type: 'parking',
    lat: 40.7125,
    lng: -74.0065,
    address: '120 College Ave'
  },
  {
    id: 4,
    name: 'Emergency Services',
    description: 'Campus security and first aid',
    type: 'emergency',
    lat: 40.7132,
    lng: -74.0062,
    address: '127 College Ave'
  }
])

// Methods
const initializeMap = () => {
  // Using Leaflet.js for maps (simpler than Google Maps)
  const leafletScript = document.createElement('script')
  leafletScript.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
  
  const leafletCSS = document.createElement('link')
  leafletCSS.rel = 'stylesheet'
  leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
  
  document.head.appendChild(leafletCSS)
  document.body.appendChild(leafletScript)
  
  leafletScript.onload = () => {
    // Center map on campus
    map.value = L.map('map').setView([40.7128, -74.0060], 16)
    
    // Add tile layer
    updateMapLayer()
    
    // Add markers
    addMarkers()
  }
}

const updateMapLayer = () => {
  if (!map.value) return
  
  // Remove existing layers
  map.value.eachLayer((layer) => {
    if (layer._url) {
      map.value.removeLayer(layer)
    }
  })
  
  let tileLayer
  switch (selectedLayer.value) {
    case 'satellite':
      tileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}')
      break
    case 'terrain':
      tileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}')
      break
    default:
      tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')
  }
  
  tileLayer.addTo(map.value)
}

const addMarkers = () => {
  markers.value.forEach(marker => map.value.removeLayer(marker))
  markers.value = []
  
  locations.value.forEach(location => {
    const markerColor = locationCategories.find(cat => cat.type === location.type)?.color || '#6c757d'
    
    const marker = L.circleMarker([location.lat, location.lng], {
      radius: 8,
      fillColor: markerColor,
      color: '#fff',
      weight: 2,
      opacity: 1,
      fillOpacity: 0.8
    }).addTo(map.value)
    
    marker.bindPopup(`
      <div class="map-popup">
        <h6>${location.name}</h6>
        <p class="small mb-1">${location.description}</p>
        <small class="text-muted">${location.address}</small>
      </div>
    `)
    
    marker.on('click', () => {
      selectedLocation.value = location
    })
    
    markers.value.push(marker)
  })
}

const getDirections = () => {
  if (selectedLocation.value) {
    const url = `https://www.google.com/maps/dir/?api=1&destination=${selectedLocation.value.lat},${selectedLocation.value.lng}`
    window.open(url, '_blank')
  }
}

const closeLocationInfo = () => {
  selectedLocation.value = null
}

// Lifecycle
onMounted(() => {
  // Small delay to ensure container is rendered
  setTimeout(initializeMap, 100)
})

onUnmounted(() => {
  if (map.value) {
    map.value.remove()
  }
})
</script>

<style scoped>
.map-widget {
  position: relative;
}

.map-container {
  position: relative;
  height: 100%;
  border-radius: 6px;
  overflow: hidden;
}

#map {
  width: 100%;
  border-radius: 6px;
}

.map-overlays {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 1000;
  max-width: 250px;
}

.location-info .card {
  border: none;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.map-legend {
  padding: 8px;
  background: #f8f9fa;
  border-radius: 6px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.legend-marker {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
}

.marker-building { background: #dc3545; }
.marker-parking { background: #0d6efd; }
.marker-facility { background: #198754; }
.marker-emergency { background: #ffc107; }

.map-controls select {
  font-size: 12px;
  padding: 2px 6px;
}

/* Map popup styling */
:global(.leaflet-popup-content) {
  margin: 8px !important;
}

:global(.map-popup h6) {
  margin-bottom: 4px;
  color: #374151;
}

:global(.map-popup p) {
  margin-bottom: 4px;
}
</style>