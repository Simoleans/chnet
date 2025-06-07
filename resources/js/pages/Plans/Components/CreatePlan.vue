<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button variant="default" @click="isOpen = true">Crear Plan</Button>
        </DialogTrigger>
        <DialogContent class="max-w-2xl">
            <DialogHeader class="space-y-3">
                <DialogTitle>Crear Nuevo Plan</DialogTitle>
                <DialogDescription>
                    Por favor, ingresa los datos del nuevo plan.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-6" @submit.prevent="submitForm">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="name">Nombre del Plan</Label>
                        <Input
                            type="text"
                            id="name"
                            v-model="form.name"
                            placeholder="Ingrese el nombre del plan"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="price">Precio (USD)</Label>
                        <Input
                            type="number"
                            id="price"
                            v-model="form.price"
                            placeholder="0.00"
                            step="0.01"
                            min="0"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="type">Tipo de Plan</Label>
                        <select
                            id="type"
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
                        <Label for="mbps">Velocidad (Mbps)</Label>
                        <Input
                            type="number"
                            id="mbps"
                            v-model="form.mbps"
                            placeholder="100"
                            min="1"
                        />
                    </div>

                    <div class="space-y-2 col-span-2">
                        <Label for="status">Estado</Label>
                        <select
                            id="status"
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
                        Crear Plan
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

const isOpen = ref(false)

const form = useForm({
    name: '',
    price: '',
    type: '',
    mbps: '',
    status: '1'
})

const closeModal = () => {
    isOpen.value = false
    form.reset()
}

const submitForm = () => {
    console.log('Datos a enviar:', form.data())
    form.post(route('plans.store'), {
        onSuccess: () => {
            closeModal()
        },
    })
}
</script>
