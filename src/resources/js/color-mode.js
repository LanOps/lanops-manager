// Light/dark colour mode. Follows the visitor's system setting until they pick
// one with a [data-color-mode-toggle] button; the choice is kept per browser.
const STORAGE_KEY = 'color-mode';

function storedMode() {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch (e) {
        return null;
    }
}

function systemMode() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyMode(mode) {
    document.documentElement.setAttribute('data-bs-theme', mode);
}

export function initColorMode() {
    // Pages like the big screen pin their own mode
    if (document.documentElement.dataset.colorMode === 'fixed') {
        return;
    }

    applyMode(storedMode() || systemMode());

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (!storedMode()) {
            applyMode(systemMode());
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-color-mode-toggle]')) {
            return;
        }
        const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        applyMode(next);
        try {
            localStorage.setItem(STORAGE_KEY, next);
        } catch (e) {
            // Storage blocked (private mode); the choice lasts for this page only
        }
    });
}
