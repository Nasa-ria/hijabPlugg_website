/**
 * Story motion for the home page.
 *
 * Markup hooks (see resources/views/welcome.blade.php):
 *   [data-story]               root wrapper; nothing runs without it
 *   [data-chapter="tone"]      chapter section; its tone also colours the thread
 *   [data-reveal]              fades/rises in once when it enters the viewport ("soft" adds blur)
 *   [data-curtain]             image that opens from the centre once in view
 *   [data-parallax="0.08"]     drifts at a different depth while scrolling (desktop only)
 *   [data-thread-anchor="0.5"] point the thread passes through (value = x position, 0–1,
 *                              or "self" to use the anchor element's own position)
 *   [data-signature]           the "Nasaari" signature written when the thread arrives
 *
 * Timing and colours are CSS variables at the top of resources/css/story.css.
 */

// Data saver (html.lite) behaves like reduced motion: no scroll effects or animations
const reducedMotion = () => document.documentElement.classList.contains('lite') || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const isDesktop = () => window.matchMedia('(min-width: 768px)').matches;

export function initStory() {
    const root = document.querySelector('[data-story]');
    if (!root) return;

    document.documentElement.classList.add('story-js');
    window.__storyReady = true;

    initReveals(root);

    const thread = createThread(root);
    const parallax = createParallax(root);

    // One rAF-throttled scroll loop drives every scroll-linked effect
    let ticking = false;
    const update = () => {
        ticking = false;
        thread?.update();
        parallax.update();
    };
    const requestUpdate = () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    };

    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate, { passive: true });

    // Rebuild the thread whenever layout height changes (fonts, images, viewport)
    if ('ResizeObserver' in window && thread) {
        let rebuildQueued = false;
        new ResizeObserver(() => {
            if (rebuildQueued) return;
            rebuildQueued = true;
            requestAnimationFrame(() => {
                rebuildQueued = false;
                thread.build();
                update();
            });
        }).observe(root);
    }

    thread?.build();
    update();
}

/* Reveal elements once as they enter the viewport. */
function initReveals(root) {
    const targets = root.querySelectorAll('[data-reveal], [data-curtain], [data-signature]');

    if (!('IntersectionObserver' in window)) {
        targets.forEach(el => el.classList.add('is-in', 'is-signed'));
        return;
    }

    // A fully clipped curtain has no visible area, so watch its parent instead
    const watched = new Map();

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = watched.get(entry.target) || entry.target;
            if (el.hasAttribute('data-signature')) {
                // On desktop the thread signs it when it arrives; elsewhere sign on view
                if (isDesktop() && !reducedMotion() && root.querySelector('[data-thread]')) return;
                el.classList.add('is-signed');
            } else {
                el.classList.add('is-in');
            }
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.12 });

    targets.forEach(el => {
        const target = el.hasAttribute('data-curtain') && el.parentElement ? el.parentElement : el;
        if (target !== el) watched.set(target, el);
        observer.observe(target);
    });
}

/* Gentle depth: elements drift relative to the viewport centre. Desktop only. */
function createParallax(root) {
    const items = [...root.querySelectorAll('[data-parallax]')];

    return {
        update() {
            if (!items.length) return;
            const active = isDesktop() && !reducedMotion();
            const mid = window.innerHeight / 2;

            items.forEach(el => {
                if (!active) {
                    el.style.transform = '';
                    return;
                }
                const rect = el.getBoundingClientRect();
                if (rect.bottom < -200 || rect.top > window.innerHeight + 200) return;
                const speed = parseFloat(el.dataset.parallax) || 0.08;
                const offset = (rect.top + rect.height / 2 - mid) * -speed;
                // Clamp to keep movement subtle (max 40px)
                const y = Math.max(-40, Math.min(40, offset));
                el.style.transform = `translate3d(0, ${y.toFixed(1)}px, 0)`;
            });
        },
    };
}

/**
 * The Thread: one fine line that draws itself down the page as you scroll,
 * weaving through each [data-thread-anchor] and ending at the signature.
 */
