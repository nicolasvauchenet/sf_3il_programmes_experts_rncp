import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dialog', 'message'];

    connect() {
        this.handleClick = this.onClick.bind(this);
        this.handleSubmit = this.onSubmit.bind(this);
        document.addEventListener('click', this.handleClick, true);
        document.addEventListener('submit', this.handleSubmit, true);
        this.beforeCache = () => this.cancel();
        document.addEventListener('turbo:before-cache', this.beforeCache);
    }

    disconnect() {
        document.removeEventListener('click', this.handleClick, true);
        document.removeEventListener('submit', this.handleSubmit, true);
        document.removeEventListener('turbo:before-cache', this.beforeCache);
        if (this.dialogTarget.open) this.dialogTarget.close();
    }

    onClick(event) {
        const link = event.target.closest('a[data-confirm-message]');
        if (!link || event.button !== 0) return;
        const newTab = event.ctrlKey || event.metaKey || event.shiftKey || link.target === '_blank';
        this.ask(event, link, () => {
            if (newTab) window.open(link.href, '_blank', 'noopener');
            else window.location.assign(link.href);
        });
    }

    onSubmit(event) {
        const form = event.target;
        if (!form.matches('form[data-confirm-message]')) return;
        if (this.approvedForm === form) { this.approvedForm = null; return; }
        const submitter = event.submitter;
        this.ask(event, form, () => {
            this.approvedForm = form;
            form.requestSubmit(submitter || undefined);
            this.approvedForm = null;
        });
    }

    ask(event, element, action) {
        event.preventDefault();
        event.stopImmediatePropagation();
        if (this.dialogTarget.open) return;
        this.previousFocus = document.activeElement;
        this.pendingAction = action;
        this.messageTarget.textContent = element.dataset.confirmMessage;
        this.dialogTarget.showModal();
    }

    cancel(event) {
        event?.preventDefault();
        this.pendingAction = null;
        if (this.dialogTarget.open) this.dialogTarget.close();
    }

    accept() {
        const action = this.pendingAction;
        this.pendingAction = null;
        this.dialogTarget.close();
        action?.();
    }

    restoreFocus() { this.previousFocus?.focus(); }
}
