# Dashboard — metas por ministerio y resumen de sobres

Actualizado: 13 oct 2026. Responde a las dos observaciones del cliente sobre el Dashboard.

## 1. Meta por ministerio según su tamaño

**Problema:** todos los ministerios tenían metas parecidas aunque unos tienen 17 células y otros una sola.

**Cómo funciona ahora:** la meta anual de cada ministerio se sigue escribiendo a mano en
*Discipular → Ministerios → Editar → Metas por semestre*. Ahí el sistema muestra una **meta anual sugerida**:

```
meta sugerida = meta total de la iglesia × (células activas del ministerio ÷ células activas de la iglesia)
```

- "Meta total de la iglesia" es la suma de las metas anuales que ya están guardadas en todos los ministerios.
- "Células activas" son las células con estado Activa; cada una cuenta para el ministerio de la célula o, si no lo tiene, el de su líder.
- El botón **Usar la sugerida** solo rellena el campo. La meta cambia cuando se pulsa **Guardar metas**.
- Si el ministerio no tiene células, o la iglesia aún no tiene metas, no se muestra sugerencia.

**Cómo llegar:** en la pantalla de *Discipular* hay un botón **Ministerios y metas** (arriba, junto al título) que abre la lista de ministerios.
En esa lista, cada fila tiene el botón verde **Configurar metas** (abre la pantalla del ministerio
directamente en "Metas por semestre"). La lista también muestra las columnas **Células** y **Meta anual** para comparar de un vistazo.
El botón solo aparece para quien tiene el permiso `ministerios:editar` (el administrador siempre lo tiene).

Con esto la meta total de la iglesia no cambia: solo se reparte de forma proporcional al tamaño de cada ministerio.
Las tarjetas "Metas por Ministerio" del Dashboard siguen leyendo la meta guardada, así que se ajustan cuando se guarda la nueva.

**Pendiente de la iglesia (lo escala el equipo con ellos):** el documento del cliente no define la fórmula, solo pide que la meta
sea proporcional al tamaño del ministerio. Falta confirmar: la meta total anual de la iglesia, si el reparto es solo por número de
células o también por asistencia / ganados del año anterior, y qué meta lleva un ministerio con 0 células.
Cuando lo definan se cambia la fórmula en `MinisterioController::calcularMetaAnualSugerida`.

Archivos: `app/Models/Celula.php` (`contarActivasPorMinisterio`), `app/Controllers/MinisterioController.php`,
`views/discipular/ministerios/formulario.php`.

## 2. Resumen de sobres / reportes de célula sin títulos

Ya quedó resuelto en el trabajo de Células (ver `docs/CELULAS.md`): la tabla del final del Dashboard muestra los títulos
(Asistentes, Nuevos, Antiguas, Frecuentes, Regulares, Sem. sin entregar sobre, Sem. sin reportar), los subtotales dicen
"N células" y la tabla total dice "Células sin entregar sobre" y "Células sin reportar".
