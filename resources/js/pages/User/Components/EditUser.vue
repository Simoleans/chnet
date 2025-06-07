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
                <DialogTitle>Editar Usuario</DialogTitle>
                <DialogDescription>
                    Modifica los datos del usuario.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-6" @submit.prevent="submitForm">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="edit-name">Nombre Completo</Label>
                        <Input
                            type="text"
                            id="edit-name"
                            v-model="form.name"
                            placeholder="Ingrese el nombre completo"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-email">Email</Label>
                        <Input
                            type="email"
                            id="edit-email"
                            v-model="form.email"
                            placeholder="usuario@ejemplo.com"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-phone">Teléfono</Label>
                        <Input
                            type="text"
                            id="edit-phone"
                            v-model="form.phone"
                            placeholder="Ingrese el teléfono"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-nationality">Nacionalidad</Label>
                        <select
                            id="edit-nationality"
                            v-model="form.nationality"
                            class="w-full p-2 border rounded-md"
                            required
                        >
                            <option value="">Selecciona nacionalidad</option>
                            <option value="V">V - Venezolano</option>
                            <option value="E">E - Extranjero</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-id_number">Número de Cédula</Label>
                        <Input
                            type="text"
                            id="edit-id_number"
                            v-model="form.id_number"
                            placeholder="12345678"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-code">Código</Label>
                        <Input
                            type="text"
                            id="edit-code"
                            v-model="form.code"
                            placeholder="USR001"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-password">Nueva Contraseña (opcional)</Label>
                        <Input
                            type="password"
                            id="edit-password"
                            v-model="form.password"
                            placeholder="Dejar vacío para no cambiar"
                        />
                    </div>

                    <div class="space-y-2 col-span-2">
                        <Label for="edit-address">Dirección</Label>
                        <textarea
                            id="edit-address"
                            v-model="form.address"
                            class="w-full p-2 border rounded-md"
                            rows="3"
                            placeholder="Ingrese la dirección completa"
                        ></textarea>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-zone_id">Zona</Label>
                        <select
                            id="edit-zone_id"
                            v-model="form.zone_id"
                            class="w-full p-2 border rounded-md"
                        >
                            <option value="">Selecciona una zona</option>
                            <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                                {{ zone.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-plan_id">Plan</Label>
                        <select
                            id="edit-plan_id"
                            v-model="form.plan_id"
                            class="w-full p-2 border rounded-md"
                        >
                            <option value="">Selecciona un plan</option>
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                {{ plan.name }} - ${{ plan.price }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-role">Rol</Label>
                        <select
                            id="edit-role"
                            v-model="form.role"
                            class="w-full p-2 border rounded-md"
                            required
                        >
                            <option value="">Selecciona un rol</option>
                            <option value="0">Usuario</option>
                            <option value="1">Administrador</option>
                        </select>
                    </div>

                    <div class="space-y-2">
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
                        Actualizar Usuario
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

interface User {
    id: number
    name: string
    email: string
    phone: string | null
    address: string | null
    zone_id: number | null
    code: string
    id_number: string
    plan_id: number | null
    status: string
    role: string
}

const props = defineProps<{
    userData: User
    zones: Array
    plans: Array
}>()

const isOpen = ref(false)

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    zone_id: '',
    code: '',
    nationality: '',
    id_number: '',
    plan_id: '',
    password: '',
    status: '1',
    role: ''
})

const openModal = () => {
    // Llenar el formulario con los datos del usuario
    form.name = props.userData.name
    form.email = props.userData.email
    form.phone = props.userData.phone || ''
    form.address = props.userData.address || ''
    form.zone_id = props.userData.zone_id || ''
    form.code = props.userData.code

    // Separar nacionalidad del número de cédula
    const idNumber = props.userData.id_number
    if (idNumber && idNumber.includes('-')) {
        const parts = idNumber.split('-')
        form.nationality = parts[0]
        form.id_number = parts[1]
    } else {
        form.nationality = ''
        form.id_number = idNumber || ''
    }

    form.plan_id = props.userData.plan_id || ''
    form.status = props.userData.status
    form.role = props.userData.role
    form.password = '' // Siempre vacío para edición
    isOpen.value = true
}

const closeModal = () => {
    isOpen.value = false
    form.reset()
}

const submitForm = () => {
    console.log('Datos a actualizar:', form.data())
    form.put(route('users.update', props.userData.id), {
        onSuccess: () => {
            closeModal()
        },
    })
}
</script>
