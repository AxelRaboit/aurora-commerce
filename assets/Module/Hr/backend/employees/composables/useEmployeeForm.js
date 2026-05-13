import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";
import { required } from "@/shared/utils/validation/validators.js";

const DEFAULT_FORM = {
    firstName: "",
    lastName:  "",
    jobTitle:  null,
    phone:     null,
    workEmail: null,
    hiredAt:   null,
    leftAt:    null,
    userId:    null,
    serviceId: null,
    agencyId:  null,
};

export function useEmployeeForm(createPath, updatePath, options = {}) {
    const { t } = useI18n();
    const extraFields       = options.extraFields ?? {};
    const onSuccessCallback = options.onSuccess ?? null;

    const { modal, form, errors, loading, openCreate, openEdit, close, submit } = useFormModal({
        empty: () => ({
            ...DEFAULT_FORM,
            ...Object.fromEntries(
                Object.entries(extraFields).map(([key, def]) => [key, def.default]),
            ),
        }),
        fromEntity: (employee) => ({
            firstName: employee.firstName,
            lastName:  employee.lastName,
            jobTitle:  employee.jobTitle ?? null,
            phone:     employee.phone ?? null,
            workEmail: employee.workEmail ?? null,
            hiredAt:   employee.hiredAt ?? null,
            leftAt:    employee.leftAt ?? null,
            userId:    employee.user?.id ? String(employee.user.id) : null,
            serviceId: employee.service?.id ? String(employee.service.id) : null,
            agencyId:  employee.agency?.id ? String(employee.agency.id) : null,
            ...Object.fromEntries(
                Object.entries(extraFields).map(([key, def]) => [key, def.fromEntity(employee)]),
            ),
        }),
        createUrl: () => createPath,
        editUrl:   (employee) => buildPath(updatePath, { id: employee.id }),
        rules: () => ({
            firstName: () => required(t("backend.employees.errors.first_name_required"))(form.firstName),
            lastName:  () => required(t("backend.employees.errors.last_name_required"))(form.lastName),
        }),
        onSuccess: ({ isCreate }) => {
            toast.success(
                isCreate
                    ? t("backend.employees.toast.created")
                    : t("backend.employees.toast.updated"),
            );
            onSuccessCallback?.();
        },
    });

    return { modal, form, errors, loading, openCreate, openEdit, close, submit };
}
