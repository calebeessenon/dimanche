/**
 * Premium Shop — Customizer live preview (colors, radius, logo size, title).
 */
(function (api) {
	'use strict';

	var map = (window.premiumShopPreview && window.premiumShopPreview.vars) || {};
	var root = document.documentElement;

	function contrast(hex) {
		hex = String(hex || '').replace('#', '');
		if (hex.length === 3) { hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2]; }
		var rgb = [0, 2, 4].map(function (i) {
			var c = parseInt(hex.substr(i, 2), 16) / 255;
			return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
		});
		var l = 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
		return l > 0.4 ? '#16181d' : '#ffffff';
	}

	Object.keys(map).forEach(function (key) {
		api('ps_' + key, function (setting) {
			setting.bind(function (value) {
				var unit = map[key][1] || '';
				root.style.setProperty(map[key][0], value + unit);
				if (key === 'color_primary') { root.style.setProperty('--ps-on-primary', contrast(value)); }
				if (key === 'color_accent') { root.style.setProperty('--ps-on-accent', contrast(value)); }
			});
		});
	});

	api('blogname', function (setting) {
		setting.bind(function (value) {
			document.querySelectorAll('.ps-logo-text').forEach(function (el) { el.textContent = value; });
		});
	});
}(wp.customize));
