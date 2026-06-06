import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useOrderRefund(refundPath, order) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const showModal = ref(false);
    const refundAmount = ref("");
    const isFullRefund = ref(true);

    function open() {
        refundAmount.value = "";
        isFullRefund.value = true;
        showModal.value = true;
    }

    function close() {
        showModal.value = false;
    }

    async function confirm() {
        const payload = {};
        if (!isFullRefund.value && refundAmount.value) {
            // Convert to cents
            payload.amountCents = Math.round(
                parseFloat(refundAmount.value) * 100,
            );
        }

        const data = await request(refundPath, payload, HttpMethod.Post);

        if (data?.success) {
            order.value = { ...order.value, ...data.order };
            toast.success(t("backend.ecommerce.orders.refund.success"));
            showModal.value = false;
        } else if (data?.error) {
            toast.error(t(data.error) || data.error);
        }
    }

    return {
        loading,
        showModal,
        refundAmount,
        isFullRefund,
        open,
        close,
        confirm,
    };
}
