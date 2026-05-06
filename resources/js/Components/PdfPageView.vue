<script setup lang="ts">
import type { PdfPage } from '@/types'

const props = defineProps<{
    documentId: number
    page: PdfPage
}>()
</script>

<template>
    <div :id="`page-${page.page_number}`" class="page-container">
        <img :src="`/documents/${props.documentId}/pages/${props.page.id}/image`" class="pdf-background" />
    </div>
</template>

<style scoped>
.page-container {
    display: block;
    position: relative;
    overflow: hidden;
    background-color: white;
    margin: 0 auto 10px;
    max-width: 100%;
    border: 1px solid grey;
}
.pdf-background {
    width: 100%;
}
</style>
