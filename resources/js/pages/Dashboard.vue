<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { useForm } from '@inertiajs/vue3';

// Components

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useBcvStore } from '@/stores/bcv';
import { storeToRefs } from 'pinia';


const form = useForm({
    client: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mi CHNET',
        href: '/dashboard',
    },
];

// Usar el store de BCV
const bcvStore = useBcvStore()
const { bcv, date, loading, error } = storeToRefs(bcvStore)

const payFee = () => {
    form.post(route('pay-fee'));
}

const reloadBcvRate = async () => {
    await bcvStore.$reloadBcvAmount()
}

</script>

<template>
    <Head title="Mi CHNET" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <div class="p-4">
                            <h3 class="text-lg font-semibold mb-2">Tasa BCV</h3>
                            <div v-if="loading" class="text-sm text-gray-500">Cargando...</div>
                            <div v-else-if="error" class="text-sm text-red-500">{{ error }}</div>
                            <div v-else class="space-y-2">
                                <p class="text-2xl font-bold">{{ bcv ? `${bcv} Bs` : 'No disponible' }}</p>
                                <p class="text-sm text-gray-500">{{ date ? `Fecha: ${date}` : '' }}</p>
                                <Button @click="reloadBcvRate" size="sm" variant="outline" :disabled="loading">
                                    Actualizar
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern v-if="!$page.props.auth.user?.plan_id" />
                    <div v-else class="p-4">
                        <h3 class="text-lg font-semibold mb-2">Mi Plan</h3>
                        <div class="space-y-2">
                            <p class="text-2xl font-bold">{{ $page.props.auth.user.plan.name }}</p>
                            <div class="space-y-1">
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium">Velocidad:</span>
                                    {{ $page.props.auth.user.plan.mbps ? `${$page.props.auth.user.plan.mbps} Mbps` : '-' }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium">Precio:</span>
                                    ${{ $page.props.auth.user.plan.price }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium">Tipo:</span>
                                    {{ $page.props.auth.user.plan.type }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <PlaceholderPattern />
                <pre>{{ $page.props.auth.user}}</pre>
            </div>
        </div>
    </AppLayout>
</template>
