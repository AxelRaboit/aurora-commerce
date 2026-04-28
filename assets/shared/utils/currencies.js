export const CURRENCY_OPTIONS = [
    { value: "EUR", symbol: "€", label: "Euro" },
    { value: "USD", symbol: "$", label: "US Dollar" },
    { value: "GBP", symbol: "£", label: "British Pound" },
    { value: "CHF", symbol: "CHF", label: "Swiss Franc" },
    { value: "JPY", symbol: "¥", label: "Japanese Yen" },
    { value: "CAD", symbol: "$", label: "Canadian Dollar" },
    { value: "AUD", symbol: "$", label: "Australian Dollar" },
    { value: "SEK", symbol: "kr", label: "Swedish Krona" },
];

export const CURRENCY_BY_CODE = Object.fromEntries(
    CURRENCY_OPTIONS.map((c) => [c.value, c]),
);

export const DEFAULT_CURRENCY = "EUR";

export function symbolFor(code) {
    return CURRENCY_BY_CODE[code]?.symbol ?? code;
}
