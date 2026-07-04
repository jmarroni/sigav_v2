# Prompt para Claude Design — Landing comercial de SIGAV

> Copiar todo el bloque de abajo y pegarlo en Claude design web.
> Antes de pegar, completar los datos entre `[corchetes]`: WhatsApp, email y números de la franja de confianza.

---

Diseñá una landing page comercial de una sola página (one-page), en español de Argentina, para promocionar SIGAV, un sistema de gestión para comercios minoristas. El objetivo N°1 de la página es captar clientes potenciales: que un dueño de comercio que la visite termine pidiendo una demo por WhatsApp o dejando sus datos en el formulario.

## Qué es SIGAV

SIGAV es un sistema de punto de venta, stock y facturación pensado para comercios con una o varias sucursales. Lo usan negocios reales todos los días. Sus funcionalidades principales (usalas como contenido, no inventes otras):

- Punto de venta rápido: cobrás en segundos, con descuentos por producto o sobre el total.
- Cobro con QR de Mercado Pago integrado: el QR aparece en pantalla y el sistema avisa solo cuando se acredita el pago.
- Facturación electrónica ARCA (ex AFIP): facturas y notas de crédito emitidas directo desde la venta.
- Stock por sucursal: sabés cuánto hay de cada producto en cada local, en tiempo real.
- Transferencias entre sucursales con seguimiento.
- Integración con Mercado Libre para publicar y sincronizar productos.
- Presupuestos en PDF con tu logo, listos para enviar al cliente.
- Cierres de caja por sucursal y reportes de ventas.
- Etiquetas con código de barras para góndola, impresas desde el sistema.
- Clientes, proveedores, usuarios y roles con permisos por sucursal.
- Módulos a medida: se adapta a rubros específicos (por ejemplo, ópticas con pedidos de laboratorio).

## Audiencia

Dueños y encargados de comercios minoristas en Argentina (kioscos, regalerías, ópticas, indumentaria, ferreterías), muchos con 2 a 10 sucursales. No son técnicos: hablales de problemas concretos (colas en la caja, no saber el stock del otro local, facturar a mano, conciliar pagos de Mercado Pago), no de tecnología.

## Estructura de la página

1. **Hero**: titular fuerte orientado al beneficio (ej.: "Vendé, facturá y controlá el stock de todas tus sucursales desde un solo sistema"). Subtítulo de una línea. Dos CTAs: botón primario "Pedí una demo por WhatsApp" y secundario "Ver funcionalidades". Incluí un mockup ilustrativo de la pantalla de ventas (podés dibujarlo vos con HTML/CSS, no uses imágenes externas).
2. **Problema → solución**: 3 o 4 dolores típicos del comerciante y cómo SIGAV los resuelve, en una línea cada uno.
3. **Funcionalidades**: grilla tipo bento con las funcionalidades de arriba, agrupadas (Ventas y cobros / Stock y sucursales / Facturación / Integraciones). Destacá visualmente las 3 estrella: QR de Mercado Pago, facturación ARCA y stock multi-sucursal.
4. **Cómo funciona**: 3 pasos simples (Nos contactás → Lo configuramos con tus productos y sucursales → Empezás a vender). Remarcar que incluye acompañamiento y soporte directo, sin call centers.
5. **Para quién es**: chips o tarjetas con los rubros (óptica, kiosco, indumentaria, etc.) y una nota de que se hacen adaptaciones a medida.
6. **Confianza**: franja con números (ej.: "+X años funcionando en comercios reales", "+X sucursales operando", "ventas procesadas todos los días") — dejá los números como [X] para completar.
7. **FAQ**: 4-5 preguntas (¿Necesito instalar algo? ¿Funciona con mi impresora de tickets? ¿Emite factura electrónica? ¿Qué pasa con mis datos? ¿Cuánto cuesta? — respuesta: se cotiza según sucursales, pedí una demo).
8. **CTA final + formulario de contacto**: nombre, comercio, cantidad de sucursales, teléfono. Botón de WhatsApp bien visible también acá. WhatsApp: [+54 9 XXX XXX-XXXX]. Email: [contacto@ejemplo.com].
9. **Footer** simple: marca, links a secciones, datos de contacto.

## Dirección visual

Estilo SaaS tech moderno e intencional, NO template genérico:

- Fondo claro dominante con una sección oscura de contraste (por ejemplo el bloque de confianza o el CTA final).
- Un solo color de acento vibrante (verde esmeralda o violeta eléctrico) usado con criterio: CTAs, números, highlights. Nada de arcoíris de colores.
- Tipografía display grande y con carácter para titulares (contraste fuerte de escala entre h1 y texto), sans-serif legible para el cuerpo.
- Jerarquía y ritmo: secciones con espaciado generoso pero variado, no todo con el mismo padding.
- Detalles de producto: mockups de la interfaz dibujados en CSS (una pantalla de venta con ticket, un QR, una tarjeta de stock por sucursal) para que se vea el sistema "vivo".
- Microinteracciones sobrias: hover en botones y tarjetas, aparición suave al scrollear. Nada que distraiga.
- Botón flotante de WhatsApp visible en toda la página.

## Requisitos técnicos

- Una sola página HTML autocontenida (CSS y JS inline), responsive (mobile-first: la mayoría va a entrar desde el celular).
- Sin dependencias externas ni imágenes remotas.
- Textos en español rioplatense, tono cercano pero profesional (tuteo con "vos").
- SEO básico: title, meta description, headings semánticos.
- Que el botón de WhatsApp use el link wa.me con un mensaje precargado tipo "Hola, quiero una demo de SIGAV".
