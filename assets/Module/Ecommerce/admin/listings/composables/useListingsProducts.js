import { ref, onMounted } from "vue";

export function useListingsProducts(productsPath) {
    const availableProducts = ref([]);

    async function loadProducts() {
        const response = await fetch(productsPath, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        const data = await response.json();
        if (data.success) availableProducts.value = data.items;
    }

    onMounted(loadProducts);

    return { availableProducts, loadProducts };
}
