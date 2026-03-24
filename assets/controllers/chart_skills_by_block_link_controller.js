import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        promotion: String,
        year: String,
    };

    connect() {
        this.boundOnChartConnect = this.onChartConnect.bind(this);
        this.element.addEventListener('chartjs:connect', this.boundOnChartConnect);
    }

    disconnect() {
        this.element.removeEventListener('chartjs:connect', this.boundOnChartConnect);
    }

    onChartConnect(event) {
        const chart = event.detail.chart;
        const dataset = chart.data?.datasets?.[0] ?? null;
        const fileCodes = dataset?.fileCodes ?? [];
        const blockCodes = dataset?.blockCodes ?? [];
        const hasLinks = Array.isArray(fileCodes) && Array.isArray(blockCodes);

        if (!hasLinks) {
            return;
        }

        chart.options.onHover = (_, activeElements) => {
            chart.canvas.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
        };

        chart.options.onClick = (mouseEvent) => {
            const points = chart.getElementsAtEventForMode(
                mouseEvent,
                'nearest',
                {intersect: true},
                true,
            );

            if (!points.length) {
                return;
            }

            const point = points[0];
            const clickedFileCode = String(fileCodes[point.index] ?? '').trim();
            const clickedBlockCode = String(blockCodes[point.index] ?? '').trim();

            if (!clickedFileCode) {
                return;
            }

            const params = new URLSearchParams({
                promotion: this.promotionValue,
                year: this.yearValue,
                code: clickedFileCode,
            });

            if (clickedBlockCode !== '') {
                params.set('block', clickedBlockCode);
            }

            window.location.href = `/competences?${params.toString()}`;
        };

        chart.update();
    }
}
