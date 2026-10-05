"""HTTP smoke checks. No valid business writes; data JSON hashes must stay identical."""
import hashlib
import http.cookiejar
import json
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8766').rstrip('/') + '/'
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def hashes():
    return {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in (ROOT / 'data').glob('*.json')}

def request(path, fields=None):
    data = urllib.parse.urlencode(fields).encode() if fields is not None else None
    return opener.open(urllib.request.Request(BASE + path, data=data))

before = hashes()
try:
    paths = ['pages/dashboard.php', 'pages/inventario/index.php', 'pages/inventario/kardex.php',
             'pages/kardex/index.php', 'pages/ventas/documento.php?id=1',
             'pages/ventas/nueva.php', 'pages/compras/nueva.php', 'pages/ventas/index.php',
             'pages/ventas/detalle.php?id=1', 'pages/inventario/kardex.php?venta_id=1',
             'pages/cuentas_cobrar/index.php', 'pages/reportes/index.php',
             'pages/historial/index.php', 'pages/sunat/index.php',
             'pages/sunat/documentos.php', 'pages/sunat/nota_credito.php',
             'pages/sunat/nota_debito.php', 'pages/sunat/estado.php',
             'pages/sunat/configuracion.php',
             'actions/exportar_csv.php?reporte=ventas']
    paths += [f'pages/{table}/{page}.php' for table in ['clientes', 'proveedores', 'productos', 'almacenes'] for page in ['index', 'form']]
    for path in paths:
        response = request(path)
        html = response.read().decode('utf-8-sig')
        assert response.status == 200 and not re.search(r'(Warning|Fatal error|Parse error)(?:</b>)?:', html), path
        for attributes, script in re.findall(r'<script([^>]*)>(.*?)</script>', html, re.S):
            if re.search(r'type=["\']application/json["\']', attributes, re.I):
                continue
            if not script.strip():
                continue
            with tempfile.NamedTemporaryFile(suffix='.js', delete=False, mode='w', encoding='utf-8') as f:
                f.write(script)
                filename = f.name
            try:
                subprocess.run(['node', '--check', filename], check=True, capture_output=True)
            finally:
                Path(filename).unlink()
    inventory_html = request('pages/inventario/index.php?estado=sin_stock').read().decode('utf-8-sig')
    assert 'name="estado"' in inventory_html and 'value="sin_stock" selected' in inventory_html
    inventory_html = request('pages/inventario/index.php').read().decode('utf-8-sig')
    assert re.search(r'pages/inventario/kardex\.php\?producto_id=\d+&(?:amp;)?almacen_id=\d+', inventory_html), 'Inventory Kardex link missing'
    sale_html = request('pages/ventas/detalle.php?id=1').read().decode('utf-8-sig')
    assert re.search(r'pages/inventario/kardex\.php\?venta_id=\d+', sale_html), 'Sale Kardex link missing'
    kardex_html = request('pages/inventario/kardex.php').read().decode('utf-8-sig')
    for marker in ['name="tipo_operacion"', 'Datos del movimiento', 'Entradas', 'Salidas', 'Saldo histórico', 'snapshots históricos', 'Próximamente']:
        assert marker in kardex_html, 'Kardex marker missing: ' + marker
    sunat_html = request('pages/sunat/documentos.php').read().decode('utf-8-sig')
    assert 'No enviado' in sunat_html and 'Disponible en integraci' in sunat_html
    assert re.search(r'sidebar-module open sidebar-future', sunat_html), 'SUNAT sidebar is not active'
    assert not re.search(r'https?://[^"\']*(?:sunat|gob\.pe)', sunat_html, re.I), 'Unexpected external SUNAT call or link'
    sale_html = request('pages/ventas/detalle.php?id=1').read().decode('utf-8-sig')
    assert 'pages/sunat/documentos.php' in sale_html, 'Sale SUNAT link missing'
    for path in ['data/clientes.json', 'data/ventas.json', 'tests/business.php', 'includes/helpers.php', '.git/config']:
        try:
            request(path)
            raise AssertionError('Private file exposed: ' + path)
        except urllib.error.HTTPError as e:
            assert e.code in [403, 404], (path, e.code)
    try:
        request('actions/guardar_cliente.php', {'empresa_id': '999', 'nombre': 'NO_GUARDAR'})
        raise AssertionError('Expected company mismatch')
    except urllib.error.HTTPError as e:
        assert e.code == 422 and 'error' in json.loads(e.read())
    print(f'HTTP OK: {len(paths)} paginas; JS inline valido; Kardex técnico y enlaces desde Inventario/Venta; 5 archivos privados bloqueados; empresa incorrecta rechazada.')
finally:
    assert before == hashes(), 'Project JSON data changed!'
    print('Datos JSON: hashes identicos antes/despues.')
