document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formDespacho');
    if (!form) return;
    const rows = [...form.querySelectorAll('tr[data-product]')];
    const stocks = new Map();
    const button = form.querySelector('button[type="submit"]');
    const preview = form.querySelector('.dispatch-preview');
    form.dataset.requiresStock = 'true';
    function update() {
        const totals = new Map(); let valid = true;
        rows.forEach(row => {
            const input = row.querySelector('.dispatch-qty');
            const qty = Number(input.value);
            if (!input.checkValidity() || !Number.isFinite(qty)) valid = false;
            totals.set(row.dataset.product, (totals.get(row.dataset.product) || 0) + qty);
        });
        const lines = [];
        for (const [id, qty] of totals) {
            if (qty <= 0) continue;
            const row = rows.find(row => row.dataset.product === id);
            const stock = stocks.get(id);
            if (stock === undefined || Math.round(qty * 1000) > Math.round(stock * 1000)) valid = false;
            lines.push(`${row.querySelector('.product-name').textContent}: saldrán ${Number(qty.toFixed(3))} ${row.dataset.unit}; quedarán ${stock === undefined ? 'por consultar' : Number((stock - qty).toFixed(3))}.`);
        }
        if (!lines.length) valid = false;
        preview.textContent = lines.length ? lines.join(' ') : 'Ingresa al menos una cantidad a despachar.';
        form.dataset.stockValid = String(valid);
        button.disabled = !valid || form.dataset.processing === 'true';
    }
    form.addEventListener('input', update);
    for (const id of new Set(rows.map(row => row.dataset.product))) {
        const url = new URL(form.dataset.stockUrl, location.href);
        url.search = new URLSearchParams({producto_id: id, almacen_id: form.dataset.warehouse, empresa_id: form.elements.empresa_id.value});
        fetch(url).then(async response => {
            const data = await response.json();
            if (!response.ok || data.error) throw new Error(data.error || 'Error consultando stock');
            stocks.set(id, Number(data.stock));
            rows.filter(row => row.dataset.product === id).forEach(row => { row.querySelector('.stock-label').textContent = data.stock + ' ' + row.dataset.unit; });
        }).catch(error => {
            rows.filter(row => row.dataset.product === id).forEach(row => { row.querySelector('.stock-label').textContent = error.message; });
        }).finally(update);
    }
    update();
});
