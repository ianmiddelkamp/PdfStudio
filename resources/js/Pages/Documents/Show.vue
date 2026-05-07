<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import PdfPageView from '../../Components/PdfPageView.vue'
import type { PdfDocument } from '@/types'

const props = defineProps<{
    document: PdfDocument
}>()

const status = ref(props.document.status)
let pollInterval: ReturnType<typeof setInterval> | null = null

onMounted(() => {
    if (status.value !== 'ready' && status.value !== 'failed') {
        pollInterval = setInterval(async () => {
            const res = await fetch(`/documents/${props.document.id}/status`)
            const data = await res.json()

            if (data.status === 'ready') {
                clearInterval(pollInterval!)
                status.value = 'ready'
                router.reload()
            } else if (data.status === 'failed') {
                clearInterval(pollInterval!)
                status.value = 'failed'
            }
        }, 2000)
    }
})

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval)
})

function scrollToPage(pageNumber: number) {
    window.document.getElementById(`page-${pageNumber}`)?.scrollIntoView({ behavior: 'smooth' })
}

function deleteDocument() {
    router.delete(`/documents/${props.document.id}`)
}
</script>

<template>
    <div>
        <div v-if="status === 'failed'" class="error-state">
            <p>Processing failed.</p>
            <button class="btn btn-danger" @click="deleteDocument">Delete Document</button>
        </div>

        <div v-else-if="status !== 'ready'" class="processing-state">
            <p>Processing document, please wait...</p>
        </div>

        <template v-else>
            <div class="sidebar">
                <div v-for="page in document.pages" :key="page.id" class="sidebar-item"
                    @click="scrollToPage(page.page_number)">
                    {{ page.page_number }}
                </div>
            </div>

            <div class="viewer-container">
                <PdfPageView v-for="page in document.pages" :key="page.id" :page="page" :document-id="document.id" />
            </div>

            <div class="button-row">
                <button class="btn btn-primary" @click="deleteDocument">Delete Document</button>
                <Link :href="`/documents/${document.id}/edit`" class="btn btn-primary">Edit Document</Link>
            </div>
           
        </template>
    </div>
</template>

<style scoped>
.viewer-container {
    overflow-y: auto;
    max-width: 75%;
    margin-left: auto;
    margin-right: auto;
}

.sidebar {
    position: fixed;
    top: 50%;
    right: 16px;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    gap: 4px;
    z-index: 10;
}

.sidebar-item {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid grey;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.sidebar-item:hover {
    background: #f0f0f0;
}

.button-row {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    gap:5px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    padding: 10px;
    background-color: white;
    z-index: 10;
    border-top: 1px solid grey;
}

.processing-state {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 50vh;
    font-size: 1.2rem;
    color: #666;
}

.error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 50vh;
    gap: 12px;
    color: #dc2626;
}
</style>
