/**
 * Shop page: opens the product popup and handles colour, quantity and add to cart.
 *
 * Markup (resources/views/components/shop/*):
 *   [data-shop-grid]          wrapper; nothing runs without it
 *   [data-product-open="id"]  grid item that opens <template id="product-modal-id">
 *   #productModal             the <dialog>; showModal() keeps focus inside it
 *
 * Timing lives in resources/css/shop.css (--shop-modal etc.).
 */

import { track } from './track';

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function initShop() {
    const grid = document.querySelector('[data-shop-grid]');
    const dialog = document.getElementById('productModal');
    if (!grid || !dialog) return;

    const body = dialog.querySelector('[data-modal-body]');
    let opener = null;
    let closeTimer = null;

    grid.addEventListener('click', (event) => {
        const dot = event.target.closest('[data-dot]');
        if (dot) {
            chooseDot(dot);
            return;
        }
        const trigger = event.target.closest('[data-product-open]');
        if (trigger) {
            track('product_click', trigger.dataset.productOpen, 'shop_grid');
            open(trigger);
        }
    });

    // Hovering a colour circle previews that colour; leaving returns to the chosen one
    grid.addEventListener('mouseover', (event) => {
        const dot = event.target.closest('[data-dot]');
        if (dot) showItemColour(dot.closest('[data-shop-item]'), dot);
    });
    grid.addEventListener('mouseout', (event) => {
        const dots = event.target.closest('.shop-item__dots');
        if (!dots || dots.contains(event.relatedTarget)) return;
        const item = dots.closest('[data-shop-item]');
        const chosen = item.querySelector(`[data-dot][data-variant-id="${item.dataset.selectedVariant}"]`);
        if (chosen) showItemColour(item, chosen);
    });

    function open(trigger) {
        const template = document.getElementById(`product-modal-${trigger.dataset.productOpen}`);
        if (!template) return;

        clearTimeout(closeTimer);
        opener = trigger;
        body.replaceChildren(template.content.cloneNode(true));
        const panel = body.querySelector('[data-product-modal]');
        dialog.setAttribute('aria-labelledby', panel.getAttribute('aria-labelledby'));
        dialog.removeAttribute('aria-label');
        setupProduct(panel);

        // Start on the colour chosen in the grid (if it can be bought)
        const chosen = trigger.closest('[data-shop-item]')?.dataset.selectedVariant;
        panel.querySelector(`[data-swatch][data-variant-id="${chosen}"]:not(:disabled)`)?.click();

        track('product_view', trigger.dataset.productOpen, 'popup');
        document.documentElement.classList.add('shop-modal-open');
        dialog.showModal();
        // Next frame so the opening transition runs from the closed state
        requestAnimationFrame(() => dialog.classList.add('is-open'));
    }

    function close() {
        if (!dialog.open || !dialog.classList.contains('is-open')) return;
        dialog.classList.remove('is-open');
        closeTimer = setTimeout(() => {
            dialog.close();
            body.replaceChildren();
            document.documentElement.classList.remove('shop-modal-open');
            opener?.focus();
        }, reducedMotion() ? 200 : 420);
    }

    // Escape key
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        close();
    });

    // Close button, "Continue shopping", or a click outside the panel
    dialog.addEventListener('click', (event) => {
        if (event.target.closest('[data-modal-close]')) {
            close();
            return;
        }
        if (event.target === dialog) {
            const r = dialog.getBoundingClientRect();
            const outside = event.clientX < r.left || event.clientX > r.right
                || event.clientY < r.top || event.clientY > r.bottom;
            if (outside) close();
        }
    });
}

