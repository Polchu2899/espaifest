# EspaiFest — Contexto de sesión (actualizado 2026-05-19)

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

### Fix 3 — Franja blanca del header (PENDIENTE / SIN RESOLVER)
**El problema**: el nav tiene `height: 180px` en desktop. En el Surface Pro 7 (resolución CSS ~1368×912px en landscape), esta altura es desproporcionada para la pantalla pequeña de 13".

**Lo que se ha intentado:**
1. Añadir breakpoint `@media(max-width:1199px) and (min-width:769px)` con nav 100px/logo 80px → Surface a 1368px no entraba en el breakpoint (1368 > 1199)
2. Ampliar breakpoint a `max-width:1450px` → la franja mejoró pero el usuario dijo "peor" (posiblemente el nav de 90px quedaba demasiado compacto o afectaba a otros formatos)
3. Subir a 120px/96px con breakpoint 1450px → sigue viéndose mal según el usuario

**Estado actual en el código** (commit `0e5861e`):
```css
@media(max-width:1450px) and (min-width:769px){
  nav{height:120px;}
  .n-logo img{height:96px;}
  .hero{padding-top:120px;}
  body.banner-on .hero{padding-top:calc(120px + var(--banner-h,44px));}
}
```

**Tamaños en vigor:**
| Formato | Nav | Logo |
|---|---|---|
| PC grande (>1450px) | 180px | 160px |
| Surface/laptop (769-1450px) | 120px | 96px |
| Móvil (≤768px) | 110px | 90px |

### Posibles causas que no se han descartado
1. **El Surface puede tener Windows DPI scaling al 150%** → viewport CSS = 1824px → el breakpoint 1450px NO aplica y sigue con 180px. Solucionar: subir el breakpoint a `max-width:1900px`.
2. **Caché del navegador** en el Surface → la clienta necesita hacer `Ctrl+Shift+R` (recarga sin caché) o borrar caché del navegador.
3. **El hero padding-top** al reducir el nav podría generar un salto visual en la transición si el banner está activo.

### Próximos pasos sugeridos para mañana
1. Pedir a Laura que haga `Ctrl+Shift+R` en el Surface y compruebe si cambia algo.
2. Preguntarle qué % de escala tiene el Surface en Configuración → Pantalla → Escala. Si es 150%: subir el breakpoint de 1450px a **1900px** en `index.html` y `es/index.html`.
3. Si sigue sin funcionar: eliminar el breakpoint y usar `clamp()` en el nav:
   ```css
   nav { height: clamp(110px, 9vw, 180px); }
   .n-logo img { height: clamp(88px, 8vw, 160px); }
   .hero { padding-top: clamp(110px, 9vw, 180px); }
   ```
   Esto reduce el nav de forma fluida según el ancho del viewport sin depender de breakpoints.

---

## Imágenes de referencia guardadas en el proyecto
- `WhatsApp Image 2026-05-31 at 18.46.04.jpeg` — Surface original, logo deformado
- `WhatsApp Image 2026-05-31 at 19.20.23.jpeg` — Surface tras fix logo, franja blanca visible
- `2026-05-31 19_44_40-...png` — DevTools Surface Pro 7, franja blanca en simulación
- `2026-05-31 19_44_40-...2.png` — DevTools tras ampliar breakpoint, "peor"
