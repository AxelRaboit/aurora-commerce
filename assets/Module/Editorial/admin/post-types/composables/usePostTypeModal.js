import { reactive } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";

const SUPPORTS = ["blocks", "thumbnail", "excerpt"];

export function usePostTypeModal(
    props,
    postTypes,
    selectedId,
    replacePostType,
) {
    const { modal, openCreate, openEdit, submit } = useFormModal();
    const form = reactive({
        slug: "",
        label: "",
        icon: "",
        hasArchive: false,
        supports: [],
        taxonomyIds: [],
    });

    function openCreatePostType() {
        openCreate(() =>
            Object.assign(form, {
                slug: "",
                label: "",
                icon: "",
                hasArchive: false,
                supports: [...SUPPORTS],
                taxonomyIds: [],
            }),
        );
    }

    function openEditPostType(pt) {
        openEdit(pt, (p) =>
            Object.assign(form, {
                slug: p.slug,
                label: p.label,
                icon: p.icon ?? "",
                hasArchive: p.hasArchive,
                supports: [...(p.supports ?? [])],
                taxonomyIds: [...(p.taxonomyIds ?? [])],
            }),
        );
    }

    async function submitPostType() {
        const url = modal.editing
            ? buildPath(props.editPath, { id: modal.editing.id })
            : props.createPath;
        await submit(url, form, (data) => {
            replacePostType(data.postType);
            selectedId.value = data.postType.id;
        });
    }

    function toggleIn(list, value) {
        return list.includes(value)
            ? list.filter((item) => item !== value)
            : [...list, value];
    }

    return {
        postTypeModal: modal,
        form: form,
        openCreatePostType,
        openEditPostType,
        submitPostType,
        toggleIn,
    };
}
