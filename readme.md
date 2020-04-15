Este es un note-taking personal que está basado en otro que desarrolle hace bastante tiempo para agrupar guiones y notas de programación.

Es mi primer intento de hacer algo simple con Vue, así que cualquier sugerencia y/o corrección serán bien recibidas.

----------------------------- o -----------------------------

PDTE

- incluir edicion (solo localhost)

- incluir for de busqueda

- sistema de tagging

- posible mejora, ahora que abierto cerrado son clases hacerle bind al componenete

- cambiar codigo de la clase y hacer interface con abstrata para comunes
y clases concretas para cada tipo de modelo de pagina

+ incluir SyntaxHighlighter y markdown/Parsedown

+ cambio abierto/cerrado a clases css para que worwratp detalle nota se correcto (OLD1)

- modelo 3 su opciones no se despligan como menu de opciones en panel

----------------------------- o -----------------------------

- primera version ya funcional

----------------------------- o -----------------------------

- OLD1
version show/hide menu con
	document.getElementById('menu').style.width = '0';
	document.getElementById('panel').style.marginLeft = '0';

tenia el problema qie el word-wrap del detalle de nota no lo hacia bien

la "solucion" fue pasar esos estados a clases y hacer el witdh dl panel en funcion a ello
#panel.abierto {
	margin-left: 250;
	width: calc(100% - 280px);
}
#panel.cerrado {
	margin-left: 0;

----------------------------- o -----------------------------

tipo de pagina

- null 
modelpag = primero en arbol arriba que no sea nulo


- tipo 3
devuelve opciones menu y notas
cada nota vuelve a abrir nuevo menu con opciones

- tipo 2
1ra vez devuelve opciones y notas
despues solo notas

- tipo 1
1ra vez devuelve opciones y notas
devuelve notas y su detalle

- tipo 0
(no va bien era para privado, parecido o = a 1)
pdte profundizar

----------------------------- o -----------------------------
