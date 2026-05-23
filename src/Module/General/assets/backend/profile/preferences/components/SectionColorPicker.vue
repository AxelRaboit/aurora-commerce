<script setup>
import { useI18n } from "vue-i18n";
import { Paintbrush, X } from "lucide-vue-next";

const { t } = useI18n();

defineProps({
    /** Currently picked colour name (null = use the section's default). */
    modelValue: { type: String, default: null },
});

const emit = defineEmits(["update:modelValue"]);

// Same palette as `useSidemenuSectionTheme` — keep in sync.
const PALETTE = [
    { name: "slate",   swatch: "bg-slate-500" },
    { name: "stone",   swatch: "bg-stone-500" },
    { name: "zinc",    swatch: "bg-zinc-500" },
    { name: "red",     swatch: "bg-red-500" },
    { name: "orange",  swatch: "bg-orange-500" },
    { name: "amber",   swatch: "bg-amber-500" },
    { name: "yellow",  swatch: "bg-yellow-500" },
    { name: "lime",    swatch: "bg-lime-500" },
    { name: "green",   swatch: "bg-green-500" },
    { name: "emerald", swatch: "bg-emerald-500" },
    { name: "teal",    swatch: "bg-teal-500" },
    { name: "cyan",    swatch: "bg-cyan-500" },
    { name: "sky",     swatch: "bg-sky-500" },
    { name: "blue",    swatch: "bg-blue-500" },
    { name: "indigo",  swatch: "bg-indigo-500" },
    { name: "violet",  swatch: "bg-violet-500" },
    { name: "purple",  swatch: "bg-purple-500" },
    { name: "fuchsia", swatch: "bg-fuchsia-500" },
    { name: "pink",    swatch: "bg-pink-500" },
    { name: "rose",    swatch: "bg-rose-500" },
];

function pick(colorName) {
    emit("update:modelValue", colorName);
}

function clear() {
    emit("update:modelValue", null);
}
</script>

<template>
    <div class="flex items-center gap-1.5">
        <button
            v-for="colour in PALETTE"
            :key="colour.name"
            type="button"
            class="w-5 h-5 rounded ring-offset-1 ring-offset-surface transition-all hover:scale-110"
            :class="[colour.swatch, modelValue === colour.name ? 'ring-2 ring-primary' : 'opacity-70 hover:opacity-100']"
            :title="colour.name"
            v-on:click="pick(colour.name)"
        />
        <button
            type="button"
            class="ml-1 p-1 rounded text-muted hover:text-primary hover:bg-surface-2 transition-colors"
            :title="t('backend.profile.sidemenu.color_reset')"
            :disabled="!modelValue"
            :class="{ 'opacity-30 cursor-not-allowed': !modelValue }"
            v-on:click="clear"
        >
            <X class="w-3.5 h-3.5" :stroke-width="2.5" />
        </button>
        <Paintbrush v-if="!modelValue" class="w-3.5 h-3.5 text-muted ml-1" :stroke-width="2" />
    </div>
</template>
