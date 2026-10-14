# Discipular

Registro al 13 de octubre de 2026. Estos cambios están en el código y en la página.

## Qué muestra el módulo

Discipular abre el equipo de 12. Cada líder tiene las casillas 1 a 12, con la escalera rápida y el botón **Cambiar** en cada fila.

Listados generales salió de esta pantalla. La red completa se consulta en Escalera del éxito.

Cambiar sustituye a la persona de la casilla. La persona que sale queda sin líder. La ficha no se elimina. El reemplazo es una sola operación y hace falta el permiso de editar.

El grupo de backup no está armado. Falta el número que definirá la iglesia.

## Pantalla

La pantalla ocupa todo el ancho disponible. Los pasos son tres: elegir red, ver los líderes de 12 de esa red y abrir el equipo de un líder.

Cada nivel tiene un solo enlace **Volver**, que lleva al nivel anterior. Se retiró la ruta de migas de pan.

Un equipo muestra siempre sus 12 casillas como tarjetas grandes en columnas. La tarjeta ocupada trae el nombre, el rol, su equipo, la escalera rápida y los botones **Ver equipo** y **Cambiar**. La casilla libre trae el botón **Asignar persona**.

Asignar y cambiar se hacen dentro de la misma tarjeta, sin ventana flotante: el botón abre un buscador (nombre, cédula o teléfono), se elige a la persona con **Elegir** y se confirma. Una casilla ocupada también tiene **Dejar la casilla libre**. Si la persona elegida tenía otro líder, la lista lo avisa. La persona que sale de una casilla queda sin líder.

La única ventana flotante que queda es la de configurar los líderes principales, que aparece solo cuando una red no tiene pastor.

Del código de la pantalla salieron los bloques antiguos que ya no se mostraban: el encabezado con filtros, las pestañas de listados y las tablas de la red completa.

## Quién ve qué

Pastor, Ganar y el rol administrativo Células recorren las redes.

El líder de 12 y el líder de 144 entran solo a sus 12. No ven el equipo del pastor ni el de su líder principal, y no pueden cambiar casillas de otro líder. Volver y la ruta de otra cobertura los devuelven a su equipo.

El líder de célula no tiene equipo de 12 en Discipular. Su gente está en Células. El rol no tiene ver, crear, editar ni eliminar en ministerios, así que el módulo no aparece en el menú. El rol administrativo Células no se tocó.

## Tope de 12

Bajo un pastor van líderes de 12. Bajo un líder de 12 van líderes de 144. Bajo un líder de 144 van los líderes de célula, y esos líderes de célula cuentan para las 12 casillas.

Si la ficha asigna un líder de célula a un líder de 144 y hay casilla libre, ocupa la primera libre. Si las 12 ya están ocupadas, la ficha no deja agregar otro.

Quien ya estaba bajo ese líder se puede seguir editando. Si había más de 12 líderes de célula sin número, las casillas libres se llenan por nombre y apellido. Quien no alcance casilla sigue bajo ese líder y entra cuando se libere una.

## Menú

La versión del menú lateral quedó en 15 para que la sesión vuelva a leer los permisos y oculte Discipular a quien ya no puede verlo.
