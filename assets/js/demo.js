document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formDemo');
    if (!form) return;
    let busy = false;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy) return;
        busy = true;
        const button = form.querySelector('button');
        const result = document.getElementById('demoResult');
        button.disabled = true;
        result.className = 'alert alert-info mt-3';
        result.textContent = 'Cargando datos PRUEBA…';
        try {
            const response = await fetch(form.action, {method:'POST', body:new FormData(form)});
            const data = await response.json();
            if (!response.ok || data.error) throw new Error(data.error || 'No se pudo completar la carga.');
            result.className = 'alert alert-success mt-3';
            result.textContent = data.message;
            const list = document.createElement('ul');
            for (const company of data.empresas) {
                const item = document.createElement('li');
                item.textContent = `${company.empresa}: ${company.almacen}; ${company.producto}; ${company.cliente}.`;
                list.append(item);
            }
            result.append(list);
        } catch (error) {
            result.className = 'alert alert-danger mt-3';
            result.textContent = error.message + ' Puedes repetir la carga para completar lo pendiente.';
        } finally { busy = false; button.disabled = false; }
    });
});
