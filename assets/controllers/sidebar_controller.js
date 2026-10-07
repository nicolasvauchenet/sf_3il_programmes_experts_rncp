import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.panel = this.element.querySelector('.app-toc-desktop');
        this.scheduleResize = () => {
            if (this.frame) return;
            this.frame = requestAnimationFrame(() => {
                this.frame = null;
                this.resize();
            });
        };
        this.observer = new ResizeObserver(this.scheduleResize);
        const header = document.querySelector('.app-header');
        if (header) this.observer.observe(header);
        window.addEventListener('resize', this.scheduleResize);
        window.addEventListener('scroll', this.scheduleResize, {passive: true});
        this.resize();
    }

    disconnect() {
        this.observer.disconnect();
        window.removeEventListener('resize', this.scheduleResize);
        window.removeEventListener('scroll', this.scheduleResize);
        cancelAnimationFrame(this.frame);
        this.element.style.removeProperty('--sidebar-height');
        this.element.style.removeProperty('--sidebar-top');
    }

    resize() {
        if (!window.matchMedia('(min-width: 768px)').matches) {
            this.element.style.removeProperty('--sidebar-height');
            this.element.style.removeProperty('--sidebar-top');
            return;
        }

        const bottomGap = parseFloat(getComputedStyle(this.element.parentElement).columnGap);
        const header = document.querySelector('.app-header');
        const headerBottom = header ? header.getBoundingClientRect().bottom : 0;
        this.element.style.setProperty('--sidebar-top', `${headerBottom + bottomGap}px`);
        const height = Math.max(0, window.innerHeight - this.panel.getBoundingClientRect().top - bottomGap);
        this.element.style.setProperty('--sidebar-height', `${height}px`);
    }
}
