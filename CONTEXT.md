# EspaiFest — Contexto de sesión (actualizado 2026-05-31)

## Proyecto
Web de sala de fiestas en Martorell para Laura (laurarmarin81@gmail.com).
Stack: HTML+JS puro, Firebase Firestore.
Proyecto Firebase: `reservify-espaifest-2c567` (cuenta de Laura).
Hosting actual: GitHub Pages (`polchu2899.github.io/espaifest`).
Hosting definitivo: **Indedmedia** (migración pendiente).

---

## Problema original
El `onSnapshot` activo sobre toda la colección `calendario_publico` en el admin agotaba las 50.000 lecturas/día del plan Spark gratuito de Firebase.

---

## Cambios aplicados (sesión completa 2026-05-18)

### Firebase
- Migrado de proyecto `reservify-espaifest` (cuenta propia) a `reservify-espaifest-2c567` (cuenta de Laura).
- Ambos archivos `index.html` y `reservify-firebase.html` actualizados con el nuevo `firebaseConfig`.
- Reglas Firestore configuradas: admin autenticado lee/escribe todo; web pública lee tarifas, calendario, servicios, kits, blog, promociones, config; solo crea en reservas.
- Dominios autorizados en Firebase Auth: `polchu2899.github.io`, `espaifest.com`, `localhost`.

### `reservify-firebase.html`
1. `loadAdminCalData()`: reemplazado `onSnapshot` por `getDocs` (carga única por sesión).
2. `loadAllData()`: awaita `loadAdminCalData()` en paralelo con el resto.
3. `saveFechaConcreta()`:
   - Usa `setDoc(ref, _fcData, { merge: true })` en lugar de `getDoc + merge manual` (ahorra 1 lectura).
   - Llama `await bustPublicCache()` (awaited, no fire-and-forget).
   - Refresca `loadAdminCalData()` y `loadFechasConcretas()` tras guardar.
4. `saveTarifas()` y `saveTodasTarifas()`: Firebase primario + `_syncTarifasPHP` fire-and-forget + `await bustPublicCache()`.
5. `bustPublicCache()`: escribe a `/api/cache_bust.php` (PHP) Y a `config/cache_bust` (Firebase).
6. `confirmarReserva`, `cancelarReserva`, `eliminarReserva`: mantienen Firebase + añaden `_syncCalPHP()` fire-and-forget.
7. Módulo `_PHP = { token, base }` con helpers `_syncTarifasPHP`, `_syncCalPHP`.
8. Funciones de export/import Firebase añadidas en panel Configuración (`exportarFirebase`, `importarFirebase`).
9. Botón "Sincronizar datos a servidor PHP" en Configuración (`migrarAphp`).
10. Tabla "Fechas especiales configuradas": precio muestra `franjasCustom[0].precio` si existe, si no `precioEsp`.

### `index.html` (catalán/principal)
1. `getDoc` añadido a los imports de Firebase.
2. `loadTarifasIndex()`: detección de cambio de proyecto Firebase (`espaifest_proj`), PHP-first, cache_bust (PHP o Firebase), caché localStorage 6h (`espaifest_tar_v1`).
3. `loadFechasOcupadas()`: PHP-first, caché localStorage 1h (`espaifest_cal_v1`), incluye `franjasCustom` en el objeto de datos.
4. Caché localStorage añadida a 5 colecciones más:
   - `loadBlogPosts` → `espaifest_blog_v1` TTL 24h
   - `loadServiciosWeb` → `espaifest_svc_v1` TTL 24h
   - `loadPromocionesWeb` → `espaifest_promo_v1` TTL 2h
   - `loadFidelizacionWeb` → `espaifest_niv_v1` TTL 24h
   - `loadKits` → `espaifest_kits_v1` TTL 24h
5. Cache bust invalida las 7 claves simultáneamente.

### `es/index.html` (español)
Tenía 4 bugs críticos respecto al catalán, todos corregidos:
1. Firebase config → actualizado al nuevo proyecto.
2. `updateCalOccupied()`: añadido `if(calSelected) renderFranjas(calSelected)` — antes no refrescaba el panel.
3. `renderFranjas()`: añadido bloque que limpia slots normales cuando hay `franjasCustom` + bloque completo de renderizado de franjas custom en la sección noche.
4. `webLoadFranjasPorFecha()`: añadido manejo completo de `franjasCustom` — antes ignoraba las franjas personalizadas de fechas concretas.
5. Mismas cachés localStorage que el catalán.

### Archivos PHP nuevos (para Indedmedia, inactivos en GitHub Pages)
```
api/config.php         ← token + DATA_DIR
api/tarifas.php        ← GET/POST tarifas.json
api/calendario.php     ← GET/POST/DELETE calendario.json + bulk sync
api/cache_bust.php     ← GET/POST cache_bust.json
data/.htaccess         ← Options -Indexes
```
Cuando la web esté en Indedmedia, leerá de PHP (sin cuota) con Firebase como fallback.

---

## Estado actual (en GitHub Pages)

