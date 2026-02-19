import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    onChartConnect(event) {
        const chart = event.detail.chart;

        if (chart.$modulesBySemesterTooltipPatched) return;
        chart.$modulesBySemesterTooltipPatched = true;

        const options = chart.config.options || (chart.config.options = {});
        const plugins = options.plugins || (options.plugins = {});
        const tooltip = plugins.tooltip || (plugins.tooltip = {});
        const callbacks = tooltip.callbacks || (tooltip.callbacks = {});

        callbacks.label = function (context) {
            const value = context.parsed.y;

            return [` ${value} Module${value > 1 ? "s" : ""} dans ce semestre`];
        };
    }
}
