<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore'

const editorStore = useEditorStore()
</script>

<template>
    <div class="nudge-pad">
        <button class="nudge-btn" @click="editorStore.nudge(0, -1)">↑</button>
        <button class="nudge-btn" @click="editorStore.nudge(-1, 0)">←</button>
        <button class="nudge-btn" @click="editorStore.nudge(1, 0)">→</button>
        <button class="nudge-btn" @click="editorStore.nudge(0, 1)">↓</button>
    </div>

    <label class="property-label">Font Size</label>
    <input type="number" class="property-input" v-model.number="editorStore.selectedField!.font_size"
        @input="editorStore.updateField(editorStore.selectedField!)" />

    <label class="property-label">Font Weight</label>
    <select class="property-input" v-model="editorStore.selectedField!.font_weight"
        @change="editorStore.updateField(editorStore.selectedField!)">
        <option value="normal">Normal</option>
        <option value="bold">Bold</option>
    </select>

    <label class="property-label">Text Align</label>
    <select class="property-input" v-model="editorStore.selectedField!.text_align"
        @change="editorStore.updateField(editorStore.selectedField!)">
        <option value="left">Left</option>
        <option value="center">Center</option>
        <option value="right">Right</option>
    </select>

    <label class="property-label">Text Color</label>
    <input type="color" class="property-input" :value="editorStore.selectedField!.text_color ?? '#000000'"
        @input="(e) => { editorStore.selectedField!.text_color = (e.target as HTMLInputElement).value; editorStore.updateField(editorStore.selectedField!) }" />

    <label class="property-label">Background</label>
    <input type="color" class="property-input" :value="editorStore.selectedField!.background_color ?? '#ffffff'"
        @input="(e) => { editorStore.selectedField!.background_color = (e.target as HTMLInputElement).value; editorStore.updateField(editorStore.selectedField!) }" />
</template>

<style scoped>
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

.nudge-btn:hover { background: #f0f0f0; }

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
</style>
