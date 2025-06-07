<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button variant="secondary" @click="isOpen = true">Registrar Manualmente</Button>
        </DialogTrigger>
        <DialogContent class="max-w-4xl">
            <DialogHeader class="space-y-3">
                <DialogTitle>Registrar MiBUS</DialogTitle>
                <DialogDescription>
                    Por favor, ingresa los datos de los Buses para agregarlos a la base de datos.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-6" @submit.prevent="submitForm">
                <div v-for="(bus, index) in form.buses" :key="index" class="relative border rounded-lg p-4 mb-4">
                    <div class="absolute top-2 right-2">
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            @click="removeBus(index)"
                            v-if="form.buses.length > 1"
                        >
                            ×
                        </Button>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label :for="'fecha-' + index">Fecha</Label>
                            <Input
                                type="date"
                                :id="'fecha-' + index"
                                v-model="bus.fecha"
                                required
                            />
                        </div>

                        <div class="space-y-2">
                            <Label :for="'bus-' + index">Número de Bus</Label>
                            <Input
                                type="number"
                                :id="'bus-' + index"
                                v-model="bus.bus"
                                placeholder="Ingrese el número de bus"
                                required
                            />
                        </div>

                        <div class="space-y-2">
                            <Label :for="'operador-' + index">Nombre del Operador</Label>
                            <Input
                                type="text"
                                :id="'operador-' + index"
                                v-model="bus.operador"
                                placeholder="Ingrese el nombre del operador"
                                required
                            />
                        </div>

                        <div class="space-y-2">
                            <Label :for="'patio-' + index">Patio</Label>
                            <Input
                                type="text"
                                :id="'patio-' + index"
                                v-model="bus.patio"
                                placeholder="Ingrese el patio"
                                required
                            />
                        </div>

                        <div class="space-y-2">
                            <Label :for="'formato-' + index">Formato</Label>
                            <Input
                                type="text"
                                :id="'formato-' + index"
                                v-model="bus.formato"
                                placeholder="Ingrese el formato"
                                required
                            />
                        </div>

                        <div class="space-y-2">
                            <Label :for="'fechaAudiencia-' + index">Fecha de Audiencia</Label>
                            <Input
                                type="date"
                                :id="'fechaAudiencia-' + index"
                                v-model="bus.fechaAudiencia"
                                required
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-center">
                    <Button
                        type="button"
                        variant="outline"
                        @click="addNewBus"
                        class="w-full bg-green-200 hover:bg-green-300"
                    >
                        + Agregar otro bus
                    </Button>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" @click="closeModal">Cancelar</Button>
                    </DialogClose>
                    <Button variant="default" type="submit" :disabled="form.processing">Guardar</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogClose, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ref } from 'vue'

const isOpen = ref(false)

const emptyBusData = () => ({
    fecha: '',
    bus: '',
    operador: '',
    patio: '',
    formato: '',
    fechaAudiencia: '',
})

const form = useForm({
    buses: [emptyBusData()]
});

const addNewBus = () => {
    form.buses.push(emptyBusData())
}

const removeBus = (index) => {
    form.buses.splice(index, 1)
}

const closeModal = () => {
    isOpen.value = false
    form.reset()
    form.buses = [emptyBusData()]
}

const submitForm = () => {
    console.log('Datos a enviar:', form.buses);
    form.post(route('bus.store'), {
        onSuccess: () => {
            closeModal();
        },
    });
}
</script>
