<script setup lang="ts">
import { ref, computed } from 'vue'
import type { PdfPage } from '@/types'

const PTS_TO_PX = 700 / 72

const props = defineProps<{
    documentId: number
    page: PdfPage
    zoom: number
}>()

const emit = defineEmits<{
    (e: 'natural-width', width: number): void
}>()

const imgRef = ref<HTMLImageElement | null>(null)
const naturalWidth = ref(0)
const naturalHeight = ref(0)

function onImageLoad() {
    if (!imgRef.value) return
    naturalWidth.value = imgRef.value.naturalWidth
    naturalHeight.value = imgRef.value.naturalHeight
    emit('natural-width', naturalWidth.value)
}

const wrapperStyle = computed(() => ({
    width: Math.round(naturalWidth.value * props.zoom) + 'px',
    height: Math.round(naturalHeight.value * props.zoom) + 'px',
}))

const containerStyle = computed(() => ({
    width: naturalWidth.value + 'px',
    height: naturalHeight.value + 'px',
    transformOrigin: 'top left',
    transform: `scale(${props.zoom})`,
}))

function fieldStyle(field: PdfPage['fields'][number]) {
    return {
        left: field.css_left * PTS_TO_PX + 'px',
        top: field.css_top * PTS_TO_PX + 'px',
        width: field.css_width * PTS_TO_PX + 'px',
        height: field.css_height * PTS_TO_PX + 'px',
    }
}
</script>

<template>
    <div class="page-wrapper" :style="wrapperStyle">
        <div
            :id="`page-${page.page_number}`"
            class="page-container"
            :style="containerStyle"
        >
            <img
                ref="imgRef"
                :src="`/documents/${documentId}/pages/${page.id}/image`"
                class="pdf-background"
                @load="onImageLoad"
            />
            <div
                v-for="field in page.fields"
                :key="field.id"
                class="field-overlay"
                :style="fieldStyle(field)"
            >
                {{ field.field_name }}
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-wrapper {
    margin: 0 auto 10px;
    overflow: hidden;
    border: 1px solid grey;
    box-sizing: border-box;
}
.page-container {
    position: relative;
    overflow: hidden;
}
.pdf-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
.field-overlay {
    position: absolute;
    border: 2px solid #3b82f6;
    background: rgba(59, 130, 246, 0.1);
    font-size: 11px;
    padding: 2px;
    box-sizing: border-box;
    cursor: move;
}
</style>
