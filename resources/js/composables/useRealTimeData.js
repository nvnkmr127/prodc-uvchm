import { ref, onMounted, onUnmounted } from 'vue'
import Echo from 'laravel-echo'

export function useRealTimeData(widgetId) {
  const data = ref(null)
  const isLoading = ref(false)
  const error = ref(null)

  let channel = null

  const subscribeToUpdates = () => {
    channel = Echo.channel(`widget.${widgetId}`)
      .listen('WidgetDataUpdated', (event) => {
        data.value = event.data
      })
  }

  const fetchData = async () => {
    isLoading.value = true
    try {
      const response = await fetch(`/api/widgets/${widgetId}/data`)
      data.value = await response.json()
    } catch (err) {
      error.value = err.message
    } finally {
      isLoading.value = false
    }
  }

  onMounted(() => {
    fetchData()
    subscribeToUpdates()
  })

  onUnmounted(() => {
    if (channel) {
      Echo.leave(`widget.${widgetId}`)
    }
  })

  return { data, isLoading, error, refetch: fetchData }
}