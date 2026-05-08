<script setup>
/**
 * Coloured clickable chip representing a single planning event in the
 * resource grid. Wraps a native button with the event status tone
 * applied as inline style — no App* component fits this case because
 * the colour comes from the event's planning + status, not from a
 * fixed variant.
 */
defineProps({
    event: { type: Object, required: true },
    backgroundColor: { type: String, required: true },
    extraClass: { type: String, default: "" },
});

defineEmits(["select"]);

function formatHour(isoDate) {
    if (!isoDate) return "";
    return new Intl.DateTimeFormat(document.documentElement.lang || "fr", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    }).format(new Date(isoDate));
}
</script>

<template>
    <button
        type="button"
        class="text-left text-xs text-white px-1.5 py-1 rounded shadow-sm truncate hover:brightness-110 transition w-full"
        :class="extraClass"
        :style="{ backgroundColor }"
        :aria-label="event.title"
        v-on:click.stop="$emit('select', event)"
    >
        <span v-if="!event.allDay" class="font-medium opacity-90 mr-1">
            {{ formatHour(event.startAt) }}
        </span>
        {{ event.title }}
    </button>
</template>