function createThread(root) {
    const svg = root.querySelector('[data-thread]');
    if (!svg) return null;

    const line = svg.querySelector('.thread-line');
    const glow = svg.querySelector('.thread-glow');
    const gradient = svg.querySelector('linearGradient');
    const signature = root.querySelector('[data-signature]');

    // Thread colour per chapter tone: bright rose on dark plum, deeper rose on porcelain
    const toneColours = { ink: '#e0708a', plum: '#e0708a', cream: '#c2506b', sand: '#c2506b' };

    let length = 0;
    let samples = [];   // [{ len, y }] with y kept monotonic
    let endY = 0;

    const active = () => isDesktop() && !reducedMotion();

    function build() {
        if (!active()) {
            line.removeAttribute('d');
            glow.removeAttribute('d');
            return;
        }

        const rootRect = root.getBoundingClientRect();
        const width = rootRect.width;
        const height = root.scrollHeight;
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);

        const points = [...root.querySelectorAll('[data-thread-anchor]')]
            .map(el => {
                const r = el.getBoundingClientRect();
                const value = parseFloat(el.dataset.threadAnchor);
                return {
                    // A number is a fraction of the page width; "self" uses the anchor's own position
                    x: Number.isNaN(value) ? r.left - rootRect.left + r.width / 2 : value * width,
                    y: r.top - rootRect.top + r.height / 2,
                };
            })
            .sort((a, b) => a.y - b.y);

        if (points.length < 2) return;

        const d = smoothPath(points);
        line.setAttribute('d', d);
        glow.setAttribute('d', d);

        // Colour stops follow the chapters so the thread is visible on every tone
        gradient.setAttribute('y2', height);
        gradient.innerHTML = '';
        root.querySelectorAll('[data-chapter]').forEach(chapter => {
            const r = chapter.getBoundingClientRect();
            const mid = (r.top - rootRect.top + r.height / 2) / height;
            const stop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
            stop.setAttribute('offset', Math.min(1, Math.max(0, mid)).toFixed(4));
            stop.setAttribute('stop-color', toneColours[chapter.dataset.chapter] || '#e0708a');
            gradient.appendChild(stop);
        });

        length = line.getTotalLength();
        line.style.strokeDasharray = glow.style.strokeDasharray = `${length}`;

        // Sample the path so scroll position can be mapped to drawn length
        samples = [];
        let maxY = -Infinity;
        const steps = 240;
        for (let i = 0; i <= steps; i++) {
            const len = (length * i) / steps;
            maxY = Math.max(maxY, line.getPointAtLength(len).y);
            samples.push({ len, y: maxY });
        }
        endY = points[points.length - 1].y;
    }

    function update() {
        if (!active() || !length) return;

        // The pen sits ~65% down the viewport
        const penY = window.scrollY + window.innerHeight * 0.65 - (root.getBoundingClientRect().top + window.scrollY);

        let drawn = 0;
        if (penY >= endY) {
            drawn = length;
        } else {
            for (let i = 0; i < samples.length; i++) {
                if (samples[i].y >= penY) {
                    drawn = samples[i].len;
                    break;
                }
            }
        }

        const offset = `${length - drawn}`;
        line.style.strokeDashoffset = glow.style.strokeDashoffset = offset;

        if (signature && drawn >= length * 0.985) {
            signature.classList.add('is-signed');
        }
    }

    return { build, update };
}

/* Catmull-Rom spline through the points, as cubic Béziers. */
function smoothPath(points) {
    let d = `M ${points[0].x} ${points[0].y}`;
    for (let i = 0; i < points.length - 1; i++) {
        const p0 = points[i - 1] || points[i];
        const p1 = points[i];
        const p2 = points[i + 1];
        const p3 = points[i + 2] || p2;
        const c1x = p1.x + (p2.x - p0.x) / 6;
        const c1y = p1.y + (p2.y - p0.y) / 6;
        const c2x = p2.x - (p3.x - p1.x) / 6;
        const c2y = p2.y - (p3.y - p1.y) / 6;
        d += ` C ${c1x} ${c1y}, ${c2x} ${c2y}, ${p2.x} ${p2.y}`;
    }
    return d;
}
