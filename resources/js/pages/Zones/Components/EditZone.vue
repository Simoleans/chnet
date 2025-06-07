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
        <DialogContent class="max-w-md">
            <DialogHeader class="space-y-3">
                <DialogTitle>Editar Zona</DialogTitle>
                <DialogDescription>
                    Modifica el nombre de la zona.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-6" @submit.prevent="submitForm">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="edit-name">Nombre de la Zona</Label>
                        <Input
                            type="text"
                            id="edit-name"
                            v-model="form.name"
                            placeholder="Ingrese el nombre de la zona"
                            required
                        />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" @click="closeModal">Cancelar</Button>
                    </DialogClose>
                    <Button variant="default" type="submit" :disabled="form.processing">
                        Actualizar Zona
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

interface Zone {
    id: number
    name: string
}

const props = defineProps<{
    zoneData: Zone
}>()

const isOpen = ref(false)

const form = useForm({
    name: ''
})

const openModal = () => {
    // Llenar el formulario con los datos de la zona
    form.name = props.zoneData.name
    isOpen.value = true
}

const closeModal = () => {
    isOpen.value = false
    form.reset()
}

const submitForm = () => {
    console.log('Datos a actualizar:', form.data())
    form.put(route('zones.update', props.zoneData.id), {
        onSuccess: () => {
            closeModal()
        },
    })
}
</script>
