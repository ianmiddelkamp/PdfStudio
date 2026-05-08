import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { PdfField } from '@/types'

export const useEditorStore = defineStore('editor', () => {
    const selectedField = ref<PdfField | null>(null)
    const pendingChanges = new Map<number, PdfField>()

    function select(field: PdfField) {
        selectedField.value = field.id === selectedField.value?.id ? null : { ...field }
    }

    function updateField(field: PdfField) {
        pendingChanges.set(field.id, { ...field })
        if (selectedField.value?.id === field.id) {
            selectedField.value = { ...field }
        }
    }

    function nudge(dx: number, dy: number) {
        if (!selectedField.value) return
        selectedField.value.css_left += dx
        selectedField.value.css_top += dy
        updateField(selectedField.value)
    }

    return { selectedField, pendingChanges, select, updateField, nudge }
})
