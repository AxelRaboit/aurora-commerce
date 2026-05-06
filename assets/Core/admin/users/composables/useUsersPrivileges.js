import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useUsersPrivileges(props, fetchUsers) {
    const { t } = useI18n();

    const privilegesModal = reactive({
        open: false,
        user: null,
        saving: false,
    });
    const pendingPrivileges = ref([]);

    function openPrivileges(user) {
        privilegesModal.user = user;
        pendingPrivileges.value = [...(user.privileges ?? [])];
        privilegesModal.open = true;
    }

    function togglePrivilege(name) {
        const idx = pendingPrivileges.value.indexOf(name);
        if (idx >= 0) pendingPrivileges.value.splice(idx, 1);
        else pendingPrivileges.value.push(name);
    }

    async function savePrivileges() {
        if (!privilegesModal.user || !props.privilegesPath) return;
        privilegesModal.saving = true;
        try {
            const url = buildPath(props.privilegesPath, {
                id: privilegesModal.user.id,
            });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ privileges: pendingPrivileges.value }),
            });
            const data = await response.json();
            if (data?.success) {
                toast.success(t("backend.users.privileges.saved"));
                privilegesModal.open = false;
                fetchUsers();
            }
        } finally {
            privilegesModal.saving = false;
        }
    }

    return {
        privilegesModal,
        pendingPrivileges,
        openPrivileges,
        togglePrivilege,
        savePrivileges,
    };
}
