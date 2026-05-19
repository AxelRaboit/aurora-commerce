import { ref, computed, onMounted } from "vue";

export function useContactsTags(tagsPath) {
    const availableTags = ref([]);

    async function loadTags() {
        const response = await fetch(tagsPath, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        const data = await response.json();
        if (data.success) availableTags.value = data.items;
    }

    const flatTags = computed(() => {
        return availableTags.value.map((tag) => ({
            id: tag.id,
            label: tag.label,
            name: tag.label,
            color: tag.color,
        }));
    });

    onMounted(loadTags);

    return { availableTags, flatTags, loadTags };
}
