# Cómo arrancar Salvando Huellas en tu ordenador

Esta guía explica cómo instalar y abrir el proyecto en local. Los comandos están preparados para la terminal PowerShell de Visual Studio Code.

## 1. Programas necesarios

- Git, si vas a descargar el proyecto desde GitHub.
- Visual Studio Code.
- PHP compatible con las dependencias del proyecto. El proyecto declara PHP 8.3 o superior; Composer comprobará también los requisitos de las dependencias instaladas.
- Composer.
- Node.js 24 LTS y npm.
- MySQL o MariaDB. Puedes utilizar el servicio MySQL de XAMPP y phpMyAdmin.

Comprueba que los comandos están disponibles:

```powershell
php -v
composer --version
node -v
npm -v
```

Si Composer indica que falta una extensión de PHP o que la versión no es compatible, corrige ese requisito antes de continuar. No utilices `--ignore-platform-reqs`.

## 2. Descargar el proyecto

Desde la carpeta donde quieras guardarlo:

```powershell
git clone --branch dev https://github.com/Alberto21-21/ProyectoProtectora.git
cd ProyectoProtectora
code .
```

También puedes descargar el ZIP de la rama `dev`, descomprimirlo y abrir su carpeta en VS Code.

La carpeta principal debe contener:

```text
ProyectoProtectora/
    backend/
    frontend/
```

## 3. Crear la base de datos

Inicia MySQL desde XAMPP o desde el servicio que utilices.

En phpMyAdmin crea una base de datos llamada `protectora`. No necesitas crear las tablas a mano: las crearán las migraciones de Laravel.

Si quieres usar phpMyAdmin mediante XAMPP, inicia también Apache. El servidor de Laravel se arrancará por separado.

## 4. Instalar y configurar el backend

Abre una terminal en la carpeta principal y ejecuta:

```powershell
cd backend
composer install
```

Utiliza `composer install` para instalar las versiones del archivo `composer.lock`.

### Crear el archivo de configuración

Si no existe `backend/.env`, copia la plantilla:

```powershell
Copy-Item .env.example .env
```

Si no está incluida `.env.example`, pide al equipo una plantilla sin contraseñas ni claves privadas. No copies el `.env` personal de otra persona completo.

Abre `backend/.env` y ajusta estas variables, conservando el resto de la plantilla:

```dotenv
APP_NAME="Salvando Huellas"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=protectora
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=100001
REVERB_APP_KEY=protectora_local_key
REVERB_APP_SECRET=protectora_local_secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
```

`root` sin contraseña es solo un ejemplo para una instalación local. Pon el usuario, contraseña y puerto reales de tu MySQL.

Las claves de Reverb de este ejemplo son para desarrollo local. La clave pública debe coincidir con la del frontend.

### Cloudinary

Para subir fotografías necesitas las credenciales de Cloudinary autorizadas para el proyecto. Añade esta variable al `.env`, sustituyendo los marcadores:

```dotenv
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME
```

La API Secret se guarda únicamente en el backend. No la pongas en el frontend ni subas el `.env` a GitHub. Si no tienes credenciales, solicita al equipo la configuración por un canal privado; las subidas de imágenes no funcionarán hasta configurarla.

### Generar la clave y crear las tablas

En esta primera instalación, con `APP_KEY` vacía:

```powershell
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan db:seed
php artisan storage:link
```

- `key:generate` crea la clave propia de esta instalación. No la regeneres cada vez que arranques.
- `migrate` crea las tablas pendientes, incluidas las necesarias para las funcionalidades que estén integradas en la rama descargada.
- `db:seed` crea el administrador inicial si no existe.
- `storage:link` permite servir fotografías antiguas guardadas en el almacenamiento local. Si indica que el enlace ya existe, puedes continuar.

No ejecutes `migrate:fresh` sobre una base de datos con información que quieras conservar: elimina las tablas y sus datos.

## 5. Instalar y configurar el frontend

Abre otra terminal desde la carpeta principal:

```powershell
cd frontend
npm install
```

Crea `frontend/.env`, al mismo nivel que `package.json`, con este contenido:

```dotenv
VITE_REVERB_APP_KEY=protectora_local_key
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=8080
```

Estas variables permiten que el navegador conecte con Reverb. Si cambias este archivo mientras Vite está abierto, reinicia Vite.

