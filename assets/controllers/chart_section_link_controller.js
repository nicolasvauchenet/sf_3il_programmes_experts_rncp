import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static values = {anchors: Array};

    onChartConnect({detail: {chart}}) {
        const anchorFor = (elements) => {
            const index = elements[0]?.index;
            if (index === undefined) return null;
            const anchor = this.anchorsValue[index];
            return anchor && document.getElementById(anchor) ? anchor : null;
        };

        chart.options.onHover = (_, elements) => {
            chart.canvas.style.cursor = anchorFor(elements) ? 'pointer' : 'default';
        };
        chart.options.onClick = (_, elements) => {
            const anchor = anchorFor(elements);
            if (anchor) this.scrollToSection(anchor);
        };
        chart.update('none');
    }

    scrollToSection(anchor) {
        const heading = document.getElementById(anchor);
        if (!heading) return;

        const section = heading.closest('section') ?? heading;
        const header = document.querySelector('.app-header');
        const headerBottom = header?.getBoundingClientRect().bottom ?? 0;
        const layout = section.closest('.app-grid.layout');
        const gap = layout ? parseFloat(getComputedStyle(layout).columnGap) || 0 : 0;
        const top = Math.max(0, window.scrollY + section.getBoundingClientRect().top - headerBottom - gap);

        // Avoid a second native/Turbo anchor scroll competing with this movement.
        const hash = `#${anchor}`;
        if (window.location.hash !== hash) {
            window.history.pushState(window.history.state, '', hash);
        }
        heading.focus({preventScroll: true});
        window.scrollTo({
            top,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
        });
    }
}
