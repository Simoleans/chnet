import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useBanksStore = defineStore('banks', () => {
    const banks = ref([])
    const loading = ref(false)
    const error = ref(null)

    const loadBanks = async () => {
        if (banks.value.length > 0) {
            return // Ya están cargados
        }

        loading.value = true
        error.value = null

        try {
            const response = await axios.get('/api/banks')

            if (response.data.success) {
                banks.value = response.data.data
            } else {
                error.value = response.data.message || 'Error al cargar bancos'
                console.error('Error loading banks:', response.data.message)
            }
        } catch (err) {
            error.value = err.response?.data?.error || 'Error de conexión al cargar bancos'
            console.error('Error loading banks:', err)
        } finally {
            loading.value = false
        }
    }

    const getBankName = (bankCode) => {
        const bank = banks.value.find(b => b.Code === bankCode)
        return bank ? bank.Name : bankCode
    }

    const getBankOptions = () => {
        return banks.value.map(bank => ({
            value: bank.Code,
            label: `${bank.Code} - ${bank.Name}`
        }))
    }

    return {
        banks,
        loading,
        error,
        loadBanks,
        getBankName,
        getBankOptions
    }
})
