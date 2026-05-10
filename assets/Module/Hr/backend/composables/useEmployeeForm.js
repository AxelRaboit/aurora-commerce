import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { required } from "@/shared/utils/validation/validators.js";

const DEFAULT_FORM = {
    firstName: "",
    lastName: "",
    jobTitle: null,
    phone: null,
    workEmail: null,
    hiredAt: null,
    leftAt: null,
    userId: null,
    serviceId: null,
    agencyId: null,
};

export function useEmployeeForm(createPath, updatePath, options = {}) {
    const { t } = useI18n();
    const { request } = useRequest();
    const { errors, validate, setErrors, clearErrors } = useForm();
    const extraFields = options.extraFields ?? {};
    const onSuccess = options.onSuccess ?? null;

    const modalOpen = ref(false);
    const saving = ref(false);
    const editingEmployee = ref(null);
    const form = reactive({
        ...DEFAULT_FORM,
        ...Object.fromEntries(
            Object.entries(extraFields).map(([key, def]) => [key, def.default]),
        ),
    });

    function resetForm() {
        Object.assign(form, DEFAULT_FORM);
        for (const [key, def] of Object.entries(extraFields)) {
            form[key] = def.default;
        }
    }

    function loadFrom(employee) {
        Object.assign(form, {
            firstName: employee.firstName,
            lastName: employee.lastName,
            jobTitle: employee.jobTitle ?? null,
            phone: employee.phone ?? null,
            workEmail: employee.workEmail ?? null,
            hiredAt: employee.hiredAt ?? null,
            leftAt: employee.leftAt ?? null,
            userId: employee.user?.id ? String(employee.user.id) : null,
            serviceId: employee.service?.id
                ? String(employee.service.id)
                : null,
            agencyId: employee.agency?.id ? String(employee.agency.id) : null,
        });
        for (const [key, def] of Object.entries(extraFields)) {
            form[key] = def.fromEntity(employee);
        }
    }

    function openCreate() {
        editingEmployee.value = null;
        clearErrors();
        resetForm();
        modalOpen.value = true;
    }

    function openEdit(employee) {
        editingEmployee.value = employee;
        clearErrors();
        loadFrom(employee);
        modalOpen.value = true;
    }

    function closeModal() {
        modalOpen.value = false;
    }

    async function submit() {
        const valid = validate({
            firstName: () =>
                required(t("backend.employees.errors.first_name_required"))(
                    form.firstName,
                ),
            lastName: () =>
                required(t("backend.employees.errors.last_name_required"))(
                    form.lastName,
                ),
        });
        if (!valid) return;

        saving.value = true;
        try {
            const isCreate = null === editingEmployee.value;
            const url = isCreate
                ? createPath
                : buildPath(updatePath, { id: editingEmployee.value.id });
            const data = await request(url, { ...form });

            if (!data?.success) {
                setErrors(translateServerErrors(t, data?.errors));
                return;
            }

            toast.success(
                isCreate
                    ? t("backend.employees.toast.created")
                    : t("backend.employees.toast.updated"),
            );
            modalOpen.value = false;
            onSuccess?.();
        } finally {
            saving.value = false;
        }
    }

    return {
        modalOpen,
        saving,
        form,
        errors,
        editingEmployee,
        openCreate,
        openEdit,
        closeModal,
        submit,
    };
}
