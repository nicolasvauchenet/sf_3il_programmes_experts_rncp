import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    onChartConnect(event) {
        const chart = event.detail.chart;

        if (chart.$evaluationsVsSkillsTooltipPatched) return;
        chart.$evaluationsVsSkillsTooltipPatched = true;

        const options = chart.config.options || (chart.config.options = {});
        const plugins = options.plugins || (options.plugins = {});
        const tooltip = plugins.tooltip || (plugins.tooltip = {});
        const callbacks = tooltip.callbacks || (tooltip.callbacks = {});

        callbacks.label = function (context) {
            const ec = context.label;
            const value = context.parsed.x;

            const ds = context.dataset || {};
            const map = ds.skillsByEvaluation || {};
            const skills = map[ec] || [];

            const lines = [];
            lines.push(` ${value} Compétence${value > 1 ? 's' : ''} mobilisée${value > 1 ? 's' : ''} :`);

            if (skills.length) {
                const chunkSize = 6;
                for (let i = 0; i < skills.length; i += chunkSize) {
                    lines.push(
                        i === 0
                            ? skills.slice(i, i + chunkSize).join(", ")
                            : skills.slice(i, i + chunkSize).join(", ")
                    );
                }
            }

            return lines;
        };
    }
}
