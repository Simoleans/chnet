<template>
    <div class="fixed top-4 right-4 z-[1000000] space-y-2 w-[90%] max-w-sm">
      <!-- Flash Messages -->
      <transition-group name="fade" tag="div">
        <div
          v-if="$page.props.flash.type"
          :class="[
            'p-4 rounded-lg shadow-md text-white',
            {
              'bg-green-500': $page.props.flash.type === 'success',
              'bg-red-500': $page.props.flash.type === 'error',
              'bg-yellow-500': $page.props.flash.type === 'warning',
            }
          ]"
        >
          {{ $page.props.flash.message }}
        </div>
      </transition-group>

      <!-- Composable Notifications -->
      <transition-group name="fade" tag="div">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="[
            'p-4 rounded-lg shadow-md text-white',
            {
              'bg-green-500': notification.type === 'success',
              'bg-red-500': notification.type === 'error',
              'bg-yellow-500': notification.type === 'warning',
            }
          ]"
        >
          {{ notification.message }}
        </div>
      </transition-group>
    </div>
  </template>

  <script setup lang="ts">
  import { usePage } from '@inertiajs/vue3'
  import { watch } from 'vue'
  import { useNotifications } from '@/composables/useNotifications'

  const page = usePage()
  const { notifications } = useNotifications()

  watch(
    () => page.props.flash,
    (flash) => {
      if (flash?.type && flash?.message) {
        setTimeout(() => {
          page.props.flash.type = null
          page.props.flash.message = null
        }, 2000)
      }
    },
    { immediate: true, deep: true }
  )
  </script>

  <style>
  .fade-enter-active, .fade-leave-active {
    transition: all 0.3s ease;
  }
  .fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
  }
  </style>
