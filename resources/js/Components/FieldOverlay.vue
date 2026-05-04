<script setup>
import { ref, onMounted } from 'vue'
import interact from 'interactjs'

const props = defineProps({
    field: Object,
    position: Object,
})

const emit = defineEmits(['moved'])

const el = ref(null)
let currentX = 0
let currentY = 0

onMounted(() => {
    interact(el.value).draggable({
        listeners: {
            move(event) {
                currentX += event.dx
                currentY += event.dy

                event.target.style.transform =
                    `translate(${currentX}px, ${currentY}px)`

                emit('moved', {
                    name: props.field.FieldName,
                    x: props.position.x + currentX,
                    y: props.position.y + currentY,
                })
            }
        }
    })
})
</script>

<template>
    <div
        ref="el"
        class="pdf-field"
        :style="{
            position: 'absolute',
            left: position.x + 'px',
            top: position.y + 'px',
            width: position.w + 'px',
            height: position.h + 'px',
        }"
    >
        {{ field.FieldName }}
    </div>
</template>

<style scoped>
.pdf-field {
    border: 2px solid #3b82f6;
    background: rgba(59, 130, 246, 0.1);
    cursor: move;
    font-size: 11px;
    padding: 2px;
    box-sizing: border-box;
}
</style>