/* Shop toolbar: dropdowns apply straight away, mobile search expands, Filter opens a bottom sheet. */
export function initShopToolbar() {
    const toolbar = document.querySelector('[data-shop-toolbar]');
    if (!toolbar) return;

    toolbar.querySelectorAll('[data-autosubmit] select').forEach(select =>
        select.addEventListener('change', () => select.form.requestSubmit()));

    // Loading state while the next page of results is fetched (search, filters, category chips)
    const showLoading = () => document.body.classList.add('shop-is-loading');
    document.querySelectorAll('[data-shop-toolbar] form, #filterSheet form').forEach(form =>
        form.addEventListener('submit', showLoading));
    document.querySelectorAll('.shop-category, .shop-results .pagination a, nav[role="navigation"] a').forEach(link =>
        link.addEventListener('click', (e) => { if (!e.metaKey && !e.ctrlKey && !e.shiftKey) showLoading(); }));
    // Coming back with the browser's Back button shows the cached page: clear the overlay
    window.addEventListener('pageshow', () => document.body.classList.remove('shop-is-loading'));

    const search = toolbar.querySelector('[data-mobile-search]');
    const toggle = toolbar.querySelector('[data-search-toggle]');
    toggle?.addEventListener('click', () => {
        const input = search.querySelector('input[name="search"]');
        if (search.classList.contains('is-open') && input.value.trim()) {
            search.requestSubmit();
            return;
        }
        const open = search.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) input.focus();
    });

    const sheet = document.getElementById('filterSheet');
    if (!sheet) return;
    const close = () => {
        sheet.classList.remove('is-open');
        setTimeout(() => sheet.close(), reducedMotion() ? 150 : 400);
    };
    toolbar.querySelector('[data-filter-open]')?.addEventListener('click', () => {
        sheet.showModal();
        requestAnimationFrame(() => sheet.classList.add('is-open'));
    });
    sheet.querySelector('[data-filter-close]')?.addEventListener('click', close);
    sheet.addEventListener('cancel', (e) => { e.preventDefault(); close(); });
    sheet.addEventListener('click', (e) => { if (e.target === sheet) close(); });
}

/* Product detail page: same colour, quantity and add-to-cart behaviour as the popup. */
export function initProductDetail() {
    const panel = document.querySelector('[data-product-detail]');
    if (panel) setupProduct(panel);
}

/* Grid: keep the clicked colour as this item's choice. */
function chooseDot(dot) {
    const item = dot.closest('[data-shop-item]');
    item.dataset.selectedVariant = dot.dataset.variantId;
    item.querySelectorAll('[data-dot]').forEach(d => d.setAttribute('aria-pressed', d === dot ? 'true' : 'false'));
    showItemColour(item, dot);
}

/* Grid: switch the item's photo to a colour, with a short fade once the new photo is ready. */
function showItemColour(item, dot) {
    const img = item?.querySelector('[data-item-image]');
    const src = dot.dataset.image;
    if (!img || !src || img.dataset.showing === src) return;

    img.dataset.showing = src;
    const name = item.querySelector('.shop-item__name')?.textContent.trim();
    const next = new Image();
    next.src = src;
    const swap = () => {
        if (img.dataset.showing !== src) return; // pointer already moved to another colour
        img.src = src;
        img.alt = `${name} in ${dot.dataset.colorName}`;
        img.classList.remove('is-swapping');
    };
    if (next.complete) {
        swap();
    } else {
        img.classList.add('is-swapping');
        next.onload = swap;
        next.onerror = () => img.classList.remove('is-swapping');
    }
}

