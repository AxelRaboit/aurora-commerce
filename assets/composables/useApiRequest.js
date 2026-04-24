import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpStatus } from "@/enums/HttpStatus.js";

export function useApiRequest() {
    const { t } = useI18n();
    const loading = ref(false);

    async function request(url, body = null, method = "POST") {
        if (loading.value) return null;
        loading.value = true;
        try {
            const options = {
                method,
                headers: { "Content-Type": "application/json" },
            };
            if (body !== null) options.body = JSON.stringify(body);
            const response = await fetch(url, options);
            if (
                !response.ok &&
                response.status !== HttpStatus.UnprocessableEntity &&
                response.status !== HttpStatus.Conflict
            )
                throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } catch {
            toast.error(t("common.error"));
            return null;
        } finally {
            loading.value = false;
        }
    }

    return { loading, request };
}
