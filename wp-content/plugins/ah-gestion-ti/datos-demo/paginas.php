<?php
/**
 * Páginas institucionales y artículos del blog (lo usa `wp ah sembrar`).
 *
 * Marcadores que se reemplazan por URLs reales al cargar el contenido:
 *   {{producto:slug}}  {{categoria:slug}}  {{pagina:slug}}  {{tienda}}
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

return array(

	'paginas' => array(
		/* slug => [título, contenido, plantilla, meta title SEO, meta description SEO, palabra clave] */
		'nosotros'                  => array(
			'Nosotros',
			'<p>Artesanías Huejutla nació para acercar el trabajo de las familias artesanas de Huejutla de Reyes y sus comunidades a todo México, con precios justos y contando la historia que hay detrás de cada pieza.</p>
<h2>Nuestra historia</h2>
<p>Durante generaciones, el tianguis de Huejutla ha sido el punto de encuentro donde las familias de la región llevan sus canastas, petates, tortilleros y bordados. Muchas de esas piezas, llenas de técnica y significado, se vendían a precios que no reflejaban el tiempo invertido. Nuestro propósito es cambiar eso con una tienda en línea que reconoce el oficio y garantiza un pago digno.</p>
<h2>Tradiciones de la Huasteca Hidalguense</h2>
<p>Huejutla es el corazón de la Huasteca Hidalguense, una región de clima cálido y raíces nahuas. El son huasteco, el Xantolo y la vida comunitaria están presentes en los colores y motivos de nuestras artesanías. Conoce más en <a href="{{pagina:historia-de-los-artesanos}}">la historia de los artesanos</a>.</p>
<h2>Cómo se elaboran nuestros productos</h2>
<ul>
<li><strong>Recolección:</strong> la palma se corta en temporada para que la planta vuelva a crecer.</li>
<li><strong>Secado y teñido:</strong> se seca al sol y, en algunas piezas, se tiñe para lograr colores vivos.</li>
<li><strong>Tejido o bordado:</strong> cada fibra se entrelaza a mano; un rebozo bordado puede llevar semanas.</li>
<li><strong>Revisión y empaque:</strong> revisamos cada pieza y la enviamos protegida.</li>
</ul>
<h2>Materiales naturales</h2>
<p>Trabajamos con palma, manta e hilos de algodón y semillas de la región: materiales duraderos, renovables y con menor impacto ambiental que los productos industriales. Explora la <a href="{{categoria:cesteria-de-palma}}">cestería de palma</a> y los <a href="{{categoria:textiles-bordados}}">textiles bordados</a>.</p>
<h2>Conservar las tradiciones mexicanas</h2>
<p>Cuando una técnica deja de practicarse se pierde una parte de nuestra historia. Por eso documentamos los procesos, reconocemos a cada familia artesana y promovemos que las nuevas generaciones encuentren en la artesanía un oficio digno.</p>',
			'',
			'Nosotros | Artesanías Huejutla, tradición de la Huasteca',
			'Conoce la historia de Artesanías Huejutla, las tradiciones de la Huasteca Hidalguense y los materiales naturales de nuestras artesanías hechas a mano.',
			'artesanías de la Huasteca',
		),
		'historia-de-los-artesanos' => array(
			'Historia de los artesanos',
			'<p>Detrás de cada canasta, rebozo o mantel hay manos que aprendieron el oficio mirando a sus madres y abuelas. En las comunidades de Huejutla el tejido de palma y el bordado forman parte de la vida diaria y se transmiten de generación en generación.</p>
<h2>Un saber que se hereda</h2>
<p>Las niñas y los niños aprenden primero a preparar la palma: limpiarla, abrirla en tiras y dejarla secar. Después vienen los primeros tejidos sencillos, como petates pequeños, hasta dominar piezas complejas como canastas de tejido cerrado o tortilleros con tapa.</p>
<h2>El bordado: puntada a puntada</h2>
<p>El bordado sobre manta de algodón requiere paciencia y precisión. Las flores, aves y grecas que aparecen en manteles y blusas guardan símbolos de la naturaleza y de la vida comunitaria de la Huasteca.</p>
<h2>Comercio justo</h2>
<p>Trabajamos directamente con las familias artesanas: acordamos un pago justo por cada pieza, reconocemos su comunidad de origen y apoyamos la compra de materiales de calidad. Cada compra en la <a href="{{tienda}}">tienda</a> sostiene la economía de quienes viven de su oficio.</p>
<h2>¿Eres artesana o artesano?</h2>
<p>Si deseas ofrecer tus piezas en Artesanías Huejutla, escríbenos desde la página de <a href="{{pagina:contacto}}">contacto</a> o solicita una videollamada.</p>',
			'',
			'Historia de los artesanos de Huejutla | Artesanías Huejutla',
			'Conoce a los artesanos de Huejutla: el tejido de palma y el bordado que se heredan de generación en generación en la Huasteca Hidalguense.',
			'artesanos de Huejutla',
		),
		'contacto'                  => array(
			'Contacto',
			'<p>¿Tienes dudas sobre una pieza, un pedido o compras al mayoreo? Escríbenos y te responderemos con un número de seguimiento.</p>
[ah_formulario_contacto]',
			'plantillas/pagina-contacto.php',
			'Contacto | Artesanías Huejutla, Huejutla de Reyes, Hidalgo',
			'Contáctanos para dudas sobre artesanías, pedidos o mayoreo. Atención desde Huejutla de Reyes, Hidalgo, por formulario, WhatsApp o videollamada.',
			'contacto artesanías Huejutla',
		),
		'preguntas-frecuentes'      => array(
			'Preguntas frecuentes',
			'<p>Resolvemos las dudas más comunes sobre nuestras artesanías, envíos y pagos. Si no encuentras tu respuesta, abre un ticket en la <a href="{{pagina:mesa-de-ayuda}}">mesa de ayuda</a>.</p>
[ah_faq]
¿Las artesanías son realmente hechas a mano? || Sí. Todas las piezas son elaboradas a mano por artesanos de Huejutla y sus comunidades; por eso cada una puede tener pequeñas variaciones que la hacen única.
¿Hacen envíos a todo México? || Sí. El envío estándar tarda de 3 a 7 días hábiles y es gratis en compras desde $299 MXN. También hay envío express y la opción de recoger en el taller en Huejutla.
¿Qué formas de pago aceptan? || Por ahora transferencia bancaria y pago contra entrega. Las pasarelas de pago con tarjeta se habilitarán al concluir su configuración segura.
¿Puedo cambiar o devolver una pieza? || Sí, dentro de los 15 días naturales posteriores a la entrega si la pieza llega dañada o no corresponde a tu pedido. Consulta la política de envíos y devoluciones.
¿Cómo doy seguimiento a mi pedido? || Desde Mi cuenta → Pedidos. Si tienes un problema, crea un ticket en la mesa de ayuda y recibirás un número de seguimiento.
¿Venden al mayoreo o hacen pedidos personalizados? || Sí. Escríbenos desde Contacto indicando cantidades y fechas.
¿Cómo cuido mis piezas de palma? || Mantenlas en un lugar seco y ventilado, límpialas con un paño seco y evita mojarlas. Si se humedecen, déjalas secar a la sombra.
[/ah_faq]',
			'',
			'Preguntas frecuentes | Artesanías Huejutla',
			'Respuestas sobre artesanías hechas a mano, envíos a todo México, formas de pago, cambios y devoluciones en Artesanías Huejutla.',
			'preguntas frecuentes artesanías',
		),
		'mesa-de-ayuda'             => array(
			'Mesa de ayuda',
			'<p>Reporta un problema con tu compra, pago, pedido o acceso, o solicita información. Cada solicitud recibe un número de seguimiento y se atiende según su prioridad.</p>
[ah_mesa_ayuda]',
			'',
			'Mesa de ayuda y soporte | Artesanías Huejutla',
			'Crea un ticket de soporte y consulta su avance con tu número de seguimiento. Atención a problemas de compras, pagos, pedidos, acceso y solicitudes de información.',
			'soporte artesanías Huejutla',
		),
		'favoritos'                 => array(
			'Mis favoritos',
			'[ah_favoritos]',
			'',
			'Mis favoritos | Artesanías Huejutla',
			'Tus artesanías favoritas guardadas.',
			'',
		),
		'rastrear-pedido'           => array(
			'Rastrear pedido',
			'<p>Consulta en qué etapa va tu compra: confirmada, en preparación, en camino o entregada. Escribe tu número de compra y el correo con el que compraste.</p>
[ah_rastreo_pedido]',
			'',
			'Rastrear pedido | Artesanías Huejutla',
			'Consulta el estado de tu compra de artesanías: en preparación, en camino o entregada, con tu número de pedido y correo.',
			'',
		),
		'envios-y-devoluciones'     => array(
			'Políticas de envío y devoluciones',
			'<h2>Envíos</h2>
<ul>
<li><strong>Cobertura:</strong> enviamos a toda la República Mexicana.</li>
<li><strong>Envío estándar:</strong> 3 a 7 días hábiles. Gratis en compras desde $299 MXN.</li>
<li><strong>Envío express:</strong> 1 a 3 días hábiles.</li>
<li><strong>Recoger en taller:</strong> sin costo en Huejutla de Reyes, Hidalgo.</li>
<li>Los tiempos corren a partir de la confirmación del pago.</li>
<li>Cada pieza se empaca con materiales protectores; las piezas frágiles se aseguran individualmente.</li>
</ul>
<h2>Cambios y devoluciones</h2>
<ul>
<li>Tienes <strong>15 días naturales</strong> a partir de la entrega para solicitar un cambio o devolución.</li>
<li>Aplica cuando la pieza llega dañada, incompleta o no corresponde a tu pedido.</li>
<li>Abre un ticket en la <a href="{{pagina:mesa-de-ayuda}}">mesa de ayuda</a> en la categoría "Problemas con pedidos", con tu número de pedido y fotografías.</li>
<li>Una vez revisado el caso, repondremos la pieza o reembolsaremos el importe por el mismo medio de pago.</li>
<li>Las variaciones naturales de color, forma o medida propias del trabajo hecho a mano no se consideran defectos.</li>
</ul>',
			'',
			'Envíos y devoluciones | Artesanías Huejutla',
			'Conoce costos y tiempos de envío a todo México, envío gratis desde $299 y cómo solicitar cambios o devoluciones de tus artesanías.',
			'envíos artesanías México',
		),
		'terminos-y-condiciones'    => array(
			'Términos y condiciones',
			'<p><em>Documento base con fines académicos. Antes de operar comercialmente debe ser revisado por un asesor legal.</em></p>
<h2>1. Aceptación</h2>
<p>Al usar este sitio y realizar compras aceptas estos términos. Si no estás de acuerdo, te pedimos no utilizar el sitio.</p>
<h2>2. Productos</h2>
<p>Las artesanías son elaboradas a mano; las fotografías son ilustrativas y cada pieza puede presentar variaciones de color, forma y medida. Las existencias se actualizan en tiempo real y están sujetas a disponibilidad.</p>
<h2>3. Precios y pagos</h2>
<p>Los precios se expresan en pesos mexicanos (MXN). El pedido se procesa una vez confirmado el pago por los medios habilitados. Este sitio no almacena datos de tarjetas bancarias.</p>
<h2>4. Envíos, cambios y devoluciones</h2>
<p>Se rigen por la <a href="{{pagina:envios-y-devoluciones}}">política de envíos y devoluciones</a>.</p>
<h2>5. Cuenta de usuario</h2>
<p>Eres responsable de la confidencialidad de tu contraseña. Las contraseñas se almacenan cifradas por WordPress y nunca se envían por correo.</p>
<h2>6. Propiedad intelectual</h2>
<p>Los textos, diseños y fotografías del sitio pertenecen a sus titulares y no pueden reproducirse sin autorización.</p>
<h2>7. Atención a clientes</h2>
<p>Cualquier aclaración se atiende mediante la <a href="{{pagina:mesa-de-ayuda}}">mesa de ayuda</a>. Para controversias son aplicables la Ley Federal de Protección al Consumidor y las leyes de los Estados Unidos Mexicanos.</p>',
			'',
			'Términos y condiciones | Artesanías Huejutla',
			'Términos y condiciones de compra en Artesanías Huejutla: productos hechos a mano, precios en MXN, pagos, envíos y atención a clientes.',
			'',
		),
		'politica-de-privacidad'    => array(
			'Aviso de privacidad',
			'<p><em>Plantilla basada en la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP), con fines académicos. Debe completarse con los datos del responsable y revisarse por un asesor legal antes de operar comercialmente.</em></p>
<h2>Responsable</h2>
<p>Artesanías Huejutla, con domicilio en Huejutla de Reyes, Hidalgo, México, es responsable del tratamiento de tus datos personales.</p>
<h2>Datos que recabamos</h2>
<ul>
<li>Identificación y contacto: nombre, correo electrónico, teléfono.</li>
<li>Envío y facturación: domicilio de entrega.</li>
<li>Soporte: información que proporcionas en tickets y formularios de contacto.</li>
<li>Navegación: datos técnicos y de uso mediante cookies y Google Analytics 4, cuando se encuentre habilitado.</li>
</ul>
<p>No almacenamos datos de tarjetas bancarias; los pagos con tarjeta se procesan directamente por la pasarela de pago autorizada.</p>
<h2>Finalidades</h2>
<ul>
<li>Procesar y entregar pedidos, y atender aclaraciones (finalidades primarias).</li>
<li>Enviar novedades y promociones, solo con tu consentimiento (finalidad secundaria).</li>
<li>Mejorar el sitio con estadísticas agregadas.</li>
</ul>
<h2>Derechos ARCO</h2>
<p>Puedes ejercer tus derechos de Acceso, Rectificación, Cancelación y Oposición, o revocar tu consentimiento, creando un ticket en la <a href="{{pagina:mesa-de-ayuda}}">mesa de ayuda</a> con la categoría "Solicitudes de información". Responderemos en los plazos que marca la ley.</p>
<h2>Transferencias</h2>
<p>Solo compartimos los datos necesarios con empresas de paquetería y procesadores de pago para cumplir tu pedido.</p>
<h2>Cambios al aviso</h2>
<p>Cualquier modificación se publicará en esta página.</p>',
			'',
			'Aviso de privacidad | Artesanías Huejutla',
			'Aviso de privacidad de Artesanías Huejutla: datos que recabamos, finalidades, derechos ARCO y uso de cookies conforme a la LFPDPPP.',
			'',
		),
	),

	'blog'    => array(
		/* [título, slug, categoría, imagen, extracto, contenido, productos relacionados (slugs), meta title, meta description, palabra clave] */
		array(
			'Historia de las artesanías de Huejutla',
			'historia-de-las-artesanias-de-huejutla',
			'Cultura huasteca',
			'puesto-cesteria-palma-huejutla.jpg',
			'Un recorrido por el origen de las artesanías de Huejutla, Hidalgo: la palma, el bordado y el tianguis como punto de encuentro de la Huasteca.',
			'<p>Huejutla de Reyes es la principal ciudad de la Huasteca Hidalguense y, desde hace generaciones, un punto de encuentro para las comunidades de la región. Su tianguis reúne cada semana a familias que llegan con frutas, café y, sobre todo, con artesanías elaboradas en casa.</p>
<h2>La palma: el material de la vida diaria</h2>
<p>En la Huasteca la palma ha sido parte de la vida cotidiana: con ella se tejen petates para descansar, tortilleros para la cocina y canastas para cargar la cosecha. Estas piezas utilitarias se convirtieron con el tiempo en artesanías apreciadas por su resistencia y belleza, como la <a href="{{producto:canasta-de-palma-tejida}}">canasta de palma tejida</a>.</p>
<h2>El bordado como identidad</h2>
<p>Junto con la cestería, el bordado sobre manta de algodón distingue a la región. Flores, aves y grecas decoran blusas, servilletas y manteles que se usan en fiestas y celebraciones como el Xantolo, la celebración huasteca de Día de Muertos.</p>
<h2>De generación en generación</h2>
<p>Las técnicas no se aprenden en escuelas: se transmiten en casa. Por eso cada familia tiene sus propios puntos, combinaciones de color y acabados, y cada pieza conserva la huella de quien la elaboró.</p>
<h2>Las artesanías hoy</h2>
<p>Hoy las artesanías de Huejutla llegan a todo México gracias a la venta en línea. Comprar directamente a las familias artesanas ayuda a que estas técnicas sigan vivas. Conoce nuestra <a href="{{categoria:cesteria-de-palma}}">cestería de palma</a> y los <a href="{{categoria:textiles-bordados}}">textiles bordados</a>.</p>',
			array( 'canasta-de-palma-tejida', 'tortillero-de-palma-tejida', 'mantel-bordado-a-mano' ),
			'Historia de las artesanías de Huejutla, Hidalgo',
			'Descubre el origen de las artesanías de Huejutla: la cestería de palma, el bordado huasteco y el tianguis como corazón de la Huasteca Hidalguense.',
			'artesanías Huejutla',
		),
		array(
			'El proceso de elaboración de las canastas de palma',
			'proceso-de-elaboracion-de-las-canastas-de-palma',
			'Guías',
			'canastas-palma-collares-semillas.jpg',
			'Así se elabora una canasta de palma tejida a mano: desde la recolección y el secado de la palma hasta el tejido y los acabados.',
			'<p>Una canasta de palma tejida puede parecer sencilla, pero detrás de ella hay días de trabajo y un conocimiento que se ha perfeccionado durante generaciones. Te contamos paso a paso cómo se elabora.</p>
<h2>1. Recolección de la palma</h2>
<p>La palma se corta en la temporada adecuada y solo se toman las hojas maduras, para que la planta siga creciendo. Este manejo cuidadoso hace de la palma un material renovable.</p>
<h2>2. Secado al sol</h2>
<p>Las hojas se extienden al sol durante varios días hasta que adquieren su característico color claro. Un buen secado evita que la pieza se deforme o se llene de hongos.</p>
<h2>3. Preparación y teñido</h2>
<p>La palma se abre en tiras del ancho necesario. Para las piezas de color, como la <a href="{{producto:cesta-de-palma-con-tapa}}">cesta de palma con tapa</a>, algunas tiras se tiñen antes de tejer.</p>
<h2>4. Tejido</h2>
<p>El tejido comienza por la base y sube en espiral o en trama cerrada. La tensión de cada tira define la firmeza de la canasta. Una pieza mediana puede requerir entre uno y dos días de trabajo.</p>
<h2>5. Asas y acabados</h2>
<p>Las asas se refuerzan con palma trenzada y los bordes se rematan para que no se abran con el uso. Finalmente se revisa que no queden puntas sueltas.</p>
<h2>¿Cómo cuidar tu canasta?</h2>
<ul>
<li>Guárdala en un lugar seco y ventilado.</li>
<li>Límpiala con un paño seco o una brocha suave.</li>
<li>Si se moja, déjala secar a la sombra.</li>
</ul>
<p>Encuentra canastas y tortilleros en la categoría de <a href="{{categoria:canastas-artesanales}}">canastas artesanales</a>.</p>',
			array( 'canasta-de-palma-tejida', 'cesta-de-palma-con-tapa', 'tortillero-de-palma-tejida' ),
			'Cómo se elabora una canasta de palma tejida a mano',
			'Paso a paso: recolección, secado, teñido y tejido de una canasta de palma hecha a mano en Huejutla. Además, consejos para cuidarla.',
			'canasta de palma tejida',
		),
		array(
			'Tradiciones artesanales de la Huasteca Hidalguense',
			'tradiciones-artesanales-de-la-huasteca-hidalguense',
			'Cultura huasteca',
			'artesana-blusa-bordada-huasteca.jpg',
			'La música, las fiestas y la vida comunitaria de la Huasteca Hidalguense se reflejan en sus artesanías de palma y sus textiles bordados.',
			'<p>La Huasteca Hidalguense es una región de sierra verde, ríos y clima cálido donde conviven tradiciones nahuas y mestizas. Sus artesanías no pueden entenderse sin sus fiestas, su música y su vida en comunidad.</p>
<h2>El Xantolo</h2>
<p>El Xantolo es la celebración de Día de Muertos en la Huasteca. Los altares se adornan con arcos, flores de cempasúchil y canastas con ofrendas. Muchas de las piezas de palma que se usan en estas fechas se elaboran en las propias comunidades.</p>
<h2>El son huasteco</h2>
<p>Con violín, jarana y huapanguera, el son huasteco acompaña bodas, fiestas patronales y reuniones familiares. En estas celebraciones lucen blusas bordadas y sombreros de palma como el <a href="{{producto:sombrero-de-palma-huasteco}}">sombrero de palma huasteco</a>.</p>
<h2>Textiles que cuentan historias</h2>
<p>Los motivos bordados —flores, aves, estrellas y grecas— representan la naturaleza y la vida cotidiana. Una <a href="{{producto:blusa-bordada-huasteca}}">blusa bordada</a> o un <a href="{{producto:rebozo-tenango-bordado}}">rebozo bordado</a> son, en realidad, piezas de identidad.</p>
<h2>Trabajo en comunidad</h2>
<p>La elaboración de artesanías suele ser una actividad familiar: unos preparan la palma, otros tejen o bordan y otros venden en el tianguis. Este trabajo colectivo es parte esencial de la cultura de la región.</p>',
			array( 'rebozo-tenango-bordado', 'blusa-bordada-huasteca', 'sombrero-de-palma-huasteco' ),
			'Tradiciones artesanales de la Huasteca Hidalguense',
			'Xantolo, son huasteco y trabajo comunitario: conoce las tradiciones de la Huasteca Hidalguense que dan vida a sus artesanías hechas a mano.',
			'artesanías de la Huasteca',
		),
		array(
			'Importancia de apoyar a los artesanos locales',
			'importancia-de-apoyar-a-los-artesanos-locales',
			'Comercio justo',
			'artesana-tejiendo-en-telar.jpg',
			'Comprar artesanías directamente a sus creadores impulsa la economía local, conserva técnicas tradicionales y promueve un consumo responsable.',
			'<p>Cada vez que compras una artesanía hecha a mano estás haciendo mucho más que adquirir un objeto. Estas son algunas razones para apoyar a los artesanos locales.</p>
<h2>1. Ingreso justo para las familias</h2>
<p>Muchas familias de la Huasteca dependen de su oficio. Comprar sin intermediarios abusivos permite que reciban un pago acorde al tiempo y al talento que invierten en cada pieza.</p>
<h2>2. Conservación de técnicas tradicionales</h2>
<p>Si una técnica deja de ser rentable, deja de enseñarse. Al valorar la artesanía, motivamos a las nuevas generaciones a aprender el tejido de palma y el bordado.</p>
<h2>3. Consumo responsable</h2>
<p>Las artesanías se elaboran con materiales naturales y renovables, en pequeña escala y sin procesos industriales contaminantes. Además, son piezas duraderas.</p>
<h2>4. Piezas únicas con historia</h2>
<p>Ninguna pieza hecha a mano es idéntica a otra. Un regalo como la <a href="{{producto:caja-de-regalo-huasteca}}">caja de regalo huasteca</a> lleva consigo la historia de quienes la hicieron.</p>
<h2>¿Cómo puedes ayudar?</h2>
<ul>
<li>Compra directamente a las familias artesanas o a tiendas de comercio justo.</li>
<li>Comparte en redes sociales el trabajo de los artesanos.</li>
<li>Valora el precio: refleja horas de trabajo y conocimiento.</li>
</ul>
<p>Visita la <a href="{{tienda}}">tienda</a> y conoce la <a href="{{pagina:historia-de-los-artesanos}}">historia de nuestros artesanos</a>.</p>',
			array( 'caja-de-regalo-huasteca', 'bolsa-artesanal-de-palma', 'monedero-de-palma-bordado' ),
			'Por qué apoyar a los artesanos locales de México',
			'Comprar artesanías mexicanas hechas a mano impulsa la economía de las familias artesanas, conserva tradiciones y promueve el consumo responsable.',
			'artesanías mexicanas hechas a mano',
		),
		array(
			'Cómo identificar una artesanía hecha a mano',
			'como-identificar-una-artesania-hecha-a-mano',
			'Guías',
			'bordado-flores-hilo-algodon.jpg',
			'Aprende a distinguir una artesanía auténtica hecha a mano de una imitación industrial: materiales, acabados, variaciones y origen.',
			'<p>En mercados y tiendas en línea abundan productos que imitan las artesanías. Estas claves te ayudarán a reconocer una pieza auténtica hecha a mano.</p>
<h2>1. Pequeñas irregularidades</h2>
<p>En una pieza hecha a mano las puntadas o el tejido no son perfectamente idénticos. Esas variaciones no son defectos: son la huella del trabajo humano.</p>
<h2>2. Materiales naturales</h2>
<p>La palma natural tiene olor característico y variaciones de tono; el algodón se siente fresco al tacto. Desconfía de fibras plásticas brillantes o colores demasiado uniformes.</p>
<h2>3. El reverso del bordado</h2>
<p>En un bordado a mano el reverso muestra los hilos y nudos del trabajo. Los bordados industriales suelen tener un reverso con una malla o pegamento.</p>
<h2>4. Origen y artesano</h2>
<p>Una tienda de artesanías auténticas puede decirte dónde y cómo se elaboró la pieza. En cada producto de nuestra tienda indicamos materiales, lugar de elaboración y medidas.</p>
<h2>5. El precio</h2>
<p>Un <a href="{{producto:mantel-bordado-a-mano}}">mantel bordado a mano</a> puede llevar semanas de trabajo. Precios demasiado bajos suelen indicar producción industrial.</p>
<p>Descubre piezas auténticas en nuestra <a href="{{tienda}}">tienda de artesanías mexicanas hechas a mano</a>.</p>',
			array( 'mantel-bordado-a-mano', 'servilletas-bordadas-juego-de-4', 'rebozo-tenango-bordado' ),
			'Cómo identificar una artesanía hecha a mano auténtica',
			'Guía para reconocer artesanías mexicanas hechas a mano: irregularidades, materiales naturales, reverso del bordado, origen y precio justo.',
			'artesanía hecha a mano',
		),
	),
);
