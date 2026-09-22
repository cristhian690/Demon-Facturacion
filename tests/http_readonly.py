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
             'pages/ventas/detalle.php?id=1', 'pages/inventario/kardex.php?venta_id=1']
    paths += [f'pages/{table}/{page}.php' for table in ['clientes', 'proveedores', 'productos', 'almacenes'] for page in ['index', 'form']]
    for path in paths:
        response = request(path)
        html = response.read().decode('utf-8-sig')
        assert response.status == 200 and not re.search(r'(Warning|Fatal error|Parse error)(?:</b>)?:', html), path
        for script in re.findall(r'<script(?:\s[^>]*)?>(.*?)</script>', html, re.S):
            if not script.strip():
                continue
            with tempfile.NamedTemporaryFile(suffix='.js', delete=False, mode='w', encoding='utf-8') as f:
                f.write(script)
                filename = f.name
            try:
                subprocess.run(['node', '--check', filename], check=True, capture_output=True)
            finally:
                Path(filename).unlink()
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
    print(f'HTTP OK: {len(paths)} paginas; JS inline valido; 5 archivos privados bloqueados; empresa incorrecta rechazada.')
finally:
    assert before == hashes(), 'Project JSON data changed!'
    print('Datos JSON: hashes identicos antes/despues.')
