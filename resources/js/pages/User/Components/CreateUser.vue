<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button variant="default" @click="isOpen = true">Crear Usuario</Button>
        </DialogTrigger>
        <DialogContent class="max-w-2xl">
            <DialogHeader class="space-y-3">
                <DialogTitle>Crear Nuevo Usuario</DialogTitle>
                <DialogDescription>
                    Por favor, ingresa los datos del nuevo usuario.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-6" @submit.prevent="submitForm">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="name">Nombre Completo</Label>
                        <Input
                            type="text"
                            id="name"
                            v-model="form.name"
                            placeholder="Ingrese el nombre completo"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            type="email"
                            id="email"
                            v-model="form.email"
                            placeholder="usuario@ejemplo.com"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">Teléfono</Label>
                        <Input
                            type="text"
                            id="phone"
                            v-model="form.phone"
                            placeholder="Ingrese el teléfono"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="nationality">Nacionalidad</Label>
                        <select
                            id="nationality"
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
                        <Label for="id_number">Número de Cédula</Label>
                        <Input
                            type="text"
                            id="id_number"
                            v-model="form.id_number"
                            placeholder="12345678"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="code">Código</Label>
                        <Input
                            type="text"
                            id="code"
                            v-model="form.code"
                            placeholder="USR001"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="password">Contraseña</Label>
                        <Input
                            type="password"
                            id="password"
                            v-model="form.password"
                            placeholder="Contraseña"
                            required
                        />
                    </div>

                    <div class="space-y-2 col-span-2">
                        <Label for="address">Dirección</Label>
                        <textarea
                            id="address"
                            v-model="form.address"
                            class="w-full p-2 border rounded-md"
                            rows="3"
                            placeholder="Ingrese la dirección completa"
                        ></textarea>
                    </div>

                    <div class="space-y-2">
                        <Label for="zone_id">Zona</Label>
                        <select
                            id="zone_id"
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
                        <Label for="plan_id">Plan</Label>
                        <select
                            id="plan_id"
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
                        <Label for="role">Rol</Label>
                        <select
                            id="role"
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
                        Crear Usuario
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

const props = defineProps({
    zones: Array,
    plans: Array,
})

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

const closeModal = () => {
    isOpen.value = false
    form.reset()
}

const submitForm = () => {
    console.log('Datos a enviar:', form.data())
    form.post(route('users.store'), {
        onSuccess: () => {
            closeModal()
        },
    })
}
</script>
