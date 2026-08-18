import '../bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('candidateCombobox', ({ url, initial = null }) => ({
    query: initial?.name ?? '',
    selected: initial,
    items: [],
    loading: false,
    error: '',
    open: false,
    activeIndex: -1,
    controller: null,

    async handleInput() {
        if (this.selected && this.query !== this.selected.name) {
            this.selected = null;
        }

        const term = this.query.trim();
        this.controller?.abort();
        this.error = '';
        this.activeIndex = -1;

        if (term.length < 2) {
            this.items = [];
            this.open = false;
            this.loading = false;
            return;
        }

        this.controller = new AbortController();
        this.loading = true;
        this.open = true;

        try {
            const endpoint = new URL(url, window.location.origin);
            endpoint.searchParams.set('search', term);
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            const payload = await response.json();

            if (!response.ok) {
                this.items = [];
                this.error = payload.message || 'Không thể tải danh sách phù hợp.';
                return;
            }

            this.items = payload.data ?? [];
            this.activeIndex = this.items.length > 0 ? 0 : -1;
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.warn('Candidate lookup request failed.', {
                    name: error.name,
                    message: error.message,
                });
                this.items = [];
                this.error = 'Không thể tải kết quả. Vui lòng thử lại.';
            }
        } finally {
            this.loading = false;
        }
    },

    select(item) {
        this.selected = item;
        this.query = item.name;
        this.close();
    },

    clear() {
        this.selected = null;
        this.query = '';
        this.items = [];
        this.close();
    },

    move(offset) {
        if (this.items.length === 0) return;
        this.open = true;
        this.activeIndex = (this.activeIndex + offset + this.items.length) % this.items.length;
    },

    chooseActive() {
        if (this.open && this.activeIndex >= 0) {
            this.select(this.items[this.activeIndex]);
        }
    },

    close() {
        this.open = false;
        this.activeIndex = -1;
    },
}));

Alpine.start();

const dialogTriggers = new WeakMap();

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;

    const openTrigger = event.target.closest('[data-dialog-open]');
    if (openTrigger) {
        const dialog = document.getElementById(openTrigger.dataset.dialogOpen);
        if (dialog instanceof HTMLDialogElement && !dialog.open) {
            dialogTriggers.set(dialog, openTrigger);
            dialog.showModal();
            requestAnimationFrame(() => dialog.querySelector('input:not([type="hidden"]), button')?.focus());
        }
        return;
    }

    const closeTrigger = event.target.closest('[data-dialog-close]');
    if (closeTrigger) {
        closeTrigger.closest('dialog')?.close();
    }
});

document.addEventListener('close', (event) => {
    if (!(event.target instanceof HTMLDialogElement)) return;
    dialogTriggers.get(event.target)?.focus();
}, true);

const errorDialog = document.querySelector('dialog[data-open-on-error="true"]');
if (errorDialog instanceof HTMLDialogElement && !errorDialog.open) {
    errorDialog.showModal();
}
