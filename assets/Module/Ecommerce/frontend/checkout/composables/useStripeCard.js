import { ref, onMounted } from "vue";

function loadStripeScript() {
    return new Promise((resolve, reject) => {
        if (window.Stripe) {
            resolve();
            return;
        }
        const script = document.createElement("script");
        script.src = "https://js.stripe.com/v3/";
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

function resolvedColor(cssClass) {
    const el = document.createElement("span");
    el.className = cssClass;
    el.style.cssText = "position:fixed;visibility:hidden";
    document.body.appendChild(el);
    const color = getComputedStyle(el).color;
    document.body.removeChild(el);
    return color || undefined;
}

export function useStripeCard(
    publicKey,
    locale = "fr",
    mountSelector = "#card-element",
) {
    const cardError = ref("");
    let stripe = null;
    let card = null;

    onMounted(async () => {
        await loadStripeScript();

        stripe = window.Stripe(publicKey);
        const elements = stripe.elements({ locale });

        card = elements.create("card", {
            hidePostalCode: true,
            disableLink: true,
            style: {
                base: {
                    color: resolvedColor("text-primary"),
                    fontFamily: "system-ui, sans-serif",
                    fontSize: "15px",
                    "::placeholder": { color: resolvedColor("text-muted") },
                },
                invalid: { color: "#ef4444" },
            },
        });

        card.mount(mountSelector);
        card.on("ready", () => {
            console.log("[Stripe] Card element ready");
        });
        card.on("change", (e) => {
            cardError.value = e.error ? e.error.message : "";
        });
    });

    async function confirmPayment(clientSecret, billingDetails, returnUrl) {
        return stripe.confirmCardPayment(clientSecret, {
            payment_method: { card, billing_details: billingDetails },
            return_url: returnUrl,
        });
    }

    return { cardError, confirmPayment };
}
