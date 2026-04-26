/**
 * Listens to the 'cart:changed' custom event dispatched by CartApp.vue
 * and updates all [data-cart-count] badges in the page (e.g. topbar dropdown).
 */
function updateBadges(count) {
    const badges = document.querySelectorAll("[data-cart-count]");
    for (const badge of badges) {
        if (count > 0) {
            badge.textContent = String(count);
            badge.style.display = "";
        } else {
            badge.style.display = "none";
        }
    }
}

document.addEventListener("cart:changed", (event) => {
    updateBadges(event.detail?.count ?? 0);
});
