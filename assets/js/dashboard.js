document.addEventListener('DOMContentLoaded', () => {
    const source = document.getElementById('dashboardChartData');
    if (!source || typeof Chart === 'undefined') return;
    const data = JSON.parse(source.textContent);
    const money = value => 'S/ ' + Number(value).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    Chart.defaults.font.family = "Inter, system-ui, sans-serif";
    Chart.defaults.color = '#64748b';

    const trendCanvas = document.getElementById('salesPurchasesChart');
    if (trendCanvas) new Chart(trendCanvas, {
        type: 'bar',
        data: { labels: data.labels, datasets: [
            {label:'Ventas', data:data.sales, backgroundColor:'rgba(37,99,235,.82)', borderRadius:7, maxBarThickness:34},
            {label:'Compras', data:data.purchases, backgroundColor:'rgba(14,165,233,.25)', borderColor:'#0ea5e9', borderWidth:1, borderRadius:7, maxBarThickness:34}
        ]},
        options: {responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false}, plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:18}},tooltip:{callbacks:{label:ctx=>ctx.dataset.label+': '+money(ctx.raw)}}}, scales:{x:{grid:{display:false}},y:{beginAtZero:true,grid:{color:'rgba(148,163,184,.15)'},ticks:{callback:value=>'S/ '+Number(value).toLocaleString('es-PE')}}}}
    });

    const warehouseCanvas = document.getElementById('warehouseValueChart');
    if (warehouseCanvas) new Chart(warehouseCanvas, {
        type: 'doughnut',
        data: {labels:data.warehouseLabels.length?data.warehouseLabels:['Sin inventario'], datasets:[{data:data.warehouseValues.length?data.warehouseValues:[1], backgroundColor:data.warehouseValues.length?['#2563eb','#06b6d4','#8b5cf6','#f59e0b','#22c55e']:['#e2e8f0'], borderWidth:0, hoverOffset:5}]},
        options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:14}},tooltip:{callbacks:{label:ctx=>data.warehouseValues.length?ctx.label+': '+money(ctx.raw):'Sin inventario'}}}}
    });
});
