export function formatCurrency(
    amount,
    currency = "EUR",
    { fallbackDecimals = 2 } = {},
) {
    if (amount === null || amount === undefined) return "—";
    try {
        return new Intl.NumberFormat(undefined, {
            style: "currency",
            currency: currency || "EUR",
        }).format(amount);
    } catch {
        const safe =
            typeof amount === "number"
                ? amount.toFixed(fallbackDecimals)
                : amount;
        return `${safe} ${currency || ""}`.trim();
    }
}

export function formatProductPrice(product) {
    if (!product) return "—";
    return formatCurrency(product.price, product.currency, {
        fallbackDecimals: product.currencyDecimals ?? 2,
    });
}
