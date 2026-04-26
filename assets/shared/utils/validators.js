import { EMAIL_REGEX } from "@/shared/utils/validation.js";

export const required = (msg) => (value) => {
    if (value === null || value === undefined) return msg;
    if (typeof value === "string" && !value.trim()) return msg;
    if (Array.isArray(value) && value.length === 0) return msg;
    return null;
};

export const email = (msg) => (value) => {
    if (!value || !String(value).trim()) return null;
    return EMAIL_REGEX.test(String(value).trim()) ? null : msg;
};

export const compose =
    (...validators) =>
    (value) => {
        for (const validator of validators) {
            const error = validator(value);
            if (error) return error;
        }
        return null;
    };