/* Colour, quantity and add-to-cart behaviour for one opened product. */
function setupProduct(panel) {
    const form = panel.querySelector('[data-add-form]');
    const swatches = [...panel.querySelectorAll('[data-swatch]')];
    const images = [...panel.querySelectorAll('[data-stage-image]')];
    const thumbs = [...panel.querySelectorAll('[data-thumb]')];
    const qty = form.querySelector('[data-qty]');
    const addButton = form.querySelector('[data-add-button]');
    const addLabel = form.querySelector('[data-add-label]');
    const message = form.querySelector('[data-message]');
    const afterAdd = form.querySelector('[data-after-add]');
    const price = panel.querySelector('[data-price]');
    const compare = panel.querySelector('[data-compare]');
    const colourTitle = panel.querySelector('[data-color-title]');
    const selectedLabel = panel.querySelector('[data-selected-color]');
    const stockLeft = panel.querySelector('[data-stock-left]');

    const hasVariants = form.dataset.hasVariants === '1';
    let maxQty = Math.min(100, parseInt(form.dataset.stock, 10) || 1);

    const showImage = (key) => {
        images.forEach(img => img.classList.toggle('is-active', img.dataset.stageImage === key));
        thumbs.forEach(t => t.classList.toggle('is-active', t.dataset.thumb === key));
    };

    const say = (text, tone = 'quiet') => {
        const colours = { quiet: 'text-gray-500', error: 'text-red-700', success: 'text-emerald-700' };
        message.innerHTML = '';
        if (!text) return;
        const span = document.createElement('span');
        span.className = colours[tone];
        span.textContent = text;
        message.appendChild(span);
    };

    // ── Colours ──
    function select(swatch) {
        if (swatch.disabled) return;
        swatches.forEach(s => {
            const on = s === swatch;
            s.setAttribute('aria-checked', on ? 'true' : 'false');
            s.tabIndex = on ? 0 : -1;
        });
        form.elements.variant_id.value = swatch.dataset.variantId;
        showImage(swatch.dataset.variantId);
        price.textContent = swatch.dataset.price;
        compare.textContent = swatch.dataset.compare;
        colourTitle.textContent = swatch.dataset.colorName;
        selectedLabel.textContent = swatch.dataset.colorName;
        const stock = parseInt(swatch.dataset.stock, 10) || 0;
        maxQty = Math.min(100, stock || 1);
        setQty(qty.value);
        if (stockLeft) {
            stockLeft.innerHTML = '';
            const note = document.createElement('span');
            note.className = stock <= 5 ? 'text-accent-700 font-semibold' : 'text-gray-600';
            note.textContent = stock <= 5 ? `Only ${stock} left in ${swatch.dataset.colorName}` : `${stock} left in ${swatch.dataset.colorName}`;
            stockLeft.appendChild(note);
        }
        addButton.disabled = false;
        say('');
    }

    swatches.forEach(swatch => {
        swatch.addEventListener('click', () => select(swatch));
        // Arrow keys move between available colours, like any radio group
        swatch.addEventListener('keydown', (event) => {
            const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];
            if (!step) return;
            event.preventDefault();
            const enabled = swatches.filter(s => !s.disabled);
            const next = enabled[(enabled.indexOf(swatch) + step + enabled.length) % enabled.length];
            if (next) { next.focus(); select(next); }
        });
    });
    // Keyboard users land on the first colour they can actually choose
    const firstAvailable = swatches.find(s => !s.disabled);
    swatches.forEach(s => { s.tabIndex = s === firstAvailable ? 0 : -1; });

    // A colour's photo thumbnail selects that colour; other thumbnails just change the photo
    thumbs.forEach(thumb => thumb.addEventListener('click', () => {
        const swatch = swatches.find(s => s.dataset.variantId === thumb.dataset.thumb && !s.disabled);
        swatch ? select(swatch) : showImage(thumb.dataset.thumb);
    }));

    // ── Quantity ──
    function setQty(value) {
        const n = Math.max(1, Math.min(maxQty, parseInt(value, 10) || 1));
        qty.value = n;
        qty.max = maxQty;
    }
    form.querySelectorAll('[data-qty-step]').forEach(btn => {
        btn.addEventListener('click', () => setQty((parseInt(qty.value, 10) || 1) + parseInt(btn.dataset.qtyStep, 10)));
    });
    qty.addEventListener('change', () => setQty(qty.value));

    // ── Add to cart ──
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (hasVariants && !form.elements.variant_id.value) {
            say('Please choose a colour first.', 'error');
            return;
        }

        addButton.disabled = true;
        addLabel.textContent = 'Adding…';
        say('');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            });
            const data = await response.json().catch(() => null);

            if (response.ok && data?.success) {
                addLabel.textContent = 'Added ✓';
                say(data.message, 'success');
                updateCartCount(data.cart_count);
                afterAdd.classList.remove('hidden');
                afterAdd.classList.add('flex');
                setTimeout(() => {
                    addLabel.textContent = 'Add to cart';
                    addButton.disabled = false;
                }, 1800);
                return;
            }

            const fallback = response.status === 419
                ? 'Your session expired. Please refresh the page and try again.'
                : 'We couldn’t add this right now. Please try again.';
            say(data?.message || fallback, 'error');
        } catch (error) {
            say('We couldn’t reach the store. Check your connection and try again.', 'error');
        }

        addLabel.textContent = 'Add to cart';
        addButton.disabled = false;
    });
}

/* Update the header cart count with a gentle pulse. */
function updateCartCount(count) {
    const badge = document.getElementById('nav-cart-count');
    if (!badge || typeof count !== 'number') return;
    badge.textContent = count || '';
    badge.classList.toggle('hidden', !count);
    badge.classList.remove('cart-pulse');
    void badge.offsetWidth; // restart the animation
    badge.classList.add('cart-pulse');
}
