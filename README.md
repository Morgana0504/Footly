Footly – Sistema de gestión y consulta de calzado

1. Descripción del proyecto
Footly es una aplicación web desarrollada para digitalizar y facilitar la consulta de un catálogo de calzado, así como la gestión de las operaciones de un distribuidor.
El sistema permite consultar productos, administrar información y gestionar usuarios mediante diferentes roles.
Este proyecto incorpora una arquitectura basada en servicios PHP y una base de datos MySQL, con soporte para contenedores mediante Docker y Docker Compose.


Funcionalidades principales:
- Inicio de sesión de usuarios.
- Autenticación y validación de credenciales.
- Control de acceso según el rol del usuario.
- Acceso de administrador al panel de administración.
- Acceso de cliente al catálogo de productos.
- Consulta de productos y sus variantes.
- Gestión de pedidos, clientes, inventario y pagos.


5. Requisitos para ejecutar el proyecto
- Docker Desktop instalado.
- Git instalado.
- Visual Studio Code u otro editor de código.
- Puerto 8080 disponible.
- Puerto 3306 disponible, si no se modifica la configuración de MySQL.

---*** Configuración del entorno ***---

Paso 1. Clonar el repositorio: git clone URL_DEL_REPOSITORIO
Ingresar a la carpeta: cd Footly_Backup

Paso 2. Crear el archivo de variables de entorno
Copiar la plantilla: cp .env.example .env
Editar el archivo .env y establecer las credenciales locales de MySQL.
No compartir este archivo ni publicarlo en GitHub.

Paso 3. Preparar la base de datos
El respaldo SQL con información real no se distribuye en el repositorio.
Para ejecutar el proyecto, se debe proporcionar una copia local compatible de la base de datos en: database/footly.sql
La base de datos debe conservar las tablas, relaciones, índices y estructura requeridos por la aplicación.

Paso 4. Construir e iniciar los contenedores
docker compose build
docker compose up -d

Paso 5. Verificar los servicios
docker compose ps
Comprobar que los servicios web y de base de datos estén activos.

Paso 6. Acceder a Footly
Abrir el navegador y visitar: http://localhost:8080

7. Servicios Docker
El proyecto utiliza dos contenedores principales:


8. Pruebas funcionales
Se realizaron pruebas de inicio de sesión utilizando dos perfiles:
- Administrador: autenticación correcta y acceso al panel administrativo.
- Cliente: autenticación correcta y acceso al catálogo de productos.
Se verificó la comunicación entre PHP y MySQL dentro del entorno Docker.

9. Detener los contenedores
Para detener los servicios: docker compose down
Para iniciarlos nuevamente: docker compose up -d
Los datos de MySQL se conservan en un volumen persistente de Docker.

10. Seguridad
Para proteger la información del proyecto:
- Las credenciales se administran mediante variables de entorno.
- El archivo .env está excluido mediante .gitignore.
- El respaldo de la base de datos con información real no se publica.
- Se utiliza .env.example como plantilla de configuración.
- No se deben publicar contraseñas, tokens ni información privada.
