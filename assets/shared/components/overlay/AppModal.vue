<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { X } from "lucide-vue-next";
import { useI18n } from "vue-i18n";
import { useBackButtonClose } from "@/shared/composables/overlay/useBackButtonClose.js";

const props = defineProps({
    show: { type: Boolean, default: false },
    maxWidth: { type: String, default: "md" },
    closeable: { type: Boolean, default: true },
    // When provided, renders a flex header row (title + X close button).
    // Leave empty to manage the header yourself inside the slot.
    title: { type: String, default: null },
    noPadding: { type: Boolean, default: false },
    scrollable: { type: Boolean, default: true },
});

const { t } = useI18n();
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

const { requestClose } = useBackButtonClose({
    isOpen: () => props.show,
    onClose: () => emit("close"),
});

function close() {
    if (props.closeable) requestClose();
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
    "2xl": "max-w-2xl",
    "3xl": "max-w-3xl",
    "4xl": "max-w-4xl",
    "5xl": "max-w-5xl",
    "6xl": "max-w-6xl",
    "7xl": "max-w-7xl",
    full: "max-w-full",
}[props.maxWidth] ?? "max-w-md"));

const panelClass = computed(() => [
    maxWidthClass.value,
    props.noPadding ? "overflow-hidden" : "p-6 space-y-4",
    props.scrollable ? "overflow-y-auto max-h-[90vh] scrollbar-thin" : "",
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
                    role="dialog"
                    aria-modal="true"
                    class="relative z-10 w-full bg-surface border border-line rounded-xl shadow-xl"
                    :class="panelClass"
                >
                    <div v-if="title" class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-primary">{{ title }}</h2>
                        <button
                            v-if="closeable"
                            type="button"
                            class="shrink-0 p-1.5 text-muted hover:text-primary hover:bg-surface-2 rounded-lg transition-colors"
                            :aria-label="t('shared.common.close')"
                            v-on:click="close"
                        >
                            <X class="w-4 h-4" :stroke-width="2" />
                        </button>
                    </div>
                    <slot />
                </div>
            </Transition>
        </div>
    </Teleport>
</template>
