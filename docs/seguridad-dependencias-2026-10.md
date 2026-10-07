# Dependencias: estado de seguridad (2026-10-07)

Origen: 48 alertas abiertas de Dependabot en `jmarroni/sigav_v2` (1 crítica,
19 altas, 26 medias, 2 bajas) más `osv-scanner` sobre `composer.lock`,
`public/composer.lock` y `package.json`. Muchas alertas son el mismo paquete
repetido; por paquete eran 10.

## Lo que se corrigió en este cambio

| Paquete | Antes | Ahora | Alertas cerradas |
|---|---|---|---|
| `guzzlehttp/guzzle` | 7.11.0 | 7.15.5 | 1 alta + 8 medias |
| `guzzlehttp/psr7` | 2.11.0 | 2.13.1 | 2 medias |
| `phpseclib/phpseclib` | 2.0.54 | 2.0.55 | 1 media (SSRF por AIA en X.509) |
| `axios` (devDependency npm) | ^0.19 | eliminado | 25 (nada lo usa: Mix apunta a `resources/js/app.js`, que no existe) |

Exposición web real cerrada, independiente de las versiones:

- `public/vendor/*/*/examples`, `doc` y `test` (html2pdf, tcpdf, afipsdk)
  **se servían por URL**; `examples/forms.php` de html2pdf respondía 200 y es
  el XSS de GHSA-99fg-2h75-m92h. Se borraron del repo (243 archivos) y Apache
  niega todo `/vendor/` (`deploy/afip-protect.conf`).
- `composer.json`, `composer.lock`, `package.json` y `.env*` del docroot ya
  no se sirven (`FilesMatch` en la misma conf).
- `docker-compose.yml` local monta ahora la misma conf que prod para poder
  probarla.

`composer.json` fija `config.platform.php = 7.4.33`: la máquina de desarrollo
tiene PHP 8.3 y sin ese pin `composer update` resolvía versiones que no corren
en el contenedor (php:7.4-apache).

## Lo que NO se puede corregir sin subir de Laravel

Todas requieren PHP 8 o un major de Laravel/Passport. Evaluación de riesgo
real en este código:

| Paquete | Alerta | Por qué queda | Exposición real |
|---|---|---|---|
| `laravel/framework` 7.30.7 | GHSA-5vg9-5847-vvmq (alta, CRLF en regla `email`) | fix en 12.60 | **Nula**: la app no envía mail con Laravel Mail (el legacy usa PHPMailer) y L7 usa SwiftMailer, no Symfony Mailer. |
| `laravel/framework` | GHSA-78fx-h6xr-vch4 (media, validación `files.*`) | fix en 10.48.29 | **Nula**: no hay reglas wildcard sobre archivos. |
| `laravel/framework` | GHSA-crmm-hgp2-wgrp (media, URLs firmadas temporales) | fix en 12.61.1 | **Nula**: no se usa `temporaryUrl`/`signedRoute`. |
| `league/commonmark` 1.6.7 | 7 altas + 3 medias (DoS/XSS en Markdown) | L7 pide `^1.3`; fix en 2.x (PHP 7.4+ pero incompatible con L7) | **Nula**: no se renderiza Markdown (sin Markdown mail ni `Str::markdown`). |
| `firebase/php-jwt` 5.5.1 | GHSA-8xf4-w7qw-pjjw (**crítica**, confusión de algoritmo vía `kid`) | Passport 9 pide `^5.0`; fix en 6.0 | **Baja**: Passport decodifica con una sola clave HS256 y algoritmo fijo (`TokenGuard::decodeJwtTokenCookie`); el ataque necesita un llavero con varios tipos de clave. Además el middleware `CreateFreshApiToken` no está activo, así que esa cookie ni se emite. |
| `laminas/laminas-diactoros` 2.17.0 | GHSA-xv3h-4844-9h36 (alta, LF al final de un nombre de header) | fix en 2.18.1, requiere PHP 8 | **Baja**: solo se usa para convertir la request del flujo OAuth; el atacante tendría que controlar *nombres* de headers que la app re-serialice. |
| `phpseclib/phpseclib` 2.0.55 | GHSA-q97c-8qh3-fpc6 (media, X25519 no constante) | fix en 3.0.57 | **Nula**: Passport solo lo usa para generar las claves RSA (`passport:keys`); no hay X25519. |
| `league/flysystem` 1.1.10 | GHSA-cxf4-7mrp-vvpr (baja) | fix en 3.35.3 | Baja. |
| `spipu/html2pdf` 5.2.4 (`public/composer.lock`) | GHSA-99fg-2h75-m92h (media, XSS en `examples/forms.php`) | se dejó la versión a propósito | **Cerrada por otra vía** (examples borrados + `/vendor/` denegado). No se sube porque la 5.3.x cambia cómo resuelve `src=file://` del logo y los PDF legacy saldrían sin logo; ver `legacy_logo_pdf()`. |

Nota: Composer 2.10 bloquea por defecto cualquier versión con advisory. Toda la
rama 2.0.x de phpseclib está marcada, así que `composer update` de ese paquete
necesita `composer config policy.advisories.block false` temporal (no quedó en
`composer.json`).

## Siguiente paso de fondo

Cerrar el resto exige el salto Laravel 7 → 10/11 con PHP 8.2 y Passport 11+.
Es un proyecto aparte (ver `docs/informe-seguridad-upgrade-2026-07.md`).
Hasta entonces, las alertas de Dependabot sobre `laravel/framework`,
`league/commonmark`, `firebase/php-jwt`, `laminas-diactoros`, `phpseclib` y
`flysystem` se pueden descartar en GitHub como "riesgo tolerable" con esta
tabla como justificación.

## Deploy

El código legacy carga `public/vendor` desde disco; borrar `examples` no
afecta a nada. `composer.lock` cambió, así que en la VM hace falta
`composer install` además del `git pull`, y `docker restart` del contenedor
para que Apache relea la conf.
