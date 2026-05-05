<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
    document: Object,
})

function deleteDocument() {
    router.delete(`/documents/${props.document.id}`)
}
</script>

<template>
    <div>
        <h1>{{ document.original_name }}</h1>
        <p>Status: {{ document.status }}</p>

        <div v-for="page in document.pages" :key="page.id">
            <p>Page {{ page.page_number }}</p>
            <img :src="`/documents/${document.id}/pages/${page.id}/image`" />
        </div>

        <button @click="deleteDocument">Delete Document</button>
    </div>
</template>
