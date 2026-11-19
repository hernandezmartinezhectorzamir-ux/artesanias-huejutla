/**
 * Tema Artesanías Huejutla: navegación móvil, submenús accesibles,
 * copiar enlaces/cupones, botón "volver arriba" y animaciones suaves.
 */
(function () {
	'use strict';

	var cfg = window.ahTema || {};

	/* ---------- Menú móvil ---------- */
	function iniciarMenu() {
		var boton = document.querySelector('.ah-boton-menu');
		var nav = document.getElementById('ah-navegacion');
		var velo = document.querySelector('.ah-velo');
		if (!boton || !nav) { return; }

		function alternar(abrir) {
			nav.classList.toggle('abierta', abrir);
			boton.setAttribute('aria-expanded', abrir ? 'true' : 'false');
			if (velo) { velo.hidden = !abrir; }
			document.body.classList.toggle('ah-sin-scroll', abrir);
			if (abrir) {
				var primero = nav.querySelector('a, button');
				if (primero) { primero.focus(); }
			}
		}
		boton.addEventListener('click', function () { alternar(!nav.classList.contains('abierta')); });
		if (velo) { velo.addEventListener('click', function () { alternar(false); }); }
		var cerrar = nav.querySelector('.ah-navegacion__cerrar');
		if (cerrar) { cerrar.addEventListener('click', function () { alternar(false); boton.focus(); }); }
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && nav.classList.contains('abierta')) { alternar(false); boton.focus(); }
		});

		// Botón para desplegar cada submenú (teclado y pantallas táctiles).
		nav.querySelectorAll('.menu-item-has-children, .page_item_has_children').forEach(function (item, i) {
			var sub = item.querySelector('.sub-menu, .children');
			if (!sub) { return; }
			sub.id = sub.id || 'ah-submenu-' + i;
			var toggle = document.createElement('button');
			toggle.type = 'button';
			toggle.className = 'ah-submenu-toggle';
			toggle.setAttribute('aria-expanded', 'false');
			toggle.setAttribute('aria-controls', sub.id);
			toggle.setAttribute('aria-label', cfg.abrirSub || 'Mostrar submenú');
			toggle.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
			item.querySelector('a').after(toggle);
			toggle.addEventListener('click', function (e) {
				e.stopPropagation();
				var abierto = item.classList.toggle('abierto');
				toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
			});
		});
		document.addEventListener('click', function (e) {
			nav.querySelectorAll('.abierto').forEach(function (item) {
				if (!item.contains(e.target)) {
					item.classList.remove('abierto');
					var t = item.querySelector('.ah-submenu-toggle');
					if (t) { t.setAttribute('aria-expanded', 'false'); }
				}
			});
		});
	}

	/* ---------- Copiar enlace o cupón ---------- */
	function copiar(texto) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(texto);
		}
		return new Promise(function (resolver) {
			var area = document.createElement('textarea');
			area.value = texto;
			area.style.position = 'fixed';
			area.style.opacity = '0';
			document.body.appendChild(area);
			area.select();
			try { document.execCommand('copy'); } catch (e) { /* sin soporte */ }
			area.remove();
			resolver();
		});
	}

	function aviso(mensaje) {
		var zona = document.getElementById('ah-aviso-tema');
		if (!zona) {
			zona = document.createElement('div');
			zona.id = 'ah-aviso-tema';
			zona.className = 'ah-toast';
			zona.setAttribute('role', 'status');
			zona.setAttribute('aria-live', 'polite');
			document.body.appendChild(zona);
		}
		zona.textContent = mensaje;
		zona.classList.add('visible');
		clearTimeout(aviso.t);
		aviso.t = setTimeout(function () { zona.classList.remove('visible'); }, 2600);
	}

	document.addEventListener('click', function (e) {
		var boton = e.target.closest('[data-ah-copiar]');
		if (!boton) { return; }
		var valor = boton.getAttribute('data-ah-copiar');
		copiar(valor).then(function () {
			aviso((cfg.copiado || 'Copiado') + ': ' + valor);
		});
	});

	/* ---------- Volver arriba y encabezado compacto ---------- */
	function iniciarScroll() {
		var arriba = document.querySelector('.ah-arriba');
		var encabezado = document.getElementById('encabezado');
		function alDesplazar() {
			var y = window.scrollY;
			if (arriba) { arriba.classList.toggle('visible', y > 700); }
			if (encabezado) { encabezado.classList.toggle('compacto', y > 30); }
		}
		window.addEventListener('scroll', alDesplazar, { passive: true });
		alDesplazar();
		if (arriba) {
			arriba.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
		}
	}

	/* ---------- Animación al aparecer (solo elementos fuera de pantalla) ---------- */
	function iniciarRevelado() {
		if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
		var observador = new IntersectionObserver(function (entradas) {
			entradas.forEach(function (entrada) {
				if (entrada.isIntersecting) {
					entrada.target.classList.remove('ah-oculto');
					observador.unobserve(entrada.target);
				}
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
		document.querySelectorAll('.ah-revelar').forEach(function (el) {
			if (el.getBoundingClientRect().top > window.innerHeight) {
				el.classList.add('ah-oculto');
				observador.observe(el);
			}
		});
	}

	/* ---------- Filtros plegados en móvil ---------- */
	function iniciarFiltros() {
		var panel = document.querySelector('.ah-filtros__panel');
		if (panel && window.matchMedia('(max-width: 1024px)').matches) {
			panel.removeAttribute('open');
		}
	}

	/* ---------- Desplegables del encabezado (notificaciones y perfil) ---------- */
	function iniciarDesplegables() {
		var todos = document.querySelectorAll(".ah-desplegable");
		document.addEventListener("click", function (e) {
			todos.forEach(function (d) { if (d.open && !d.contains(e.target)) { d.open = false; } });
		});
		document.addEventListener("keydown", function (e) {
			if (e.key === "Escape") { todos.forEach(function (d) { if (d.open) { d.open = false; d.querySelector("summary").focus(); } }); }
		});
		todos.forEach(function (d) {
			d.addEventListener("toggle", function () { if (d.open) { todos.forEach(function (o) { if (o !== d) { o.open = false; } }); } });
		});
	}

	/* Acceso y registro: pestañas accesibles, copiar datos y cuenta de prueba. Sin JS, ambos formularios quedan visibles. */
	function iniciarAcceso() {
		var caja = document.querySelector("[data-ah-acceso]");
		if (!caja) { return; }
		var pestanas = Array.prototype.slice.call(caja.querySelectorAll("[data-ah-tab]"));
		var paneles = Array.prototype.slice.call(caja.querySelectorAll("[data-ah-panel]"));

		function mostrar(nombre, enfocar) {
			if (!pestanas.length) { return; }
			caja.setAttribute("data-activa", nombre);
			pestanas.forEach(function (p) {
				var activa = p.getAttribute("data-ah-tab") === nombre;
				p.setAttribute("aria-selected", activa ? "true" : "false");
				p.tabIndex = activa ? 0 : -1;
				if (activa && enfocar) { p.focus(); }
			});
			paneles.forEach(function (panel) { panel.hidden = panel.getAttribute("data-ah-panel") !== nombre; });
		}

		pestanas.forEach(function (p, i) {
			p.addEventListener("click", function () { mostrar(p.getAttribute("data-ah-tab"), false); });
			p.addEventListener("keydown", function (e) {
				if (e.key === "ArrowRight" || e.key === "ArrowLeft") {
					e.preventDefault();
					var siguiente = pestanas[(i + (e.key === "ArrowRight" ? 1 : pestanas.length - 1)) % pestanas.length];
					mostrar(siguiente.getAttribute("data-ah-tab"), true);
				}
			});
		});
		caja.querySelectorAll("[data-ah-ir]").forEach(function (b) {
			b.addEventListener("click", function () {
				var destino = b.getAttribute("data-ah-ir");
				mostrar(destino, false);
				var campo = caja.querySelector(destino === "registro" ? "#reg_email" : "#username");
				if (campo) { campo.focus(); }
			});
		});

		var inicial = location.hash === "#crear-cuenta" ? "registro" : (caja.getAttribute("data-inicial") || "acceder");
		mostrar(inicial, false);
		window.addEventListener("hashchange", function () { if (location.hash === "#crear-cuenta") { mostrar("registro", false); } });

		var aviso = caja.querySelector("[data-ah-demo-aviso]");
		function avisar(texto) { if (aviso) { aviso.textContent = texto; } }

		caja.querySelectorAll("[data-ah-copiar]").forEach(function (b) {
			b.addEventListener("click", function () {
				var valor = b.getAttribute("data-ah-copiar");
				var listo = function () {
					b.classList.add("is-copiado");
					avisar("Copiado al portapapeles.");
					setTimeout(function () { b.classList.remove("is-copiado"); }, 1600);
				};
				if (navigator.clipboard && window.isSecureContext) {
					navigator.clipboard.writeText(valor).then(listo, function () { avisar("No se pudo copiar; selecciona el texto manualmente."); });
				} else {
					var temporal = document.createElement("textarea");
					temporal.value = valor;
					temporal.setAttribute("readonly", "");
					temporal.style.position = "absolute";
					temporal.style.left = "-9999px";
					document.body.appendChild(temporal);
					temporal.select();
					try { document.execCommand("copy"); listo(); } catch (err) { avisar("No se pudo copiar; selecciona el texto manualmente."); }
					document.body.removeChild(temporal);
				}
			});
		});

		var usar = caja.querySelector("[data-ah-demo-usar]");
		if (usar) {
			usar.addEventListener("click", function () {
				var correo = caja.querySelector("[data-ah-demo-correo]");
				var clave = caja.querySelector("[data-ah-demo-clave]");
				var usuario = caja.querySelector("#username");
				var contrasena = caja.querySelector("#password");
				var formulario = caja.querySelector("form.woocommerce-form-login");
				var enviar = formulario && formulario.querySelector("button[name='login']");
				if (!correo || !clave || !usuario || !contrasena || !formulario) { return; }
				mostrar("acceder", false);
				usuario.value = correo.textContent.trim();
				contrasena.value = clave.textContent.trim();
				usar.disabled = true;
				avisar("Entrando con la cuenta de prueba…");
				// requestSubmit incluye el botón "login", que WooCommerce necesita para procesar el acceso.
				if (formulario.requestSubmit && enviar) { formulario.requestSubmit(enviar); } else if (enviar) { enviar.click(); }
			});
		}
	}

	/* Proceso artesanal en video: capítulos, barra de avance y paso al siguiente al terminar.
	   Nunca reproduce solo: empieza cuando la persona pulsa play o un capítulo. */
	function iniciarProceso() {
		var seccion = document.querySelector("[data-ah-proceso]");
		if (!seccion) { return; }
		var video = seccion.querySelector("[data-ah-proceso-video]");
		var capitulos = Array.prototype.slice.call(seccion.querySelectorAll("[data-ah-capitulo]"));
		var rotuloN = seccion.querySelector("[data-ah-proceso-n]");
		var rotuloT = seccion.querySelector("[data-ah-proceso-titulo]");
		if (!video || !capitulos.length) { return; }
		var actual = 0;

		function marcar(i) {
			capitulos.forEach(function (c, j) {
				c.classList.toggle("is-activo", j === i);
				if (j === i) { c.setAttribute("aria-current", "true"); } else { c.removeAttribute("aria-current"); }
				var barra = c.querySelector(".ah-proceso__barra span");
				if (barra) { barra.style.width = j < i ? "100%" : "0"; }
			});
			if (rotuloN) { rotuloN.textContent = (i + 1) + " / " + capitulos.length; }
			if (rotuloT) { rotuloT.textContent = capitulos[i].getAttribute("data-titulo"); }
		}

		function cargar(i, reproducir) {
			actual = i;
			var c = capitulos[i];
			video.setAttribute("poster", c.getAttribute("data-poster") || "");
			video.setAttribute("aria-label", c.getAttribute("data-titulo") || "");
			video.src = c.getAttribute("data-src");
			marcar(i);
			if (reproducir) {
				var promesa = video.play();
				if (promesa && promesa.catch) { promesa.catch(function () {}); }
			}
		}

		capitulos.forEach(function (c, i) {
			c.addEventListener("click", function (e) {
				e.preventDefault();
				cargar(i, true);
				if (window.matchMedia("(max-width: 1024px)").matches) { video.scrollIntoView({ block: "center", behavior: "smooth" }); }
			});
		});

		video.addEventListener("timeupdate", function () {
			if (!video.duration) { return; }
			var barra = capitulos[actual].querySelector(".ah-proceso__barra span");
			if (barra) { barra.style.width = Math.min(100, (video.currentTime / video.duration) * 100) + "%"; }
		});
		video.addEventListener("ended", function () {
			if (actual < capitulos.length - 1) { cargar(actual + 1, true); }
		});
	}

	document.addEventListener("DOMContentLoaded", function () {
		iniciarDesplegables();
		iniciarMenu();
		iniciarScroll();
		iniciarRevelado();
		iniciarFiltros();
		iniciarAcceso();
		iniciarProceso();
	});
})();
