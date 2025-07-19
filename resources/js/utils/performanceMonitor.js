// resources/js/utils/performanceMonitor.js
class PerformanceMonitor {
  constructor() {
    this.metrics = new Map()
    this.observers = new Map()
    this.setupObservers()
  }

  setupObservers() {
    // Performance Observer for navigation timing
    if ('PerformanceObserver' in window) {
      const navObserver = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          this.recordMetric('navigation', {
            loadTime: entry.loadEventEnd - entry.loadEventStart,
            domComplete: entry.domComplete - entry.domLoading,
            firstPaint: entry.responseEnd - entry.requestStart
          })
        }
      })
      navObserver.observe({ entryTypes: ['navigation'] })

      // Widget render time observer
      const measureObserver = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          if (entry.name.startsWith('widget-render')) {
            this.recordMetric('widget-render', {
              name: entry.name,
              duration: entry.duration
            })
          }
        }
      })
      measureObserver.observe({ entryTypes: ['measure'] })
    }
  }

  startWidgetRender(widgetId) {
    performance.mark(`widget-render-${widgetId}-start`)
  }

  endWidgetRender(widgetId) {
    performance.mark(`widget-render-${widgetId}-end`)
    performance.measure(
      `widget-render-${widgetId}`,
      `widget-render-${widgetId}-start`,
      `widget-render-${widgetId}-end`
    )
  }

  recordMetric(name, data) {
    if (!this.metrics.has(name)) {
      this.metrics.set(name, [])
    }
    this.metrics.get(name).push({
      ...data,
      timestamp: Date.now()
    })

    // Send to backend every 30 seconds
    this.throttledSend()
  }

  throttledSend = throttle(() => {
    this.sendMetrics()
  }, 30000)

  async sendMetrics() {
    if (this.metrics.size === 0) return

    try {
      await fetch('/api/dashboard/metrics', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
          metrics: Object.fromEntries(this.metrics),
          session: this.getSessionId()
        })
      })
      this.metrics.clear()
    } catch (error) {
      console.warn('Failed to send performance metrics:', error)
    }
  }

  getSessionId() {
    if (!this.sessionId) {
      this.sessionId = `session-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
    }
    return this.sessionId
  }
}

// Throttle utility
function throttle(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

export default new PerformanceMonitor()