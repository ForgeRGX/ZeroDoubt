# Cuentas Claras

**Aplicación web para gestionar tus ingresos y gastos mensuales.**

Desarrollada en PHP 8.3 con MySQL 8.4 y desplegada con Docker Compose. Sigue la misma estructura (controladores, modelos, vistas, rutas propias, sin framework), el mismo estilo de interfaz y las mismas convenciones que [TR Tracking](https://github.com/ForgeRGX/TR-Tracking).

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white" alt="MySQL 8.4">
  <img src="https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white" alt="Docker Compose">
</p>

---

## Funcionalidades

**Lo que pediste**

- **Ingresos y gastos mensuales** con descripción, fecha, categoría y notas.
- **Gráficas globales, anuales y mensuales** (pestañas en *Informes*), consultables **filtrando por fechas**:
  - *Global*: rango libre de fechas, con atajos (3, 6, 12 meses, este año, todo).
  - *Anual*: navegación año a año, matriz categoría × mes y comparación con el año anterior.
  - *Mensual*: detalle por día, mayores gastos del mes y progreso frente a presupuestos.
  - Todas se pueden filtrar por categoría y comparan con el periodo anterior equivalente.
- **Categorías propias de gastos y de ingresos**, con color e icono. Se crean unas por defecto al registrarse (editables o eliminables).
- **Ingresos brutos y netos con retenciones**. Al registrar un ingreso eliges cómo introducirlo:
  1. *Solo neto*.
  2. *Bruto + retención %* → se calcula el neto.
  3. *Bruto + neto* → se calculan la retención en € y el %.

  Las gráficas usan el neto (lo que realmente entra); el bruto y las retenciones se muestran aparte (retención efectiva del periodo incluida). Cada categoría de ingreso puede tener una retención por defecto (p. ej. 15 % en nómina).
- **Gastos (e ingresos) recurrentes con validación**. Defines una regla («Gimnasio, 50 €, cada mes, desde el 5/1»); cada repetición vencida se crea como **pendiente**. Tú la **validas** indicando la fecha exacta y el importe real (o la confirmas tal cual con un clic, o «Validar todos»). Los pendientes no cuentan en las gráficas hasta validarse. Frecuencias: semanal, quincenal, mensual, bimestral, trimestral, semestral y anual; con fecha de fin opcional, pausa/reactivación y confirmación automática opcional.
- **Multiusuario**: cada usuario solo ve y gestiona sus propios datos (todas las consultas se filtran por usuario).

**Mejoras añadidas**

- **Presupuestos mensuales por categoría** con barra de progreso y avisos al pasarte.
- **Dashboard** con balance del mes, variación frente al mes anterior, tasa de ahorro, próximos recurrentes (30 días) y pendientes de validar.
- **Buscador y filtros** en movimientos (tipo, categoría, estado, fechas, texto), paginación y **exportación a CSV**.
- **Duplicar movimientos** y botón de alta rápida.
- **Español / inglés** (formato de importes y fechas según idioma), cambio de contraseña y ajustes.
- **Seguridad**: contraseñas con hash, protección CSRF, límite de intentos de login por IP/usuario, cookies `HttpOnly`/`SameSite`, consultas preparadas, cabeceras de seguridad y opción de desactivar el registro.
- **Copias de seguridad**: `scripts/backup.sh` y `scripts/restore.sh`.
- phpMyAdmin opcional (solo accesible desde tu máquina).

---

## Requisitos

- Docker Engine y Docker Compose v2.
- Un archivo `.env` con las credenciales (se crea a partir de `.env.example`).

---

## Instalación

```bash
# 1. Configuración
cp .env.example .env                          # cambia las contraseñas
cp docker-compose.yml.example docker-compose.yml

# 2. Construir y arrancar
docker compose build
docker compose up -d

# 3. Abrir http://localhost:8080 y crear tu cuenta
```

- El esquema de la base de datos (`database/schema.sql`) se aplica automáticamente **la primera vez** (volumen vacío).
- Si no existe ningún usuario, el registro está siempre abierto para crear el primero. Después puedes poner `ALLOW_REGISTRATION=false` en `.env` y ejecutar `docker compose up -d` para impedir nuevas altas.
- phpMyAdmin: <http://localhost:8081> (puerto `PMA_PORT`, solo `127.0.0.1`).
- Para ver errores detallados, usa `APP_DEBUG=true` en `.env` (no en producción).

### Comandos útiles

```bash
docker compose logs --tail=100 web     # registros de la aplicación
scripts/lint.sh                        # sintaxis PHP + traducciones (sin reconstruir la imagen)
scripts/backup.sh                      # backups/cuentas_claras_AAAA-MM-DD.sql
scripts/restore.sh backups/fichero.sql # restaurar un backup
php scripts/check_translations.php     # textos sin traducir al inglés
```

---

## Cómo funcionan los recurrentes

1. En **Recurrentes → Nueva regla** defines tipo, categoría, descripción, importe previsto, frecuencia y primera fecha.
2. Cada vez que entras en la aplicación (como mucho una vez por hora) se crean las repeticiones vencidas como movimientos **pendientes**. La clave única `(regla, fecha prevista)` impide duplicados.
3. En **Pendientes de validar** puedes: *Validar* con lo previsto, *Revisar* (cambiar fecha e importe reales y validar), u *Omitir* esa repetición.
4. Si la primera fecha es el día 31, en los meses más cortos se usa el último día del mes y se vuelve al 31 cuando es posible.
5. Al reactivar una regla pausada no se generan retroactivamente los meses en pausa.
6. Borrar una regla elimina sus pendientes, pero conserva los movimientos ya validados.

---

## Estructura del proyecto

```text
cuentas-claras/
├── config/          # Configuración y definición de rutas
├── controllers/     # Controladores
├── core/            # Router, vistas, autenticación, i18n, helpers
├── database/        # Esquema SQL
├── docker/          # Configuración de Apache
├── lang/            # Traducciones (en.php; el español es el idioma base)
├── models/          # Acceso a datos y estadísticas
├── public/          # Punto de entrada y recursos estáticos (CSS, JS, imágenes)
├── scripts/         # Backup, restauración, lint y comprobación de traducciones
├── views/           # Plantillas
├── Dockerfile
└── docker-compose.yml.example
```

### Modelo de datos

| Tabla | Contenido |
|---|---|
| `users` | Usuarios, hash de contraseña e idioma |
| `categories` | Categorías de ingreso/gasto por usuario (color, icono, retención por defecto) |
| `transactions` | Movimientos: `amount` (neto en ingresos), `gross_amount`, retención, estado `confirmed`/`pending`, regla de origen y fecha prevista |
| `recurring_rules` | Reglas recurrentes y su próxima fecha prevista |
| `budgets` | Presupuesto mensual por categoría de gasto |
| `login_attempts` | Intentos de login fallidos (anti fuerza bruta) |

---

## Licencia

Pendiente de definir.
