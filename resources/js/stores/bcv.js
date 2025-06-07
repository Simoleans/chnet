import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useBcvStore = defineStore('bcv', () => {
  const bcv = ref(null)
  const date = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchBcvRate = async () => {
    loading.value = true
    error.value = null

    try {
      const res = await fetch('/api/bcv')
      const json = await res.json()

      bcv.value = json?.Rate || null
      date.value = json?.Date || null
    } catch (e) {
      console.error('Error fetching BCV rate:', e)
      error.value = e.message || 'Error al cargar la tasa BCV'
      bcv.value = null
      date.value = null
    } finally {
      loading.value = false
    }
  }

  const $reloadBcvAmount = async () => {
    await fetchBcvRate()
  }

  // Inicializar trae el bcv
  fetchBcvRate()

  return {
    bcv,
    date,
    loading,
    error,
    fetchBcvRate,
    $reloadBcvAmount
  }
})
