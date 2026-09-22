document.addEventListener('DOMContentLoaded', () => {
    const body = document.querySelector('#tablaDetalle tbody');
    if (!body) return;
    function update(row) {
        const option = row.querySelector('.producto-select')?.selectedOptions[0];
        const input = row.querySelector('.txt-cantidad');
        if (!input) return;
        input.min = option?.dataset.step || '1';
        input.step = input.min;
        input.title = input.step === '1' ? 'Cantidad entera' : 'Hasta 3 decimales';
    }
    body.querySelectorAll('tr').forEach(update);
    body.addEventListener('change', event => { if (event.target.matches('.producto-select')) update(event.target.closest('tr')); });
    new MutationObserver(() => body.querySelectorAll('tr').forEach(update)).observe(body, {childList:true});
    const purchase = document.getElementById('formCompra');
    if (purchase) {
        const warehouse = purchase.querySelector('select[name="almacen_id"]');
        async function stock(row) {
            const select = row.querySelector('.producto-select');
            let label = row.querySelector('.purchase-stock');
            if (!label) { label = document.createElement('small'); label.className = 'purchase-stock text-muted d-block mt-1'; select.after(label); }
            const productId = select.value, warehouseId = warehouse.value;
            if (!productId || !warehouseId) { label.textContent = ''; return; }
            label.textContent = 'Consultando stock…';
            const url = new URL(purchase.dataset.stockUrl, location.href);
            url.search = new URLSearchParams({producto_id:productId, almacen_id:warehouseId, empresa_id:purchase.elements.empresa_id.value});
            try {
                const response = await fetch(url); const data = await response.json();
                if (productId !== select.value || warehouseId !== warehouse.value) return;
                if (!response.ok || data.error) throw new Error(data.error || 'No se pudo consultar stock');
                label.textContent = `Stock actual: ${data.stock} ${data.unidad_medida}`;
            } catch (error) { if (productId === select.value && warehouseId === warehouse.value) label.textContent = error.message; }
        }
        body.addEventListener('change', event => { if (event.target.matches('.producto-select')) stock(event.target.closest('tr')); });
        warehouse.addEventListener('change', () => body.querySelectorAll('tr').forEach(stock));
    }
});
