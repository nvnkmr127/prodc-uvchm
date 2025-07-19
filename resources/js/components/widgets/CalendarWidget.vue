<template>
  <div class="calendar-widget h-100">
    <div class="calendar-header d-flex justify-content-between align-items-center mb-3" v-if="!isPreview">
      <h6 class="mb-0">{{ config.title || 'Academic Calendar' }}</h6>
      <div class="calendar-controls">
        <button class="btn btn-sm btn-outline-primary" @click="prevMonth">
          <i class="fas fa-chevron-left"></i>
        </button>
        <span class="mx-2 small">{{ currentMonthYear }}</span>
        <button class="btn btn-sm btn-outline-primary" @click="nextMonth">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <div class="calendar-container">
      <div class="calendar-grid">
        <!-- Days of week header -->
        <div class="calendar-header-row">
          <div v-for="day in daysOfWeek" :key="day" class="calendar-day-header">
            {{ day }}
          </div>
        </div>

        <!-- Calendar days -->
        <div class="calendar-body">
          <div
            v-for="day in calendarDays"
            :key="day.date"
            :class="['calendar-day', {
              'other-month': !day.isCurrentMonth,
              'today': day.isToday,
              'has-events': day.events.length > 0
            }]"
            @click="selectDate(day)"
          >
            <div class="day-number">{{ day.dayOfMonth }}</div>
            <div class="day-events">
              <div
                v-for="event in day.events.slice(0, 2)"
                :key="event.id"
                :class="['event-dot', `event-${event.type}`]"
                :title="event.title"
              ></div>
              <div v-if="day.events.length > 2" class="more-events">
                +{{ day.events.length - 2 }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Event Details Modal -->
    <div v-if="selectedDay" class="event-details mt-3">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">{{ formatDate(selectedDay.date) }}</h6>
        </div>
        <div class="card-body">
          <div v-if="selectedDay.events.length === 0" class="text-muted">
            No events scheduled
          </div>
          <div v-else>
            <div
              v-for="event in selectedDay.events"
              :key="event.id"
              class="event-item mb-2 p-2 border rounded"
            >
              <div class="d-flex justify-content-between">
                <strong>{{ event.title }}</strong>
                <span :class="`badge bg-${event.type}`">{{ event.type }}</span>
              </div>
              <small class="text-muted">{{ event.time }}</small>
              <div v-if="event.description" class="small mt-1">
                {{ event.description }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  data: Object,
  config: Object,
  isPreview: Boolean,
  instanceId: String
})

// State
const currentDate = ref(new Date())
const selectedDay = ref(null)
const events = ref([])

// Computed
const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const currentMonthYear = computed(() => {
  return currentDate.value.toLocaleDateString('en-US', { 
    month: 'long', 
    year: 'numeric' 
  })
})

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const firstCalendarDay = new Date(firstDay)
  firstCalendarDay.setDate(firstCalendarDay.getDate() - firstCalendarDay.getDay())
  
  const days = []
  const today = new Date()
  
  for (let i = 0; i < 42; i++) {
    const date = new Date(firstCalendarDay)
    date.setDate(firstCalendarDay.getDate() + i)
    
    const dayEvents = events.value.filter(event => 
      new Date(event.date).toDateString() === date.toDateString()
    )
    
    days.push({
      date: date.toISOString().split('T')[0],
      dayOfMonth: date.getDate(),
      isCurrentMonth: date.getMonth() === month,
      isToday: date.toDateString() === today.toDateString(),
      events: dayEvents
    })
  }
  
  return days
})

// Methods
const prevMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
}

const nextMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
}

const selectDate = (day) => {
  selectedDay.value = day
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Load calendar events
onMounted(() => {
  // Sample events - replace with real data
  events.value = [
    {
      id: 1,
      title: 'Semester Start',
      date: '2025-01-15',
      time: '8:00 AM',
      type: 'primary',
      description: 'Beginning of Spring semester'
    },
    {
      id: 2,
      title: 'Registration Deadline',
      date: '2025-01-20',
      time: '11:59 PM',
      type: 'warning',
      description: 'Last day for course registration'
    },
    {
      id: 3,
      title: 'Fee Payment Due',
      date: '2025-01-25',
      time: '5:00 PM',
      type: 'danger',
      description: 'Semester fee payment deadline'
    }
  ]
})
</script>

<style scoped>
.calendar-widget {
  font-size: 13px;
}

.calendar-grid {
  border: 1px solid #dee2e6;
  border-radius: 6px;
  overflow: hidden;
}

.calendar-header-row {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: #f8f9fa;
}

.calendar-day-header {
  padding: 8px 4px;
  text-align: center;
  font-weight: 600;
  border-right: 1px solid #dee2e6;
  font-size: 11px;
}

.calendar-day-header:last-child {
  border-right: none;
}

.calendar-body {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  grid-template-rows: repeat(6, 1fr);
}

.calendar-day {
  min-height: 60px;
  padding: 4px;
  border-right: 1px solid #dee2e6;
  border-bottom: 1px solid #dee2e6;
  cursor: pointer;
  transition: background-color 0.2s;
  position: relative;
}

.calendar-day:hover {
  background: #f8f9fa;
}

.calendar-day:nth-child(7n) {
  border-right: none;
}

.calendar-day.other-month {
  color: #adb5bd;
  background: #f8f9fa;
}

.calendar-day.today {
  background: #e3f2fd;
}

.calendar-day.today .day-number {
  background: #2196f3;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: bold;
}

.calendar-day.has-events {
  background: #fff3cd;
}

.day-number {
  font-size: 11px;
  font-weight: 500;
}

.day-events {
  margin-top: 2px;
  display: flex;
  flex-wrap: wrap;
  gap: 2px;
}

.event-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #6c757d;
}

.event-dot.event-primary {
  background: #0d6efd;
}

.event-dot.event-warning {
  background: #ffc107;
}

.event-dot.event-danger {
  background: #dc3545;
}

.event-dot.event-success {
  background: #198754;
}

.more-events {
  font-size: 8px;
  color: #6c757d;
}

.event-item {
  border-left: 3px solid #0d6efd;
}

.calendar-controls button {
  padding: 2px 8px;
  font-size: 12px;
}
</style>