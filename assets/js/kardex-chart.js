document.addEventListener('DOMContentLoaded', () => {
    const source = document.getElementById('kardexChartData');
    const canvas = document.getElementById('kardexMovementChart');
    if (!source || !canvas || typeof Chart === 'undefined') return;
    const data = JSON.parse(source.textContent);
    const labels = data.labels.length ? data.labels : ['Sin movimientos'];
    const entries = data.entries.length ? data.entries : [0];
    const exits = data.exits.length ? data.exits : [0];
    const money = value => 'S/ ' + Number(value).toLocaleString('es-PE', {minimumFractionDigits:2,maximumFractionDigits:2});
    new Chart(canvas, {
        type:'bar',
        data:{labels,datasets:[
            {label:'Entradas',data:entries,backgroundColor:'rgba(34,197,94,.78)',borderRadius:6,maxBarThickness:38},
            {label:'Salidas',data:exits,backgroundColor:'rgba(239,68,68,.72)',borderRadius:6,maxBarThickness:38}
        ]},
        options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:18}},tooltip:{callbacks:{label:ctx=>ctx.dataset.label+': '+money(ctx.raw)}}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,grid:{color:'rgba(148,163,184,.15)'},ticks:{callback:value=>'S/ '+Number(value).toLocaleString('es-PE')}}}}
    });
});
