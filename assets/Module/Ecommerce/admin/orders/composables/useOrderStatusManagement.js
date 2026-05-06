import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Truck, PackageCheck } from "lucide-vue-next";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { OrderStatus } from "@/Module/Ecommerce/utils/enums/orderStatus.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useOrderStatusManagement(updateStatusPath, order, activity) {
    const { t } = useI18n();
    const { loading, request } = useApiRequest();

    const availableTransitions = computed(() => {
        const requiresShipping = order.value.requiresShipping ?? true;
        const map = {
            [OrderStatus.Pending]: [],
            [OrderStatus.Paid]: requiresShipping
                ? [
                      {
                          status: OrderStatus.Shipped,
                          label: t(
                              "admin.ecommerce.orders.actions.markShipped",
                          ),
                          icon: Truck,
                          color: "accent",
                      },
                  ]
                : [
                      {
                          status: OrderStatus.Delivered,
                          label: t(
                              "admin.ecommerce.orders.actions.markFulfilled",
                          ),
                          icon: PackageCheck,
                          color: "emerald",
                      },
                  ],
            [OrderStatus.Shipped]: [
                {
                    status: OrderStatus.Delivered,
                    label: t("backend.ecommerce.orders.actions.markDelivered"),
                    icon: PackageCheck,
                    color: "emerald",
                },
            ],
            [OrderStatus.Delivered]: [],
            [OrderStatus.Cancelled]: [],
        };
        return map[order.value.status] ?? [];
    });

    const canCancel = computed(
        () =>
            ![OrderStatus.Delivered, OrderStatus.Cancelled].includes(
                order.value.status,
            ),
    );

    const pendingTransition = ref(null);

    function confirmTransition(target) {
        pendingTransition.value = target;
    }

    async function applyTransition() {
        if (!pendingTransition.value) return;
        const target = pendingTransition.value;
        const data = await request(
            updateStatusPath,
            { status: target.status },
            HttpMethod.Patch,
        );
        pendingTransition.value = null;
        if (data?.success) {
            order.value = { ...order.value, ...data.order };
            toast.success(
                t("backend.ecommerce.orders.actions.transition_success"),
            );
            prependActivity(target.status);
        } else if (data?.error) {
            toast.error(data.error);
        }
    }

    function prependActivity(targetStatus) {
        activity.value = [
            {
                id: Date.now(),
                module: "ecommerce",
                action:
                    targetStatus === OrderStatus.Cancelled
                        ? "order.cancelled"
                        : `order.${targetStatus}`,
                entityType: "Order",
                entityId: order.value.id,
                userId: null,
                userEmail: null,
                userName: t("backend.ecommerce.orders.actions.you"),
                data: { number: order.value.number },
                createdAt: new Date().toISOString(),
            },
            ...activity.value,
        ];
    }

    function actionLabel(action) {
        const map = {
            "order.created": t("backend.ecommerce.orders.activity.created"),
            "order.paid": t("backend.ecommerce.orders.activity.paid"),
            "order.shipped": t("backend.ecommerce.orders.activity.shipped"),
            "order.delivered": t("backend.ecommerce.orders.activity.delivered"),
            "order.cancelled": t("backend.ecommerce.orders.activity.cancelled"),
        };
        return map[action] ?? action;
    }

    return {
        loading,
        availableTransitions,
        canCancel,
        pendingTransition,
        confirmTransition,
        applyTransition,
        actionLabel,
    };
}
