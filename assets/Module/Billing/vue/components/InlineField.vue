<script setup>
import { ref, computed, nextTick, watch } from "vue";
import { Pencil, Check, X } from "lucide-vue-next";
import AppDatePicker from "@/shared/components/form/AppDatePicker.vue";

const props = defineProps({
    /** Display value (already formatted) shown in read-only mode. */
    displayValue: { type: [String, Number, null], default: null },
    /** Raw value used to seed the input when entering edit mode. */
    rawValue: { type: [String, Number, null], default: null },
    /** Input type: text | date | money (cents) | number */
    type: { type: String, default: "text" },
    /** Currency suffix shown in money mode (e.g. "EUR"). */
    currency: { type: String, default: "" },
    /** Placeholder shown when value is empty and we hover. */
    placeholder: { type: String, default: "—" },
    /** Disable the inline behaviour (e.g. while saving). */
    disabled: { type: Boolean, default: false },
    /** Visual alignment of the read-only label and the input. */
    align: { type: String, default: "left", validator: (v) => ["left", "right"].includes(v) },
});

const emit = defineEmits(["save"]);

const editing = ref(false);
const inputRef = ref(null);
const localValue = ref("");
const saving = ref(false);

const formatted = computed(() => {
    const v = props.displayValue;
    return (v === null || v === undefined || v === "") ? props.placeholder : v;
});

const isEmpty = computed(() => {
    const v = props.displayValue;
    return v === null || v === undefined || v === "";
});

function seedValue() {
    if (props.type === "money") {
        // rawValue is cents; show in major units
        localValue.value = (props.rawValue === null || props.rawValue === undefined || props.rawValue === "")
            ? ""
            : (Number(props.rawValue) / 100).toString();
        return;
    }
    localValue.value = props.rawValue === null || props.rawValue === undefined ? "" : String(props.rawValue);
}

async function startEdit() {
    if (props.disabled || saving.value) return;
    seedValue();
    editing.value = true;
    if (props.type === "date") return; // AppDatePicker handles its own focus
    await nextTick();
    inputRef.value?.focus();
    inputRef.value?.select?.();
}

// Date picker: commits as soon as user picks (or clears) a date.
function onDatePicked(value) {
    localValue.value = value || "";
    commit();
}

function cancel() {
    editing.value = false;
}

async function commit() {
    if (!editing.value) return;

    let payload = localValue.value === "" ? null : localValue.value;

    if (props.type === "money" && payload !== null) {
        const num = Number(payload.toString().replace(',', '.'));
        if (Number.isNaN(num)) { cancel(); return; }
        payload = Math.round(num * 100); // back to cents
    } else if (props.type === "number" && payload !== null) {
        const num = Number(payload);
        if (Number.isNaN(num)) { cancel(); return; }
        payload = num;
    }

    saving.value = true;
    try {
        await emit("save", payload);
    } finally {
        saving.value = false;
        editing.value = false;
    }
}

function onKeydown(e) {
    if (e.key === "Enter") { e.preventDefault(); commit(); }
    else if (e.key === "Escape") { e.preventDefault(); cancel(); }
}

// Cancel edit if disabled flips on while editing.
watch(() => props.disabled, (d) => { if (d) editing.value = false; });
</script>

<template>
    <div class="inline-field" :class="align === 'right' ? 'text-right' : 'text-left'">
        <button
            v-if="!editing"
            type="button"
            class="group inline-flex items-center gap-1.5 rounded px-1 -mx-1 py-0.5 hover:bg-surface-2 transition-colors w-full"
            :class="[
                align === 'right' ? 'flex-row-reverse text-right' : 'text-left',
                { 'opacity-50 cursor-not-allowed': disabled, 'text-muted italic': isEmpty },
            ]"
            :disabled="disabled"
            v-on:click="startEdit"
        >
            <span class="flex-1 truncate" :class="align === 'right' ? 'tabular-nums' : ''">
                {{ formatted }}
                <span v-if="type === 'money' && !isEmpty && currency" class="text-muted ml-1">{{ currency }}</span>
            </span>
            <Pencil class="w-3 h-3 text-muted opacity-0 group-hover:opacity-100 transition-opacity shrink-0" :stroke-width="2" />
        </button>

        <AppDatePicker
            v-else-if="type === 'date'"
            :model-value="localValue"
            v-on:update:model-value="onDatePicked"
        />

        <div v-else class="flex items-center gap-1">
            <input
                ref="inputRef"
                v-model="localValue"
                :type="type === 'money' || type === 'number' ? 'number' : 'text'"
                :step="type === 'money' ? '0.01' : (type === 'number' ? 'any' : undefined)"
                class="w-full px-2 py-1 rounded border border-accent-500 bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent-500/30"
                :class="align === 'right' ? 'text-right tabular-nums' : ''"
                :disabled="saving"
                v-on:keydown="onKeydown"
            >
            <button type="button" :disabled="saving" class="inline-flex items-center justify-center w-6 h-6 rounded text-emerald-400 hover:bg-emerald-500/10 transition-colors shrink-0 disabled:opacity-50" v-on:click="commit">
                <Check class="w-3.5 h-3.5" :stroke-width="2.5" />
            </button>
            <button type="button" :disabled="saving" class="inline-flex items-center justify-center w-6 h-6 rounded text-rose-400 hover:bg-rose-500/10 transition-colors shrink-0 disabled:opacity-50" v-on:click="cancel">
                <X class="w-3.5 h-3.5" :stroke-width="2.5" />
            </button>
        </div>
    </div>
</template>
