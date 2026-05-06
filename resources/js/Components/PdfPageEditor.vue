<script setup lang="ts">
import { ref, computed, watch, nextTick} from 'vue'
import type { PdfPage, PdfField } from '@/types'
import { useFieldInteract } from '@/composables/useFieldInteract';
import { PTS_TO_PX } from '@/const';



const props = defineProps<{
    documentId: number
    page: PdfPage
    zoom: number,
    selectedFieldId: number | null
}>()

const emit = defineEmits<{
    (e: 'natural-width', width: number): void
    (e: 'select-field', fieldId: number): void
    (e: 'field-moved', field: PdfField): void
}>()


const zoom = computed(() => props.zoom)

const imgRef = ref<HTMLImageElement | null>(null)
const naturalWidth = ref(0)
const naturalHeight = ref(0)

const localFields = ref<PdfField[]>(props.page.fields.map(f => ({ ...f })))

const fieldEls = new Map<number, HTMLElement>()

const {addInteract, removeInteract} = useFieldInteract(zoom, (movedField) => emit('field-moved', movedField))

function setFieldRef(el: HTMLElement | null, fieldId: number) {
    if (el) fieldEls.set(fieldId, el)
    else fieldEls.delete(fieldId)
}



function startInteraction(fieldId: number) {
    const el = fieldEls.get(fieldId)
    const field = localFields.value.find(f => f.id === fieldId)
    if (!el || !field) return
    addInteract(el, field)
}

watch(
    () => props.selectedFieldId,
    (newId) => {
        removeInteract()
        if (newId === null) return
        const field = localFields.value.find(f => f.id === newId)
        if (field) nextTick(() => startInteraction(newId))
    }
)


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

function fieldStyle(field: PdfField) {
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
                v-for="field in localFields"
                :key="field.id"
                :ref="(el) => setFieldRef(el as HTMLElement | null, field.id)"
                class="field-overlay"
                :class="{selected: field.id === selectedFieldId}"
                @click.stop="emit('select-field', field.id)"
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
    user-select: none;
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
    cursor: pointer;
}
.field-overlay.selected {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    cursor: move;
}
</style>
