import { describe, it, expect } from "vitest";
import { useForm } from "./useForm.js";

describe("useForm", () => {
    it("starts with empty errors", () => {
        const { errors } = useForm();
        expect(errors.value).toEqual({});
    });

    it("validate returns true and clears errors when all checks pass", () => {
        const { errors, validate } = useForm();
        errors.value = { name: "required" };
        const valid = validate({ name: () => null });
        expect(valid).toBe(true);
        expect(errors.value).toEqual({});
    });

    it("validate returns false and populates errors when checks fail", () => {
        const { errors, validate } = useForm();
        const valid = validate({
            email: () => "email_required",
            name: () => null,
        });
        expect(valid).toBe(false);
        expect(errors.value).toEqual({ email: "email_required" });
    });

    it("setErrors replaces the errors ref", () => {
        const { errors, setErrors } = useForm();
        setErrors({ field: "some_error" });
        expect(errors.value).toEqual({ field: "some_error" });
    });

    it("clearErrors resets errors to empty object", () => {
        const { errors, setErrors, clearErrors } = useForm();
        setErrors({ field: "error" });
        clearErrors();
        expect(errors.value).toEqual({});
    });
});
