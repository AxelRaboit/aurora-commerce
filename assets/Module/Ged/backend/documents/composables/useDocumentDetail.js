import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useDocumentDetail(versionsPath) {
    const viewingDoc = ref(null);
    const viewingDocVersions = ref([]);

    async function viewDoc(doc) {
        viewingDoc.value = doc;
        viewingDocVersions.value = [];

        if (!versionsPath) return;

        fetch(buildPath(versionsPath, { id: doc.id }), {
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((r) => r.json())
            .then((data) => {
                if (data.success)
                    viewingDocVersions.value = data.versions ?? [];
            })
            .catch(() => {});
    }

    function closeDetail() {
        viewingDoc.value = null;
        viewingDocVersions.value = [];
    }

    return { viewingDoc, viewingDocVersions, viewDoc, closeDetail };
}
