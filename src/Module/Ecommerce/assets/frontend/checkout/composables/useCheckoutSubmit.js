import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";

export function useCheckoutSubmit(submitPath, confirmPayment) {
    const { t } = useI18n();

    const errors = reactive({});
    const stockError = ref("");
    const processing = ref(false);

    function clearErrors() {
        Object.keys(errors).forEach((k) => delete errors[k]);
        stockError.value = "";
    }

    async function submit(form, billingDetails) {
        clearErrors();
        processing.value = true;

        let data;
        try {
            const body = new FormData();
            Object.entries(form).forEach(([k, v]) => body.append(k, v ?? ""));
            const res = await fetch(submitPath, {
                method: "POST",
                body,
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            data = await res.json();
        } catch {
            stockError.value = t("shared.common.error");
            processing.value = false;
            return;
        }

        if (!data.success) {
            const errs = data.errors ?? {};
            Object.entries(errs).forEach(([k, v]) => {
                if (k === "stock") stockError.value = v;
                else errors[k] = t(v);
            });
            processing.value = false;
            return;
        }

        const { error, paymentIntent } = await confirmPayment(
            data.clientSecret,
            billingDetails,
            data.returnUrl,
        );

        if (error) {
            processing.value = false;
            return;
        }

        // No 3DS required → Stripe doesn't redirect automatically. Do it ourselves.
        if (paymentIntent && paymentIntent.status === "succeeded") {
            window.location.href = `${data.returnUrl}?payment_intent=${paymentIntent.id}`;
        }
    }

    return { errors, stockError, processing, submit };
}
