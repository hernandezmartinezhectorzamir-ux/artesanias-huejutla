/**
 * GA4: mide "add_to_cart" cuando WooCommerce agrega productos por AJAX
 * desde los listados (catálogo, categorías, inicio).
 * Solo se carga si el administrador configuró un ID de medición válido.
 */
(function ($) {
	'use strict';

	$(document.body).on('added_to_cart', function (evento, fragmentos, hash, $boton) {
		if (typeof window.gtag !== 'function' || !$boton || !$boton.length) {
			return;
		}
		var item = $boton.data('ah_item');
		if (!item) {
			return;
		}
		if (typeof item === 'string') {
			try { item = JSON.parse(item); } catch (e) { return; }
		}
		var cantidad = parseInt($boton.data('quantity'), 10) || 1;
		item.quantity = cantidad;
		window.gtag('event', 'add_to_cart', {
			currency: (window.ahGa4 && window.ahGa4.moneda) || 'MXN',
			value: (item.price || 0) * cantidad,
			items: [item]
		});
	});
})(jQuery);
