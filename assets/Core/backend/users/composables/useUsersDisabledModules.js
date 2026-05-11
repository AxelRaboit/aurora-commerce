import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useUsersDisabledModules(props, fetchUsers) {
    const { t } = useI18n();

    const modulesModal = reactive({
        open: false,
        user: null,
        saving: false,
    });
    const pendingDisabledModules = ref([]);

    function openModules(user) {
        modulesModal.user = user;
        pendingDisabledModules.value = [...(user.disabledModules ?? [])];
        modulesModal.open = true;
    }

    function toggleModule(moduleKey) {
        const index = pendingDisabledModules.value.indexOf(moduleKey);
        if (index >= 0) {
            pendingDisabledModules.value.splice(index, 1);
        } else {
            pendingDisabledModules.value.push(moduleKey);
        }
    }

    async function saveModules() {
        if (!modulesModal.user || !props.disabledModulesPath) {
            return;
        }
        modulesModal.saving = true;
        try {
            const url = buildPath(props.disabledModulesPath, {
                id: modulesModal.user.id,
            });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    disabledModules: pendingDisabledModules.value,
                }),
            });
            const data = await response.json();
            if (data?.success) {
                toast.success(t("backend.users.modules.saved"));
                modulesModal.open = false;
                fetchUsers();
            } else if (data?.message) {
                toast.error(t(data.message, data.message));
            }
        } finally {
            modulesModal.saving = false;
        }
    }

    return {
        modulesModal,
        pendingDisabledModules,
        openModules,
        toggleModule,
        saveModules,
    };
}
