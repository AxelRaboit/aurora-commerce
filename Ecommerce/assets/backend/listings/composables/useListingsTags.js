import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { pickTranslation } from "@/shared/utils/i18n/pickTranslation.js";

export function useListingsTags(tagsPath) {
    const { locale } = useI18n();
    const availableTags = ref([]);

    async function loadTags() {
        const response = await fetch(tagsPath, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        const data = await response.json();
        if (data.success) availableTags.value = data.items;
    }

    const flatTags = computed(() => {
        return availableTags.value.map((tag) => {
            const translation = pickTranslation(tag, locale.value);
            const name = translation?.name ?? `#${tag.id}`;
            return {
                id: tag.id,
                label: name,
                name,
                color: tag.color,
            };
        });
    });

    onMounted(loadTags);

    return { availableTags, flatTags, loadTags };
}
