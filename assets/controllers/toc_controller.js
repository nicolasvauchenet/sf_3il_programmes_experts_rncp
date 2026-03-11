import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['backdrop', 'panel', 'trigger'];

    connect() {
        this.isOpen = false;
        this.boundKeydown = this.handleKeydown.bind(this);
    }

    disconnect() {
        document.removeEventListener('keydown', this.boundKeydown);
        document.body.classList.remove('toc-drawer-open');
    }

    open() {
        if (this.isOpen) {
            return;
        }

        this.isOpen = true;
        this.backdropTarget.hidden = false;

        requestAnimationFrame(() => {
            this.backdropTarget.classList.add('is-open');
        });

        document.body.classList.add('toc-drawer-open');
        document.addEventListener('keydown', this.boundKeydown);

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'true');
        }

        this.scrollToActiveItem();
    }

    close() {
        if (!this.isOpen) {
            return;
        }

        this.isOpen = false;
        this.backdropTarget.classList.remove('is-open');
        document.body.classList.remove('toc-drawer-open');
        document.removeEventListener('keydown', this.boundKeydown);

        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute('aria-expanded', 'false');
        }

        window.setTimeout(() => {
            if (!this.isOpen) {
                this.backdropTarget.hidden = true;
            }
        }, 220);
    }

    backdropClose(event) {
        if (event.target === this.backdropTarget) {
            this.close();
        }
    }

    handleKeydown(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }

    scrollToActiveItem() {
        const activeItem = this.panelTarget.querySelector('.toc a.active');

        if (!activeItem) {
            return;
        }

        activeItem.scrollIntoView({
            block: 'nearest',
            behavior: 'smooth',
        });
    }
}