## 6. Arrancar la aplicación

Mantén MySQL encendido y abre cuatro terminales. Los comandos se ejecutan desde las carpetas indicadas, no desde la raíz del repositorio.

| Terminal | Carpeta | Comando | Función |
| --- | --- | --- | --- |
| 1 | `backend` | `php artisan serve` | API de Laravel, normalmente en el puerto 8000. |
| 2 | `backend` | `php artisan reverb:start` | Servidor WebSocket, configurado en el puerto 8080. |
| 3 | `backend` | `php artisan queue:work` | Procesa los trabajos y eventos enviados a la cola. |
| 4 | `frontend` | `npm run dev` | Abre el frontend con Vite. |

No cierres estas terminales mientras uses la aplicación. Para detener cada proceso pulsa `Ctrl + C` en su terminal.

Abre en el navegador la dirección que muestre Vite, normalmente:

```text
http://localhost:5173
```

No abras los HTML haciendo doble clic ni mediante Live Server. La aplicación debe abrirse desde Vite.

El frontend utiliza la API local `http://127.0.0.1:8000`. Si Laravel arranca en otro puerto, libera el 8000 o adapta las direcciones del frontend antes de continuar.

## 7. Entrar en la aplicación

Puedes registrarte desde la página de inicio para crear una cuenta de adoptante.

El seeder del proyecto crea este administrador local si todavía no existe:

```text
Correo: admin@salvandohuellas.test
Contraseña: Admin12345!
```

Son credenciales iniciales de desarrollo. Si la cuenta ya existía, el seeder no sustituye su contraseña. Cambia la contraseña antes de utilizar la aplicación fuera del entorno de clase.

La base de datos nueva puede no contener animales, solicitudes ni fichas laborales. Es normal que los listados aparezcan vacíos: clonar el código no copia la base de datos del equipo. Para ver datos, créalos desde las pantallas autorizadas o solicita una exportación de demostración sin datos personales reales.

## 8. Volver a arrancar otro día

No necesitas reinstalarlo todo. Inicia MySQL y ejecuta los cuatro comandos del apartado 6.

Si has descargado cambios del equipo, con tus cambios locales guardados, actualiza desde la carpeta principal:

```powershell
git switch dev
git pull origin dev
```

Después, desde `backend`:

```powershell
composer install
php artisan migrate
php artisan config:clear
```

Y desde `frontend`:

```powershell
npm install
```

Reinicia los procesos para que carguen el código y la configuración nuevos. No necesitas regenerar `APP_KEY` ni recrear la base de datos.

## 9. Problemas frecuentes

| Mensaje o problema | Qué revisar |
| --- | --- |
| `Could not open input file: artisan` | Ejecuta el comando desde `backend`. |
| Falta `vendor/autoload.php` | Ejecuta `composer install` en `backend`. |
| `vite` no se reconoce | Ejecuta `npm install` en `frontend`. |
| `Access denied` de MySQL | Revisa usuario y contraseña del backend `.env`. |
| Conexión rechazada en el puerto 3306 | Comprueba que MySQL está iniciado y que su puerto coincide con `DB_PORT`. |
| Falta una tabla | Ejecuta `php artisan migrate` en `backend`. |
| Falta la tabla `cache` | Esta guía usa `CACHE_STORE=file`. Guarda el `.env` y ejecuta `php artisan config:clear`. |
| Falta la tabla `jobs` | Revisa que has descargado y ejecutado las migraciones de la cola incluidas por el equipo. |
| El frontend no conecta con la API | Mantén `php artisan serve` abierto en el puerto 8000. |
| No llegan mensajes en tiempo real | Comprueba Reverb, el trabajador de cola y la coincidencia de la clave pública de ambos `.env`. Inicia sesión. |
| Falla la subida de una fotografía | Revisa la configuración privada de Cloudinary y la conexión a Internet. |
| `Module mysqli is already loaded` | PHP está cargando la extensión dos veces. Revisa las entradas repetidas en su configuración; es un aviso distinto de los errores de Laravel. |

Para comprobar que el frontend compila, desde `frontend` puedes ejecutar:

```powershell
npm run build
```

Esta guía es para ejecutar el proyecto en el mismo ordenador. Un despliegue en Internet necesita su propia configuración de dominio, HTTPS, base de datos, secretos y WebSockets.
