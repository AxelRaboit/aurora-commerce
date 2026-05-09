import { ref, computed } from "vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";

export function useCart(props) {
    const cart = ref(props.initialCart);
    const pendingUpdates = ref(new Set());
    const updatingHeader = ref(false);

    const items = computed(() => cart.value.items ?? []);
    const isEmpty = computed(() => items.value.length === 0);

    async function postJSON(url, body) {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
            body: JSON.stringify(body),
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    }

    function broadcastCartChange(count) {
        updatingHeader.value = true;
        document.dispatchEvent(
            new CustomEvent("cart:changed", { detail: { count } }),
        );
        setTimeout(() => {
            updatingHeader.value = false;
        }, 200);
    }

    async function syncQuantity(listingId, quantity) {
        pendingUpdates.value.add(listingId);
        try {
            const data = await postJSON(props.updatePath, {
                listingId,
                quantity,
            });
            if (data.success) {
                cart.value = data.cart;
                broadcastCartChange(data.cart.totalQuantity);
            }
        } catch {
            // silently fail; user will see stale state and can retry
        } finally {
            pendingUpdates.value.delete(listingId);
        }
    }

    const debouncedSync = useDebounce(syncQuantity, 400);

    function onQuantityChange(item, newValue) {
        const qty = Math.max(1, parseInt(newValue, 10) || 1);
        item.quantity = qty;
        item.subtotal = item.unitPrice * qty;
        cart.value.totalQuantity = items.value.reduce(
            (sum, it) => sum + it.quantity,
            0,
        );
        cart.value.total = items.value.reduce(
            (sum, it) => sum + it.subtotal,
            0,
        );
        debouncedSync(item.listingId, qty);
    }

    function increment(item) {
        onQuantityChange(item, item.quantity + 1);
    }
    function decrement(item) {
        if (item.quantity > 1) onQuantityChange(item, item.quantity - 1);
    }

    async function removeItem(item) {
        pendingUpdates.value.add(item.listingId);
        try {
            const data = await postJSON(props.removePath, {
                listingId: item.listingId,
            });
            if (data.success) {
                cart.value = data.cart;
                broadcastCartChange(data.cart.totalQuantity);
            }
        } finally {
            pendingUpdates.value.delete(item.listingId);
        }
    }

    return {
        cart,
        pendingUpdates,
        updatingHeader,
        items,
        isEmpty,
        onQuantityChange,
        increment,
        decrement,
        removeItem,
    };
}
