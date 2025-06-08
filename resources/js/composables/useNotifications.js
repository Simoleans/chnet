import { ref } from 'vue'

// ✅ Esta es la clave: definir el ref fuera de la función
const notifications = ref([])

let id = 0

export function useNotifications() {
  const notify = ({ message, type = 'success', duration = 3000 }) => {
    const newNotification = {
      id: id++,
      message,
      type,
    }

    notifications.value.push(newNotification)

    setTimeout(() => {
      notifications.value = notifications.value.filter(n => n.id !== newNotification.id)
    }, duration)
  }

  return {
    notifications,
    notify,
  }
}
