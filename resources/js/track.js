/**
 * Anonymous storefront tracking pings (product clicks and popup views).
 * Uses navigator.sendBeacon so it never delays clicks or page changes.
 * Page views and add-to-cart are recorded on the server.
 */
export function track(type, productId, source, variantId = null) {
    if (!productId || !navigator.sendBeacon) return;
    const data = new FormData();
    data.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
    data.append('type', type);
    data.append('product_id', productId);
    data.append('source', source);
    if (variantId) data.append('variant_id', variantId);
    navigator.sendBeacon('/track', data);
}

/* Links marked data-track-click="home" data-product-id="…" record a product click. */
export function initTrackClicks() {
    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-track-click]');
        if (link) track('product_click', link.dataset.productId, link.dataset.trackClick);
    });
}
