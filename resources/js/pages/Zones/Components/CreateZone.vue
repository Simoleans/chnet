<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button variant="default" @click="isOpen = true">Crear Zona</Button>
        </DialogTrigger>
        <DialogContent class="max-w-md">
            <DialogHeader class="space-y-3">
                <DialogTitle>Crear Nueva Zona</DialogTitle>
                <DialogDescription>
                    Por favor, ingresa el nombre de la nueva zona.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-6" @submit.prevent="submitForm">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Nombre de la Zona</Label>
                        <Input
                            type="text"
                            id="name"
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
                        Crear Zona
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
    name: ''
})

const closeModal = () => {
    isOpen.value = false
    form.reset()
}

const submitForm = () => {
    console.log('Datos a enviar:', form.data())
    form.post(route('zones.store'), {
        onSuccess: () => {
            closeModal()
        },
    })
}
</script>
