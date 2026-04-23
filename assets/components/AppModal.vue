<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    maxWidth: { type: String, default: "md" },
    closeable: { type: Boolean, default: true },
    noPadding: { type: Boolean, default: false },
    scrollable: { type: Boolean, default: false },
});

const emit = defineEmits(["close"]);
const showSlot = ref(props.show);

watch(() => props.show, (show) => {
    if (show) {
        document.body.style.overflow = "hidden";
        showSlot.value = true;
    } else {
        document.body.style.overflow = "";
        setTimeout(() => { showSlot.value = false; }, 200);
    }
});

function close() {
    if (props.closeable) emit("close");
}

function closeOnEscape(event) {
    if (event.key === "Escape") {
        event.preventDefault();
        if (props.show) close();
    }
}

onMounted(() => {
    document.addEventListener("keydown", closeOnEscape);
    if (props.show) document.body.style.overflow = "hidden";
});
onUnmounted(() => {
    document.removeEventListener("keydown", closeOnEscape);
    document.body.style.overflow = "";
});

const maxWidthClass = computed(() => ({
    sm: "max-w-sm",
    md: "max-w-md",
    lg: "max-w-lg",
    xl: "max-w-xl",
    "3xl": "max-w-3xl",
}[props.maxWidth] ?? "max-w-md"));

const panelClass = computed(() => [
    maxWidthClass.value,
    props.noPadding ? "overflow-hidden" : "p-6 space-y-4",
    props.scrollable ? "overflow-y-auto max-h-[90vh]" : "",
]);
</script>

<template>
    <Teleport to="body">
        <div v-if="showSlot" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <Transition
                enter-active-class="ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="show" class="fixed inset-0 bg-black/60" v-on:click="close" />
            </Transition>

            <Transition
                enter-active-class="ease-out duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="ease-in duration-150"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-show="show"
                    class="relative z-10 w-full bg-surface border border-line rounded-xl shadow-xl"
                    :class="panelClass"
                >
                    <slot />
                </div>
            </Transition>
        </div>
    </Teleport>
</template>
