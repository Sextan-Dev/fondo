# Fondo Unido ERP

ERP de United Way México (Fondo Unido), construido sobre la base de *Sextan for Non Profit – Nido Social*.
Lleva la contabilidad de los fondos de cada donante —ingresos, egresos, traspasos, costo operativo,
liberaciones y remanentes— y la liga con los proyectos, programas y aliados que esos fondos financian.

Módulos principales:

- **Donantes y directorio**: contactos, cuentas y estados de cuenta por donante.
- **Operaciones**: captura, importación desde Excel e historial de cambios.
- **Proyectos y programas**: proyectos, cotizaciones y reportes por proyecto.
- **Reportes**: saldos de donantes, reporte general, reporte por donante, aliado y proyecto, y el reporte
  público con enlace firmado.
- **Tareas, calendario, notificaciones y biblioteca** de documentos.
- **Administración**: usuarios, roles, parámetros y configuración.

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.4 · Laravel 13 · MySQL 8 |
| Autenticación | JWT (`tymon/jwt-auth`); la SPA guarda el token en `localStorage.jwt_token` |
| Frontend | SPA con Vue 2.7, vue-router, Vuex, Element UI, Bootstrap 4, DataTables y jQuery |
| Build | webpack 5 (sin Laravel Mix; `mix-manifest.json` lo genera un plugin propio) y minificación con esbuild |
| Excel | `maatwebsite/excel` para importar y `yajra/laravel-datatables-oracle` para las tablas del servidor |
| Correo | SparkPost |
| Tiempo real | Pusher (notificaciones y aviso de nueva versión) |
| Traducciones | gettext: `_()` en PHP y `vue-gettext` en la SPA (es_ES y fr_FR) |

## Estructura

```
app/
  Http/Controllers/   API (routes/api.php) y controladores de reportes
  Helpers/            Base.php y Privileges.php (se cargan siempre, vía composer)
  Support/            ReportAccess: firma de los enlaces a reportes públicos
  Console/Commands/   Avisos de despliegue y recordatorios
resources/
  vue/                SPA: app.js, routes.js, components/, store/, helpers/
  assets/             Sass y archivos estáticos que el build copia a public/
  views/app.blade.php Única vista: monta la SPA
public/               Salida del build y archivos subidos por los usuarios (public/uploads)
tests/                PHPUnit (Unit, Feature) y tests/frontend
```

`public/` está en `.gitignore`, salvo `index.php`, `.htaccess`, `web.config` y `robots.txt`.
**El build nunca limpia `public/`**, porque también guarda los adjuntos de los usuarios.

## Desarrollo local

Fondo corre en el stack Docker compartido `Docker-apps-container` (un repositorio aparte, junto a
`Sextan-Apps/`), que monta `Sextan-Apps/` en `/var/www/html`.
El sitio ya está declarado en su `sites.yml` (`domain: fondo`, `php: "8.4"`, `template: laravel`).
Ahí se usan los contenedores `php84`, `mysql` (puerto 6306 en el host) y `web` (nginx).

El equipo anfitrión tiene una versión de PHP más vieja, así que Composer y Artisan se corren dentro de
`php84`.

1. **Variables de entorno**

   ```bash
   cp .env.example.docker .env
   ```

   - `APP_URL` debe ser **exactamente** el host que abres en el navegador. La SPA llama a la API en
     `APP_URL/api` y la app no manda cabeceras CORS, así que otro host carga la página pero sus
     llamadas a la API fallan. nginx sirve `http://fondo.localhost` y `http://fondo.test`; se
     recomienda `http://fondo.localhost`.
   - `DB_HOST=mysql`, y `DB_PASSWORD` es la contraseña de root del stack.
   - `REPORT_SIGNING_KEY` necesita al menos 32 caracteres (por ejemplo `openssl rand -hex 32`).
     Sin ella fallan los enlaces a reportes públicos.

2. **Dependencias de PHP y llaves**

   ```bash
   docker exec -w /var/www/html/fondo php84 composer install
   ```

   ```bash
   docker exec -w /var/www/html/fondo php84 php artisan key:generate
   ```

   ```bash
   docker exec -w /var/www/html/fondo php84 php artisan jwt:secret --force
   ```

   `JWT_SECRET` debe tener al menos 32 caracteres: lcobucci/jwt 5 rechaza llaves HS256 más cortas y
   el login responde *"Could not create token"*.

3. **Base de datos**

   Las migraciones de `database/migrations` son de 2018 y ya no describen el esquema real. Se trabaja
   con un respaldo de producción (por ejemplo, exportado con TablePlus):

   ```bash
   docker exec mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS fondo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"'
   ```

   ```bash
   docker exec -i mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" fondo' < ~/Downloads/fondo.sql
   ```

   La copia local se queda congelada a la hora del respaldo. Antes de comparar una exportación local
   con una de producción, revisa qué tan reciente es la copia: el equipo captura todos los días
   operaciones con fecha de meses anteriores, y los saldos de apertura cambian.

   ```bash
   docker exec -e HOME=/tmp -w /var/www/html/fondo php84 php artisan tinker --execute 'echo DB::table("operations")->max("created_at");'
   ```

