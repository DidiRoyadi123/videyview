import { reactive, readonly } from 'vue'

const state = reactive({
  toasts: []
})

export function useToast() {
  const addToast = ({ message, type = 'success', duration = 5000 }) => {
    const id = Date.now() + Math.random().toString(36).substr(2, 9)
    const toast = { id, message, type }

    state.toasts.push(toast)

    if (duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, duration)
    }

    return id
  }

  const removeToast = (id) => {
    const index = state.toasts.findIndex((t) => t.id === id)
    if (index !== -1) {
      state.toasts.splice(index, 1)
    }
  }

  const success = (message, duration) => addToast({ message, type: 'success', duration })
  const error = (message, duration) => addToast({ message, type: 'error', duration })
  const warning = (message, duration) => addToast({ message, type: 'warning', duration })
  const info = (message, duration) => addToast({ message, type: 'info', duration })

  return {
    toasts: readonly(state.toasts),
    addToast,
    removeToast,
    success,
    error,
    warning,
    info
  }
}
