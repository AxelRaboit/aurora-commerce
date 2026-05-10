import { ref, reactive, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { SettingErrorCode } from "@core/utils/enums/settings/settingErrorCode.js";

export function useModules(
    modulesPath,
    moduleUpdatePath,
    moduleVerifyPasswordPath,
    initialData,
) {
    const { t } = useI18n();

    const parameters = ref(initialData?.parameters ?? []);
    const fieldValues = reactive({});
    const initialValues = {};
    const parameterByKey = {};
    const saving = ref(false);
    const searchInput = ref("");

    const filteredParameters = computed(() => {
        const query = searchInput.value.trim().toLowerCase();
        if (!query) return parameters.value;
        return parameters.value.filter(
            (parameter) =>
                parameter.label.toLowerCase().includes(query) ||
                parameter.key.toLowerCase().includes(query) ||
                (parameter.subModules ?? []).some(
                    (sub) =>
                        sub.label.toLowerCase().includes(query) ||
                        sub.key.toLowerCase().includes(query),
                ),
        );
    });

    // Password gate — verified once per page load, then all toggles are free
    const passwordVerified = ref(false);
    const showPasswordModal = ref(false);
    const password = ref("");
    const passwordError = ref("");
    const verifying = ref(false);
    const pendingToggle = ref(null);

    function init(params) {
        for (const parameter of params) {
            const value = parameter.value ?? "0";
            fieldValues[parameter.key] = value;
            initialValues[parameter.key] = value;
            parameterByKey[parameter.key] = parameter;

            for (const sub of parameter.subModules ?? []) {
                const subValue = sub.value ?? "0";
                fieldValues[sub.key] = subValue;
                initialValues[sub.key] = subValue;
                parameterByKey[sub.key] = sub;
            }
        }
    }

    init(parameters.value);

    async function load() {
        const response = await fetch(modulesPath, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        const data = await response.json();
        parameters.value = data.parameters ?? [];
        init(parameters.value);
    }

    function isLocked(parameter) {
        return parameter.requires
            ? fieldValues[parameter.requires] !== "1"
            : false;
    }

    function lockReason(parameter) {
        const parent = parameter.requires
            ? parameterByKey[parameter.requires]
            : null;
        return parent
            ? t("backend.settings.cascadeLocked", { parent: parent.label })
            : "";
    }

    function applyToggle(parameter, enabled) {
        fieldValues[parameter.key] = enabled ? "1" : "0";
        if (!enabled) {
            for (const child of Object.values(parameterByKey)) {
                if (
                    child.requires === parameter.key &&
                    fieldValues[child.key] === "1"
                ) {
                    applyToggle(child, false);
                }
            }
        }
    }

    function allParameters() {
        const all = [];
        for (const param of parameters.value) {
            all.push(param);
            for (const sub of param.subModules ?? []) {
                all.push(sub);
            }
        }
        return all;
    }

    async function save() {
        if (saving.value) return;
        saving.value = true;

        const changed = allParameters()
            .filter(
                (parameter) =>
                    fieldValues[parameter.key] !== initialValues[parameter.key],
            )
            .sort((a, b) => (a.requires ? 1 : 0) - (b.requires ? 1 : 0));

        try {
            for (const parameter of changed) {
                const response = await fetch(
                    moduleUpdatePath.replace("__key__", parameter.key),
                    {
                        method: HttpMethod.Patch,
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            value: fieldValues[parameter.key],
                        }),
                    },
                );

                const result = await response.json();

                if (!result.success) {
                    if (result.error === SettingErrorCode.CascadeViolation) {
                        const parent = parameterByKey[result.parentKey];
                        toast.error(
                            t("backend.settings.cascadeLocked", {
                                parent: parent?.label ?? result.parentKey,
                            }),
                        );
                    } else {
                        toast.error(t("shared.common.error"));
                    }
                    return;
                }

                initialValues[parameter.key] = fieldValues[parameter.key];
            }

            toast.success(t("backend.settings.saved"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            saving.value = false;
        }
    }

    function onToggle(parameter, enabled) {
        if (passwordVerified.value) {
            applyToggle(parameter, enabled);
            save();
            return;
        }
        pendingToggle.value = { parameter, enabled };
        password.value = "";
        passwordError.value = "";
        showPasswordModal.value = true;
    }

    async function confirmPassword() {
        passwordError.value = "";
        verifying.value = true;

        try {
            const response = await fetch(moduleVerifyPasswordPath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ password: password.value }),
            });

            if (!response.ok) {
                passwordError.value = t(
                    "backend.settings.confirmPasswordInvalid",
                );
                return;
            }
        } catch {
            passwordError.value = t("backend.settings.confirmPasswordInvalid");
            return;
        } finally {
            verifying.value = false;
        }

        passwordVerified.value = true;
        showPasswordModal.value = false;

        if (pendingToggle.value) {
            applyToggle(
                pendingToggle.value.parameter,
                pendingToggle.value.enabled,
            );
            pendingToggle.value = null;
            save();
        }
    }

    return {
        parameters,
        filteredParameters,
        fieldValues,
        saving,
        searchInput,
        showPasswordModal,
        password,
        passwordError,
        verifying,
        load,
        isLocked,
        lockReason,
        onToggle,
        confirmPassword,
    };
}
