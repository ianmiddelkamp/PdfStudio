<script setup>
import { useForm } from '@inertiajs/vue3'

defineProps({
    documents: Array,
})

const form = useForm({
    pdf: null,
})

function submit() {
    form.post('/documents', {
        forceFormData: true,
    })
}
</script>

<template>
    <div>
        <h1>PDF Studio</h1>

        <form @submit.prevent="submit" enctype="multipart/form-data">
            <input
                type="file"
                accept="application/pdf"
                @change="form.pdf = $event.target.files[0]"
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
            <li v-for="document in documents" :key="document.id">
                <a :href="`/documents/${document.id}`">
                    {{ document.original_name }}
                </a>
            </li>
        </ul>
        <p v-else>No documents yet.</p>
    </div>
</template>