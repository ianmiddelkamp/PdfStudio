import { type Ref, onBeforeUnmount } from 'vue'
import interact from 'interactjs'
import type { PdfField } from '@/types'

interface FieldInteract {
    addInteract: (el: HTMLElement, field: PdfField) => void
    removeInteract: () => void
}

export function useFieldInteract(zoom: Ref<number>, onMoved: (field: PdfField) => void): FieldInteract {

    let activeInteract: ReturnType<typeof interact> | null = null

    function addInteract(el: HTMLElement, field: PdfField) {
        activeInteract = interact(el)
            .draggable({
                listeners: {
                    move(event) {
                        field.css_left += event.dx / zoom.value
                        field.css_top += event.dy / zoom.value
                        onMoved({ ...field })
                    },
                },
            })
            .resizable({
                edges: { left: true, right: true, bottom: true, top: true },
                listeners: {
                    move(event) {
                        field.css_left += event.deltaRect.left / zoom.value
                        field.css_top += event.deltaRect.top / zoom.value
                        field.css_width = event.rect.width / zoom.value
                        field.css_height = event.rect.height / zoom.value
                        onMoved({ ...field })
                    },
                },
            })
    }

    function removeInteract() {
        if (activeInteract) {
            activeInteract.unset()
            activeInteract = null
        }
    }

    onBeforeUnmount(() => removeInteract())
    return { addInteract, removeInteract }
}
