<script setup>
import { useI18n } from "vue-i18n";
import { Check, X } from "lucide-vue-next";
import AppIconButton from "@/components/AppIconButton.vue";

const { t } = useI18n();

const props = defineProps({
    accessRequest: { type: Object, required: true },
});

const emit = defineEmits(["approve", "reject"]);
</script>

<template>
    <template v-if="accessRequest.status === 'pending'">
        <AppIconButton color="emerald" :title="t('admin.access_requests.approve')" v-on:click="emit('approve', props.accessRequest)">
            <Check class="w-4 h-4" :stroke-width="2" />
        </AppIconButton>
        <AppIconButton color="rose" :title="t('admin.access_requests.reject')" v-on:click="emit('reject', props.accessRequest)">
            <X class="w-4 h-4" :stroke-width="2" />
        </AppIconButton>
    </template>
</template>
