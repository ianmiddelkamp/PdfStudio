<script setup lang="ts">
import { ref } from 'vue'
import PdfPageEditor from '../../Components/PdfPageEditor.vue'
import FieldEditor from '@/Components/FieldEditor.vue'
import type { PdfDocument } from '@/types'
import { useZoom } from '@/composables/useZoom'
import { useEditorStore } from '@/stores/editorStore'

const props = defineProps<{
    document: PdfDocument
}>()

const viewerRef = ref<HTMLElement | null>(null)
const naturalWidth = ref(0)

const { zoom, recalculate } = useZoom(viewerRef, naturalWidth)
const editorStore = useEditorStore()

function onNaturalWidth(width: number) {
    if (naturalWidth.value === 0) {
        naturalWidth.value = width
        recalculate()
    }
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

        <div v-if="editorStore.selectedField" class="property-panel">

            <div class="property-panel-title">{{ editorStore.selectedField.field_name }}</div>
            <FieldEditor />
        </div>

        <div ref="viewerRef" class="viewer-container">
            <PdfPageEditor v-for="page in document.pages" :key="page.id" :page="page" :document-id="document.id"
                :zoom="zoom" @natural-width="onNaturalWidth" />
        </div>

        <div class="button-row">
            <button class="btn btn-primary" @click="save">Save</button>
        </div>
    </div>
</template>

<style scoped>
.viewer-container {
    overflow-y: auto;
    width: 75%;
    margin-left: auto;
    margin-right: auto;
}

.property-panel {
    position: fixed;
    top: 0;
    left: 0;
    width: 200px;
    height: calc(100% - 49px);
    overflow-y: auto;
    background: white;
    border-right: 1px solid grey;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    z-index: 10;
}

.property-panel-title {
    font-weight: bold;
    font-size: 13px;
    margin-bottom: 8px;
    word-break: break-all;
}

.nudge-pad {
    display: grid;
    grid-template-columns: repeat(3, 28px);
    grid-template-rows: repeat(3, 28px);
    justify-content: center;
    margin: 6px 0 2px;
}

.nudge-pad .nudge-btn:nth-child(1) { grid-column: 2; grid-row: 1; }
.nudge-pad .nudge-btn:nth-child(2) { grid-column: 1; grid-row: 2; }
.nudge-pad .nudge-btn:nth-child(3) { grid-column: 3; grid-row: 2; }
.nudge-pad .nudge-btn:nth-child(4) { grid-column: 2; grid-row: 3; }

.nudge-btn {
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid #ccc;
    border-radius: 3px;
    cursor: pointer;
    font-size: 10px;
    padding: 0;
}

.nudge-btn:hover {
    background: #f0f0f0;
}

.property-label {
    font-size: 11px;
    color: #555;
    margin-top: 6px;
}

.property-input {
    width: 100%;
    padding: 4px 6px;
    font-size: 12px;
    border: 1px solid #ccc;
    border-radius: 3px;
    box-sizing: border-box;
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
