import { type Ref, onBeforeUnmount } from 'vue'
import interact from 'interactjs'
import type { PdfField } from '@/types'
import { PTS_TO_PX } from '@/const'


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
                        field.css_left += event.dx / zoom.value / PTS_TO_PX
                        field.css_top += event.dy / zoom.value / PTS_TO_PX
                        onMoved({ ...field })
                    },
                },
            })
            .resizable({
                edges: { left: true, right: true, bottom: true, top: true },
                listeners: {
                    move(event) {
                        field.css_left += event.deltaRect.left / zoom.value / PTS_TO_PX
                        field.css_top += event.deltaRect.top / zoom.value / PTS_TO_PX
                        field.css_width = event.rect.width / zoom.value / PTS_TO_PX
                        field.css_height = event.rect.height / zoom.value / PTS_TO_PX
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
