import { ref, onMounted } from 'vue'

export default function useBcvRate() {
  const bcv = ref(null)
  const date = ref(null)
  const loading = ref(true)

  onMounted(async () => {
    try {
      const res = await fetch('/api/bcv')
      const json = await res.json()
      bcv.value = json?.Rate || null
      date.value = json?.Date || null
    } catch (e) {
      console.error(e)
      bcv.value = null
    } finally {
      loading.value = false
    }
  })

  return { bcv, loading, date }
}
