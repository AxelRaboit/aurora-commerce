import { reactive } from "vue";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";

/**
 * Unified create+edit modal composable.
 *
 * Handles the universal pattern of a single modal that serves both creation
 * and editing, with conditional URL and success message.
 *
 * openCreate()        → resets form, entity=null, opens modal
 * openEdit(entity)    → populates form, entity=set, opens modal
 * submit()            → validates → posts to createUrl or editUrl → handles response
 * modal.entity        → null while creating, the entity while editing
 * modal.open          → bind to :show on AppModal
 *
 * @param {Object} options
 * @param {() => object}                 options.empty       Factory for a blank form.
 * @param {(entity: *) => object}       [options.fromEntity] Populate form from existing entity.
 * @param {() => string}                 options.createUrl
 * @param {(entity: *) => string}        options.editUrl
 * @param {(form: object) => object}    [options.buildBody]  Transform form before sending (default: spread).
 * @param {() => Record<string, () => string|null>} [options.rules] Lazy validation rules.
 * @param {({ data, isCreate, entity }) => void|Promise} [options.onSuccess]
 *
 * Usage:
 *   const { modal, form, errors, loading, openCreate, openEdit, submit } = useFormModal({
 *     empty:      () => ({ name: "" }),
 *     fromEntity: (item) => ({ name: item.name }),
 *     createUrl:  () => createPath,
 *     editUrl:    (item) => buildPath(updatePath, { id: item.id }),
 *     rules:      () => ({ name: () => required(t("…"))(form.name) }),
 *     onSuccess:  ({ isCreate }) => {
 *       toast.success(t(isCreate ? "…created" : "…updated"));
 *       reset();
 *     },
 *   });
 */
export function useFormModal({
    empty,
    fromEntity,
    createUrl,
    editUrl,
    buildBody,
    rules,
    onSuccess,
} = {}) {
    const modal = reactive({ open: false, entity: null });
    const form = reactive({ ...empty?.() });

    const { errors, validate, clearErrors, handleErrors } = useServerErrors();
    const { loading, request } = useRequest();

    function openCreate() {
        if (empty) Object.assign(form, empty());
        clearErrors();
        modal.entity = null;
        modal.open = true;
    }

    function openEdit(entity) {
        if (fromEntity) Object.assign(form, fromEntity(entity));
        else if (empty) Object.assign(form, empty());
        clearErrors();
        modal.entity = entity;
        modal.open = true;
    }

    function close() {
        modal.open = false;
    }

    async function submit() {
        if (rules && !validate(rules())) return;

        const isCreate = modal.entity === null;
        const url = isCreate ? createUrl?.() : editUrl?.(modal.entity);
        const body = buildBody ? buildBody(form) : { ...form };
        const data = await request(url, body);
        if (!data) return;

        if (data.success) {
            clearErrors();
            modal.open = false;
            await onSuccess?.({ data, isCreate, entity: modal.entity });
        } else {
            handleErrors(data.errors);
        }
    }

    return {
        modal,
        form,
        errors,
        loading,
        openCreate,
        openEdit,
        close,
        submit,
        clearErrors,
    };
}
