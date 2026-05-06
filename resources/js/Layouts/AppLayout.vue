<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { Flash } from '@/types'

const page = usePage()
const flash = computed(() => page.props.flash as Flash)
</script>

<template>
    <div>
        <nav class="navbar navbar-dark bg-dark px-3">
            <Link class="navbar-brand" href="/">PDF Studio</Link>
        </nav>

        <main class="container mt-4">
            <div v-if="flash.success" class="alert alert-success alert-dismissible fade show">
                {{ flash.success }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div v-if="flash.error" class="alert alert-danger alert-dismissible fade show">
                {{ flash.error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <slot />
        </main>
    </div>
</template>
