import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { SettingErrorCode } from "@core/utils/enums/settings/settingErrorCode.js";
import { ParameterType } from "@core/utils/enums/settings/parameterType.js";

export function useSettingsForm(groups, availableGroups, updatePath) {
    const { t } = useI18n();

    const fieldValues = reactive({});
    const initialValues = {};
    const parameterByKey = {};
    const mediaState = reactive({});

    for (const groupName of availableGroups) {
        for (const parameter of groups[groupName]) {
            const value = parameter.value ?? "";
            fieldValues[parameter.key] = value;
            initialValues[parameter.key] = value;
            parameterByKey[parameter.key] = parameter;
            if (parameter.type === ParameterType.Media) {
                mediaState[parameter.key] = {
                    id: value ? Number(value) : null,
                    url: parameter.mediaUrl ?? null,
                };
            }
        }
    }

    function onMediaChange(parameter, picked) {
        const id = picked?.id ?? null;
        const url = picked?.url ?? null;
        mediaState[parameter.key] = { id, url };
        fieldValues[parameter.key] = id ? String(id) : "";
    }

    function dependencyDepth(parameter) {
        let depth = 0;
        let current = parameter.requires;
        while (current) {
            depth++;
            current = parameterByKey[current]?.requires;
        }
        return depth;
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

    function onBoolChange(parameter, enabled) {
        fieldValues[parameter.key] = enabled ? "1" : "0";
        if (!enabled) {
            for (const child of Object.values(parameterByKey)) {
                if (
                    child.requires === parameter.key &&
                    fieldValues[child.key] === "1"
                ) {
                    onBoolChange(child, false);
                }
            }
        }
    }

    const savingGroups = reactive({});

    async function saveGroup(groupName) {
        savingGroups[groupName] = true;

        const changed = groups[groupName]
            .filter((p) => fieldValues[p.key] !== initialValues[p.key])
            .sort((a, b) => dependencyDepth(a) - dependencyDepth(b));

        if (changed.length === 0) {
            savingGroups[groupName] = false;
            toast.success(t("backend.settings.saved"));
            return;
        }

        try {
            for (const parameter of changed) {
                const response = await fetch(updatePath, {
                    method: HttpMethod.Post,
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        key: parameter.key,
                        value: fieldValues[parameter.key],
                    }),
                });

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
            savingGroups[groupName] = false;
        }
    }

    return {
        fieldValues,
        mediaState,
        isLocked,
        lockReason,
        onBoolChange,
        onMediaChange,
        savingGroups,
        saveGroup,
    };
}
