document.addEventListener('DOMContentLoaded', () => {
    const host = document.createElement('div');
    host.className = 'modal fade';
    host.tabIndex = -1;
    host.setAttribute('aria-labelledby', 'recordModalTitle');
    host.innerHTML = `<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down"><div class="modal-content">
      <div class="modal-header"><h2 class="modal-title fs-5" id="recordModalTitle"></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body"></div></div></div>`;
    document.body.append(host);
    const modal = new bootstrap.Modal(host);
    const body = host.querySelector('.modal-body');
    const pageCompany = document.querySelector('meta[name="empresa-id"]')?.content;
    let baseline = new Map(), currentForm = null, busy = false, saved = false, selectTarget = null, opening = false;
    const fields = form => [...form.elements].filter(el => el.name && el.type !== 'hidden' && el.type !== 'submit');
    const dirty = () => currentForm && fields(currentForm).some(el => baseline.get(el.name) !== el.value);
    function error(form, message) {
        let box = form.querySelector('.form-errors');
        if (!box) { box = document.createElement('div'); box.className = 'alert alert-danger form-errors'; box.setAttribute('role', 'alert'); form.prepend(box); }
        box.textContent = message; box.classList.remove('d-none'); box.scrollIntoView({block: 'nearest'});
    }
    function notice(message) {
        let box = document.getElementById('saveNotice');
        if (!box) { box = document.createElement('div'); box.id = 'saveNotice'; box.className = 'alert alert-success'; box.setAttribute('role', 'status'); document.querySelector('.container-fluid.p-4').prepend(box); }
        box.textContent = message;
    }
    async function post(form) {
        const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
        const data = await response.json();
        if (!response.ok || data.error) throw new Error(data.error || 'No se pudo guardar.');
        return data;
    }
    host.addEventListener('hide.bs.modal', event => {
        if (busy || (!saved && dirty() && !confirm('Hay cambios sin guardar. ¿Deseas descartarlos?'))) event.preventDefault();
    });
    host.addEventListener('hidden.bs.modal', () => { currentForm = null; body.replaceChildren(); });
    host.addEventListener('shown.bs.modal', () => currentForm?.querySelector('input:not([type="hidden"]),select')?.focus());
    window.addEventListener('beforeunload', event => {
        if (!saved && dirty()) { event.preventDefault(); event.returnValue = ''; }
    });
    document.addEventListener('click', async event => {
        const link = event.target.closest('a[href]');
        if (!link) return;
        const target = new URL(link.href, location.href);
        if (target.origin !== location.origin || !/\/pages\/(clientes|proveedores|productos|almacenes|empresas)\/form\.php$/.test(target.pathname)) return;
        event.preventDefault();
        if (opening) return;
        opening = true;
        try {
            const response = await fetch(target);
            if (!response.ok) throw new Error('El registro no está disponible en esta empresa.');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const form = doc.querySelector('form[action*="guardar_"]');
            if (!form) throw new Error('No se pudo abrir el formulario.');
            if (form.elements.empresa_id.value !== pageCompany) throw new Error('La empresa activa cambió. Recarga la página antes de continuar.');
            currentForm = form; busy = false; saved = false; selectTarget = link.dataset.selectTarget || null;
            baseline = new Map(fields(form).map(el => [el.name, el.value]));
            host.querySelector('.modal-title').textContent = doc.querySelector('h2.h3')?.textContent || 'Editar registro';
            const cancel = document.createElement('button');
            cancel.type = 'button'; cancel.className = 'btn btn-secondary me-2'; cancel.textContent = 'Cancelar'; cancel.dataset.bsDismiss = 'modal';
            form.querySelector('button[type="submit"]').before(cancel);
            fields(form).forEach((el, index) => {
                el.id = 'modalField' + index;
                const label = el.parentElement.querySelector('label');
                if (label) label.htmlFor = el.id;
                if (form.elements.id) {
                    const marker = document.createElement('small'); marker.className = 'text-warning-emphasis d-none'; marker.textContent = 'Modificado'; el.after(marker);
                    const update = () => { const changed = baseline.get(el.name) !== el.value; el.classList.toggle('border-warning', changed); marker.classList.toggle('d-none', !changed); };
                    el.addEventListener('input', update); el.addEventListener('change', update);
                }
            });
            form.addEventListener('submit', async event => {
                event.preventDefault();
                if (busy || !form.reportValidity()) return;
                busy = true;
                const submit = form.querySelector('button[type="submit"]'); submit.disabled = true;
                try {
                    const data = await post(form);
                    saved = true;
                    if (/\/guardar_empresa\.php$/.test(new URL(form.action, location.href).pathname)) {
                        const selector = document.querySelector('form[action*="cambiar_empresa.php"] select[name="empresa_id"]');
                        if (selector) {
                            const activeId = selector.value;
                            let option = [...selector.options].find(item => item.value === String(data.record.id));
                            if (!option) { option = new Option('', data.record.id); selector.add(option); }
                            option.textContent = data.record.razon_social;
                            selector.value = activeId;
                        }
                    }
                    if (selectTarget) {
                        const select = document.querySelector(`select[name="${selectTarget}"]`);
                        const option = new Option(data.record.nombre + ' (' + data.record.numero_documento + ')', data.record.id, true, true);
                        select.add(option); select.dispatchEvent(new Event('change', {bubbles: true}));
                    } else {
                        try {
                            const response = await fetch(location.href);
                            if (!response.ok) throw new Error();
                            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                            if (doc.querySelector('meta[name="empresa-id"]')?.content !== pageCompany) throw new Error();
                            const replacement = doc.querySelector('.table-responsive');
                            if (!replacement) throw new Error();
                            document.querySelector('.table-responsive').replaceWith(replacement);
                        } catch (_) {
                            notice(data.message + '. Recarga el listado para ver el cambio.');
                            busy = false; modal.hide(); return;
                        }
                    }
                    notice(data.message); busy = false; modal.hide();
                } catch (e) { error(form, e.message || 'No se pudo guardar. Intenta nuevamente.'); }
                finally { busy = false; submit.disabled = false; }
            });
            body.replaceChildren(form); modal.show();
        } catch (e) { notice(e.message); }
        finally { opening = false; }
    });
    // Wide purchase/sale forms and standalone catalog fallback keep entered data on errors.
    document.querySelectorAll('form[action*="procesar_"], form[action*="guardar_cliente"], form[action*="guardar_proveedor"], form[action*="guardar_producto"], form[action*="guardar_almacen"], form[action*="guardar_empresa"]').forEach(form => {
        let processing = false;
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (processing || !form.reportValidity()) return;
            if (form.dataset.requiresStock === 'true' && form.dataset.stockValid !== 'true') {
                error(form, 'Revisa las cantidades y espera la consulta de stock.'); return;
            }
            const preview = form.querySelector('.dispatch-preview');
            if (preview && !confirm(preview.textContent + '\n¿Confirmar?')) return;
            processing = true;
            form.dataset.processing = 'true';
            const button = form.querySelector('button[type="submit"]'); button.disabled = true;
            try {
                const data = await post(form);
                if (data.redirect) location.assign(data.redirect);
                else { sessionStorage.setItem('saveNotice', data.message); location.assign('index.php'); }
            } catch (e) { error(form, e.message || 'No se pudo guardar. Intenta nuevamente.'); processing = false; form.dataset.processing = 'false'; button.disabled = false; }
        });
    });
    const previousNotice = sessionStorage.getItem('saveNotice');
    if (previousNotice) { notice(previousNotice); sessionStorage.removeItem('saveNotice'); }
});