4. **Frontend**

   ```bash
   npm ci
   ```

   ```bash
   npm run dev
   ```

   Usa Node 22 en desarrollo (`.nvmrc`). Abre `http://fondo.localhost`.

### Entrar sin contraseña en local

Genera un token para un usuario administrador del respaldo y guárdalo en el navegador:

```bash
docker exec -e HOME=/tmp -w /var/www/html/fondo php84 php artisan tinker --execute 'echo JWTAuth::fromUser(App\User::find(ID));'
```

En la consola del navegador, sobre `http://fondo.localhost`, corre
`localStorage.jwt_token = '<token>'` y recarga. El token vale solo para ese origen.
`HOME=/tmp` hace falta porque psysh no puede escribir su configuración en el contenedor.

## Comandos

| Comando | Qué hace |
|---|---|
| `npm run dev` | Build de desarrollo (unos segundos) |
| `npm run watch` | Build de desarrollo que se recompila al guardar (`watch-poll` si no detecta cambios) |
| `npm run production` | Build minificado para producción |
| `npm run test:html` | Verifica que el saneamiento de HTML rechace inyecciones |
| `docker exec -w /var/www/html/fondo php84 vendor/bin/phpunit` | Pruebas de PHP (Unit y Feature) |

`MigrationWorkflowTest` se salta a menos que exista `FONDO_MIGRATION_TEST=1`. Prueba login y permisos,
captura de operaciones y traspasos, importación de Excel, reportes y aislamiento entre organizaciones.
Trabaja dentro de una transacción que se revierte al final. Córrelo solo contra una copia restaurada de
la base, nunca contra producción:

```bash
docker exec -e FONDO_MIGRATION_TEST=1 -w /var/www/html/fondo php84 vendor/bin/phpunit tests/Feature/MigrationWorkflowTest.php
```

## Producción

- **PHP 8.4** con, al menos, `gettext` (las traducciones `_()`), `pdo_mysql`, `mbstring`, `xml`, `gd`
  (imágenes y Excel) y `zip` (Excel).
- **Node ≥ 18.12** y **al menos 1 GB de RAM** para `npm run production`. Las herramientas están fijadas
  para eso: no actualices `sass`, `sass-loader` o `webpack-cli`, ni regreses `copy-webpack-plugin` o
  terser, sin revisar la versión de Node y la memoria del servidor. Con 768 MB el build se cae.
- Variables obligatorias: `APP_KEY`, `JWT_SECRET` (≥ 32 caracteres), `REPORT_SIGNING_KEY`
  (≥ 32 caracteres) y `SPARKPOST_SECRET`. Revisa también `TRUSTED_PROXIES` y
  `SESSION_SECURE_COOKIE=true` detrás de HTTPS.
- Las cuentas de SparkPost en la región UE usan
  `SPARKPOST_ENDPOINT=https://api.eu.sparkpost.com/api/v1`.
- `LOG_SLACK_WEBHOOK_URL` es opcional: si existe, los errores también llegan a Slack.
  Fuera de producción, los errores se registran en `storage/logs/laravel.log`.

Despliegue típico:

```bash
composer install --no-dev --optimize-autoloader
```

```bash
npm ci && npm run production
```

```bash
php artisan alertDeployment:execute
```

`alertDeployment:execute` manda a todos los usuarios una notificación para que recarguen la página.
`updateConnections:execute` emite el evento `DeploymentNotification` a quienes estén conectados.

## Notas para quien desarrolle

- **Los estados de cuenta no deben moverse.** Si cambias una consulta o una fórmula de un reporte,
  compara el controlador anterior contra el nuevo, mes por mes, sobre la misma base, antes de darlo por
  bueno. En octubre de 2026, el reporte de saldos de donantes se validó así para todos los meses de
  dic-2020 a nov-2026.
- **MySQL corre en modo no estricto** (`'strict' => false`). Los reportes dependen de un `GROUP BY` laxo:
  las columnas no agregadas toman el primer registro, y por eso la forma en que se escribió la primera
  operación de una fuente decide el nombre de su fila. `amount`, `fee` y `val_operating_cost` son
  `varchar`.
- **Impresión**: las páginas sin nombre usan el papel de la impresora. Un reporte que necesite su propia
  hoja define un `@page` con nombre (`project-report`, `donor-report`, `donors-report`,
  `donors-balance`). Nunca agregues un `@page` sin nombre en un componente: todos los estilos terminan
  en `app.css` y afectarían a toda la app.
- En la SPA, `_` es **underscore**, no lodash.
- `app.blade.php` aplica `zoom: .9` al `body`, así que lo que mides en pantalla es 0.9 veces el valor CSS.

## License

The MIT License (MIT)

Copyright (c) 2018 SEXTAN GROUP SA DE CV

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
