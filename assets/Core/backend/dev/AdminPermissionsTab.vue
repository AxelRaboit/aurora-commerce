<script setup>
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import { useAdminPermissions } from "@core/backend/dev/composables/useAdminPermissions.js";

const { t } = useI18n();

const props = defineProps({
    permissionsPath: { type: String, required: true },
    initialData: { type: Object, default: null },
});

const { data, load } = useAdminPermissions(props.permissionsPath, props.initialData);

onMounted(() => {
    if (!data.value?.modules?.length) load();
});

// Client-side filter — the registry is small and fully loaded, no need to
// round-trip the server.
const searchInput = ref("");

function matches(haystack, needle) {
    return haystack.toLowerCase().includes(needle);
}

const filteredModules = computed(() => {
    const query = searchInput.value.trim().toLowerCase();
    const modules = data.value?.modules ?? [];
    if (!query) return modules;
    return modules
        .map((moduleEntry) => {
            const moduleLabel = t(`backend.modules.${moduleEntry.id}`);
            const moduleHit = matches(moduleEntry.id, query) || matches(moduleLabel, query);
            const matchingPerms = moduleEntry.permissions.filter((permission) => {
                if (moduleHit) return true;
                const label = t(`backend.permissions.names.${permission.name}`);
                return matches(permission.name, query) || matches(label, query);
            });
            return matchingPerms.length ? { ...moduleEntry, permissions: matchingPerms } : null;
        })
        .filter(Boolean);
});
</script>

<template>
    <div class="space-y-3">
        <p class="text-sm text-secondary">{{ t('backend.permissions.intro') }}</p>
        <AppSearchInput
            v-model="searchInput"
            :placeholder="t('backend.permissions.searchPlaceholder')"
        />
        <p v-if="!filteredModules.length" class="py-8 text-center text-sm text-muted">{{ t('backend.permissions.empty') }}</p>
        <div v-for="moduleEntry in filteredModules" :key="moduleEntry.id" class="bg-surface border border-line rounded-lg overflow-hidden">
            <div class="bg-surface-2 border-b border-line px-4 py-2.5">
                <h3 class="text-sm font-semibold text-primary">{{ t(`backend.modules.${moduleEntry.id}`) }}</h3>
            </div>
            <p v-if="!moduleEntry.permissions.length" class="px-4 py-3 text-xs text-muted">{{ t('backend.permissions.none') }}</p>
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.permissions.name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted font-mono">{{ t('backend.permissions.key') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="permission in moduleEntry.permissions" :key="permission.name" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-4 py-2"><span class="text-primary text-sm">{{ t(`backend.permissions.names.${permission.name}`) }}</span></td>
                        <td class="px-4 py-2"><span class="font-mono text-xs text-muted">{{ permission.name }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
