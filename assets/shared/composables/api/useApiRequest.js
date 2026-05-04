import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { HttpStatus } from "@/shared/utils/http/HttpStatus.js";

export function useApiRequest() {
    const { t } = useI18n();
    const loading = ref(false);

    async function request(url, body = null, method = HttpMethod.Post) {
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
                response.status !== HttpStatus.Conflict &&
                response.status !== HttpStatus.BadRequest
            )
                throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } catch {
            toast.error(t("shared.common.error"));
            return null;
        } finally {
            loading.value = false;
        }
    }

    return { loading, request };
}
