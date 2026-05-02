export function formatMoney(amount, symbol) {
    return `${(amount ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${symbol ?? ""}`.trim();
}
