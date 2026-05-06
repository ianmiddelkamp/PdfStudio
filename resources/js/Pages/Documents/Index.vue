<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { PdfDocument } from '@/types'

defineProps<{
    documents: PdfDocument[]
}>()

const form = useForm({
    pdf: null as File | null,
})

function submit() {
    form.post('/documents', {
        forceFormData: true,
    })
}
</script>

<template>
    <AppLayout>
        <form @submit.prevent="submit" enctype="multipart/form-data">
            <input
                type="file"
                accept="application/pdf"
                @change="form.pdf = ($event.target as HTMLInputElement).files?.[0] ?? null"
            >
            <span v-if="form.errors.pdf" style="color: red">
                {{ form.errors.pdf }}
            </span>
            <button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Uploading...' : 'Upload' }}
            </button>
        </form>

        <h2>Documents</h2>
        <ul v-if="documents.length">
            <li v-for="doc in documents" :key="doc.id">
                <a :href="`/documents/${doc.id}`">
                    {{ doc.original_name }}
                </a>
            </li>
        </ul>
        <p v-else>No documents yet.</p>
    </AppLayout>
</template>
