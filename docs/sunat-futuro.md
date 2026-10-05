# Arquitectura futura de integración SUNAT

Esta documentación describe una etapa posterior al prototipo. El sistema actual no genera XML tributarios, no firma documentos y no se conecta con SUNAT.

## Flujo previsto

```text
Facturación
    ↓
XmlGenerator
    ↓
XmlSigner
    ↓
SunatClient
    ↓
CdrProcessor
    ↓
Estado del comprobante
```

- **Facturación** valida y conserva el comprobante comercial.
- **XmlGenerator** transformará el comprobante validado al formato electrónico aplicable.
- **XmlSigner** aplicará la firma con el certificado de la empresa.
- **SunatClient** enviará y consultará documentos mediante los servicios oficiales.
- **CdrProcessor** validará y asociará la respuesta con el comprobante original.

Estas responsabilidades deberán implementarse como componentes reales cuando se defina el proveedor, las especificaciones vigentes y el entorno de despliegue. No se deben crear respuestas de aceptación o rechazo sin una respuesta verificable del servicio oficial.

## Separación de inventario

La emisión tributaria y el inventario son procesos relacionados, pero independientes:

```text
Venta
├── Inventario → Kardex
└── Comprobante → Integración SUNAT
```

Un cambio en el estado SUNAT no debe recalcular el CPP, alterar snapshots del Kardex ni descontar stock.

## Seguridad

En producción se deberá cumplir como mínimo lo siguiente:

- Mantener certificados fuera de carpetas públicas.
- No almacenar contraseñas ni credenciales SOL en texto plano.
- Inyectar secretos mediante variables de entorno o un almacén de secretos.
- Cifrar material sensible almacenado.
- Restringir el acceso por empresa y por rol.
- Conservar trazabilidad de cada intento de envío y consulta.
- Asociar XML y CDR con el comprobante y la empresa correctos.
- Validar integridad, tipo y tamaño antes de almacenar respuestas.
- Definir políticas de rotación, respaldo y vencimiento de certificados.

## Multiempresa

Cada empresa tendrá configuración separada para RUC, series, certificado, credenciales, ambiente, XML, CDR y logs. Ningún artefacto tributario podrá reutilizarse o consultarse desde otra empresa.

## Almacenamiento futuro

Los XML, CDR y logs se almacenarán fuera del directorio público, con nombres internos no predecibles, control de acceso y referencias explícitas al comprobante. El prototipo actual no crea estos archivos.

