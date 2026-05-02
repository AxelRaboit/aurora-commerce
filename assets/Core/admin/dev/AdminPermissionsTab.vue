<script setup>
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import { useAdminPermissions } from "@core/admin/dev/composables/useAdminPermissions.js";

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
            const moduleLabel = t(`admin.modules.${moduleEntry.id}`);
            const moduleHit = matches(moduleEntry.id, query) || matches(moduleLabel, query);
            const matchingPerms = moduleEntry.permissions.filter((permission) => {
                if (moduleHit) return true;
                const label = t(`admin.permissions.names.${permission.name}`);
                const role = t(`admin.roles.${permission.role}`);
                return matches(permission.name, query)
                    || matches(label, query)
                    || matches(permission.role, query)
                    || matches(role, query);
            });
            return matchingPerms.length ? { ...moduleEntry, permissions: matchingPerms } : null;
        })
        .filter(Boolean);
});
</script>

<template>
    <div class="space-y-3">
        <p class="text-sm text-secondary">{{ t('admin.permissions.intro') }}</p>
        <AppSearchInput
            v-model="searchInput"
            :placeholder="t('admin.permissions.searchPlaceholder')"
        />
        <p v-if="!filteredModules.length" class="py-8 text-center text-sm text-muted">{{ t('admin.permissions.empty') }}</p>
        <div v-for="moduleEntry in filteredModules" :key="moduleEntry.id" class="bg-surface border border-line rounded-lg overflow-hidden">
            <div class="bg-surface-2 border-b border-line px-4 py-2.5">
                <h3 class="text-sm font-semibold text-primary">{{ t(`admin.modules.${moduleEntry.id}`) }}</h3>
            </div>
            <p v-if="!moduleEntry.permissions.length" class="px-4 py-3 text-xs text-muted">{{ t('admin.permissions.none') }}</p>
            <table v-else class="w-full text-sm">
                <thead class="border-b border-line">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-secondary text-xs uppercase tracking-wide">{{ t('admin.permissions.name') }}</th>
                        <th class="px-4 py-2 text-left font-semibold text-secondary text-xs uppercase tracking-wide">{{ t('admin.permissions.role') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="permission in moduleEntry.permissions" :key="permission.name">
                        <td class="px-4 py-2"><span class="text-primary text-sm">{{ t(`admin.permissions.names.${permission.name}`) }}</span></td>
                        <td class="px-4 py-2"><span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-surface-2 text-secondary">{{ t(`admin.roles.${permission.role}`) }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
