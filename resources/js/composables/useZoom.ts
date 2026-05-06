import { ref, onMounted, onUnmounted, type Ref } from 'vue'

export function useZoom(containerRef: Ref<HTMLElement | null>, naturalWidth: Ref<number>) {
    const zoom = ref(1)

    function recalculate() {
        if (!containerRef.value || naturalWidth.value === 0) return
        zoom.value = containerRef.value.clientWidth / naturalWidth.value
    }

    onMounted(() => {
        window.addEventListener('resize', recalculate)
    })

    onUnmounted(() => {
        window.removeEventListener('resize', recalculate)
    })

    return { zoom, recalculate }
}
