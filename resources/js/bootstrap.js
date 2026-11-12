/**
 * Shared browser helpers.
 *
 * (Axios used to be loaded here but nothing used it; every request goes through fetch,
 * which saves ~35 KB of JavaScript on slow connections.)
 */

/**
 * fetch() that gives up after `timeout` ms so slow connections get a clear message
 * instead of a spinner that never ends. Rejects with an Error named "TimeoutError".
 */
export async function fetchWithTimeout(url, options = {}, timeout = 30000) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeout);

    try {
        return await fetch(url, { ...options, signal: controller.signal });
    } catch (error) {
        if (error.name === 'AbortError') {
            const timedOut = new Error('The connection is slow and the request timed out.');
            timedOut.name = 'TimeoutError';
            throw timedOut;
        }
        throw error;
    } finally {
        clearTimeout(timer);
    }
}

window.fetchWithTimeout = fetchWithTimeout;

/* "You're offline" notice (markup in layouts/app.blade.php) */
export function initConnectionNotice() {
    const notice = document.getElementById('offline-notice');
    if (!notice) return;

    const text = notice.querySelector('[data-offline-text]');
    const show = (message, autoHide) => {
        text.textContent = message;
        notice.classList.remove('hidden');
        notice.classList.add('flex');
        clearTimeout(notice._timer);
        if (autoHide) notice._timer = setTimeout(hide, autoHide);
    };
    const hide = () => {
        notice.classList.add('hidden');
        notice.classList.remove('flex');
    };

    window.addEventListener('offline', () => show("You're offline. Your cart is saved; we'll carry on when you're back online."));
    window.addEventListener('online', () => show("You're back online.", 3000));
    if (!navigator.onLine) show("You're offline. Your cart is saved; we'll carry on when you're back online.");
}
