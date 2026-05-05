<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import FieldOverlay from '@/Components/FieldOverlay.vue'

const props = defineProps({
    document: Object,
    fields: Array,
})
const fields =  props.fields ?? [];
// Track positions locally — starts from parsed PDF positions
const positions = ref(
    Object.fromEntries(
        fields.map(field => [
            field.FieldName,
            {
                x: parseFloat(field.FieldRect?.split(',')[0] ?? 0),
                y: parseFloat(field.FieldRect?.split(',')[1] ?? 0),
                w: parseFloat(field.FieldRect?.split(',')[2] ?? 100),
                h: parseFloat(field.FieldRect?.split(',')[3] ?? 20),
            }
        ])
    )
)

function savePositions() {
    router.post(`/documents/${props.document.id}/fields`, {
        fields: positions.value,
    })
}

function deleteDocument(){
    router.delete(`/documents/${props.document.id}`)
}

</script>

<template>
    <div>
        <h1>{{ document.original_name }}</h1>

        <div id="pdf-editor" style="position: relative;">
            <FieldOverlay
                v-for="field in fields"
                :key="field.FieldName"
                :field="field"
                :position="positions[field.FieldName]"
                @moved="({ name, x, y }) => { positions[name].x = x; positions[name].y = y }"
            />
        </div>

        <button @click="savePositions">Save Positions</button>
        <button @click="deleteDocument">Delete Document</button>
    </div>
</template>