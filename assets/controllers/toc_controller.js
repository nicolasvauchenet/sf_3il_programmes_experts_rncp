import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['backdrop', 'panel', 'trigger', 'desktopNav', 'mobileNav'];

    static values = {
        selectedCode: String,
    };

    connect() {
        this.isOpen = false;
        this.boundKeydown = this.handleKeydown.bind(this);

        this.scrollDesktopToActiveItem();
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

        requestAnimationFrame(() => {
            this.scrollMobileToActiveItem();
        });
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

    scrollDesktopToActiveItem() {
        if (!this.hasDesktopNavTarget) {
            return;
        }

        this.scrollContainerToActiveItem(this.desktopNavTarget);
    }

    scrollMobileToActiveItem() {
        if (!this.hasMobileNavTarget) {
            return;
        }

        this.scrollContainerToActiveItem(this.mobileNavTarget);
    }

    scrollContainerToActiveItem(container) {
        const activeItem = this.findActiveItem(container);

        if (!activeItem) {
            return;
        }

        const targetTop =
            activeItem.offsetTop - container.clientHeight / 2 + activeItem.clientHeight / 2;

        container.scrollTo({
            top: Math.max(targetTop, 0),
            behavior: 'smooth',
        });
    }

    findActiveItem(container) {
        if (this.hasSelectedCodeValue && this.selectedCodeValue) {
            const selectedCode = this.selectedCodeValue.toLowerCase();
            const item = container.querySelector(`a[data-code="${selectedCode}"]`);

            if (item) {
                return item;
            }
        }

        return container.querySelector('a.active');
    }
}
