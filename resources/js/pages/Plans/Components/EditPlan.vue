<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <button
                @click="openModal"
                class="px-3 py-1 bg-green-600 text-white rounded-md hover:bg-green-700 transition text-sm"
            >
                Editar
            </button>
        </DialogTrigger>
        <DialogContent class="max-w-2xl">
            <DialogHeader class="space-y-3">
                <DialogTitle>Editar Plan</DialogTitle>
                <DialogDescription>
                    Modifica los datos del plan.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-6" @submit.prevent="submitForm">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="edit-name">Nombre del Plan</Label>
                        <Input
                            type="text"
                            id="edit-name"
                            v-model="form.name"
                            placeholder="Ingrese el nombre del plan"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-price">Precio (USD)</Label>
                        <Input
                            type="number"
                            id="edit-price"
                            v-model="form.price"
                            placeholder="0.00"
                            step="0.01"
                            min="0"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-type">Tipo de Plan</Label>
                        <select
                            id="edit-type"
                            v-model="form.type"
                            class="w-full p-2 border rounded-md"
                            required
                        >
                            <option value="">Selecciona un tipo</option>
                            <option value="tv">TV</option>
                            <option value="internet">Internet</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-mbps">Velocidad (Mbps)</Label>
                        <Input
                            type="number"
                            id="edit-mbps"
                            v-model="form.mbps"
                            placeholder="100"
                            min="1"
                        />
                    </div>

                    <div class="space-y-2 col-span-2">
                        <Label for="edit-status">Estado</Label>
                        <select
                            id="edit-status"
                            v-model="form.status"
                            class="w-full p-2 border rounded-md"
                        >
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" @click="closeModal">Cancelar</Button>
                    </DialogClose>
                    <Button variant="default" type="submit" :disabled="form.processing">
                        Actualizar Plan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogClose, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ref } from 'vue'

interface Plan {
    id: number
    name: string
    price: string
    type: string
    mbps: string | null
    status: string
}

const props = defineProps<{
    planData: Plan
}>()

const isOpen = ref(false)

const form = useForm({
    name: '',
    price: '',
    type: '',
    mbps: '',
    status: '1'
})

const openModal = () => {
    // Llenar el formulario con los datos del plan
    form.name = props.planData.name
    form.price = props.planData.price
    form.type = props.planData.type
    form.mbps = props.planData.mbps || ''
    form.status = props.planData.status
    isOpen.value = true
}

const closeModal = () => {
    isOpen.value = false
    form.reset()
}

const submitForm = () => {
    console.log('Datos a actualizar:', form.data())
    form.put(route('plans.update', props.planData.id), {
        onSuccess: () => {
            closeModal()
        },
    })
}
</script>
