<script setup lang="ts">
import { ref } from 'vue'
import PdfPageEditor from '../../Components/PdfPageEditor.vue'
import type { PdfDocument, PdfField } from '@/types'
import { useZoom } from '@/composables/useZoom'

const props = defineProps<{
    document: PdfDocument
}>()

const viewerRef = ref<HTMLElement | null>(null)
const naturalWidth = ref(0)
const selectedFieldId = ref<number | null>(null)
const pendingChanges = new Map<number, PdfField>()

const { zoom, recalculate } = useZoom(viewerRef, naturalWidth)

function onNaturalWidth(width: number) {
    //run only once. on first page which calls it.
    if (naturalWidth.value === 0) {
        naturalWidth.value = width
        recalculate()
    }
}

function onSelectField(fieldId: number) {
    //set or unset selected field.
    selectedFieldId.value = fieldId === selectedFieldId.value ? null : fieldId
}

function onFieldMoved(field: PdfField) {
    pendingChanges.set(field.id, field)
}

function scrollToPage(pageNumber: number) {
    window.document.getElementById(`page-${pageNumber}`)?.scrollIntoView({ behavior: 'smooth' })
}

function save() {
    // TODO
}
</script>

<template>
    <div>
        <div class="sidebar">
            <div v-for="page in document.pages" :key="page.id" class="sidebar-item"
                @click="scrollToPage(page.page_number)">
                {{ page.page_number }}
            </div>
        </div>

        <div ref="viewerRef" class="viewer-container">
            <PdfPageEditor v-for="page in document.pages" :key="page.id" :page="page" :document-id="document.id"
                :zoom="zoom" :selected-field-id="selectedFieldId" @select-field="onSelectField"
                @field-moved="onFieldMoved" @natural-width="onNaturalWidth" />
        </div>

        <div class="button-row">
            <button class="btn btn-primary" @click="save">Save</button>
        </div>
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
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    padding: 10px;
    background-color: white;
    z-index: 10;
    border-top: 1px solid grey;
}
</style>
