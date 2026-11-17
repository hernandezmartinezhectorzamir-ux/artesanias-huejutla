/**
 * Mis compras: abre/cierra el panel de seguimiento (<dialog>) e imprime el comprobante.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var abrir = e.target.closest('[data-ah-abrir]');
		if (abrir) {
			var dialogo = document.getElementById(abrir.getAttribute('data-ah-abrir'));
			if (dialogo && typeof dialogo.showModal === 'function') {
				dialogo.showModal();
			}
			return;
		}

		if (e.target.closest('[data-ah-cerrar]')) {
			var abierto = e.target.closest('dialog');
			if (abierto) {
				abierto.close();
			}
			return;
		}

		// Clic en el fondo oscuro del panel: cerrar.
		if (e.target.tagName === 'DIALOG' && e.target.classList.contains('ah-cajon')) {
			var r = e.target.getBoundingClientRect();
			if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) {
				e.target.close();
			}
			return;
		}

		if (e.target.closest('[data-ah-imprimir]')) {
			var envio = document.querySelector('.ah-compra__envio');
			if (envio) {
				envio.open = true;
			}
			window.print();
		}
	});
})();
