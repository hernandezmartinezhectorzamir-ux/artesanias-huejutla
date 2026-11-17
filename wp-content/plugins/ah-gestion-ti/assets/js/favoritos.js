/**
 * Favoritos de Artesanías Huejutla.
 * - Guarda los IDs en localStorage (visitantes).
 * - Si hay sesión, sincroniza con la cuenta mediante /wp-json/ah/v1/favoritos (nonce wp_rest).
 * - Actualiza corazones [data-ah-fav], contadores [data-ah-fav-contador]
 *   y dibuja la página [ah_favoritos] usando la Store API de WooCommerce.
 */
(function () {
	'use strict';

	var cfg = window.ahFavoritos || {};
	var CLAVE = 'ah_favoritos';
	var t = cfg.textos || {};

	function leer() {
		try {
			var ids = JSON.parse(localStorage.getItem(CLAVE) || '[]');
			return Array.isArray(ids) ? ids.map(Number).filter(Boolean) : [];
		} catch (e) {
			return [];
		}
	}

	function escribir(ids) {
		try { localStorage.setItem(CLAVE, JSON.stringify(ids)); } catch (e) { /* almacenamiento no disponible */ }
	}

	function peticion(metodo, datos) {
		return fetch(cfg.rest, {
			method: metodo,
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
			body: datos ? JSON.stringify(datos) : undefined
		}).then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); });
	}

	/** Combina favoritos locales con los de la cuenta al iniciar sesión. */
	function sincronizar() {
		if (!cfg.sesion) { return Promise.resolve(leer()); }
		return peticion('GET').then(function (servidor) {
			var unidos = Array.from(new Set(leer().concat(servidor.map(Number))));
			escribir(unidos);
			if (unidos.length !== servidor.length) { peticion('POST', { ids: unidos }).catch(function () {}); }
			return unidos;
		}).catch(function () { return leer(); });
	}

	function actualizarInterfaz() {
		var ids = leer();
		document.querySelectorAll('[data-ah-fav]').forEach(function (boton) {
			var activo = ids.indexOf(Number(boton.getAttribute('data-ah-fav'))) !== -1;
			boton.classList.toggle('activo', activo);
			boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
			var texto = boton.querySelector('[data-ah-fav-texto]');
			if (texto) { texto.textContent = activo ? t.enFavoritos : t.agregarFav; }
		});
		document.querySelectorAll('[data-ah-fav-contador]').forEach(function (el) {
			el.textContent = ids.length;
			el.hidden = ids.length === 0;
		});
	}

	function avisar(mensaje) {
		var zona = document.getElementById('ah-aviso-favoritos');
		if (!zona) {
			zona = document.createElement('div');
			zona.id = 'ah-aviso-favoritos';
			zona.className = 'ah-toast';
			zona.setAttribute('role', 'status');
			zona.setAttribute('aria-live', 'polite');
			document.body.appendChild(zona);
		}
		zona.innerHTML = '';
		var p = document.createElement('p');
		p.textContent = mensaje;
		zona.appendChild(p);
		if (cfg.pagina) {
			var a = document.createElement('a');
			a.href = cfg.pagina;
			a.textContent = '♥';
			a.setAttribute('aria-label', 'Ver favoritos');
			zona.appendChild(a);
		}
		zona.classList.add('visible');
		clearTimeout(avisar.temporizador);
		avisar.temporizador = setTimeout(function () { zona.classList.remove('visible'); }, 3000);
	}

	function alternar(id) {
		var ids = leer();
		var i = ids.indexOf(id);
		if (i === -1) { ids.push(id); } else { ids.splice(i, 1); }
		escribir(ids);
		actualizarInterfaz();
		avisar(i === -1 ? t.agregado : t.quitado);
		if (cfg.sesion) { peticion('POST', { ids: ids }).catch(function () {}); }
		dibujarPagina();
	}

	/** Página de favoritos con datos de la Store API. */
	function dibujarPagina() {
		var contenedor = document.getElementById('ah-favoritos-lista');
		if (!contenedor) { return; }
		var ids = leer();
		if (!ids.length) {
			contenedor.innerHTML = '';
			var vacio = document.createElement('div');
			vacio.className = 'ah-vacio';
			vacio.innerHTML = '<p></p><a class="ah-boton"></a>';
			vacio.querySelector('p').textContent = t.vacio;
			vacio.querySelector('a').textContent = t.explorar;
			vacio.querySelector('a').href = cfg.catalogo;
			contenedor.appendChild(vacio);
			return;
		}
		fetch(cfg.tienda + '?per_page=100&include=' + ids.join(','), { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (productos) {
				contenedor.innerHTML = '';
				var lista = document.createElement('ul');
				lista.className = 'ah-favoritos__lista';
				productos.forEach(function (p) {
					var li = document.createElement('li');
					li.className = 'ah-favoritos__item';
					var img = p.images && p.images[0] ? p.images[0] : null;
					li.innerHTML =
						'<a class="ah-favoritos__img" href=""><img alt="" loading="lazy"></a>' +
						'<div class="ah-favoritos__info"><h3><a href=""></a></h3><p class="ah-favoritos__precio"></p></div>' +
						'<div class="ah-favoritos__acciones"><a class="ah-boton ah-boton--secundario"></a>' +
						'<button type="button" class="ah-boton ah-boton--texto"></button></div>';
					li.querySelectorAll('a[href=""]').forEach(function (a) { a.href = p.permalink; });
					var imagen = li.querySelector('img');
					if (img) { imagen.src = img.thumbnail || img.src; imagen.alt = img.alt || p.name; } else { imagen.remove(); }
					var titulo = li.querySelector('h3 a');
					titulo.innerHTML = p.name; // La Store API devuelve el nombre ya escapado.
					li.querySelector('.ah-favoritos__precio').innerHTML = p.is_in_stock ? (p.price_html || '') : t.agotado;
					li.querySelector('.ah-boton--secundario').textContent = t.ver;
					var quitar = li.querySelector('button');
					quitar.textContent = t.quitar;
					quitar.addEventListener('click', function () { alternar(p.id); });
					lista.appendChild(li);
				});
				contenedor.appendChild(lista);
			})
			.catch(function () { contenedor.textContent = ''; });
	}

	document.addEventListener('click', function (e) {
		var boton = e.target.closest('[data-ah-fav]');
		if (!boton) { return; }
		e.preventDefault();
		alternar(Number(boton.getAttribute('data-ah-fav')));
	});

	document.addEventListener('DOMContentLoaded', function () {
		actualizarInterfaz();
		sincronizar().then(function () {
			actualizarInterfaz();
			dibujarPagina();
		});
	});

	window.addEventListener('storage', function (e) {
		if (e.key === CLAVE) { actualizarInterfaz(); dibujarPagina(); }
	});
})();