| Función | Fuente |
|---|---|
| Tarifas/calendario (web pública) | Firebase + caché localStorage (PHP cuando esté en Indedmedia) |
| Cambios del admin visibles en web | Al recargar página (cache_bust detecta cambios) |
| Emails de reserva | Cloudflare Worker → Brevo (sin cambios) |
| Login admin | Firebase Auth (sin cambios) |
| Reservas, clientes | Firebase (sin cambios) |

---

## Lecturas Firebase estimadas en producción normal

| Escenario | Lecturas |
|---|---|
| Carga de página con caché válida | 1 (solo cache_bust) |
| Carga tras cambio del admin | ~100 (cache_bust + todas las colecciones) |
| Estimación diaria (100 visitas + Laura hace 3 cambios) | ~400 lecturas/día |
| Límite plan Spark | 50.000/día |

---

## Pendiente al migrar a Indedmedia

1. Subir todo el repositorio al hosting.
2. Renombrar `htaccess.txt` → `.htaccess`.
3. Dar permisos **755** a `/data/` desde cPanel.
4. Editar `api/config.php`: cambiar token `espaifest_CAMBIA_ESTO_2026` por uno secreto real.
5. En `reservify-firebase.html` buscar `_PHP.token` y poner el mismo token.
6. Admin → Configuración → **"Sincronizar ahora"** (migración inicial Firebase → PHP).
7. Verificar en `espaifest.com/api/tarifas.php` que devuelve JSON con datos.

---

## Cambios aplicados (sesión 2026-05-19)

### `index.html` y `es/index.html` — responsive móvil y calendario
1. **Calendario móvil**: añadido bloque `@media(max-width:640px)` para `.cal-day`, `.cal-day-hdr`, dots y franjas — celdas menos aplastadas en móvil.
2. **Banner de promoción**:
   - Cambiado de `position:relative;z-index:700` a `position:fixed;top:0;left:0;right:0;z-index:801` (por encima del nav).
   - CSS `body.banner-on nav{top:var(--banner-h)}` y `body.banner-on .hero{padding-top:calc(...)}` para bajar el nav al mostrarse.
   - El botón "Reservar" ya funciona (antes el nav interceptaba los clicks).
   - El banner ya aparece en móvil (antes el nav opaco lo tapaba).
3. **Filtro de fechas de promos**: añadido `(!p.desde || p.desde <= hoy)` — ya no muestra promos antes de su fecha de inicio.
4. **Calendario sin caché**: `loadFechasOcupadas` lee directo de Firebase con `where('fecha','>=',hoy)` — sin localStorage. Elimina desfases entre reservas confirmadas y el calendario visible.
   - Clave antigua `espaifest_cal_v1` y `espaifest_cal_v2` incluidas en `_ALL_CACHE_KEYS` para limpiarlas si existen.

### `reservify-firebase.html`
1. **`bustPublicCache()` en acciones de reserva**: añadido a `confirmarReserva`, `cancelarReserva` y `eliminarReserva` (fire-and-forget). Antes solo lo llamaban las funciones de tarifas y fechas concretas.
2. **Timestamps importados**: función global `_tsMs(v)` que reconoce Firestore Timestamps reales, objetos `{seconds,nanoseconds}` (exportados con JSON.stringify) y strings ISO.
   - `fmtDate` actualizado para usar `_tsMs`.
   - `tsOf` y `esTimestampReal` dentro del sort actualizados.
   - Todos los `r.createdAt?.toMillis?.() || new Date(...)` reemplazados por `_tsMs(r.createdAt)`.
3. **`_restoreTs` en `importarFirebase`**: convierte recursivamente `{seconds,nanoseconds}` a `Date` antes de guardar en Firestore. Futuros imports quedan con Timestamps correctos.
4. **Botón "Reparar fechas de reservas importadas"** en Configuración: asigna `createdAt` (fecha del evento a las 10:00) a todas las reservas que no tenían ese campo.

---

## Estado del caché (2026-05-19)

| Dato | Estrategia | TTL |
|---|---|---|
| Calendario ocupado | Firebase directo (sin caché) | — |
| Tarifas | localStorage `espaifest_tar_v1` | 6h |
| Blog | localStorage `espaifest_blog_v1` | 24h |
| Servicios | localStorage `espaifest_svc_v1` | 24h |
| Promociones | localStorage `espaifest_promo_v1` | 2h |
| Kits | localStorage `espaifest_kits_v1` | 24h |
| Fidelización | localStorage `espaifest_niv_v1` | 24h |

El bust (`config/cache_bust` en Firestore) se actualiza al confirmar, cancelar o eliminar una reserva, y al guardar tarifas o fechas concretas.

---

## Archivos modificados en esta sesión
- `index.html`
- `es/index.html`
- `reservify-firebase.html`
- `api/config.php` ← NUEVO (sesión 2026-05-18)
- `api/tarifas.php` ← NUEVO (sesión 2026-05-18)
- `api/calendario.php` ← NUEVO (sesión 2026-05-18)
- `api/cache_bust.php` ← NUEVO (sesión 2026-05-18)
- `data/.htaccess` ← NUEVO (sesión 2026-05-18)
- `CONTEXT.md` ← ESTE ARCHIVO

