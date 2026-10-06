document.addEventListener('chartjs:init', ({detail: {Chart}}) => {
    Chart.defaults.font.family = 'Montserrat, sans-serif';
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#586970';
    Chart.defaults.borderColor = '#e3e7e9';
    Chart.defaults.plugins.tooltip.backgroundColor = '#005067';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
});
document.addEventListener('chartjs:pre-connect', ({detail: {options}}) => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) options.animation = false;
});
document.addEventListener('chartjs:connect', ({detail: {chart}}) => {
    document.fonts.ready.then(() => { if (chart.ctx) chart.update('none'); });
});
