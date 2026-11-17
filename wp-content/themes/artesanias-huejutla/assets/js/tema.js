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

	document.addEventListener("DOMContentLoaded", function () {
		iniciarDesplegables();
		iniciarMenu();
		iniciarScroll();
		iniciarRevelado();
		iniciarFiltros();
	});
})();