---

## Cambios aplicados (sesión 2026-05-31)

### Problema reportado por la clienta Laura
Captura desde Surface Pro de Laura: logo muy pequeño/deformado y franja blanca excesiva en el header.

### Causa raíz identificada
El CSS usaba `content: url(...)` en un `<img>` para cambiar el logo según el tema (oscuro/claro).
Esta propiedad CSS no aplica el `height` correctamente en Edge/Chrome de Windows — el logo renderizaba a su tamaño intrínseco (muy pequeño).

### Fix 1 — Logo (RESUELTO)
`index.html` y `es/index.html`: eliminado `content: url()`, sustituido por dos `<img>` con clases `.logo-dark` y `.logo-light`, controladas por CSS puro:
```css
.n-logo .logo-light { display: none; }
[data-theme="light"] .n-logo .logo-dark  { display: none; }
[data-theme="light"] .n-logo .logo-light { display: block; }
```
El logo ya se ve correctamente en todos los dispositivos. ✓

### Fix 2 — Tema por defecto (HECHO)
Cambiado `<html data-theme="dark">` → `<html data-theme="light">` en ambos archivos.
La web carga en modo claro por defecto. El botón de sol/luna sigue funcionando para cambiar.

### Fix 3 — Nav responsive (RESUELTO en sesión 2026-05-31)

**Historial de intentos:**
1. Breakpoint `max-width:1199px` con 100px/80px → Surface a 1368px CSS no entraba (1368 > 1199)
2. Breakpoint `max-width:1450px` con 90px → usuario dijo "peor"
3. `clamp()` fluido → logo desaparecía en Surface portrait (912px): los 10 links del nav (≈1070px) sobrepasaban los 912px disponibles y cubrían el logo; además el logo quedaba diminuto en PC normal a 1440px
4. Hamburger a ≤1024px (fix anterior) → logo visible en Surface ✓, pero los links desaparecían en landscape 1368px porque la imagen del logo a 160px de alto ocupa ≈480px de ancho + links 1070px = 1550px > 1272px disponibles → los links desbordaban el contenedor y no eran visibles

**Solución final aplicada (commits `e8cb958` y `4bae556`):**

Sistema de 4 rangos con CSS breakpoints en cascada:

```css
/* Desktop grande: >1450px */
nav { height: 180px; }
.n-logo img { height: 160px; }
.hero { padding-top: 180px; }

/* Surface / laptop: 1025–1450px */
@media(min-width:1025px) and (max-width:1450px){
  nav { height: 120px; }
  .n-logo img { height: 96px; }
  .hero { padding-top: 120px; }
  .n-links a { padding: 5px 8px; font-size: .76rem; }
  .lang-btn { padding: 4px 9px; font-size: .66rem; }
  .theme-btn { width: 32px; height: 32px; }
  .n-cta { padding: 7px 15px!important; font-size: .78rem!important; }
}

/* Tablet / portrait Surface ≤1024px → hamburguesa */
@media(max-width:1024px){
  nav { height: 90px; padding: 0 1rem; background: var(--s0)!important; ... }
  .n-links { display: none; }
  .n-logo img { height: 72px; }
  .ham-btn { display: flex; }
  .hero { padding-top: 90px; }
}

/* Móvil ≤768px → hamburguesa */
@media(max-width:768px){
  nav { height: 110px; }
  .n-logo img { height: 90px; }
  /* ham-btn ya visible desde ≤1024px */
}
```

**Tamaños en vigor:**
| Formato | Ancho viewport | Nav | Logo | Links |
|---|---|---|---|---|
| PC grande | >1450px | 180px | 160px | Todos visibles, tamaño normal |
| Surface / laptop | 1025–1450px | 120px | 96px | Todos visibles, fuente ligeramente menor |
| Tablet / portrait | ≤1024px | 90px | 72px | Hamburguesa (drawer lateral) |
| Móvil | ≤768px | 110px | 90px | Hamburguesa (drawer lateral) |

**Notas técnicas:**
- Surface Pro 7 a 200% DPI: CSS viewport = 1368×912px. En landscape (1368px) aplica el rango 1025–1450px → todos los links visibles. En portrait (912px) aplica ≤1024px → hamburguesa.
- Surface Pro 7 a 150% DPI: CSS viewport = 1824px. Aplica el rango >1450px → desktop completo. Con logo a 160px y nav 180px todo cabe en 1824px.
- El umbral de hamburguesa se subió de 768px a 1024px en este commit.

---

## Imágenes de referencia guardadas en el proyecto
- `WhatsApp Image 2026-05-31 at 18.46.04.jpeg` — Surface original, logo deformado
- `WhatsApp Image 2026-05-31 at 19.20.23.jpeg` — Surface tras fix logo, franja blanca visible
- `2026-05-31 19_44_40-...png` — DevTools Surface Pro 7, franja blanca en simulación
- `2026-05-31 19_44_40-...2.png` — DevTools tras ampliar breakpoint, "peor"
